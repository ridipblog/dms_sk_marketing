<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoiceDetail;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\CreditNoteTrack;
use App\Models\Accounts\DebitNoteTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Dealers\DealerCompany;
use App\Models\Inventory\ProductPricing;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Modules\ReuseModule;

class InvoiceController extends Controller
{
    /**
     * Convert number to Indian Currency format (Words)
     */
    private function getIndianCurrency(float $number)
    {
        try {
            $decimal = round($number - ($no = floor($number)), 2) * 100;
            $hundred = null;
            $digits_length = strlen($no);
            $i = 0;
            $str = array();
            $words = array(
                0 => '',
                1 => 'One',
                2 => 'Two',
                3 => 'Three',
                4 => 'Four',
                5 => 'Five',
                6 => 'Six',
                7 => 'Seven',
                8 => 'Eight',
                9 => 'Nine',
                10 => 'Ten',
                11 => 'Eleven',
                12 => 'Twelve',
                13 => 'Thirteen',
                14 => 'Fourteen',
                15 => 'Fifteen',
                16 => 'Sixteen',
                17 => 'Seventeen',
                18 => 'Eighteen',
                19 => 'Nineteen',
                20 => 'Twenty',
                30 => 'Thirty',
                40 => 'Forty',
                50 => 'Fifty',
                60 => 'Sixty',
                70 => 'Seventy',
                80 => 'Eighty',
                90 => 'Ninety'
            );
            $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');

            while ($i < $digits_length) {
                $divider = ($i == 2) ? 10 : 100;
                $number = floor($no % $divider);
                $no = floor($no / $divider);
                $i += $divider == 10 ? 1 : 2;

                if ($number) {
                    $counter = count($str);
                    $plural = ($counter && $number > 9) ? '' : null;
                    $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;

                    if ($number < 21) {
                        $str[] = $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
                    } else {
                        $tens = $words[floor($number / 10) * 10];
                        $units = $words[$number % 10];
                        $str[] = $tens . ' ' . $units . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
                    }
                } else {
                    $str[] = null;
                }
            }

            $Rupees = implode('', array_reverse($str));
            $paiseStr = '';
            if ($decimal > 0) {
                $paiseStr = " and ";
                if ($decimal < 21) {
                    $paiseStr .= $words[$decimal];
                } else {
                    $paiseStr .= $words[floor($decimal / 10) * 10] . " " . $words[$decimal % 10];
                }
                $paiseStr .= ' Paise';
            }

            $result = ($Rupees ? 'INR ' . trim($Rupees) : '') . $paiseStr . ' Only';
            return preg_replace('/\s+/', ' ', $result);
        } catch (\Exception $e) {
            Log::error('Currency Conversion Error: ' . $e->getMessage());
            return 'INR ' . number_format($number, 2) . ' Only'; // Fallback
        }
    }

    /**
     * Display the view mockup (Publicly accessible via QR code).
     */
    public function view($id = null)
    {
        if (!$id) {
            return view('accounts.invoices.view', ['errorMessage' => 'Invoice ID not provided.']);
        }

        try {
            $decryptedId = Crypt::decryptString($id);
            $invoice = Invoice::where('id', $decryptedId)->with([
                'buyer.dealer',
                'shipTo.dealer',
                'invoiceDetails',
                'invoiceDetails.productPricing.product',
                'createdBy.company.bankDetail',
                'bankDetail'
            ])->firstOrFail();

            $amountInWords = $this->getIndianCurrency($invoice->chargeable_amount ?? 0);
            $taxAmountInWords = $this->getIndianCurrency($invoice->total_gst_amount ?? 0);

            return view('accounts.invoices.view', compact('invoice', 'amountInWords', 'taxAmountInWords'));
        } catch (DecryptException $e) {
            return view('accounts.invoices.view', ['errorMessage' => 'Invalid Invoice ID.']);
        } catch (\Exception $e) {
            Log::error('Invoice View Error: ' . $e->getMessage());
            return view('accounts.invoices.view', ['errorMessage' => 'Invoice not found.']);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $companyId = session('active_company_id');
            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()->with('dealer')
                ->where('status', 1)->get();

            return view('accounts.invoices.index', compact('dealerCompanies'));
        } catch (\Throwable $e) {
            Log::error('Invoice Index Error: ' . $e->getMessage());
            return view('accounts.invoices.index', [
                'errorMessage' => 'Something went wrong while loading the page.'
            ]);
        }
    }

