<?php

namespace App\Http\Controllers\Dealers;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\CreditNoteTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Dealers\DealerCompany;
use App\Models\Dealers\SchemeAmount;
use App\Modules\ReuseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class SchemeAmountController extends Controller
{
    /**
     * Display the Scheme Amount management page.
     */
    public function index(Request $request)
    {
        try {
            if (!$request->filled('dealer_company_id')) {
                return redirect()->route('dealers.index');
            }

            $selectedDealerId = null;
            try {
                $selectedDealerId = Crypt::decryptString($request->query('dealer_company_id'));
            } catch (DecryptException $e) {
                $selectedDealerId = $request->query('dealer_company_id');
            }

            $dealerCompany = ReuseModule::getOwnedDealerCompanyQuery($selectedDealerId)->first();
            if (!$dealerCompany) {
                return redirect()->route('dealers.index');
            }

            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()
                ->with('dealer')
                ->where('status', 1)
                ->get();

            return view('dealers.scheme_amounts.index', compact('dealerCompanies', 'selectedDealerId'));
        } catch (\Throwable $e) {
            Log::error('Scheme Amount Index Error: ' . $e->getMessage());
            return redirect()->route('dealers.index');
        }
    }

    /**
     * Fetch list of Scheme Amount records via AJAX.
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            $query = SchemeAmount::whereHas('dealerCompany', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->with(['dealerCompany.dealer']);

            $totalSchemeAmountFormatted = null;

            if ($request->filled('dealer_company_id')) {
                $query->where('dealer_company_id', $request->dealer_company_id);

                $dealerCompany = DealerCompany::where('id', $request->dealer_company_id)
                    ->where('company_id', $companyId)
                    ->first();

                if ($dealerCompany) {
                    $totalSchemeAmountFormatted = number_format($dealerCompany->total_scheme_amount ?? 0, 2);
                }
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $schemeAmounts = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            $html = view('dealers.scheme_amounts.partials.table', compact('schemeAmounts'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'count' => $schemeAmounts->total(),
                'total_scheme_amount' => $totalSchemeAmountFormatted
            ]);
        } catch (\Throwable $e) {
            Log::error('Scheme Amount List Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching scheme amounts']);
        }
    }

    /**
     * Store a new Scheme Amount record and apply against outstanding invoices.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dealer_company_id' => 'required|exists:dealer_companies,id',
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|digits:4',
                'quantity' => 'required|numeric|min:0.001',
                'rate_per_mt' => 'required|numeric|min:0.01',
                'amount' => 'required|numeric|min:0.01',
                'remarks' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            $amount = (float)$request->amount;

            $dealerCompany = ReuseModule::getOwnedDealerCompanyQuery($request->dealer_company_id)->first();
            if (!$dealerCompany) {
                return response()->json(['success' => false, 'message' => 'Dealer not found or unauthorized access']);
            }

            // Check if dealer has outstanding invoices and prompt user if confirmation has not been provided
            $hasOutstandingInvoices = Invoice::where('buyer_id', $dealerCompany->id)
                ->where('invoice_status', 1)
                ->whereHas('invoicePayment', function ($q) {
                    $q->where('outstanding_amount', '>', 0);
                })
                ->exists();

            if ($hasOutstandingInvoices && !$request->has('confirm_scheme')) {
                return response()->json([
                    'success' => false,
                    'prompt_scheme' => true,
                    'message' => 'This dealer has outstanding invoices available. Would you like to automatically apply this scheme amount as a Credit Note against the outstanding invoices?'
                ]);
            }

            DB::transaction(function () use ($dealerCompany, $request, $amount) {
                SchemeAmount::create([
                    'dealer_company_id' => $dealerCompany->id,
                    'quantity' => $request->quantity,
                    'month' => $request->month,
                    'year' => $request->year,
                    'rate_per_mt' => $request->rate_per_mt,
                    'amount' => $amount,
                    'remarks' => $request->remarks,
                    'created_by' => session('active_map_id'),
                ]);

                // Add calculated amount to existing total_scheme_amount
                $dealerCompany->total_scheme_amount = ($dealerCompany->total_scheme_amount ?? 0) + (float)$amount;
                $dealerCompany->save();

                // Apply scheme amount to outstanding invoices if user agreed (or if dealer had no outstanding invoices prompt)
                $useScheme = $request->boolean('use_scheme', true);

                if ($useScheme && $dealerCompany->total_scheme_amount > 0) {
                    $creditVoucherType = VoucherType::where('name', 'like', '%credit note%')->first();

                    $invoices = Invoice::where('buyer_id', $dealerCompany->id)
                        ->where('invoice_status', 1)
                        ->whereHas('invoicePayment', function ($q) {
                            $q->where('outstanding_amount', '>', 0);
                        })
                        ->with('invoicePayment')
                        ->oldest('invoice_generate_date')
                        ->oldest('id')
                        ->get();

                    foreach ($invoices as $invoice) {
                        if ($dealerCompany->total_scheme_amount <= 0) {
                            break;
                        }

                        $invoicePayment = $invoice->invoicePayment;
                        if (!$invoicePayment || $invoicePayment->outstanding_amount <= 0) {
                            continue;
                        }

                        $appliedAmount = min((float)$dealerCompany->total_scheme_amount, (float)$invoicePayment->outstanding_amount);
                        if ($appliedAmount <= 0) {
                            continue;
                        }

                        // 1. Create PaymentTrack as Credit Note
                        $paymentTrack = PaymentTrack::create([
                            'invoice_id' => $invoice->id,
                            'transaction_id' => 'TXN-SCH-' . strtoupper(uniqid()),
                            'amount' => $appliedAmount,
                            'balance_amount' => max(0, $invoicePayment->outstanding_amount - $appliedAmount),
                            'scheme_amount' => $appliedAmount,
                            'payment_for_mt' => 0,
                            'payment_mode' => 'adjustment',
                            'voucher_type_id' => $creditVoucherType ? $creditVoucherType->id : null,
                            'transaction_date' => now(),
                            'remarks' => 'Auto-applied Scheme Amount Discount' . ($request->remarks ? ': ' . $request->remarks : ''),
                        ]);

                        // 2. Create CreditNoteTrack
                        CreditNoteTrack::create([
                            'payment_track_id' => $paymentTrack->id,
                            'nos' => 0,
                            'amount' => $appliedAmount,
                        ]);

                        // 3. Update InvoicePayment
                        $invoicePayment->credit_note_amount += $appliedAmount;
                        $invoicePayment->outstanding_amount -= $appliedAmount;
                        if ($invoicePayment->outstanding_amount <= 0) {
                            $invoicePayment->clear_status = 'clear payment';
                        } else {
                            $invoicePayment->clear_status = 'pending payment';
                        }
                        $invoicePayment->save();

                        // 4. Deduct applied amount from dealer total_scheme_amount
                        $dealerCompany->total_scheme_amount -= $appliedAmount;
                    }

                    if ($dealerCompany->total_scheme_amount < 0) {
                        $dealerCompany->total_scheme_amount = 0;
                    }
                    $dealerCompany->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Scheme amount added successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Scheme Amount Store Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving scheme amount']);
        }
    }

    /**
     * Delete a Scheme Amount record.
     */
    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:scheme_amounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
            }

            $schemeAmount = SchemeAmount::where('id', $request->id)
                ->whereHas('dealerCompany', function ($q) {
                    $q->where('company_id', session('active_company_id'));
                })->first();

            if (!$schemeAmount) {
                return response()->json(['success' => false, 'message' => 'Record not found or unauthorized access']);
            }

            $dealerCompany = $schemeAmount->dealerCompany;

            DB::transaction(function () use ($schemeAmount, $dealerCompany) {
                $amount = (float)$schemeAmount->amount;
                $schemeAmount->delete();

                if ($dealerCompany) {
                    $dealerCompany->total_scheme_amount = max(0, ($dealerCompany->total_scheme_amount ?? 0) - $amount);
                    $dealerCompany->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Scheme amount record deleted successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Scheme Amount Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting record']);
        }
    }

    /**
     * Export Scheme Amount Records to Excel (CSV Stream).
     */
    public function exportExcel(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            $query = SchemeAmount::whereHas('dealerCompany', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->with(['dealerCompany.dealer']);

            if ($request->filled('dealer_company_id')) {
                $query->where('dealer_company_id', $request->dealer_company_id);
            }

            $schemeAmounts = $query->latest()->cursor();

            $fileName = 'scheme_amounts_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            $columns = [
                '#',
                'Dealer Code',
                'Dealer Name',
                'Month / Year',
                'Quantity (MT)',
                'Rate / MT (₹)',
                'Scheme Amount (₹)',
                'Remarks',
                'Date Recorded'
            ];

            $callback = function () use ($schemeAmounts, $columns, $months) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM so Microsoft Excel renders Rupee symbol (₹) correctly
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                foreach ($schemeAmounts as $index => $item) {
                    $monthName = $months[$item->month] ?? $item->month;
                    fputcsv($file, [
                        $index + 1,
                        $item->dealerCompany->dealer->dealer_code ?? 'N/A',
                        $item->dealerCompany->dealer->dealer_name ?? 'N/A',
                        $monthName . ' ' . $item->year,
                        number_format($item->quantity, 3, '.', ''),
                        number_format($item->rate_per_mt, 2, '.', ''),
                        number_format($item->amount, 2, '.', ''),
                        $item->remarks ?? '-',
                        $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d-M-Y h:i A') : '-'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Export Scheme Amounts Excel Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export scheme amounts.');
        }
    }

    /**
     * Export Scheme Amount Records to PDF.
     */
    public function exportPdf(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $company = Company::find($companyId);

            $query = SchemeAmount::whereHas('dealerCompany', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->with(['dealerCompany.dealer']);

            $selectedDealer = null;
            if ($request->filled('dealer_company_id')) {
                $query->where('dealer_company_id', $request->dealer_company_id);
                $selectedDealer = DealerCompany::with('dealer')
                    ->where('id', $request->dealer_company_id)
                    ->where('company_id', $companyId)
                    ->first();
            }

            $schemeAmounts = $query->latest()->get();
            $totalAmount = $schemeAmounts->sum('amount');
            $totalQuantity = $schemeAmounts->sum('quantity');

            $pdf = Pdf::loadView('dealers.scheme_amounts.pdf.scheme_amounts_pdf', compact('schemeAmounts', 'company', 'selectedDealer', 'totalAmount', 'totalQuantity'));
            return $pdf->stream('scheme_amounts_report_' . date('Ymd_His') . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Export Scheme Amounts PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export scheme amounts PDF.');
        }
    }

    /**
     * Fetch total invoice quantity (MT) for a dealer in a given month and year.
     */
    public function getMonthlyQuantity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dealer_company_id' => 'required',
                'month'             => 'required|integer|between:1,12',
                'year'              => 'required|integer|min:2000|max:2100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters provided.',
                    'total_quantity' => '0.000'
                ], 422);
            }

            $companyId = session('active_company_id');
            $dealerCompanyId = $request->dealer_company_id;
            $month = (int) $request->month;
            $year = (int) $request->year;

            // Query total quantity from invoices matching buyer_id, month, and year of invoice_generate_date
            $totalQuantity = Invoice::where('buyer_id', $dealerCompanyId)
                ->whereHas('buyer', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereYear('invoice_generate_date', $year)
                ->whereMonth('invoice_generate_date', $month)
                ->sum('total_quantity');

            $formattedQuantity = number_format((float)$totalQuantity, 3, '.', '');

            return response()->json([
                'success' => true,
                'total_quantity' => $formattedQuantity,
                'raw_quantity' => (float)$totalQuantity
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Monthly Quantity Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating monthly quantity',
                'total_quantity' => '0.000'
            ], 500);
        }
    }
}
