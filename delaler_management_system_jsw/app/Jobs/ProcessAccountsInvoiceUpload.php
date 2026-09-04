<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoiceDetail;
use App\Models\Dealers\Dealer;
use App\Models\Inventory\Product;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAccountsInvoiceUpload implements ShouldQueue
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
            }, Storage::disk('local')->path($this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            $header = array_shift($rows); // Remove header

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];

            // Group rows by Invoice No (or BuyerCode+Date if blank)
            $groupedInvoices = [];
            $failedRows = [];
            foreach ($rows as $index => $row) {
                if (count($row) < 5) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Incomplete data.";
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        'Incomplete data (must have at least 5 columns).'
                    ];
                    continue;
                }

                $invoiceNo = trim($row[0] ?? '');
                $buyerCode = trim($row[1] ?? '');
                $invoiceDate = trim($row[2] ?? '');

                $groupKey = $invoiceNo ? $invoiceNo : ($buyerCode . '|' . $invoiceDate);

                if (!isset($groupedInvoices[$groupKey])) {
                    $groupedInvoices[$groupKey] = [];
                }
                $groupedInvoices[$groupKey][] = ['row' => $row, 'original_row_number' => $index + 2];
            }

            // Process each group
            $companyId = $track->company_id;

            foreach ($groupedInvoices as $groupKey => $groupRows) {
                try {
                    DB::beginTransaction();

                    $firstRow = $groupRows[0]['row'];
                    $invoiceNo = trim($firstRow[0] ?? '');
                    $buyerCode = trim($firstRow[1] ?? '');
                    $invoiceDate = trim($firstRow[2] ?? '');
                    $manualUpload = isset($firstRow[5]) ? (int)trim($firstRow[5]) : 0;

                    // 1. Resolve Buyer
                    $buyerDealer = ReuseModule::getOwnedDealerQuery(null, $companyId)
                        ->where('dealer_code', $buyerCode)
                        ->first();

                    if (!$buyerDealer) {
                        throw new \Exception("Buyer code $buyerCode not found in this company.");
                    }
                    $dealerCompany = $buyerDealer->dealerCompany()->where('company_id', $companyId)->first();

                    if ($manualUpload == 1) {
                        $credit = isset($firstRow[6]) && trim($firstRow[6]) !== '' ? (float)trim($firstRow[6]) : null;
                        $debit = isset($firstRow[7]) && trim($firstRow[7]) !== '' ? (float)trim($firstRow[7]) : null;

                        if ($credit !== null) {
                            $chargeableAmount = 0.0;
                        } elseif ($debit !== null) {
                            $chargeableAmount = $debit;
                        } else {
                            throw new \Exception("Manual upload row must specify either a credit or a debit amount.");
                        }

                        // Fetch active company bank detail ID for this company
                        $activeBankDetail = \App\Models\CompanyBankDetail::where('company_id', $track->company_id)
                            ->where('status', 'active')
                            ->first();
                        $companyBankDetailId = $activeBankDetail ? $activeBankDetail->id : null;

                        // Resolve or Create Invoice for Manual Upload
                        $invoice = null;
                        if ($invoiceNo) {
                            $invoice = ReuseModule::getOwnedInvoiceQuery(null, $track->role_user_company_id)
                                ->where('invoice_no', $invoiceNo)
                                ->first();
                        }

                        if ($invoice) {
                            $invoice->update([
                                'buyer_id' => $dealerCompany->id,
                                'ship_to' => $dealerCompany->id,
                                'company_bank_detail_id' => $companyBankDetailId,
                                'total_quantity' => null,
                                'total_amount' => null,
                                'chargeable_amount' => $chargeableAmount,
                                'total_gst_amount' => null,
                                'total_cgst_amount' => null,
                                'total_sgst_amount' => null,
                                'cgst' => 9.00,
                                'sgst' => 9.00,
                                'gst' => 18.00,
                                'invoice_generate_date' => '2026-04-01',
                                'due_date' => '2026-04-22',
                                'no_of_goods' => null,
                                'round_of' => null,
                                'invoice_status' => 1,
                                'whatsapp_reminder_stage' => 3,
                                'manual_amount_update' => 1,
                            ]);
                        } else {
                            $generatedNo = $invoiceNo ? $invoiceNo : ReuseModule::generateInvoiceNumber();
                            $invoice = Invoice::create([
                                'invoice_no' => $generatedNo,
                                'buyer_id' => $dealerCompany->id,
                                'ship_to' => $dealerCompany->id,
                                'created_by' => $track->role_user_company_id, // map id
                                'company_bank_detail_id' => $companyBankDetailId,
                                'total_quantity' => null,
                                'total_amount' => null,
                                'chargeable_amount' => $chargeableAmount,
                                'total_gst_amount' => null,
                                'total_cgst_amount' => null,
                                'total_sgst_amount' => null,
                                'cgst' => 9.00,
                                'sgst' => 9.00,
                                'gst' => 18.00,
                                'invoice_generate_date' => '2026-04-01',
                                'due_date' => '2026-04-22',
                                'no_of_goods' => null,
                                'round_of' => null,
                                'invoice_status' => 1,
                                'whatsapp_reminder_stage' => 3,
                                'manual_amount_update' => 1,
                            ]);
                        }

                        // Handle invoice details table
                        if ($credit !== null) {
                            \App\Models\Accounts\InvoiceDetail::updateOrCreate(
                                ['invoice_id' => $invoice->id],
                                [
                                    'chargeable_amount' => 0.00,
                                ]
                            );
                        } elseif ($debit !== null) {
                            \App\Models\Accounts\InvoiceDetail::updateOrCreate(
                                ['invoice_id' => $invoice->id],
                                [
                                    'chargeable_amount' => $debit,
                                ]
                            );
                        }

                        // Initialize/update outstanding payment
                        $invoicePayment = \App\Models\Accounts\InvoicePayment::where('invoice_id', $invoice->id)->first();
                        if ($credit !== null) {
                            if ($invoicePayment) {
                                $invoicePayment->update([
                                    'outstanding_amount' => 0,
                                    'paid_amount' => $credit,
                                ]);
                            } else {
                                \App\Models\Accounts\InvoicePayment::create([
                                    'invoice_id' => $invoice->id,
                                    'outstanding_amount' => 0,
                                    'paid_amount' => $credit,
                                ]);
                            }
                        } elseif ($debit !== null) {
                            if ($invoicePayment) {
                                $invoicePayment->update([
                                    'outstanding_amount' => $debit,
                                ]);
                            } else {
                                \App\Models\Accounts\InvoicePayment::create([
                                    'invoice_id' => $invoice->id,
                                    'outstanding_amount' => $debit,
                                ]);
                            }
                        }

                        // Initialize/update payment track
                        if ($credit !== null) {
                            $paymentTrack = \App\Models\Accounts\PaymentTrack::where('invoice_id', $invoice->id)
                                ->where('payment_mode', 'entry')
                                ->first();
                            if ($paymentTrack) {
                                $paymentTrack->update([
                                    'amount' => $credit,
                                    'balance_amount' => 0,
                                    'payment_for_mt' => $invoice->total_quantity,
                                    'voucher_type_id' => 1,
                                    'transaction_date' => '2026-03-31',
                                ]);
                                $paymentTrack->created_at = '2026-04-01 00:00:00';
                                $paymentTrack->updated_at = '2026-04-01 00:00:00';
                                $paymentTrack->save();
                            } else {
                                $paymentTrack = \App\Models\Accounts\PaymentTrack::create([
                                    'invoice_id' => $invoice->id,
                                    'amount' => $credit,
                                    'balance_amount' => 0,
                                    'payment_for_mt' => $invoice->total_quantity,
                                    'payment_mode' => 'entry',
                                    'voucher_type_id' => 1,
                                    'transaction_date' => '2026-03-31',
                                    'remarks' => 'Auto generated credit voucher on invoice finalization',
                                ]);
                                $paymentTrack->created_at = '2026-04-01 00:00:00';
                                $paymentTrack->updated_at = '2026-04-01 00:00:00';
                                $paymentTrack->save();
                            }
                        } elseif ($debit !== null) {
                            $saleVoucherType = \App\Models\Accounts\VoucherType::where('name', 'SALES')->first();
                            $paymentTrack = \App\Models\Accounts\PaymentTrack::where('invoice_id', $invoice->id)
                                ->where('payment_mode', 'entry')
                                ->first();
                            if ($paymentTrack) {
                                $paymentTrack->update([
                                    'amount' => $debit,
                                    'balance_amount' => $debit,
                                    'payment_for_mt' => $invoice->total_quantity,
                                    'voucher_type_id' => $saleVoucherType ? $saleVoucherType->id : null,
                                    'transaction_date' => '2026-03-31',
                                ]);
                                $paymentTrack->created_at = '2026-04-01 00:00:00';
                                $paymentTrack->updated_at = '2026-04-01 00:00:00';
                                $paymentTrack->save();
                            } else {
                                $paymentTrack = \App\Models\Accounts\PaymentTrack::create([
                                    'invoice_id' => $invoice->id,
                                    'amount' => $debit,
                                    'balance_amount' => $debit,
                                    'payment_for_mt' => $invoice->total_quantity,
                                    'payment_mode' => 'entry',
                                    'voucher_type_id' => $saleVoucherType ? $saleVoucherType->id : null,
                                    'transaction_date' => '2026-03-31',
                                    'remarks' => 'Auto generated sale voucher on invoice finalization',
                                ]);
                                $paymentTrack->created_at = '2026-04-01 00:00:00';
                                $paymentTrack->updated_at = '2026-04-01 00:00:00';
                                $paymentTrack->save();
                            }
                        }
                    } else {
                        // 2. Resolve Invoice
                        $invoice = null;
                        if ($invoiceNo) {
                            $invoice = ReuseModule::getOwnedInvoiceQuery(null, $track->role_user_company_id)
                                ->where('invoice_no', $invoiceNo)
                                ->first();

                            if (!$invoice) {
                                throw new \Exception("Invoice No $invoiceNo not found. (Use blank to create new)");
                            }

                            if ($invoice->invoice_status == 1) {
                                throw new \Exception("Invoice $invoiceNo is finalized. Cannot add products.");
                            }
                        } else {
                            // Generate New Invoice
                            $generatedNo = ReuseModule::generateInvoiceNumber();

                            $invoice = Invoice::create([
                                'invoice_no' => $generatedNo,
                                'buyer_id' => $dealerCompany->id,
                                'ship_to' => $dealerCompany->id,
                                'created_by' => $track->role_user_company_id, // map id
                                'invoice_status' => 0,
                                'gst' => 0,
                                'cgst' => 0,
                                'sgst' => 0,
                            ]);
                        }

                        // 3. Process Items
                        foreach ($groupRows as $item) {
                            $row = $item['row'];
                            $rowNum = $item['original_row_number'];
                            $productCode = trim($row[3] ?? '');
                            $quantity = (float)trim($row[4] ?? 0);

                            if (!$productCode || $quantity <= 0) continue;

                            $product = ReuseModule::getOwnedProductQuery(null, $companyId)
                                ->where('hsn_code', $productCode)
                                ->first();
                            if (!$product) {
                                throw new \Exception("Row $rowNum: Product code $productCode not found.");
                            }

                            // Get Product Pricing
                            $pricing = $product->pricings()->where('status', 'active')->first();
                            if (!$pricing) {
                                throw new \Exception("Row $rowNum: Active pricing not found for product $productCode.");
                            }

                            $rate = $pricing->price_per_mt;
                            $gst_percent = $invoice->gst ?? 18;

                            $amounts = ReuseModule::calculateItemAmounts($rate, $quantity, $gst_percent);

                            InvoiceDetail::create([
                                'invoice_id' => $invoice->id,
                                'product_pricing_id' => $pricing->id,
                                'quantity' => $quantity,
                                'total_amount' => $amounts['total_amount'],
                                'gst_amount' => $amounts['gst_amount'],
                                'cgst_amount' => $amounts['cgst_amount'],
                                'sgst_amount' => $amounts['sgst_amount'],
                                'chargeable_amount' => $amounts['chargeable_amount']
                            ]);
                        }

                        // Update parent invoice totals
                        $invoice->total_quantity = $invoice->invoiceDetails()->sum('quantity');
                        $invoice->total_amount = $invoice->invoiceDetails()->sum('total_amount');
                        $invoice->total_gst_amount = $invoice->invoiceDetails()->sum('gst_amount');
                        $invoice->total_cgst_amount = $invoice->invoiceDetails()->sum('cgst_amount');
                        $invoice->total_sgst_amount = $invoice->invoiceDetails()->sum('sgst_amount');
                        $invoice->chargeable_amount = $invoice->invoiceDetails()->sum('chargeable_amount');
                        $invoice->no_of_goods = $invoice->invoiceDetails()->count();
                        $invoice->save();
                    }

                    DB::commit();
                    $imported += count($groupRows);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed += count($groupRows);
                    $errors[] = "Group ($groupKey) Failed: " . $e->getMessage();

                    foreach ($groupRows as $item) {
                        $failedRows[] = [
                            $item['row'][0] ?? '',
                            $item['row'][1] ?? '',
                            $item['row'][2] ?? '',
                            $item['row'][3] ?? '',
                            $item['row'][4] ?? '',
                            $e->getMessage()
                        ];
                    }
                }
            }

            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFilePath = 'accounts_uploads/errors/error_' . $track->id . '_' . time() . '.csv';

                $tempPath = tempnam(sys_get_temp_dir(), 'upload_err_');
                $handle = fopen($tempPath, 'w');

                fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($handle, ['Invoice No', 'Buyer Code', 'Invoice Date', 'Product Code', 'Quantity', 'Error Reason']);

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
            Log::error('Invoice Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