    /**
     * Fetch invoices list via AJAX
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            // Query invoices belonging to the active map ID
            $query = ReuseModule::getOwnedInvoiceQuery()
                ->with(['buyer.dealer', 'shipTo.dealer']);

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('buyer.dealer', function ($sq) use ($search) {
                            $sq->where('dealer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('shipTo.dealer', function ($sq) use ($search) {
                            $sq->where('dealer_name', 'like', "%{$search}%");
                        });
                });
            }

            // Handle Dealer Filter
            if ($request->has('dealer_id') && !empty($request->dealer_id)) {
                try {
                    $dealerId = Crypt::decryptString($request->dealer_id);
                    $query->where('buyer_id', $dealerId);
                } catch (DecryptException $e) {
                    // Ignore invalid dealer id
                }
            }

            // Handle Status Filter
            if ($request->has('status') && $request->status !== null) {
                $query->where('invoice_status', $request->status);
            }

            $invoices = $query->orderBy('id', 'desc')->paginate(10);

            $html = view('accounts.invoices.list', compact('invoices'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching invoices.'
            ]);
        }
    }

    /**
     * Create resource view
     */
    public function generate($invoice_id = null)
    {
        try {
            if ($invoice_id) {
                try {
                    $invoice_id = Crypt::decryptString($invoice_id);
                } catch (DecryptException $e) {
                    Log::warning('Invalid or tampered invoice ID in URL: ' . $invoice_id);
                    return redirect()->route('accounts.invoices.index')->with('error', 'Invalid Invoice Link.');
                }
            }
            $companyId = session('active_company_id');

            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()->with('dealer')
                ->where('status', 1)->get();

            $products = ReuseModule::getOwnedProductPricingQuery()->with('product')
                ->where('status', 'active')
                ->get();

            $invoice = null;
            if ($invoice_id) {
                $invoice = ReuseModule::getOwnedInvoiceQuery($invoice_id)
                    ->with(['invoiceDetails.productPricing.product'])
                    ->firstOrFail();
            }

            return view('accounts.invoices.generate', compact('dealerCompanies', 'invoice_id', 'products', 'invoice'));
        } catch (\Throwable $e) {
            Log::error('Invoice Create Error: ' . $e->getMessage());
            return view('accounts.invoices.generate', [
                'errorMessage' => 'Something went wrong while loading the page.'
            ]);
        }
    }

