<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceDetail;
use App\Models\Purchase\PurchaseInvoicePayment;
use App\Models\Purchase\PurchasePaymentTrack;
use App\Models\Purchase\Supplier;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductPricing;
use App\Modules\ReuseModule;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the purchase invoices.
     */
    public function index()
    {
        try {
            $suppliers = Supplier::where('status', 1)->get();
            return view('purchase.invoices.index', compact('suppliers'));
        } catch (\Throwable $e) {
            Log::error('Purchase Invoice Index Error: ' . $e->getMessage());
            return view('purchase.invoices.index', [
                'errorMessage' => 'Something went wrong while loading the page.'
            ]);
        }
    }

    /**
     * Fetch purchase invoices list via AJAX.
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $query = PurchaseInvoice::where('company_id', $companyId)->with(['supplier', 'purchaseInvoicePayment']);

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('user_invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Handle Supplier Filter
            if ($request->has('supplier_id') && !empty($request->supplier_id)) {
                try {
                    $supplierId = Crypt::decryptString($request->supplier_id);
                    $query->where('supplier_id', $supplierId);
                } catch (DecryptException $e) {
                    // Ignore invalid ID
                }
            }

            // Handle Status Filter
            if ($request->has('status') && $request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('id', 'desc')->paginate(10);
            $html = view('purchase.invoices.partials.list', compact('invoices'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Purchase Invoice List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching purchase invoices.'
            ]);
        }
    }

    /**
     * Create or edit draft purchase invoice.
     */
    public function generate($purchase_id = null)
    {
        try {
            if ($purchase_id) {
                try {
                    $purchase_id = Crypt::decryptString($purchase_id);
                } catch (DecryptException $e) {
                    return redirect()->route('purchase.invoices.index')->with('error', 'Invalid Link.');
                }
            }

            $companyId = session('active_company_id');
            $suppliers = Supplier::where('status', 1)->get();

            // Fetch products scoped to active company
            $products = ReuseModule::getOwnedProductQuery()->where('status', 'active')->get();

            $purchase = null;
            if ($purchase_id) {
                $purchase = PurchaseInvoice::where('company_id', $companyId)
                    ->where('id', $purchase_id)
                    ->with(['purchaseInvoiceDetails.product'])
                    ->firstOrFail();
            }

            return view('purchase.invoices.generate', compact('suppliers', 'purchase_id', 'products', 'purchase'));
        } catch (\Throwable $e) {
            Log::error('Purchase Invoice Generate View Error: ' . $e->getMessage());
            return redirect()->route('purchase.invoices.index')->with('error', 'Error loading generator page.');
        }
    }

    /**
     * Store draft purchase invoice headers.
     */
    public function store(Request $request)
    {
        try {
            if ($request->filled('purchase_id')) {
                try {
                    $request->merge(['purchase_id' => Crypt::decryptString($request->purchase_id)]);
                } catch (DecryptException $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid or tampered Purchase ID.']);
                }
            }

            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required|exists:suppliers,id',
                'user_invoice_no' => 'nullable|string|max:255',
                'purchase_date' => 'required|date',
                'due_date' => 'nullable|date|after_or_equal:purchase_date',
                'purchase_id' => 'nullable|exists:purchase_invoices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $companyId = session('active_company_id');

            $data = [
                'supplier_id' => $request->supplier_id,
                'company_id' => $companyId,
                'user_invoice_no' => $request->user_invoice_no,
                'purchase_date' => $request->purchase_date,
                'due_date' => $request->due_date,
            ];

            if ($request->purchase_id) {
                $purchase = PurchaseInvoice::where('company_id', $companyId)
                    ->where('id', $request->purchase_id)
                    ->firstOrFail();

                if ($purchase->status == 1) {
                    return response()->json(['success' => false, 'message' => 'Cannot update a finalized purchase invoice.']);
                }

                $purchase->update($data);
                $message = 'Purchase invoice draft updated successfully.';
            } else {
                $purchase = DB::transaction(function () use ($data) {
                    $data['invoice_no'] = ReuseModule::generatePurchaseInvoiceNumber();
                    $data['created_by'] = session('active_map_id');
                    $data['status'] = 0; // draft

                    return PurchaseInvoice::create($data);
                });

                $message = 'Purchase invoice draft created successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'purchase_id' => Crypt::encryptString($purchase->id),
            ]);
        } catch (\Throwable $e) {
            Log::error('Purchase Invoice Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving purchase invoice.'
            ], 500);
        }
    }

    /**
     * Fetch items of a draft purchase invoice.
     */
    public function fetchPurchaseItems(Request $request)
    {
        try {
            $purchase_id = $request->purchase_id;
            if (!$purchase_id) {
                return response()->json(['success' => false, 'message' => 'Purchase ID required']);
            }

            try {
                $purchase_id = Crypt::decryptString($purchase_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Purchase ID']);
            }

            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $purchase_id)
                ->with(['purchaseInvoiceDetails.product'])
                ->firstOrFail();

            $products = ReuseModule::getOwnedProductQuery()->where('status', 'active')->get();
            $html = view('purchase.invoices.partials.items_table', compact('purchase', 'products'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Fetch Purchase Items Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading items.']);
        }
    }

    /**
     * Store item inside draft purchase invoice.
     */
    public function storePurchaseItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_id' => 'required',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:0.001',
                'rate' => 'required|numeric|min:0',
                'gst_percentage' => 'required|numeric|min:0|max:100',
                'purchase_detail_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            try {
                $purchase_id = Crypt::decryptString($request->purchase_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Purchase ID']);
            }

            $purchase_detail_id = null;
            if ($request->has('purchase_detail_id') && !empty($request->purchase_detail_id)) {
                try {
                    $purchase_detail_id = Crypt::decryptString($request->purchase_detail_id);
                } catch (DecryptException $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid Item ID']);
                }
            }

            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $purchase_id)
                ->first();

            if (!$purchase) {
                return response()->json(['success' => false, 'message' => 'Purchase invoice not found.']);
            }

            if ($purchase->status == 1) {
                return response()->json(['success' => false, 'message' => 'Cannot add items to a finalized purchase invoice.']);
            }

            // Determine GST rate: Use request GST if provided, else look for active pricing, default to 18% if not found.
            $productPricing = ProductPricing::where('product_id', $request->product_id)
                ->where('status', 'active')
                ->first();
            $gst_percent = (float)$request->gst_percentage;

            $amounts = ReuseModule::calculateItemAmounts($request->rate, $request->quantity, $gst_percent);

            DB::transaction(function () use ($purchase, $purchase_detail_id, $request, $amounts) {
                if ($purchase_detail_id) {
                    $detail = PurchaseInvoiceDetail::where('id', $purchase_detail_id)
                        ->where('purchase_invoice_id', $purchase->id)
                        ->firstOrFail();
                } else {
                    $detail = new PurchaseInvoiceDetail();
                    $detail->purchase_invoice_id = $purchase->id;
                }

                $detail->fill([
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'rate' => $request->rate,
                    'total_amount' => $amounts['total_amount'],
                    'gst_amount' => $amounts['gst_amount'],
                    'cgst_amount' => $amounts['cgst_amount'],
                    'sgst_amount' => $amounts['sgst_amount'],
                    'chargeable_amount' => $amounts['chargeable_amount']
                ]);
                $detail->save();

                // Update invoice totals
                $purchase->total_quantity = $purchase->purchaseInvoiceDetails()->sum('quantity');
                $purchase->total_amount = $purchase->purchaseInvoiceDetails()->sum('total_amount');
                $purchase->total_gst_amount = $purchase->purchaseInvoiceDetails()->sum('gst_amount');
                $purchase->total_cgst_amount = $purchase->purchaseInvoiceDetails()->sum('cgst_amount');
                $purchase->total_sgst_amount = $purchase->purchaseInvoiceDetails()->sum('sgst_amount');
                $purchase->chargeable_amount = $purchase->purchaseInvoiceDetails()->sum('chargeable_amount');
                $purchase->no_of_goods = $purchase->purchaseInvoiceDetails()->count();
                $purchase->save();
            });

            $message = $purchase_detail_id ? 'Item updated successfully' : 'Item added successfully';
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Throwable $e) {
            Log::error('Store Purchase Item Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving item.']);
        }
    }

    /**
     * Delete an item from draft purchase invoice.
     */
    public function deletePurchaseItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_detail_id' => 'required',
                'purchase_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            try {
                $purchase_id = Crypt::decryptString($request->purchase_id);
                $purchase_detail_id = Crypt::decryptString($request->purchase_detail_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid IDs provided']);
            }

            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $purchase_id)
                ->first();

            if (!$purchase) {
                return response()->json(['success' => false, 'message' => 'Purchase invoice not found.']);
            }

            if ($purchase->status == 1) {
                return response()->json(['success' => false, 'message' => 'Cannot delete items from a finalized invoice.']);
            }

            DB::transaction(function () use ($purchase, $purchase_detail_id) {
                $detail = PurchaseInvoiceDetail::where('id', $purchase_detail_id)
                    ->where('purchase_invoice_id', $purchase->id)
                    ->firstOrFail();

                $detail->delete();

                // Update invoice totals
                $purchase->total_quantity = $purchase->purchaseInvoiceDetails()->sum('quantity');
                $purchase->total_amount = $purchase->purchaseInvoiceDetails()->sum('total_amount');
                $purchase->total_gst_amount = $purchase->purchaseInvoiceDetails()->sum('gst_amount');
                $purchase->total_cgst_amount = $purchase->purchaseInvoiceDetails()->sum('cgst_amount');
                $purchase->total_sgst_amount = $purchase->purchaseInvoiceDetails()->sum('sgst_amount');
                $purchase->chargeable_amount = $purchase->purchaseInvoiceDetails()->sum('chargeable_amount');
                $purchase->no_of_goods = $purchase->purchaseInvoiceDetails()->count();
                $purchase->save();
            });

            return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('Delete Purchase Item Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting item.']);
        }
    }

    /**
     * Finalize purchase invoice: updates stock and posts financial entry.
     */
    public function finalizePurchase(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            $id = Crypt::decryptString($request->purchase_id);
            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $id)
                ->firstOrFail();

            if ($purchase->status == 1) {
                return response()->json(['success' => false, 'message' => 'Purchase invoice is already finalized.']);
            }

            if ($purchase->purchaseInvoiceDetails()->count() == 0) {
                return response()->json(['success' => false, 'message' => 'Cannot finalize a purchase invoice without items.']);
            }

            DB::transaction(function () use ($purchase, $companyId) {
                // 1. Mark status as Finalized (1)
                $purchase->status = 1;
                $purchase->save();

                $purchaseDate = $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : date('Y-m-d');

                // 2. Increment product stock quantity and record daily stock report
                foreach ($purchase->purchaseInvoiceDetails as $detail) {
                    if ($detail->product_id) {
                        $product = Product::lockForUpdate()->find($detail->product_id);
                        if ($product) {
                            $product->stock_quantity += $detail->quantity;
                            $product->save();
                        }

                        if ($detail->quantity > 0) {
                            \App\Models\Reports\DailyStockReport::recordPurchase(
                                $companyId,
                                $detail->product_id,
                                $purchaseDate,
                                (float)$detail->quantity
                            );
                        }
                    }
                }

                $purchaseVoucher = \App\Models\Accounts\VoucherType::where('name', 'PURCHASE')->first();

                // 3. Create entry in purchase_payment_tracks representing accounts payable bill entry
                PurchasePaymentTrack::create([
                    'purchase_invoice_id' => $purchase->id,
                    'transaction_id' => $purchase->invoice_no,
                    'amount' => $purchase->chargeable_amount,
                    'balance_amount' => $purchase->chargeable_amount,
                    'payment_mode' => 'entry',
                    'voucher_type_id' => $purchaseVoucher ? $purchaseVoucher->id : null,
                    'transaction_date' => now(),
                    'remarks' => 'Auto generated purchase ledger voucher on invoice finalization',
                    'created_by' => session('active_map_id')
                ]);

                // 4. Initialize outstanding balance in purchase_invoice_payments
                PurchaseInvoicePayment::create([
                    'purchase_invoice_id' => $purchase->id,
                    'outstanding_amount' => $purchase->chargeable_amount,
                    'paid_amount' => 0.00,
                    'clear_status' => 0
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Purchase invoice finalized successfully.',
                'redirect_url' => route('purchase.invoices.view', Crypt::encryptString($purchase->id))
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid ID']);
        } catch (\Throwable $e) {
            Log::error('Finalize Purchase Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while finalizing purchase.']);
        }
    }

    /**
     * View finalized purchase invoice details.
     */
    public function view($id)
    {
        try {
            $decryptedId = Crypt::decryptString($id);
            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $decryptedId)
                ->with(['supplier', 'company', 'purchaseInvoiceDetails.product', 'purchaseInvoicePayment', 'purchasePaymentTracks'])
                ->firstOrFail();

            return view('purchase.invoices.view', compact('purchase'));
        } catch (\Throwable $e) {
            Log::error('View Purchase Invoice Error: ' . $e->getMessage());
            return redirect()->route('purchase.invoices.index')->with('error', 'Purchase invoice not found.');
        }
    }

    /**
     * Store supplier payment details.
     */
    public function storePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'purchase_id' => 'required',
                'amount' => 'required|numeric|min:0.01',
                'payment_mode' => 'required|in:cash,bank_transfer,cheque,upi',
                'transaction_id' => 'nullable|string|max:100',
                'transaction_date' => 'required|date',
                'remarks' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            $purchaseId = Crypt::decryptString($request->purchase_id);
            $companyId = session('active_company_id');
            $purchase = PurchaseInvoice::where('company_id', $companyId)
                ->where('id', $purchaseId)
                ->firstOrFail();

            if ($purchase->status == 0) {
                return response()->json(['success' => false, 'message' => 'Cannot record payments against draft invoices.']);
            }

            $paymentRecord = PurchaseInvoicePayment::where('purchase_invoice_id', $purchase->id)->firstOrFail();

            if ($paymentRecord->clear_status == 1) {
                return response()->json(['success' => false, 'message' => 'This invoice is already fully paid.']);
            }

            if ($request->amount > $paymentRecord->outstanding_amount) {
                return response()->json(['success' => false, 'message' => 'Payment amount exceeds outstanding amount (₹' . number_format($paymentRecord->outstanding_amount, 2) . ').']);
            }

            DB::transaction(function () use ($purchase, $paymentRecord, $request) {
                $newOutstanding = $paymentRecord->outstanding_amount - $request->amount;
                $newPaid = $paymentRecord->paid_amount + $request->amount;

                // 1. Update running balances
                $paymentRecord->update([
                    'outstanding_amount' => $newOutstanding,
                    'paid_amount' => $newPaid,
                    'clear_status' => $newOutstanding <= 0.01 ? 1 : 0
                ]);

                $paymentVoucher = \App\Models\Accounts\VoucherType::where('name', 'PAYMENT')->first();

                // 2. Add transaction ledger voucher
                PurchasePaymentTrack::create([
                    'purchase_invoice_id' => $purchase->id,
                    'transaction_id' => $request->transaction_id,
                    'amount' => $request->amount,
                    'balance_amount' => $newOutstanding,
                    'payment_mode' => $request->payment_mode,
                    'voucher_type_id' => $paymentVoucher ? $paymentVoucher->id : null,
                    'transaction_date' => $request->transaction_date,
                    'remarks' => $request->remarks,
                    'created_by' => session('active_map_id')
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.'
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid ID']);
        } catch (\Throwable $e) {
            Log::error('Store Purchase Payment Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while saving payment.']);
        }
    }

    /**
     * Export Purchase Invoices list to Excel/CSV.
     */
    public function export(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $query = PurchaseInvoice::where('company_id', $companyId)->with(['supplier', 'purchaseInvoicePayment']);

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Handle Supplier Filter
            if ($request->has('supplier_id') && !empty($request->supplier_id)) {
                try {
                    $supplierId = Crypt::decryptString($request->supplier_id);
                    $query->where('supplier_id', $supplierId);
                } catch (DecryptException $e) {
                    // Ignore invalid ID
                }
            }

            // Handle Status Filter
            if ($request->has('status') && $request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('id', 'desc')->cursor();

            $fileName = 'purchase_invoices_list_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                '#',
                'Invoice No',
                'Invoice Date',
                'Supplier Code',
                'Supplier Name',
                'Total Quantity (MT)',
                'Chargeable Amount (₹)',
                'Paid Amount (₹)',
                'Outstanding Balance (₹)',
                'Status'
            ];

            $callback = function () use ($invoices, $columns) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM so Microsoft Excel renders Rupee symbol (₹) correctly
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                $index = 1;
                foreach ($invoices as $invoice) {
                    $paid = $invoice->purchaseInvoicePayment->paid_amount ?? 0;
                    $outstanding = $invoice->purchaseInvoicePayment->outstanding_amount ?? 0;
                    $status = $invoice->status == 1 ? 'Finalized' : 'Draft';

                    fputcsv($file, [
                        $index++,
                        $invoice->invoice_no ?? 'N/A',
                        $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-Y') : 'N/A',
                        $invoice->supplier->supplier_code ?? 'N/A',
                        $invoice->supplier->name ?? 'N/A',
                        number_format($invoice->total_quantity ?? 0, 3, '.', ''),
                        number_format($invoice->chargeable_amount ?? 0, 2, '.', ''),
                        number_format($paid, 2, '.', ''),
                        number_format($outstanding, 2, '.', ''),
                        $status
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Purchase Invoice Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export purchase invoices list.');
        }
    }
}
