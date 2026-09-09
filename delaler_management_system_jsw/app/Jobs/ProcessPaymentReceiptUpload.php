<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\CreditNoteTrack;
use App\Models\Accounts\DebitNoteTrack;
use App\Models\Accounts\VoucherType;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessPaymentReceiptUpload implements ShouldQueue
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

            // Resolve Voucher Type for Payment Receipt
            $voucherType = VoucherType::where('name', 'like', '%receipt%')->first();
            $voucherTypeId = $voucherType ? $voucherType->id : null;

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
                $paymentMode        = trim((string)($row[3] ?? 'Bank Transfer'));
                $transactionId      = trim((string)($row[4] ?? ''));
                $remarks            = trim((string)($row[5] ?? 'Payment receipt uploaded via Excel'));

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
                    $failedRows[] = [$userInputInvoiceNo, $rawDate, $rawAmount, $paymentMode, $transactionId, $remarks, $errMsg];
                    continue;
                }

                $amount = (float)$rawAmount;
                $formattedDate = $this->parseDate($rawDate);

                try {
                    DB::beginTransaction();

                    // Find Invoice using User Invoice No
                    $invoice = Invoice::where('user_invoice_no', $userInputInvoiceNo)->first();

                    if (!$invoice) {
                        throw new \Exception("Invoice with User Invoice No '$userInputInvoiceNo' not found.");
                    }

                    // Find or create InvoicePayment record for invoice
                    $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->lockForUpdate()->first();

                    if (!$invoicePayment) {
                        $invoicePayment = InvoicePayment::create([
                            'invoice_id' => $invoice->id,
                            'outstanding_amount' => $invoice->chargeable_amount > 0 ? $invoice->chargeable_amount : 0,
                            'paid_amount' => 0.00,
                            'clear_status' => 'pending payment'
                        ]);
                    }

                    $currentBalance = (float)$invoicePayment->outstanding_amount;
                    $newBalance = $currentBalance - $amount;

                    $chargeableAmount = $invoice->chargeable_amount > 0 ? $invoice->chargeable_amount : 1;
                    $paymentForMt = round(($invoice->total_quantity / $chargeableAmount) * $amount, 3);

                    $txId = !empty($transactionId) ? $transactionId : ('TXN-' . strtoupper(uniqid()));
                    $txDate = Carbon::parse($formattedDate)->setTimeFrom(now());

                    $paymentTrack = PaymentTrack::create([
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $txId,
                        'amount' => $amount,
                        'balance_amount' => $newBalance,
                        'payment_for_mt' => $paymentForMt,
                        'payment_mode' => !empty($paymentMode) ? $paymentMode : 'Bank Transfer',
                        'voucher_type_id' => $voucherTypeId,
                        'transaction_date' => $txDate,
                        'remarks' => !empty($remarks) ? $remarks : 'Uploaded via Excel',
                    ]);

                    $invoicePayment->paid_amount += $amount;
                    $invoicePayment->outstanding_amount -= $amount;

                    // 1. Calculate Cash Discount (Credit Note) if applicable
                    if ($invoice->invoice_generate_date && ($invoice->manual_amount_update ?? 0) != 1) {
                        $generateDate = Carbon::parse($invoice->invoice_generate_date)->startOfDay();
                        $transactionDate = Carbon::parse($formattedDate)->startOfDay();
                        $daysSinceGenerate = $generateDate->diffInDays($transactionDate, false);

                        $companyId = $track->company_id ?? 1;
                        $slab = ReuseModule::getCashDiscountSlab($companyId, $daysSinceGenerate);

                        if ($slab && $paymentForMt > 0) {
                            $discountPerMt = $slab->discount_percent;
                            $discountAmount = round($paymentForMt * $discountPerMt, 2);

                            if ($discountAmount > 0) {
                                $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                                if ($creditVoucherType) {
                                    $creditPaymentTrack = PaymentTrack::create([
                                        'invoice_id' => $invoice->id,
                                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                        'amount' => $discountAmount,
                                        'balance_amount' => $newBalance - $discountAmount,
                                        'payment_for_mt' => 0,
                                        'payment_mode' => !empty($paymentMode) ? $paymentMode : 'Bank Transfer',
                                        'voucher_type_id' => $creditVoucherType->id,
                                        'transaction_date' => $txDate,
                                        'remarks' => "Auto-generated Cash Discount at Rs {$discountPerMt}/MT ({$slab->slab_name})",
                                    ]);

                                    CreditNoteTrack::create([
                                        'payment_track_id' => $creditPaymentTrack->id,
                                        'parent_payment_track_id' => $paymentTrack->id,
                                        'cash_discount_slab_id' => $slab->id,
                                        'nos' => $daysSinceGenerate,
                                        'amount' => $discountAmount,
                                    ]);

                                    $newBalance -= $discountAmount;
                                    $invoicePayment->credit_note_amount += $discountAmount;
                                    $invoicePayment->outstanding_amount -= $discountAmount;
                                }
                            }
                        }
                    }

                    // 2. Calculate Late Fine (Debit Note) if overdue (15% p.a. + 18% GST)
                    if ($invoice->due_date) {
                        $transactionDate = Carbon::parse($formattedDate)->startOfDay();
                        $dueDate = Carbon::parse($invoice->due_date)->startOfDay();

                        if ($dueDate->lt($transactionDate)) {
                            $daysOverdue = $dueDate->diffInDays($transactionDate);
                            $baseFineAmount = round(($amount * 0.15 / 365) * $daysOverdue, 2);
                            $fineGstAmount = round($baseFineAmount * 0.18, 2);
                            $fineAmount = round($baseFineAmount + $fineGstAmount, 2);

                            if ($fineAmount > 0) {
                                $debitVoucherType = VoucherType::where('name', 'like', '%debit note%')->first();

                                if ($debitVoucherType) {
                                    $finePaymentTrack = PaymentTrack::create([
                                        'invoice_id' => $invoice->id,
                                        'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                                        'amount' => $fineAmount,
                                        'balance_amount' => $newBalance + $fineAmount,
                                        'payment_for_mt' => 0,
                                        'payment_mode' => !empty($paymentMode) ? $paymentMode : 'Bank Transfer',
                                        'voucher_type_id' => $debitVoucherType->id,
                                        'transaction_date' => $txDate,
                                        'remarks' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                    ]);

                                    DebitNoteTrack::create([
                                        'payment_track_id' => $finePaymentTrack->id,
                                        'parent_payment_track_id' => $paymentTrack->id,
                                        'nos' => $daysOverdue,
                                        'amount' => $fineAmount,
                                        'base_amount' => $baseFineAmount,
                                        'gst_amount' => $fineGstAmount,
                                        'reason' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                    ]);

                                    $invoicePayment->debit_note_amount += $fineAmount;
                                    $invoicePayment->outstanding_amount += $fineAmount;
                                }
                            }
                        }
                    }

                    // 3. Update Clearance Status
                    if ($invoicePayment->outstanding_amount <= 0.01) {
                        $invoicePayment->clear_status = 'clear payment';
                    } else {
                        $invoicePayment->clear_status = 'pending payment';
                    }
                    $invoicePayment->save();

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errors[] = "Row $rowNum (Invoice: $userInputInvoiceNo) Failed: " . $e->getMessage();
                    $failedRows[] = [$userInputInvoiceNo, $rawDate, $rawAmount, $paymentMode, $transactionId, $remarks, $e->getMessage()];
                }
            }

            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFilePath = 'accounts_uploads/errors/payment_receipt_error_' . $track->id . '_' . time() . '.csv';

                $tempPath = tempnam(sys_get_temp_dir(), 'upload_pr_err_');
                $handle = fopen($tempPath, 'w');

                fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($handle, ['User Invoice No', 'Payment Date', 'Amount', 'Payment Mode', 'Transaction Ref No', 'Remarks', 'Error Reason']);

                foreach ($failedRows as $failedRow) {
                    fputcsv($handle, $failedRow);
                }
                fclose($handle);

                Storage::disk('local')->put($errorFilePath, file_get_contents($tempPath));
                unlink($tempPath);
            }

            $track->update([
                'status' => 'completed',
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'error_file_path' => $errorFilePath,
                'error_log' => empty($errors) ? null : implode("\n", $errors)
            ]);

            Storage::disk('local')->delete($this->filePath);
        } catch (\Throwable $e) {
            Log::error('Payment Receipt Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