    /**
     * Store or update the invoice.
     */
    public function store(Request $request)
    {
        try {
            // Decrypt the invoice_id if present
            if ($request->filled('invoice_id')) {
                try {
                    $request->merge(['invoice_id' => Crypt::decryptString($request->invoice_id)]);
                } catch (DecryptException $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid or tampered Invoice ID.']);
                }
            }

            $validator = Validator::make($request->all(), [
                'user_invoice_no' => 'nullable|string|max:255',
                'buyer_id' => 'required|exists:dealer_companies,id',
                'ship_to' => 'required|exists:dealer_companies,id',
                'tax_type' => 'nullable|in:intra,inter',
                'gst' => 'required|numeric|min:0',
                'invoice_generate_date' => 'nullable|date',
                'vehicle_no' => 'nullable|string|max:255',
                'delivery_note' => 'nullable|string|max:255',
                'destination' => 'nullable|string|max:255',
                'invoice_id' => 'nullable|exists:invoices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $userInvoiceNo = $request->filled('user_invoice_no') ? trim($request->user_invoice_no) : null;
            $taxType = $request->tax_type ?? 'intra';
            $isInterState = ($taxType === 'inter');

            $data = [
                'user_invoice_no' => $userInvoiceNo,
                'buyer_id' => $request->buyer_id,
                'ship_to' => $request->ship_to,
                'tax_type' => $taxType,
                'gst' => $request->gst,
                'cgst' => $isInterState ? 0 : ($request->gst / 2),
                'sgst' => $isInterState ? 0 : ($request->gst / 2),
                'igst' => $isInterState ? $request->gst : 0,
                'invoice_generate_date' => $request->invoice_generate_date ?? now()->format('Y-m-d'),
                'vehicle_no' => $request->vehicle_no,
                'delivery_note' => $request->delivery_note,
                'destination' => $request->destination,
            ];

            if ($request->invoice_id) {
                $invoice = ReuseModule::getOwnedInvoiceQuery($request->invoice_id)->firstOrFail();

                if ($invoice->invoice_status == 1) {
                    return response()->json(['success' => false, 'message' => 'Cannot update a finalized invoice.']);
                }

                // Recalculate existing items if GST percentage or tax type is updated
                $details = $invoice->invoiceDetails;
                if ($details && $details->count() > 0) {
                    foreach ($details as $detail) {
                        $rate = (float)($detail->custom_price ?? ($detail->productPricing ? $detail->productPricing->price_per_mt : 0));
                        $quantity = (float)$detail->quantity;
                        $itemAmounts = ReuseModule::calculateItemAmounts($rate, $quantity, (float)$request->gst, $taxType);

                        $detail->update([
                            'total_amount' => $itemAmounts['total_amount'],
                            'gst_amount' => $itemAmounts['gst_amount'],
                            'cgst_amount' => $itemAmounts['cgst_amount'],
                            'sgst_amount' => $itemAmounts['sgst_amount'],
                            'igst_amount' => $itemAmounts['igst_amount'],
                            'chargeable_amount' => $itemAmounts['chargeable_amount']
                        ]);
                    }

                    $totAmount = (float)$invoice->invoiceDetails()->sum('total_amount');
                    $totCgst = (float)$invoice->invoiceDetails()->sum('cgst_amount');
                    $totSgst = (float)$invoice->invoiceDetails()->sum('sgst_amount');
                    $totIgst = (float)$invoice->invoiceDetails()->sum('igst_amount');
                    $totGst = (float)$invoice->invoiceDetails()->sum('gst_amount');

                    $unroundedTotal = $totAmount + $totCgst + $totSgst + $totIgst;
                    $finalRoundedAmount = round($unroundedTotal);
                    $roundOff = round($finalRoundedAmount - $unroundedTotal, 2);

                    $data['total_quantity'] = $invoice->invoiceDetails()->sum('quantity');
                    $data['total_amount'] = $totAmount;
                    $data['total_gst_amount'] = $totGst;
                    $data['total_cgst_amount'] = $totCgst;
                    $data['total_sgst_amount'] = $totSgst;
                    $data['total_igst_amount'] = $totIgst;
                    $data['round_of'] = $roundOff;
                    $data['chargeable_amount'] = $finalRoundedAmount;
                    $data['no_of_goods'] = $invoice->invoiceDetails()->count();
                }

                $invoice->update($data);
                $message = 'Invoice updated successfully.';
            } else {
                // Generate new invoice with transaction and lock to prevent duplicates
                $invoice = DB::transaction(function () use ($data) {
                    $data['invoice_no'] = ReuseModule::generateInvoiceNumber();
                    $data['created_by'] = session('active_map_id');
                    $data['status'] = 'Generated';

                    return Invoice::create($data);
                });

                $message = 'Invoice generated successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'invoice_id' => Crypt::encryptString($invoice->id),
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the invoice.'
            ], 500);
        }
    }

    /**
     * Fetch rendered HTML of invoice items list via AJAX
     */
    public function fetchInvoiceItems(Request $request)
    {
        try {
            $invoice_id = $request->invoice_id;
            if (!$invoice_id) {
                return response()->json(['success' => false, 'message' => 'Invoice ID required']);
            }

            // Decrypt ID if it's passed encrypted via AJAX
            try {
                $invoice_id = Crypt::decryptString($invoice_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Invoice ID']);
            }

            $companyId = session('active_company_id');

            $invoice = ReuseModule::getOwnedInvoiceQuery($invoice_id)
                ->with(['invoiceDetails.productPricing.product'])
                ->first();

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found or unauthorized access']);
            }
            $products = ReuseModule::getOwnedProductPricingQuery()->with('product')
                ->where('status', 'active')
                ->get();

            $html = view('accounts.invoices.partials.invoice_items_list', [
                'invoiceDetails' => $invoice->invoiceDetails,
                'products' => $products
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Fetch Invoice Items Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching items']);
        }
    }

    /**
     * Store a new invoice item via AJAX
     */
    public function storeInvoiceItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required',
                'product_pricing_id' => 'required|exists:product_pricings,id',
                'quantity' => 'required|numeric|min:0.001',
                'custom_price' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            try {
                $invoice_id = Crypt::decryptString($request->invoice_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Invoice ID']);
            }

            $invoice_detail_id = null;
            if ($request->has('invoice_detail_id') && !empty($request->invoice_detail_id)) {
                try {
                    $invoice_detail_id = Crypt::decryptString($request->invoice_detail_id);
                } catch (DecryptException $e) {
                    return response()->json(['success' => false, 'message' => 'Invalid Item ID']);
                }
            }

            $companyId = session('active_company_id');
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoice_id)->first();

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found or unauthorized access']);
            }

            if ($invoice->invoice_status == 1) {
                return response()->json(['success' => false, 'message' => 'Cannot add or update items in a finalized invoice.']);
            }

            $productPricing = ReuseModule::getOwnedProductPricingQuery($request->product_pricing_id)->firstOrFail();

            $product = $productPricing->product;
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found.']);
            }

            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock is low. Available stock: ' . number_format($product->stock_quantity, 3) . ' MT.'
                ]);
            }

            // Use custom price if provided by user, otherwise fall back to product pricing rate
            if ($request->filled('custom_price') && (float)$request->custom_price >= 0) {
                $rate = (float)$request->custom_price;
            } else {
                $rate = (float)$productPricing->price_per_mt;
            }
            $quantity = $request->quantity;
            $gst_percent = (float)($invoice->gst ?? 18);
            $taxType = $invoice->tax_type ?? 'intra';

            $amounts = ReuseModule::calculateItemAmounts($rate, $quantity, $gst_percent, $taxType);

            DB::transaction(function () use ($invoice, $invoice_detail_id, $productPricing, $rate, $quantity, $amounts) {

                if ($invoice_detail_id) {
                    $invoiceDetail = InvoiceDetail::where('id', $invoice_detail_id)
                        ->where('invoice_id', $invoice->id)
                        ->firstOrFail();
                } else {
                    $invoiceDetail = new InvoiceDetail();
                    $invoiceDetail->invoice_id = $invoice->id;
                }

                $invoiceDetail->fill([
                    'product_pricing_id' => $productPricing->id,
                    'custom_price' => $rate,
                    'quantity' => $quantity,
                    'total_amount' => $amounts['total_amount'],
                    'gst_amount' => $amounts['gst_amount'],
                    'cgst_amount' => $amounts['cgst_amount'],
                    'sgst_amount' => $amounts['sgst_amount'],
                    'igst_amount' => $amounts['igst_amount'],
                    'chargeable_amount' => $amounts['chargeable_amount']
                ]);

                $invoiceDetail->save();

                // Update parent invoice totals
                $totAmount = (float)$invoice->invoiceDetails()->sum('total_amount');
                $totCgst = (float)$invoice->invoiceDetails()->sum('cgst_amount');
                $totSgst = (float)$invoice->invoiceDetails()->sum('sgst_amount');
                $totIgst = (float)$invoice->invoiceDetails()->sum('igst_amount');
                $totGst = (float)$invoice->invoiceDetails()->sum('gst_amount');

                $unroundedTotal = $totAmount + $totCgst + $totSgst + $totIgst;
                $finalRoundedAmount = round($unroundedTotal);
                $roundOff = round($finalRoundedAmount - $unroundedTotal, 2);

                $invoice->total_quantity = $invoice->invoiceDetails()->sum('quantity');
                $invoice->total_amount = $totAmount;
                $invoice->total_gst_amount = $totGst;
                $invoice->total_cgst_amount = $totCgst;
                $invoice->total_sgst_amount = $totSgst;
                $invoice->total_igst_amount = $totIgst;
                $invoice->round_of = $roundOff;
                $invoice->chargeable_amount = $finalRoundedAmount;
                $invoice->no_of_goods = $invoice->invoiceDetails()->count();
                $invoice->save();
            });

            $message = $invoice_detail_id ? 'Item updated successfully' : 'Item added successfully';
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Throwable $e) {
            Log::error('Store Invoice Item Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving item']);
        }
    }

    /**
     * Delete an invoice item via AJAX
     */
    public function deleteInvoiceItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_detail_id' => 'required',
                'invoice_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            try {
                $invoice_id = Crypt::decryptString($request->invoice_id);
                $invoice_detail_id = Crypt::decryptString($request->invoice_detail_id);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid IDs provided']);
            }

            $companyId = session('active_company_id');
            $invoice = ReuseModule::getOwnedInvoiceQuery($invoice_id)->first();

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found or unauthorized access']);
            }

            if ($invoice->invoice_status == 1) {
                return response()->json(['success' => false, 'message' => 'Cannot delete items from a finalized invoice.']);
            }

            DB::transaction(function () use ($invoice, $invoice_detail_id) {
                $invoiceDetail = InvoiceDetail::where('id', $invoice_detail_id)
                    ->where('invoice_id', $invoice->id)
                    ->firstOrFail();

                $invoiceDetail->delete();

                // Update parent invoice totals
                $totAmount = (float)$invoice->invoiceDetails()->sum('total_amount');
                $totCgst = (float)$invoice->invoiceDetails()->sum('cgst_amount');
                $totSgst = (float)$invoice->invoiceDetails()->sum('sgst_amount');
                $totIgst = (float)$invoice->invoiceDetails()->sum('igst_amount');
                $totGst = (float)$invoice->invoiceDetails()->sum('gst_amount');

                $unroundedTotal = $totAmount + $totCgst + $totSgst + $totIgst;
                $finalRoundedAmount = round($unroundedTotal);
                $roundOff = round($finalRoundedAmount - $unroundedTotal, 2);

                $invoice->total_quantity = $invoice->invoiceDetails()->sum('quantity');
                $invoice->total_amount = $totAmount;
                $invoice->total_gst_amount = $totGst;
                $invoice->total_cgst_amount = $totCgst;
                $invoice->total_sgst_amount = $totSgst;
                $invoice->total_igst_amount = $totIgst;
                $invoice->round_of = $roundOff;
                $invoice->chargeable_amount = $finalRoundedAmount;
                $invoice->no_of_goods = $invoice->invoiceDetails()->count();
                $invoice->save();
            });

            return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('Delete Invoice Item Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting item']);
        }
    }

    /**
     * Finalize (Generate) an invoice
     */
    public function finalizeInvoice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            $id = Crypt::decryptString($request->invoice_id);
            $companyId = session('active_company_id');
            $invoice = ReuseModule::getOwnedInvoiceQuery($id)->with('createdBy')->firstOrFail();

            if ($invoice->invoice_status == 1) {
                return response()->json(['success' => false, 'message' => 'Invoice is already finalized.']);
            }

            if ($invoice->invoiceDetails()->count() == 0) {
                return response()->json(['success' => false, 'message' => 'Cannot generate an invoice without items.']);
            }

            $dealerCompany = DealerCompany::find($invoice->buyer_id);
            if ($dealerCompany && $dealerCompany->total_scheme_amount > 0 && !$request->has('confirm_scheme')) {
                return response()->json([
                    'success' => false,
                    'prompt_scheme' => true,
                    'scheme_amount' => (float)$dealerCompany->total_scheme_amount,
                    'message' => 'Dealer has an available scheme balance of ₹' . number_format($dealerCompany->total_scheme_amount, 2) . '. Would you like to use this amount as a Credit Note for this invoice?'
                ]);
            }

            DB::transaction(function () use ($invoice, $request, $companyId) {
                $invoice->invoice_status = 1;

                // Store primary ID of active Company Bank Detail for this company at the time of finalization
                $targetCompanyId = ($invoice->createdBy && $invoice->createdBy->company_id) 
                    ? $invoice->createdBy->company_id 
                    : $companyId;

                if ($targetCompanyId) {
                    $activeBankDetail = \App\Models\CompanyBankDetail::where('company_id', $targetCompanyId)
                        ->where('status', 'active')
                        ->first();

                    if ($activeBankDetail) {
                        $invoice->company_bank_detail_id = $activeBankDetail->id;
                    }
                }

                if (empty($invoice->invoice_generate_date)) {
                    $invoice->invoice_generate_date = now()->format('Y-m-d');
                }
                $generateDate = \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('Y-m-d');
                $invoice->due_date = date('Y-m-d', strtotime('+21 days', strtotime($generateDate)));
                $invoice->save();

                // Decrease product stock & record product-wise sale quantity in DailyStockReport
                foreach ($invoice->invoiceDetails as $detail) {
                    $productId = $detail->productPricing ? $detail->productPricing->product_id : null;
                    if ($productId) {
                        $product = \App\Models\Inventory\Product::lockForUpdate()->find($productId);
                        if ($product) {
                            if ($product->stock_quantity < $detail->quantity) {
                                throw new \Exception("Stock is low for product: " . $product->product_name . ". Available stock: " . number_format($product->stock_quantity, 3) . " MT.");
                            }
                            $product->stock_quantity -= $detail->quantity;
                            $product->save();
                        }

                        if ($detail->quantity > 0) {
                            \App\Models\Reports\DailyStockReport::recordSale(
                                $companyId,
                                $productId,
                                $generateDate,
                                (float)$detail->quantity
                            );
                        }
                    }
                }

                $saleVoucherType = VoucherType::where('name', 'SALES')->first();

                $txDate = \Carbon\Carbon::parse($invoice->invoice_generate_date ?? now())->setTimeFrom(now());

                PaymentTrack::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->chargeable_amount,
                    'balance_amount' => $invoice->chargeable_amount,
                    'payment_for_mt' => $invoice->total_quantity,
                    'payment_mode' => 'entry',
                    'voucher_type_id' => $saleVoucherType ? $saleVoucherType->id : null,
                    'transaction_date' => $txDate,
                    'remarks' => 'Auto generated sale voucher on invoice finalization',
                ]);

                $invoicePayment = InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'outstanding_amount' => $invoice->chargeable_amount,
                    'paid_amount' => 0.00,
                    'clear_status' => 'pending payment'
                ]);

                // Auto-apply scheme amount if dealer has total_scheme_amount balance available and user agreed
                $dealerCompany = DealerCompany::find($invoice->buyer_id);
                $useScheme = $request->boolean('use_scheme', false);

                if ($useScheme && $dealerCompany && $dealerCompany->total_scheme_amount > 0 && $invoicePayment->outstanding_amount > 0) {
                    $appliedAmount = min((float)$dealerCompany->total_scheme_amount, (float)$invoicePayment->outstanding_amount);

                    if ($appliedAmount > 0) {
                        $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                        $creditPaymentTrack = PaymentTrack::create([
                            'invoice_id' => $invoice->id,
                            'transaction_id' => 'TXN-SCH-' . strtoupper(uniqid()),
                            'amount' => $appliedAmount,
                            'balance_amount' => max(0, $invoicePayment->outstanding_amount - $appliedAmount),
                            'scheme_amount' => $appliedAmount,
                            'payment_for_mt' => 0,
                            'payment_mode' => 'adjustment',
                            'voucher_type_id' => $creditVoucherType ? $creditVoucherType->id : null,
                            'transaction_date' => now(),
                            'remarks' => 'Auto-applied Scheme Amount Discount on Invoice Finalization',
                        ]);

                        CreditNoteTrack::create([
                            'payment_track_id' => $creditPaymentTrack->id,
                            'nos' => 0,
                            'amount' => $appliedAmount,
                        ]);

                        $invoicePayment->credit_note_amount += $appliedAmount;
                        $invoicePayment->outstanding_amount -= $appliedAmount;
                        if ($invoicePayment->outstanding_amount <= 0) {
                            $invoicePayment->clear_status = 'clear payment';
                        } else {
                            $invoicePayment->clear_status = 'pending payment';
                        }
                        $invoicePayment->save();

                        $dealerCompany->total_scheme_amount -= $appliedAmount;
                        if ($dealerCompany->total_scheme_amount < 0) {
                            $dealerCompany->total_scheme_amount = 0;
                        }
                        $dealerCompany->save();
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully.',
                'redirect_url' => route('accounts.invoices.view', Crypt::encryptString($invoice->id))
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid invoice ID']);
        } catch (\Exception $e) {
            Log::error('Finalize Invoice Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'An error occurred while generating the invoice.']);
        }
    }

    /**
     * Export Invoices list to Excel/CSV.
     */
    public function export(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            $query = ReuseModule::getOwnedInvoiceQuery()
                ->with(['buyer.dealer', 'shipTo.dealer', 'invoicePayment']);

            // Handle Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhereHas('buyer.dealer', function ($sq) use ($search) {
                            $sq->where('dealer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('shipTo.dealer', function ($sq) use ($search) {
                            $sq->where('dealer_name', 'like', "%{$search}%");
                        });
                });
            }

            // Handle Dealer Filter
            if ($request->has('dealer_id') && !empty($request->dealer_id)) {
                try {
                    $dealerId = Crypt::decryptString($request->dealer_id);
                    $query->where('buyer_id', $dealerId);
                } catch (DecryptException $e) {
                    // Ignore invalid dealer id
                }
            }

            // Handle Status Filter
            if ($request->has('status') && $request->status !== null && $request->status !== '') {
                $query->where('invoice_status', $request->status);
            }

            $invoices = $query->orderBy('id', 'desc')->cursor();

            $fileName = 'invoices_list_' . date('Ymd_His') . '.csv';

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
                'Dealer Code',
                'Dealer Name',
                'Dispatch Doc No',
                'Total Quantity (MT)',
                'Chargeable Amount (₹)',
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
                    $outstanding = $invoice->invoicePayment->outstanding_amount ?? 0;
                    $status = $invoice->invoice_status == 1 ? 'Finalized' : 'Draft';

                    fputcsv($file, [
                        $index++,
                        $invoice->invoice_no ?? 'N/A',
                        $invoice->invoice_generate_date ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d-M-Y') : 'N/A',
                        $invoice->buyer->dealer->dealer_code ?? 'N/A',
                        $invoice->buyer->dealer->dealer_name ?? 'N/A',
                        $invoice->dispatch_doc_no ?? $invoice->invoice_no ?? 'N/A',
                        number_format($invoice->total_quantity ?? 0, 3, '.', ''),
                        number_format($invoice->chargeable_amount ?? 0, 2, '.', ''),
                        number_format($outstanding, 2, '.', ''),
                        $status
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Invoice Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export invoices list.');
        }
    }

    /**
     * Check if invoice has associated payment records before soft delete.
     */
    public function checkDelete(Request $request)
    {
        try {
            $invoiceId = $request->invoice_id;
            if (!$invoiceId) {
                return response()->json(['success' => false, 'message' => 'Invoice ID is required.'], 400);
            }

            try {
                $decryptedId = Crypt::decryptString($invoiceId);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Invoice ID.'], 400);
            }

            $invoice = ReuseModule::getOwnedInvoiceQuery($decryptedId)->first();
            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found or unauthorized.'], 404);
            }

            $saleVoucherType = VoucherType::where(DB::raw('UPPER(name)'), 'SALES')->first();
            $saleVoucherTypeId = $saleVoucherType ? $saleVoucherType->id : null;

            // Count payment tracks that are NOT the sales voucher entry
            $paymentTracksQuery = PaymentTrack::where('invoice_id', $invoice->id);
            if ($saleVoucherTypeId) {
                $paymentTracksQuery->where(function ($q) use ($saleVoucherTypeId) {
                    $q->where('voucher_type_id', '!=', $saleVoucherTypeId)
                      ->orWhereNull('voucher_type_id');
                });
            }

            $paymentRecordsCount = $paymentTracksQuery->count();

            return response()->json([
                'success' => true,
                'has_payment_records' => $paymentRecordsCount > 0,
                'payment_records_count' => $paymentRecordsCount,
                'invoice_no' => $invoice->invoice_no ?? ''
            ]);
        } catch (\Throwable $e) {
            Log::error('Check Delete Invoice Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while checking invoice payment records.'], 500);
        }
    }

    /**
     * Soft delete an invoice and all its related records (invoice details, invoice payment, payment tracks, debit/credit note tracks).
     */
    public function destroy(Request $request)
    {
        try {
            $invoiceId = $request->invoice_id;
            if (!$invoiceId) {
                return response()->json(['success' => false, 'message' => 'Invoice ID is required.'], 400);
            }

            try {
                $decryptedId = Crypt::decryptString($invoiceId);
            } catch (DecryptException $e) {
                return response()->json(['success' => false, 'message' => 'Invalid Invoice ID.'], 400);
            }

            $invoice = ReuseModule::getOwnedInvoiceQuery($decryptedId)->first();
            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found or unauthorized.'], 404);
            }

            DB::transaction(function () use ($invoice) {
                // 1. Soft delete invoice details
                InvoiceDetail::where('invoice_id', $invoice->id)->delete();

                // 2. Soft delete payment tracks and child credit/debit note tracks
                $paymentTracks = PaymentTrack::where('invoice_id', $invoice->id)->get();
                $paymentTrackIds = $paymentTracks->pluck('id')->toArray();

                if (!empty($paymentTrackIds)) {
                    CreditNoteTrack::whereIn('payment_track_id', $paymentTrackIds)->delete();
                    DebitNoteTrack::whereIn('payment_track_id', $paymentTrackIds)->delete();
                    PaymentTrack::whereIn('id', $paymentTrackIds)->delete();
                }

                // 3. Soft delete invoice payment summary
                InvoicePayment::where('invoice_id', $invoice->id)->delete();

                // 4. Soft delete invoice
                $invoice->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice and all related payment records soft-deleted successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Delete Invoice Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete invoice.'], 500);
        }
    }
}
