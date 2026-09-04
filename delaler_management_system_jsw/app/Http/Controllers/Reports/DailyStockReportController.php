<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\DailyStockReport;
use App\Modules\ReuseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyStockReportController extends Controller
{
    /**
     * Display the daily stock report dashboard view with products list.
     */
    public function index()
    {
        try {
            $products = ReuseModule::getOwnedProductQuery()->get();
            return view('reports.daily_stock.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Daily Stock Report Index Error: ' . $e->getMessage());
            return view('reports.daily_stock.index', ['products' => collect()]);
        }
    }

    /**
     * Fetch daily stock report list via AJAX POST (supports 'product_wise' and 'date_wise' tabs).
     */
    public function list(Request $request)
    {
        try {
            $reportType = $request->input('report_type', 'product_wise');
            $page = $request->input('page', 1);
            $companyId = session('active_company_id');

            $query = $this->buildReportQuery($request);

            // Clone query to calculate KPI metrics across all matching records
            $totalSaleMt = (clone $query)->sum('sale_quantity');
            $totalPurchaseMt = (clone $query)->sum('purchase_quantity');

            if ($request->filled('product_id')) {
                // Single Product filtered: Get exact product opening & closing stock
                $earliestRecord = (clone $query)->orderBy('date', 'asc')->first();
                $openingStock = $earliestRecord ? (float)$earliestRecord->opening_stock : 0.000;

                $latestRecord = (clone $query)->orderBy('date', 'desc')->first();
                $latestClosingStock = $latestRecord ? (float)$latestRecord->closing_stock : 0.000;
            } else {
                // All Products: Sum opening stock of all products on earliest period date
                $earliestDate = (clone $query)->min('date');
                $openingStock = $earliestDate 
                    ? (float) DailyStockReport::where('company_id', $companyId)->where('date', $earliestDate)->sum('opening_stock')
                    : 0.000;

                // All Products: Sum closing stock of all products on latest period date
                $latestDate = (clone $query)->max('date');
                $latestClosingStock = $latestDate
                    ? (float) DailyStockReport::where('company_id', $companyId)->where('date', $latestDate)->sum('closing_stock')
                    : 0.000;
            }

            if ($reportType === 'product_wise') {
                // Product-Wise Summary Tab (Optimized with Batch Aggregation)
                $productQuery = ReuseModule::getOwnedProductQuery();
                if ($request->filled('product_id')) {
                    $productQuery->where('id', $request->product_id);
                }

                $products = $productQuery->paginate(15, ['*'], 'page', $page);
                $productIds = $products->pluck('id')->toArray();

                if (!empty($productIds)) {
                    $aggQuery = DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds);

                    if ($request->filled('start_date')) {
                        $aggQuery->where('date', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $aggQuery->where('date', '<=', $request->end_date);
                    }

                    $aggregates = (clone $aggQuery)->select(
                        'product_id',
                        DB::raw('SUM(sale_quantity) as total_sale'),
                        DB::raw('SUM(purchase_quantity) as total_purchase'),
                        DB::raw('MIN(date) as min_date'),
                        DB::raw('MAX(date) as max_date')
                    )->groupBy('product_id')->get()->keyBy('product_id');

                    $minDateList = $aggregates->pluck('min_date')->filter()->unique()->toArray();
                    $maxDateList = $aggregates->pluck('max_date')->filter()->unique()->toArray();

                    $openingStocks = !empty($minDateList) ? DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds)
                        ->whereIn('date', $minDateList)
                        ->get()
                        ->keyBy(fn($r) => $r->product_id . '_' . \Carbon\Carbon::parse($r->date)->format('Y-m-d')) : collect();

                    $closingStocks = !empty($maxDateList) ? DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds)
                        ->whereIn('date', $maxDateList)
                        ->get()
                        ->keyBy(fn($r) => $r->product_id . '_' . \Carbon\Carbon::parse($r->date)->format('Y-m-d')) : collect();

                    foreach ($products as $prod) {
                        $agg = $aggregates->get($prod->id);
                        $minDate = $agg->min_date ?? null;
                        $maxDate = $agg->max_date ?? null;

                        $openKey = $minDate ? $prod->id . '_' . \Carbon\Carbon::parse($minDate)->format('Y-m-d') : null;
                        $closeKey = $maxDate ? $prod->id . '_' . \Carbon\Carbon::parse($maxDate)->format('Y-m-d') : null;

                        $openRec = $openKey ? $openingStocks->get($openKey) : null;
                        $closeRec = $closeKey ? $closingStocks->get($closeKey) : null;

                        $prod->period_opening_stock = $openRec ? (float)$openRec->opening_stock : 0.000;
                        $prod->period_sale = $agg ? (float)$agg->total_sale : 0.000;
                        $prod->period_purchase = $agg ? (float)$agg->total_purchase : 0.000;
                        $prod->period_closing_stock = $closeRec ? (float)$closeRec->closing_stock : (float)$prod->stock_quantity;
                    }
                }

                $html = view('reports.daily_stock.partials.product_wise_table', compact('products'))->render();
            } else {
                // Product Date-Wise Log Tab
                $stockRecords = $query->orderBy('date', 'desc')
                    ->orderBy('product_id', 'asc')
                    ->paginate(15, ['*'], 'page', $page);

                $html = view('reports.daily_stock.partials.table', compact('stockRecords'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'opening_stock' => number_format($openingStock, 3) . ' MT',
                'total_sale' => number_format($totalSaleMt, 3) . ' MT',
                'total_purchase' => number_format($totalPurchaseMt, 3) . ' MT',
                'closing_stock' => number_format($latestClosingStock, 3) . ' MT'
            ]);
        } catch (\Throwable $e) {
            Log::error('Daily Stock Report List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading daily stock report data.',
                'html' => '<div class="alert alert-danger text-center">Failed to load stock data. Please try again.</div>'
            ], 500);
        }
    }

    /**
     * Export daily stock report to CSV/Excel.
     */
    public function export(Request $request)
    {
        try {
            $reportType = $request->input('report_type', 'product_wise');
            $companyId = session('active_company_id');

            if ($reportType === 'product_wise') {
                $productQuery = ReuseModule::getOwnedProductQuery();
                if ($request->filled('product_id')) {
                    $productQuery->where('id', $request->product_id);
                }

                $products = $productQuery->get();
                $productIds = $products->pluck('id')->toArray();

                $fileName = 'product_wise_stock_summary_' . date('Ymd_His') . '.csv';

                $headers = [
                    "Content-type"        => "text/csv",
                    "Content-Disposition" => "attachment; filename=" . $fileName,
                    "Pragma"              => "no-cache",
                    "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                    "Expires"             => "0"
                ];

                $columns = [
                    '#',
                    'Product Name',
                    'SKU Code',
                    'Opening Stock (MT)',
                    'Total Sale (MT)',
                    'Total Purchase (MT)',
                    'Closing Stock (MT)'
                ];

                $aggregates = collect();
                $openingStocks = collect();
                $closingStocks = collect();

                if (!empty($productIds)) {
                    $aggQuery = DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds);

                    if ($request->filled('start_date')) {
                        $aggQuery->where('date', '>=', $request->start_date);
                    }
                    if ($request->filled('end_date')) {
                        $aggQuery->where('date', '<=', $request->end_date);
                    }

                    $aggregates = (clone $aggQuery)->select(
                        'product_id',
                        DB::raw('SUM(sale_quantity) as total_sale'),
                        DB::raw('SUM(purchase_quantity) as total_purchase'),
                        DB::raw('MIN(date) as min_date'),
                        DB::raw('MAX(date) as max_date')
                    )->groupBy('product_id')->get()->keyBy('product_id');

                    $minDateList = $aggregates->pluck('min_date')->filter()->unique()->toArray();
                    $maxDateList = $aggregates->pluck('max_date')->filter()->unique()->toArray();

                    $openingStocks = !empty($minDateList) ? DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds)
                        ->whereIn('date', $minDateList)
                        ->get()
                        ->keyBy(fn($r) => $r->product_id . '_' . \Carbon\Carbon::parse($r->date)->format('Y-m-d')) : collect();

                    $closingStocks = !empty($maxDateList) ? DailyStockReport::where('company_id', $companyId)
                        ->whereIn('product_id', $productIds)
                        ->whereIn('date', $maxDateList)
                        ->get()
                        ->keyBy(fn($r) => $r->product_id . '_' . \Carbon\Carbon::parse($r->date)->format('Y-m-d')) : collect();
                }

                $callback = function () use ($products, $aggregates, $openingStocks, $closingStocks, $columns) {
                    $file = fopen('php://output', 'w');
                    fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($file, $columns);

                    $index = 1;
                    foreach ($products as $prod) {
                        $agg = $aggregates->get($prod->id);
                        $minDate = $agg->min_date ?? null;
                        $maxDate = $agg->max_date ?? null;

                        $openKey = $minDate ? $prod->id . '_' . \Carbon\Carbon::parse($minDate)->format('Y-m-d') : null;
                        $closeKey = $maxDate ? $prod->id . '_' . \Carbon\Carbon::parse($maxDate)->format('Y-m-d') : null;

                        $openRec = $openKey ? $openingStocks->get($openKey) : null;
                        $closeRec = $closeKey ? $closingStocks->get($closeKey) : null;

                        $openingStock = $openRec ? (float)$openRec->opening_stock : 0.000;
                        $saleQty = $agg ? (float)$agg->total_sale : 0.000;
                        $purchaseQty = $agg ? (float)$agg->total_purchase : 0.000;
                        $closingStock = $closeRec ? (float)$closeRec->closing_stock : (float)$prod->stock_quantity;

                        fputcsv($file, [
                            $index++,
                            $prod->product_name ?? 'N/A',
                            $prod->sku_code ?? 'N/A',
                            number_format($openingStock, 3, '.', ''),
                            number_format($saleQty, 3, '.', ''),
                            number_format($purchaseQty, 3, '.', ''),
                            number_format($closingStock, 3, '.', '')
                        ]);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } else {
                // Date-Wise Export
                $query = $this->buildReportQuery($request);
                $stockRecords = $query->orderBy('date', 'desc')
                    ->orderBy('product_id', 'asc')
                    ->cursor();

                $fileName = 'daily_product_stock_log_' . date('Ymd_His') . '.csv';

                $headers = [
                    "Content-type"        => "text/csv",
                    "Content-Disposition" => "attachment; filename=" . $fileName,
                    "Pragma"              => "no-cache",
                    "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                    "Expires"             => "0"
                ];

                $columns = [
                    '#',
                    'Date',
                    'Product Name',
                    'Opening Stock (MT)',
                    'Sale (MT)',
                    'Purchase (MT)',
                    'Closing Stock (MT)'
                ];

                $callback = function () use ($stockRecords, $columns) {
                    $file = fopen('php://output', 'w');
                    fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($file, $columns);

                    $index = 1;
                    foreach ($stockRecords as $rec) {
                        $productName = $rec->product->product_name ?? 'N/A';
                        fputcsv($file, [
                            $index++,
                            $rec->date ? $rec->date->format('Y-m-d') : 'N/A',
                            $productName,
                            number_format($rec->opening_stock ?? 0, 3, '.', ''),
                            number_format($rec->sale_quantity ?? 0, 3, '.', ''),
                            number_format($rec->purchase_quantity ?? 0, 3, '.', ''),
                            number_format($rec->closing_stock ?? 0, 3, '.', '')
                        ]);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }
        } catch (\Throwable $e) {
            Log::error('Daily Stock Report Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export daily stock report.');
        }
    }

    /**
     * Reusable query builder for DailyStockReport.
     */
    private function buildReportQuery(Request $request)
    {
        $companyId = session('active_company_id');

        $query = DailyStockReport::with('product')->where('company_id', $companyId);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        return $query;
    }
}
