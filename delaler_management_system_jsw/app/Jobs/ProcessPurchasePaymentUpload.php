<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoicePayment;
use App\Models\Purchase\PurchasePaymentTrack;
use App\Models\Accounts\VoucherType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessPurchasePaymentUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trackId;
    protected $filePath;
    public $timeout = 3600;

    public function __construct($trackId, $filePath)
    {
        $this->trackId = $trackId;
        $this->filePath = $filePath;
    }

    /**
     * Parse date string into YYYY-MM-DD format.
     */
    private function parseDate($dateStr): string
    {
        if (empty($dateStr)) {
            return date('Y-m-d');
        }

        $dateStr = trim((string)$dateStr);

        // Numeric Excel serial timestamp (e.g., 45542)
        if (is_numeric($dateStr) && (float)$dateStr > 1000) {
            try {
                return date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float)$dateStr));
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        // Match DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        }

        // Match YYYY-MM-DD or YYYY/MM/DD
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $dateStr, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }

        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Throwable $e) {
            return date('Y-m-d');
        }
    }

    public function handle(): void
    {
        $track = UploadTrack::find($this->trackId);
        if (!$track) return;

        $track->update(['status' => 'processing']);

        try {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, Storage::disk('local')->path($this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            $header = array_shift($rows); // Remove header row

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];
            $failedRows = [];

            $companyId = $track->company_id;

            // Resolve Voucher Type for Purchase Payment (matching manual storePayment)
            $paymentVoucher = VoucherType::where('name', 'PAYMENT')->first() ?? VoucherType::where('name', 'like', '%payment%')->first();
            $voucherTypeId = $paymentVoucher ? $paymentVoucher->id : null;

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                if (count($row) < 3) {
                    $failed++;
                    $errMsg = "Row $rowNum: Incomplete data (must have User Invoice No, Payment Date, Amount).";
                    $errors[] = $errMsg;
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        $row[5] ?? '',
                        $errMsg
                    ];
                    continue;
                }

                $userInputInvoiceNo = trim((string)($row[0] ?? ''));
                $rawDate            = trim((string)($row[1] ?? ''));
                $rawAmount          = trim((string)($row[2] ?? ''));
                $rawPaymentMode     = trim((string)($row[3] ?? 'bank_transfer'));
                $transactionId      = trim((string)($row[4] ?? ''));
                $remarks            = trim((string)($row[5] ?? 'Purchase payment uploaded via Excel'));

                if (empty($userInputInvoiceNo)) {
                    $failed++;
                    $errMsg = "Row $rowNum: User Invoice No is required.";
                    $errors[] = $errMsg;
                    $failedRows[] = [$row[0] ?? '', $row[1] ?? '', $row[2] ?? '', $row[3] ?? '', $row[4] ?? '', $row[5] ?? '', $errMsg];
                    continue;
                }

                if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
                    $failed++;
                    $errMsg = "Row $rowNum: Invalid payment amount '$rawAmount'. Amount must be greater than zero.";
                    $errors[] = $errMsg;
                    $failedRows[] = [$userInputInvoiceNo, $rawDate, $rawAmount, $rawPaymentMode, $transactionId, $remarks, $errMsg];
                    continue;
                }

                $amount = (float)$rawAmount;
                $formattedDate = $this->parseDate($rawDate);

                // Map payment mode string
                $modeClean = strtolower(str_replace([' ', '-'], '_', $rawPaymentMode));
                if (!in_array($modeClean, ['cash', 'bank_transfer', 'cheque', 'upi', 'neft'])) {
                    $modeClean = 'bank_transfer';
                }

                try {
                    DB::beginTransaction();

                    // Find Purchase Invoice by User Invoice No (user_invoice_no), with fallback to system invoice_no
                    $purchase = PurchaseInvoice::where('company_id', $companyId)
                        ->where('user_invoice_no', $userInputInvoiceNo)
                        ->first();

                    if (!$purchase) {
                        $purchase = PurchaseInvoice::where('company_id', $companyId)
                            ->where('invoice_no', $userInputInvoiceNo)
                            ->first();
                    }

                    if (!$purchase) {
                        throw new \Exception("Purchase Invoice with User Invoice No / Invoice No '$userInputInvoiceNo' not found.");
                    }

                    if ($purchase->status == 0) {
                        throw new \Exception("Purchase Invoice '$userInputInvoiceNo' is in draft status. Cannot record payment.");
                    }

                    $paymentRecord = PurchaseInvoicePayment::where('purchase_invoice_id', $purchase->id)->first();

                    if (!$paymentRecord) {
                        throw new \Exception("Payment record for Purchase Invoice '$userInputInvoiceNo' not found.");
                    }

                    if ($paymentRecord->clear_status == 1) {
                        throw new \Exception("Purchase Invoice '$userInputInvoiceNo' is already fully paid.");
                    }

                    if ($amount > $paymentRecord->outstanding_amount + 0.01) {
                        throw new \Exception("Payment amount (₹" . number_format($amount, 2) . ") exceeds outstanding amount (₹" . number_format($paymentRecord->outstanding_amount, 2) . ").");
                    }

                    $newOutstanding = max(0, $paymentRecord->outstanding_amount - $amount);
                    $newPaid = $paymentRecord->paid_amount + $amount;

                    // 1. Update running balances
                    $paymentRecord->update([
                        'outstanding_amount' => $newOutstanding,
                        'paid_amount' => $newPaid,
                        'clear_status' => $newOutstanding <= 0.01 ? 1 : 0
                    ]);

                    $txId = !empty($transactionId) ? $transactionId : 'PINV-PAY-' . strtoupper(uniqid());

                    // 2. Add transaction ledger voucher entry
                    PurchasePaymentTrack::create([
                        'purchase_invoice_id' => $purchase->id,
                        'transaction_id' => $txId,
                        'amount' => $amount,
                        'balance_amount' => $newOutstanding,
                        'payment_mode' => $modeClean,
                        'voucher_type_id' => $voucherTypeId,
                        'transaction_date' => $formattedDate,
                        'remarks' => !empty($remarks) ? $remarks : 'Purchase payment uploaded via Excel',
                        'created_by' => $track->role_user_company_id
                    ]);

                    DB::commit();
                    $imported++;

                } catch (\Throwable $e) {
                    DB::rollBack();
                    $failed++;
                    $errMsg = "Row $rowNum (Invoice: $userInputInvoiceNo) Failed: " . $e->getMessage();
                    $errors[] = $errMsg;

                    $failedRows[] = [
                        $userInputInvoiceNo,
                        $rawDate,
                        $rawAmount,
                        $rawPaymentMode,
                        $transactionId,
                        $remarks,
                        $e->getMessage()
                    ];
                }
            }

            // Save error file if any rows failed
            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFileName = 'errors/purchase_payment_errors_' . time() . '_' . $track->id . '.csv';
                $csvHeader = [
                    'User Invoice No',
                    'Payment Date',
                    'Amount',
                    'Payment Mode',
                    'Transaction Ref No',
                    'Remarks',
                    'Error Message'
                ];

                $csvContent = implode(',', array_map(function($h) { return '"' . str_replace('"', '""', $h) . '"'; }, $csvHeader)) . "\n";

                foreach ($failedRows as $failedRow) {
                    $csvContent .= implode(',', array_map(function($val) {
                        return '"' . str_replace('"', '""', (string)$val) . '"';
                    }, $failedRow)) . "\n";
                }

                Storage::disk('public')->put($errorFileName, $csvContent);
                $errorFilePath = 'storage/' . $errorFileName;
            }

            $track->update([
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'status' => 'completed',
                'error_log' => !empty($errors) ? implode("\n", array_slice($errors, 0, 50)) : null,
                'error_file_path' => $errorFilePath,
            ]);

        } catch (\Throwable $e) {
            Log::error('Purchase Payment Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);
        }
    }
}
