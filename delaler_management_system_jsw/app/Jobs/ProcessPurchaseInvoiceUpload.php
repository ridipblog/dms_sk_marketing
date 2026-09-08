<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Company;
use App\Models\Purchase\Supplier;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceDetail;
use App\Models\Purchase\PurchaseInvoicePayment;
use App\Models\Purchase\PurchasePaymentTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Reports\DailyStockReport;
use App\Models\Inventory\Product;
use App\Modules\ReuseModule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcessPurchaseInvoiceUpload implements ShouldQueue
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

            // Group rows by Supplier GST No + User Invoice No
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
                        $row[8] ?? '',
                        'Incomplete data (must have Supplier GST No, User Invoice No, Invoice Date, Product Name, Quantity).'
                    ];
                    continue;
                }

                $supplierGstNo     = trim($row[0] ?? '');
                $userInputInvoiceNo = trim($row[1] ?? '');
                $invoiceDate       = trim($row[2] ?? '');
                $dueDate           = trim($row[3] ?? '');
                $productName       = trim($row[4] ?? '');

                if (empty($supplierGstNo)) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Supplier GST No is required.";
                    $failedRows[] = [
                        $row[0] ?? '',
                        $row[1] ?? '',
                        $row[2] ?? '',
                        $row[3] ?? '',
                        $row[4] ?? '',
                        $row[5] ?? '',
                        $row[6] ?? '',
                        $row[7] ?? '',
                        $row[8] ?? '',
                        'Supplier GST No is required.'
                    ];
                    continue;
                }

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
                        $row[8] ?? '',
                        'User Invoice No is required.'
                    ];
                    continue;
                }

                // Check if the same User Invoice No is used for a different Supplier GST No within the same file
                if (isset($userInvoiceNoToGstMap[$userInputInvoiceNo]) && $userInvoiceNoToGstMap[$userInputInvoiceNo] !== $supplierGstNo) {
                    $failed++;
                    $errMsg = "Row " . ($index + 2) . ": User Invoice No '$userInputInvoiceNo' cannot be used for multiple suppliers (already used for GST '{$userInvoiceNoToGstMap[$userInputInvoiceNo]}').";
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
                        $row[8] ?? '',
                        $errMsg
                    ];
                    continue;
                }
                $userInvoiceNoToGstMap[$userInputInvoiceNo] = $supplierGstNo;

                $groupKey = $supplierGstNo . '|' . $userInputInvoiceNo;

                if (!isset($groupedInvoices[$groupKey])) {
                    $groupedInvoices[$groupKey] = [];
                }

                $groupedInvoices[$groupKey][] = [
                    'rowIndex' => $index + 2,
                    'rawRow' => $row
                ];
            }

            // Process each grouped purchase invoice
            foreach ($groupedInvoices as $groupKey => $groupItems) {
                list($supplierGstNo, $userInputInvoiceNo) = explode('|', $groupKey, 2);

                try {
                    DB::beginTransaction();

                    // Check if User Invoice No already exists in database
                    $existingInvoice = PurchaseInvoice::where('company_id', $companyId)
                        ->where('user_invoice_no', $userInputInvoiceNo)
                        ->first();

                    if ($existingInvoice) {
                        throw new \Exception("User Invoice No '$userInputInvoiceNo' already exists in system for this company.");
                    }

                    $firstRow = $groupItems[0]['rawRow'];
                    $rawDate = trim($firstRow[2] ?? '');
                    $rawDueDate = trim($firstRow[3] ?? '');

                    $formattedPurchaseDate = $this->parseDate($rawDate);
                    $formattedDueDate = !empty($rawDueDate) ? $this->parseDate($rawDueDate) : date('Y-m-d', strtotime($formattedPurchaseDate . ' + 30 days'));

                    // Resolve Supplier using Supplier GST Number (gstin) or Name
                    $supplier = Supplier::where('gstin', $supplierGstNo)->first();
                    if (!$supplier) {
                        $supplier = Supplier::whereRaw('LOWER(gstin) = ?', [strtolower($supplierGstNo)])->first();
                    }
                    if (!$supplier) {
                        $supplier = Supplier::where('name', $supplierGstNo)->first();
                    }
                    if (!$supplier) {
                        $supplier = Supplier::whereRaw('LOWER(name) = ?', [strtolower($supplierGstNo)])->first();
                    }

                    if (!$supplier) {
                        throw new \Exception("Supplier with GST Number / Name '$supplierGstNo' not found in system.");
                    }

                    // Auto-generate system purchase invoice number
                    $autoInvoiceNo = ReuseModule::generatePurchaseInvoiceNumber();

                    // Create Purchase Invoice Record
                    $purchase = PurchaseInvoice::create([
                        'invoice_no' => $autoInvoiceNo,
                        'user_invoice_no' => $userInputInvoiceNo,
                        'supplier_id' => $supplier->id,
                        'company_id' => $companyId,
                        'purchase_date' => $formattedPurchaseDate,
                        'due_date' => $formattedDueDate,
                        'status' => 0, // Draft initially, will be finalized below
                        'created_by' => $track->role_user_company_id,
                    ]);

                    $totalQuantity = 0;
                    $totalAmount = 0;
                    $totalGstAmount = 0;
                    $totalCgstAmount = 0;
                    $totalSgstAmount = 0;
                    $totalIgstAmount = 0;
                    $totalChargeableAmount = 0;

                    $invoiceTaxType = 'intra'; // default

                    foreach ($groupItems as $item) {
                        $row = $item['rawRow'];
                        $rIdx = $item['rowIndex'];

                        $productName   = trim($row[4] ?? '');
                        $quantity      = (float)($row[5] ?? 0);
                        $rate          = (float)($row[6] ?? 0);
                        $gstPercent    = (float)($row[7] ?? 18.00);
                        $gstType       = strtolower(trim($row[8] ?? 'intra'));

                        if (in_array($gstType, ['inter', 'igst'])) {
                            $invoiceTaxType = 'inter';
                        }

                        if (empty($productName)) {
                            throw new \Exception("Row $rIdx: Product Name is required.");
                        }

                        if ($quantity <= 0) {
                            throw new \Exception("Row $rIdx: Quantity must be greater than 0.");
                        }

                        // Resolve product by name
                        $product = Product::where('product_name', $productName)->first();
                        if (!$product) {
                            // Case-insensitive fallback
                            $product = Product::whereRaw('LOWER(product_name) = ?', [strtolower($productName)])->first();
                        }

                        if (!$product) {
                            throw new \Exception("Row $rIdx: Product '$productName' not found.");
                        }

                        // Calculate line amounts
                        $amounts = ReuseModule::calculateItemAmounts($rate, $quantity, $gstPercent, $gstType);

                        PurchaseInvoiceDetail::create([
                            'purchase_invoice_id' => $purchase->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'rate' => $rate,
                            'total_amount' => $amounts['total_amount'],
                            'cgst_amount' => $amounts['cgst_amount'],
                            'sgst_amount' => $amounts['sgst_amount'],
                            'igst_amount' => $amounts['igst_amount'] ?? 0,
                            'gst_amount' => $amounts['gst_amount'],
                            'chargeable_amount' => $amounts['chargeable_amount']
                        ]);

                        $totalQuantity += $quantity;
                        $totalAmount += $amounts['total_amount'];
                        $totalGstAmount += $amounts['gst_amount'];
                        $totalCgstAmount += $amounts['cgst_amount'];
                        $totalSgstAmount += $amounts['sgst_amount'];
                        $totalIgstAmount += ($amounts['igst_amount'] ?? 0);
                        $totalChargeableAmount += $amounts['chargeable_amount'];
                    }

                    // Update invoice totals
                    $purchase->update([
                        'tax_type' => $invoiceTaxType,
                        'total_quantity' => $totalQuantity,
                        'total_amount' => $totalAmount,
                        'total_gst_amount' => $totalGstAmount,
                        'total_cgst_amount' => $totalCgstAmount,
                        'total_sgst_amount' => $totalSgstAmount,
                        'total_igst_amount' => $totalIgstAmount,
                        'chargeable_amount' => $totalChargeableAmount,
                        'no_of_goods' => count($groupItems)
                    ]);

                    // Finalize Purchase Invoice: update stock and create financial ledger entries
                    $purchase->status = 1;
                    $purchase->save();

                    // Increase product stock & record daily stock purchase report
                    foreach ($purchase->purchaseInvoiceDetails as $detail) {
                        if ($detail->product_id) {
                            $product = Product::lockForUpdate()->find($detail->product_id);
                            if ($product) {
                                $product->stock_quantity += $detail->quantity;
                                $product->save();
                            }

                            if ($detail->quantity > 0) {
                                DailyStockReport::recordPurchase(
                                    $companyId,
                                    $detail->product_id,
                                    $formattedPurchaseDate,
                                    (float)$detail->quantity
                                );
                            }
                        }
                    }

                    // Purchase Voucher creation
                    $purchaseVoucher = VoucherType::where('name', 'like', '%purchase%')->first();

                    PurchasePaymentTrack::create([
                        'purchase_invoice_id' => $purchase->id,
                        'transaction_id' => $purchase->invoice_no,
                        'amount' => $purchase->chargeable_amount,
                        'balance_amount' => $purchase->chargeable_amount,
                        'payment_mode' => 'entry',
                        'voucher_type_id' => $purchaseVoucher ? $purchaseVoucher->id : null,
                        'transaction_date' => $formattedPurchaseDate,
                        'remarks' => 'Auto generated purchase ledger voucher on invoice finalization via Excel',
                        'created_by' => $track->role_user_company_id
                    ]);

                    // Initialize outstanding balance in purchase_invoice_payments
                    PurchaseInvoicePayment::create([
                        'purchase_invoice_id' => $purchase->id,
                        'outstanding_amount' => $purchase->chargeable_amount,
                        'paid_amount' => 0.00,
                        'clear_status' => 0
                    ]);

                    DB::commit();

                    $imported += count($groupItems);

                } catch (\Throwable $e) {
                    DB::rollBack();
                    $failed += count($groupItems);
                    $errMsg = "Invoice '$userInputInvoiceNo' (Supplier GST '$supplierGstNo'): " . $e->getMessage();
                    $errors[] = $errMsg;

                    foreach ($groupItems as $item) {
                        $r = $item['rawRow'];
                        $failedRows[] = [
                            $r[0] ?? '',
                            $r[1] ?? '',
                            $r[2] ?? '',
                            $r[3] ?? '',
                            $r[4] ?? '',
                            $r[5] ?? '',
                            $r[6] ?? '',
                            $r[7] ?? '',
                            $r[8] ?? '',
                            $e->getMessage()
                        ];
                    }
                }
            }

            // Write error file if there are failed rows
            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFileName = 'errors/purchase_invoice_errors_' . time() . '_' . $track->id . '.csv';
                $csvHeader = [
                    'Supplier GST No',
                    'User Invoice No',
                    'Invoice Date',
                    'Due Date',
                    'Product Name',
                    'Quantity',
                    'Rate (Without GST)',
                    'GST Percentage',
                    'GST Type',
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
            Log::error('Purchase Invoice Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);
        }
    }
}
