<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Company;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoiceDetail;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Reports\DailyStockReport;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductPricing;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessCompanyWiseInvoiceUpload implements ShouldQueue
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
     * Parse date string into YYYY-MM-DD format suitable for MySQL DATE columns.
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

            $companyId = $track->company_id;

            // Group rows by Dealer GST No + User Invoice No
            $groupedInvoices = [];
            $failedRows = [];
            $userInvoiceNoToGstMap = [];

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
                        $row[5] ?? '',
                        $row[6] ?? '',
                        $row[7] ?? '',
                        'Incomplete data (must have Dealer GST No, User Invoice No, Invoice Date, Product Name, Quantity).'
                    ];
                    continue;
                }

                $dealerGstNo       = trim($row[0] ?? '');
                $userInputInvoiceNo = trim($row[1] ?? '');
                $invoiceDate       = trim($row[2] ?? '');
                $productName       = trim($row[3] ?? '');

                if (empty($userInputInvoiceNo)) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": User Invoice No is required.";
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        $row[5] ?? '',
                        $row[6] ?? '',
                        $row[7] ?? '',
                        'User Invoice No is required.'
                    ];
                    continue;
                }

                // Check if the same User Invoice No is used for a different Dealer GST No within the same file
                if (isset($userInvoiceNoToGstMap[$userInputInvoiceNo]) && $userInvoiceNoToGstMap[$userInputInvoiceNo] !== $dealerGstNo) {
                    $failed++;
                    $errMsg = "Row " . ($index + 2) . ": User Invoice No '$userInputInvoiceNo' cannot be used for multiple dealers (already used for GST '{$userInvoiceNoToGstMap[$userInputInvoiceNo]}').";
                    $errors[] = $errMsg;
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        $row[5] ?? '',
                        $row[6] ?? '',
                        $row[7] ?? '',
                        $errMsg
                    ];
                    continue;
                }
                $userInvoiceNoToGstMap[$userInputInvoiceNo] = $dealerGstNo;

                $groupKey = $dealerGstNo . '|' . $userInputInvoiceNo;

                if (!isset($groupedInvoices[$groupKey])) {
                    $groupedInvoices[$groupKey] = [];
                }
                $groupedInvoices[$groupKey][] = ['row' => $row, 'original_row_number' => $index + 2];
            }

            // Process each grouped invoice
            foreach ($groupedInvoices as $groupKey => $groupRows) {
                try {
                    DB::beginTransaction();

                    $firstRow           = $groupRows[0]['row'];
                    $dealerGstNo        = trim($firstRow[0] ?? '');
                    $userInputInvoiceNo = trim($firstRow[1] ?? '');
                    $rawDate            = trim($firstRow[2] ?? '');

                    if (empty($dealerGstNo)) {
                        throw new \Exception("Dealer GST Number is missing.");
                    }

                    if (empty($userInputInvoiceNo)) {
                        throw new \Exception("User Invoice No is missing.");
                    }

                    $formattedInvoiceDate = $this->parseDate($rawDate);
                    $formattedDueDate     = date('Y-m-d', strtotime($formattedInvoiceDate . ' + 21 days'));

                    // 1. Resolve Dealer using Dealer GST Number
                    $dealer = Dealer::where('gst_number', $dealerGstNo)->first();

                    if (!$dealer) {
                        throw new \Exception("Dealer with GST Number '$dealerGstNo' not found.");
                    }

                    // 2. Resolve DealerCompany mapping for the active company
                    $dealerCompany = DealerCompany::where('dealer_id', $dealer->id)
                        ->where('company_id', $companyId)
                        ->first();

                    if (!$dealerCompany) {
                        $dealerCompany = DealerCompany::where('dealer_id', $dealer->id)->first();
                    }

                    if (!$dealerCompany) {
                        throw new \Exception("Dealer '{$dealer->dealer_name}' (GST: $dealerGstNo) is not associated with any company.");
                    }

                    // Fetch active company bank detail ID for companyId
                    $activeBankDetail = \App\Models\CompanyBankDetail::where('company_id', $companyId)
                        ->where('status', 'active')
                        ->first();
                    $companyBankDetailId = $activeBankDetail ? $activeBankDetail->id : null;

                    // 3. Resolve or Create Invoice using user_invoice_no and system invoice_no
                    $existingInvoice = Invoice::where('user_invoice_no', $userInputInvoiceNo)->first();

                    if ($existingInvoice) {
                        if ($existingInvoice->buyer_id != $dealerCompany->id) {
                            throw new \Exception("User Invoice No '$userInputInvoiceNo' is already assigned to a different dealer.");
                        }

                        if ($existingInvoice->invoice_status == 1) {
                            throw new \Exception("Invoice with User Invoice No '$userInputInvoiceNo' is finalized. Cannot modify.");
                        }

                        $invoice = $existingInvoice;
                        $invoice->update([
                            'invoice_generate_date' => $formattedInvoiceDate,
                            'due_date' => $formattedDueDate,
                        ]);
                    } else {
                        // Generate system invoice number
                        $systemInvoiceNo = ReuseModule::generateInvoiceNumber();

                        $invoice = Invoice::create([
                            'invoice_no' => $systemInvoiceNo,
                            'user_invoice_no' => $userInputInvoiceNo,
                            'buyer_id' => $dealerCompany->id,
                            'ship_to' => $dealerCompany->id,
                            'tax_type' => 'intra',
                            'created_by' => $track->role_user_company_id,
                            'company_bank_detail_id' => $companyBankDetailId,
                            'invoice_status' => 0,
                            'gst' => 18.00,
                            'cgst' => 9.00,
                            'sgst' => 9.00,
                            'igst' => 0.00,
                            'invoice_generate_date' => $formattedInvoiceDate,
                            'due_date' => $formattedDueDate,
                        ]);
                    }

                    // 4. Process Items using Product Name, Input Rate, GST % and GST Type
                    foreach ($groupRows as $item) {
                        $row = $item['row'];
                        $rowNum = $item['original_row_number'];
                        $productName  = trim($row[3] ?? '');
                        $quantity     = (float)trim($row[4] ?? 0);
                        $inputRate    = isset($row[5]) && trim($row[5]) !== '' ? (float)trim($row[5]) : null;
                        $inputGst     = isset($row[6]) && trim($row[6]) !== '' ? (float)trim($row[6]) : 18.00;
                        $inputTaxType = isset($row[7]) && trim($row[7]) !== '' ? strtolower(trim($row[7])) : 'intra';

                        if (!$productName || $quantity <= 0) continue;

                        // Find product by Product Name
                        $product = Product::where('product_name', $productName)->first();

                        if (!$product) {
                            throw new \Exception("Row $rowNum: Product '$productName' not found in products table.");
                        }

                        // Optional lookup of existing pricing record (product_pricing_id is nullable)
                        $pricing = $product->pricings()->where('status', 'active')->first();
                        $pricingId = $pricing ? $pricing->id : null;

                        $rate = ($inputRate !== null && $inputRate >= 0) ? $inputRate : ($pricing->price_per_mt ?? $product->base_price ?? 0);
                        $gst_percent = $inputGst;
                        $taxType = in_array($inputTaxType, ['inter', 'igst']) ? 'inter' : 'intra';

                        // Update invoice GST percentage and tax_type
                        $invoice->update([
                            'tax_type' => $taxType,
                            'gst' => $gst_percent,
                            'cgst' => $taxType === 'inter' ? 0 : ($gst_percent / 2),
                            'sgst' => $taxType === 'inter' ? 0 : ($gst_percent / 2),
                            'igst' => $taxType === 'inter' ? $gst_percent : 0,
                        ]);

                        $amounts = ReuseModule::calculateItemAmounts($rate, $quantity, $gst_percent, $taxType);

                        InvoiceDetail::create([
                            'invoice_id' => $invoice->id,
                            'product_pricing_id' => $pricingId,
                            'custom_price' => $rate,
                            'quantity' => $quantity,
                            'total_amount' => $amounts['total_amount'],
                            'gst_amount' => $amounts['gst_amount'],
                            'cgst_amount' => $amounts['cgst_amount'],
                            'sgst_amount' => $amounts['sgst_amount'],
                            'igst_amount' => $amounts['igst_amount'],
                            'chargeable_amount' => $amounts['chargeable_amount']
                        ]);
                    }

                    // Update parent invoice totals and finalize invoice
                    $invoice->total_quantity = $invoice->invoiceDetails()->sum('quantity');
                    $invoice->total_amount = $invoice->invoiceDetails()->sum('total_amount');
                    $invoice->total_gst_amount = $invoice->invoiceDetails()->sum('gst_amount');
                    $invoice->total_cgst_amount = $invoice->invoiceDetails()->sum('cgst_amount');
                    $invoice->total_sgst_amount = $invoice->invoiceDetails()->sum('sgst_amount');
                    $invoice->total_igst_amount = $invoice->invoiceDetails()->sum('igst_amount');
                    $invoice->chargeable_amount = $invoice->invoiceDetails()->sum('chargeable_amount');
                    $invoice->no_of_goods = $invoice->invoiceDetails()->count();
                    $invoice->invoice_status = 1; // Automatically set to Finalized
                    $invoice->save();

                    // 1. Decrease product stock & record product-wise sale quantity in DailyStockReport
                    foreach ($invoice->invoiceDetails as $detail) {
                        $productId = null;
                        if ($detail->productPricing) {
                            $productId = $detail->productPricing->product_id;
                        }
                        if (!$productId && isset($detail->product_pricing_id)) {
                            $pricingRec = ProductPricing::find($detail->product_pricing_id);
                            $productId = $pricingRec ? $pricingRec->product_id : null;
                        }

                        if ($productId) {
                            $product = Product::lockForUpdate()->find($productId);
                            if ($product) {
                                if ($product->stock_quantity < $detail->quantity) {
                                    throw new \Exception("Stock is low for product: " . $product->product_name . ". Available stock: " . number_format($product->stock_quantity, 3) . " MT.");
                                }
                                $product->stock_quantity -= $detail->quantity;
                                $product->save();
                            }

                            if ($detail->quantity > 0) {
                                DailyStockReport::recordSale(
                                    $companyId,
                                    $productId,
                                    $formattedInvoiceDate,
                                    (float)$detail->quantity
                                );
                            }
                        }
                    }

                    // 2. Create SALES Voucher in PaymentTrack
                    $saleVoucherType = VoucherType::where('name', 'SALES')->first();
                    $txDate = Carbon::parse($invoice->invoice_generate_date ?? now())->setTimeFrom(now());

                    $existingSaleTrack = PaymentTrack::where('invoice_id', $invoice->id)
                        ->where('payment_mode', 'entry')
                        ->first();

                    if ($existingSaleTrack) {
                        $existingSaleTrack->update([
                            'amount' => $invoice->chargeable_amount,
                            'balance_amount' => $invoice->chargeable_amount,
                            'payment_for_mt' => $invoice->total_quantity,
                            'voucher_type_id' => $saleVoucherType ? $saleVoucherType->id : null,
                            'transaction_date' => $txDate,
                        ]);
                    } else {
                        PaymentTrack::create([
                            'invoice_id' => $invoice->id,
                            'amount' => $invoice->chargeable_amount,
                            'balance_amount' => $invoice->chargeable_amount,
                            'payment_for_mt' => $invoice->total_quantity,
                            'payment_mode' => 'entry',
                            'voucher_type_id' => $saleVoucherType ? $saleVoucherType->id : null,
                            'transaction_date' => $txDate,
                            'remarks' => 'Auto generated sale voucher on invoice upload',
                        ]);
                    }

                    // 3. Create or update InvoicePayment record
                    $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
                    if (!$invoicePayment) {
                        InvoicePayment::create([
                            'invoice_id' => $invoice->id,
                            'outstanding_amount' => $invoice->chargeable_amount,
                            'paid_amount' => 0.00,
                            'clear_status' => 'pending payment'
                        ]);
                    } else {
                        $invoicePayment->update([
                            'outstanding_amount' => $invoice->chargeable_amount - $invoicePayment->paid_amount,
                            'clear_status' => ($invoice->chargeable_amount - $invoicePayment->paid_amount) <= 0.01 ? 'clear payment' : 'pending payment'
                        ]);
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
                            $item['row'][5] ?? '',
                            $item['row'][6] ?? '',
                            $item['row'][7] ?? '',
                            $e->getMessage()
                        ];
                    }
                }
            }

            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFilePath = 'accounts_uploads/errors/company_wise_error_' . $track->id . '_' . time() . '.csv';

                $tempPath = tempnam(sys_get_temp_dir(), 'upload_cw_err_');
                $handle = fopen($tempPath, 'w');

                fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($handle, ['Dealer GST No', 'User Invoice No', 'Invoice Date', 'Product Name', 'Quantity', 'Rate', 'GST %', 'GST Type', 'Error Reason']);

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
            Log::error('Company-Wise Invoice Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
