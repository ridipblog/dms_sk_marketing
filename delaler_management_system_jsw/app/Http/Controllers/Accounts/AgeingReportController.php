<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accounts\Invoice;
use App\Models\Dealers\Dealer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgeingReportController extends Controller
{
    /**
     * Display the ageing report dashboard.
     */
    public function index()
    {
        try {
            $companyId = session('active_company_id');

            // Fetch active dealers for filter dropdown
            $dealers = Dealer::whereHas('dealerCompany', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->where('status', 'active')->orderBy('dealer_name')->get();

            return view('accounts.ageing_report.index', compact('dealers'));
        } catch (\Throwable $e) {
            Log::error('Ageing Report Index Error: ' . $e->getMessage());
            return view('accounts.ageing_report.index', [
                'dealers' => collect(),
                'errorMessage' => 'Could not load summary metrics.'
            ]);
        }
    }

    /**
     * Fetch ageing report items via AJAX POST.
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            $query = $this->getBaseAgeingQuery($companyId);
            $this->applyAgeingFilters($query, $request);

            $query->select([
                'invoices.id',
                'invoices.invoice_no',
                'invoices.invoice_generate_date',
                'invoices.due_date',
                'invoices.chargeable_amount',
                'invoices.manual_amount_update',
                'dealers.dealer_name',
                'dealers.dealer_code',
                'dealers.phone',
                'invoice_payments.outstanding_amount',
                DB::raw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) as outstanding_days'),
                DB::raw('GREATEST(0, DATEDIFF(CURDATE(), invoices.due_date)) as overdue_days')
            ]);

            $query->orderBy(DB::raw('DATEDIFF(CURDATE(), invoices.invoice_generate_date)'), 'desc');

            $page = $request->input('page', 1);
            $invoices = $query->paginate(10, ['*'], 'page', $page);

            // Calculate dynamic KPI cards based on filters (ignoring the bucket filter)
            $totalsQuery = $this->getBaseAgeingQuery($companyId);
            $this->applyAgeingFilters($totalsQuery, $request, false);

            $totals = $totalsQuery->selectRaw("
                COALESCE(SUM(invoice_payments.outstanding_amount), 0) as total_outstanding,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.invoice_generate_date) <= 30 THEN invoice_payments.outstanding_amount ELSE 0 END), 0) as range_30,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.invoice_generate_date) BETWEEN 31 AND 60 THEN invoice_payments.outstanding_amount ELSE 0 END), 0) as range_60,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.invoice_generate_date) BETWEEN 61 AND 90 THEN invoice_payments.outstanding_amount ELSE 0 END), 0) as range_90,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoices.invoice_generate_date) > 90 THEN invoice_payments.outstanding_amount ELSE 0 END), 0) as range_90_plus
            ")->first();

            $html = view('accounts.ageing_report.partials.table', compact('invoices'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'totals' => [
                    'total_outstanding' => number_format($totals->total_outstanding, 2),
                    'range_30' => number_format($totals->range_30, 2),
                    'range_60' => number_format($totals->range_60, 2),
                    'range_90' => number_format($totals->range_90, 2),
                    'range_90_plus' => number_format($totals->range_90_plus, 2),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Ageing Report List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the ageing details.',
                'html' => '<div class="alert alert-danger">Failed to load the ageing list. Please try again.</div>'
            ], 500);
        }
    }

    /**
     * Export the ageing report as a CSV file.
     */
    public function export(Request $request)
    {
        try {
            $companyId = session('active_company_id');

            $query = $this->getBaseAgeingQuery($companyId);
            $this->applyAgeingFilters($query, $request);

            $query->select([
                'invoices.invoice_no',
                'dealers.dealer_code',
                'dealers.dealer_name',
                'dealers.phone',
                'invoices.invoice_generate_date',
                'invoices.due_date',
                'invoices.chargeable_amount',
                'invoice_payments.outstanding_amount',
                DB::raw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) as outstanding_days'),
                DB::raw('GREATEST(0, DATEDIFF(CURDATE(), invoices.due_date)) as overdue_days')
            ]);

            $query->orderBy(DB::raw('DATEDIFF(CURDATE(), invoices.invoice_generate_date)'), 'desc');

            $invoices = $query->get();

            $fileName = 'invoice_ageing_report_' . date('Ymd_His') . '.csv';

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=" . $fileName,
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                'Invoice No',
                'Dealer Code',
                'Dealer Name',
                'Phone Number',
                'Invoice Date',
                'Due Date',
                'Original Amount',
                'Outstanding Amount',
                'Ageing Days',
                'Overdue Days',
                'Ageing Bucket'
            ];

            $callback = function() use($invoices, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($invoices as $invoice) {
                    $days = (int)$invoice->outstanding_days;
                    if ($days <= 30) {
                        $bucket = '0-30 Days';
                    } elseif ($days <= 60) {
                        $bucket = '31-60 Days';
                    } elseif ($days <= 90) {
                        $bucket = '61-90 Days';
                    } else {
                        $bucket = '90+ Days';
                    }

                    fputcsv($file, [
                        $invoice->invoice_no,
                        $invoice->dealer_code,
                        $invoice->dealer_name,
                        $invoice->phone ?? 'N/A',
                        $invoice->invoice_generate_date ? \Carbon\Carbon::parse($invoice->invoice_generate_date)->format('d M Y') : 'N/A',
                        $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A',
                        number_format($invoice->chargeable_amount ?? 0, 2, '.', ''),
                        number_format($invoice->outstanding_amount ?? 0, 2, '.', ''),
                        $invoice->outstanding_days . ' Days',
                        $invoice->overdue_days > 0 ? $invoice->overdue_days . ' Days' : 'Not Due',
                        $bucket
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Ageing Report Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while exporting the report.');
        }
    }

    /**
     * Get the base query for outstanding invoices with dealer joins.
     */
    private function getBaseAgeingQuery(int $companyId)
    {
        return Invoice::join('invoice_payments', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->join('dealer_companies', 'invoices.buyer_id', '=', 'dealer_companies.id')
            ->join('dealers', 'dealer_companies.dealer_id', '=', 'dealers.id')
            ->where('dealer_companies.company_id', $companyId)
            ->whereNull('invoice_payments.deleted_at')
            ->where('invoice_payments.outstanding_amount', '>', 0);
    }

    /**
     * Apply common filters and search parameters to the ageing query.
     */
    private function applyAgeingFilters($query, Request $request, bool $includeBucket = true)
    {
        // Filter by specific dealer
        if ($request->filled('dealer_id') && $request->dealer_id !== 'all') {
            $query->where('dealers.id', $request->dealer_id);
        }

        // Filter by ageing range bucket
        if ($includeBucket && $request->filled('bucket')) {
            $bucket = $request->bucket;
            if ($bucket === '0-30') {
                $query->whereRaw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) <= 30');
            } elseif ($bucket === '31-60') {
                $query->whereRaw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) BETWEEN 31 AND 60');
            } elseif ($bucket === '61-90') {
                $query->whereRaw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) BETWEEN 61 AND 90');
            } elseif ($bucket === '90+') {
                $query->whereRaw('DATEDIFF(CURDATE(), invoices.invoice_generate_date) > 90');
            }
        }

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoices.invoice_no', 'like', "%{$search}%")
                    ->orWhere('dealers.dealer_name', 'like', "%{$search}%")
                    ->orWhere('dealers.dealer_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
