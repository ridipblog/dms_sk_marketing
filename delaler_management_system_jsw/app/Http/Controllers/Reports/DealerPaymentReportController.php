<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Accounts\PaymentTrack;
use App\Modules\ReuseModule;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealerPaymentReportController extends Controller
{
    /**
     * Display the dealer wise payment report dashboard.
     */
    public function index()
    {
        try {
            $dealerCompanies = ReuseModule::getOwnedDealerCompanyQuery()
                ->with('dealer')
                ->where('status', 1)
                ->get();

            return view('reports.dealer_payments.index', compact('dealerCompanies'));
        } catch (\Throwable $e) {
            Log::error('Dealer Payment Report Index Error: ' . $e->getMessage());
            return view('reports.dealer_payments.index', [
                'dealerCompanies' => collect(),
                'errorMessage' => 'Something went wrong while loading the report page.'
            ]);
        }
    }

    /**
     * Fetch dealer wise payment records via AJAX POST.
     */
    public function list(Request $request)
    {
        try {
            $query = $this->buildReportQuery($request);

            $page = $request->input('page', 1);

            // Clone query to calculate KPI metrics before pagination
            $kpiQuery = clone $query;
            $totalCount = $kpiQuery->count('payment_tracks.id');
            $totalAmount = $kpiQuery->sum('payment_tracks.amount');

            $payments = $query->orderBy('payment_tracks.transaction_date', 'desc')
                ->orderBy('payment_tracks.id', 'desc')
                ->paginate(10, ['*'], 'page', $page);

            $html = view('reports.dealer_payments.partials.table', compact('payments'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'total_count' => number_format($totalCount),
                'total_amount' => '₹ ' . inr($totalAmount)
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer Payment Report List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading payment report data.',
                'html' => '<div class="alert alert-danger text-center">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }

    /**
     * Export dealer wise payment report to CSV/Excel.
     */
    public function export(Request $request)
    {
        try {
            $query = $this->buildReportQuery($request);
            $payments = $query->orderBy('payment_tracks.transaction_date', 'desc')
                ->orderBy('payment_tracks.id', 'desc')
                ->cursor();

            $fileName = 'dealer_wise_payment_report_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                '#',
                'Dealer Name',
                'Dealer Code',
                'Invoice No',
                'Transaction ID',
                'Transaction Date',
                'Voucher Type / Mode',
                'Amount (₹)',
                'Remarks'
            ];

            $callback = function () use ($payments, $columns) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM for Microsoft Excel compatibility
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                $index = 1;
                foreach ($payments as $payment) {
                    $dealerName = $payment->invoice->buyer->dealer->dealer_name 
                        ?? $payment->invoice->buyer->company_name 
                        ?? 'N/A';
                    $dealerCode = $payment->invoice->buyer->dealer->dealer_code 
                        ?? $payment->invoice->buyer->buyer_code 
                        ?? 'N/A';
                    $voucherName = $payment->voucherTypeModel->name ?? $payment->payment_mode ?? 'N/A';

                    fputcsv($file, [
                        $index++,
                        $dealerName,
                        $dealerCode,
                        $payment->invoice->invoice_no ?? 'N/A',
                        $payment->transaction_id ?? 'N/A',
                        $payment->transaction_date ? $payment->transaction_date->format('d-M-Y') : 'N/A',
                        $voucherName,
                        number_format($payment->amount ?? 0, 2, '.', ''),
                        $payment->remarks ?? ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Dealer Payment Report Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export payment report.');
        }
    }

    /**
     * Build the reusable filtered query.
     */
    private function buildReportQuery(Request $request)
    {
        $activeMapId = session('active_map_id');

        // Parse dealer filter upfront if present
        $dealerCompanyId = null;
        if ($request->filled('dealer_id')) {
            try {
                $dealerCompanyId = Crypt::decryptString($request->dealer_id);
            } catch (DecryptException $e) {
                $dealerCompanyId = $request->dealer_id;
            }
        }

        // Direct JOIN optimization for ultra-fast query execution on large datasets
        // Include Dealer -> Company payments/credits (Receipts, Credit Notes, Journals, and Manual Payments)
        // Exclude Company -> Dealer debits (Sales Invoices and Debit Notes)
        $query = PaymentTrack::select('payment_tracks.*')
            ->join('invoices', 'payment_tracks.invoice_id', '=', 'invoices.id')
            ->where('invoices.created_by', $activeMapId)
            ->whereNull('invoices.deleted_at')
            ->where(function ($q) {
                $q->whereHas('voucherTypeModel', function ($vt) {
                    $vt->whereNotIn(DB::raw('UPPER(name)'), ['SALES', 'DEBIT NOTE', 'PURCHASE']);
                })->orWhereNull('payment_tracks.voucher_type_id');
            })
            ->with(['invoice.buyer.dealer', 'voucherTypeModel']);

        if ($dealerCompanyId) {
            $query->where('invoices.buyer_id', $dealerCompanyId);
        }

        // Filter by Date Range on payment_tracks.transaction_date
        if ($request->filled('start_date')) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $query->where('payment_tracks.transaction_date', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            $query->where('payment_tracks.transaction_date', '<=', $endDate);
        }

        // Filter by Search Keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_tracks.transaction_id', 'like', "%{$search}%")
                    ->orWhere('payment_tracks.payment_mode', 'like', "%{$search}%")
                    ->orWhere('payment_tracks.remarks', 'like', "%{$search}%")
                    ->orWhere('invoices.invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('invoice.buyer.dealer', function ($d) use ($search) {
                        $d->where('dealer_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
