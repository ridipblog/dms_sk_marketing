<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\VoucherType;
use App\Models\Accounts\CreditNoteTrack;
use App\Models\Accounts\DebitNoteTrack;
use Illuminate\Support\Carbon;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAccountsVoucherUpload implements ShouldQueue
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

    public function handle(): void
    {
        $track = UploadTrack::find($this->trackId);
        if (!$track) return;

        $track->update(['status' => 'processing']);

        try {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, storage_path('app/private/' . $this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            $header = array_shift($rows);

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];
            $failedRows = [];
            
            $companyId = $track->company_id;

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                if (count($row) < 5) {
                    $failed++;
                    $errors[] = "Row $rowNum: Incomplete data.";
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        '',
                        'Incomplete data (must have at least 5 columns).'
                    ];
                    continue;
                }

                $invoiceNo = trim($row[0] ?? '');
                $voucherTypeName = trim($row[1] ?? '');
                $amount = (float)trim($row[2] ?? 0);
                $paymentMode = trim($row[3] ?? '');
                $transactionDate = trim($row[4] ?? '');
                $numberOfDays = isset($row[5]) && trim($row[5]) !== '' ? (int)trim($row[5]) : null;
                
                if (!$invoiceNo) {
                    $failed++;
                    $errors[] = "Row $rowNum: Invoice No is required for voucher upload.";
                    $failedRows[] = [
                        $invoiceNo,
                        $voucherTypeName,
                        $amount,
                        $paymentMode,
                        $transactionDate,
                        $numberOfDays,
                        'Invoice No is required for voucher upload.'
                    ];
                    continue;
                }
                
                if ($amount <= 0) {
                    $failed++;
                    $errors[] = "Row $rowNum: Amount must be greater than 0.";
                    $failedRows[] = [
                        $invoiceNo,
                        $voucherTypeName,
                        $amount,
                        $paymentMode,
                        $transactionDate,
                        $numberOfDays,
                        'Amount must be greater than 0.'
                    ];
                    continue;
                }

                try {
                    DB::beginTransaction();

                    $invoice = ReuseModule::getOwnedInvoiceQuery(null, $track->role_user_company_id)
                        ->where('invoice_no', $invoiceNo)
                        ->first();

                    if (!$invoice) {
                        throw new \Exception("Invoice No $invoiceNo not found.");
                    }

                    if ($invoice->invoice_status != 1) {
                        throw new \Exception("Invoice $invoiceNo is not finalized. Vouchers cannot be added.");
                    }

                    $voucherType = VoucherType::whereRaw('LOWER(name) = ?', [strtolower($voucherTypeName)])->first();
                    if (!$voucherType) {
                        $voucherType = VoucherType::where('name', 'like', '%' . $voucherTypeName . '%')->first();
                    }
                    if (!$voucherType) {
                        throw new \Exception("Voucher Type '$voucherTypeName' not found.");
                    }
                    $dbVoucherTypeName = strtolower($voucherType->name);

                    // Automatically add 18% GST to Debit Note voucher amounts
                    $baseAmount = $amount;
                    $gstAmount = 0;
                    if (str_contains($dbVoucherTypeName, 'debit note')) {
                        $gstAmount = round($baseAmount * 0.18, 2);
                        $amount = round($baseAmount + $gstAmount, 2);
                    }

                    $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->lockForUpdate()->first();
                    if (!$invoicePayment) {
                        throw new \Exception("Invoice Payment ledger not found for invoice $invoiceNo.");
                    }

                    $currentBalance = $invoicePayment->outstanding_amount;
                    $newBalance = $currentBalance;
                    $paymentForMt = 0;

                    if (str_contains($dbVoucherTypeName, 'receipt') || str_contains($dbVoucherTypeName, 'credit note') || str_contains($dbVoucherTypeName, 'journal') || str_contains($dbVoucherTypeName, 'advance')) {
                        $newBalance -= $amount;
                        if (str_contains($dbVoucherTypeName, 'receipt') || str_contains($dbVoucherTypeName, 'advance')) {
                            $chargeableAmount = $invoice->chargeable_amount > 0 ? $invoice->chargeable_amount : 1;
                            $paymentForMt = round(($invoice->total_quantity / $chargeableAmount) * $amount, 3);
                        }
                    } elseif (str_contains($dbVoucherTypeName, 'debit note') || str_contains($dbVoucherTypeName, 'sale')) {
                        $newBalance += $amount;
                    }

                    $transactionId = 'TXN-IMP-' . strtoupper(uniqid());
                    $parsedTxDate = $transactionDate ? date('Y-m-d', strtotime($transactionDate)) : date('Y-m-d');

                    $paymentTrack = PaymentTrack::create([
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $transactionId,
                        'amount' => $amount,
                        'balance_amount' => $newBalance,
                        'payment_for_mt' => $paymentForMt,
                        'payment_mode' => $paymentMode,
                        'voucher_type_id' => $voucherType->id,
                        'transaction_date' => $parsedTxDate,
                        'remarks' => 'Imported via Excel',
                    ]);

                    if (str_contains($dbVoucherTypeName, 'receipt') || str_contains($dbVoucherTypeName, 'advance')) {
                        $invoicePayment->paid_amount += $amount;
                        $invoicePayment->outstanding_amount -= $amount;

                        // Calculate Cash Discount (Credit Note) if applicable
                        if ($invoice->invoice_generate_date) {
                            $generateDate = Carbon::parse($invoice->invoice_generate_date)->startOfDay();
                            $transactionDateObj = Carbon::parse($parsedTxDate)->startOfDay();
                            $daysSinceGenerate = $generateDate->diffInDays($transactionDateObj, false);

                            $companyId = $track->company_id;

                            // Query the slab dynamically via ReuseModule
                            $slab = ReuseModule::getCashDiscountSlab($companyId, $daysSinceGenerate);

                            if ($slab && $paymentForMt > 0) {
                                $discountPerMt = $slab->discount_percent;
                                $discountAmount = round($paymentForMt * $discountPerMt, 2);

                                if ($discountAmount > 0) {
                                    $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                                    if ($creditVoucherType) {
                                        $creditPaymentTrack = PaymentTrack::create([
                                            'invoice_id' => $invoice->id,
                                            'transaction_id' => 'TXN-IMP-' . strtoupper(uniqid()),
                                            'amount' => $discountAmount,
                                            'balance_amount' => $newBalance - $discountAmount,
                                            'payment_for_mt' => 0,
                                            'payment_mode' => $paymentMode,
                                            'voucher_type_id' => $creditVoucherType->id,
                                            'transaction_date' => $parsedTxDate,
                                            'remarks' => "Auto-generated Cash Discount at Rs {$discountPerMt}/MT ({$slab->slab_name})",
                                        ]);

                                        CreditNoteTrack::create([
                                            'payment_track_id' => $creditPaymentTrack->id,
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

                        // Calculate late fine if applicable (including 18% GST)
                        if ($invoice->due_date) {
                            $transactionDateObj = Carbon::parse($parsedTxDate)->startOfDay();
                            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();

                            if ($dueDate->lt($transactionDateObj)) {
                                $daysOverdue = $dueDate->diffInDays($transactionDateObj);
                                $baseFineAmount = round(($amount * 0.15 / 365) * $daysOverdue, 2);
                                $fineGstAmount = round($baseFineAmount * 0.18, 2);
                                $fineAmount = round($baseFineAmount + $fineGstAmount, 2); // Includes 18% GST

                                if ($fineAmount > 0) {
                                    $debitVoucherType = VoucherType::where('name', 'like', '%debit note%')->first();

                                    if ($debitVoucherType) {
                                        $finePaymentTrack = PaymentTrack::create([
                                            'invoice_id' => $invoice->id,
                                            'transaction_id' => 'TXN-IMP-' . strtoupper(uniqid()),
                                            'amount' => $fineAmount,
                                            'balance_amount' => $newBalance + $fineAmount,
                                            'payment_for_mt' => 0,
                                            'payment_mode' => $paymentMode,
                                            'voucher_type_id' => $debitVoucherType->id,
                                            'transaction_date' => $parsedTxDate,
                                            'remarks' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                        ]);

                                        DebitNoteTrack::create([
                                            'payment_track_id' => $finePaymentTrack->id,
                                            'nos' => $daysOverdue,
                                            'amount' => $fineAmount,
                                            'base_amount' => $baseFineAmount,
                                            'gst_amount' => $fineGstAmount,
                                            'reason' => "Auto-generated fine (15% p.a. + 18% GST) for {$daysOverdue} days overdue",
                                        ]);

                                        $invoicePayment->debit_note_amount += $fineAmount;
                                        $invoicePayment->outstanding_amount += $fineAmount;
                                        $newBalance += $fineAmount;
                                    }
                                }
                            }
                        }
                    } elseif (str_contains($dbVoucherTypeName, 'debit note')) {
                        $invoicePayment->debit_note_amount += $amount;
                        $invoicePayment->outstanding_amount += $amount;
                        DebitNoteTrack::create([
                            'payment_track_id' => $paymentTrack->id,
                            'nos' => $numberOfDays ?? 0,
                            'amount' => $amount,
                            'base_amount' => $baseAmount,
                            'gst_amount' => $gstAmount,
                            'reason' => 'Imported via Excel',
                        ]);
                    } elseif (str_contains($dbVoucherTypeName, 'credit note') || str_contains($dbVoucherTypeName, 'journal')) {
                        $invoicePayment->credit_note_amount += $amount;
                        $invoicePayment->outstanding_amount -= $amount;

                        if (str_contains($dbVoucherTypeName, 'credit note')) {
                            $slab = null;
                            if ($numberOfDays !== null) {
                                $slab = ReuseModule::getCashDiscountSlab($companyId, $numberOfDays);
                            }

                            CreditNoteTrack::create([
                                'payment_track_id' => $paymentTrack->id,
                                'nos' => $numberOfDays,
                                'amount' => $amount,
                                'cash_discount_slab_id' => $slab ? $slab->id : null,
                            ]);
                        }
                    }

                    if ($invoicePayment->outstanding_amount <= 0) {
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
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                    $failedRows[] = [
                        $invoiceNo,
                        $voucherTypeName,
                        $amount,
                        $paymentMode,
                        $transactionDate,
                        $numberOfDays,
                        $e->getMessage()
                    ];
                }
            }

            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFilePath = 'accounts_uploads/errors/error_' . $track->id . '_' . time() . '.csv';
                
                $tempPath = tempnam(sys_get_temp_dir(), 'upload_err_');
                $handle = fopen($tempPath, 'w');
                
                fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($handle, ['Invoice No', 'Voucher Type', 'Amount', 'Payment Mode', 'Transaction Date', 'Number of Days', 'Error Reason']);
                
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
            Log::error('Voucher Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
