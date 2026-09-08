<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UploadTrack;
use App\Jobs\ProcessPurchaseInvoiceUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseInvoiceUploadController extends Controller
{
    /**
     * Display Purchase Invoice Upload page index.
     */
    public function index()
    {
        return view('purchase.invoices.upload');
    }

    /**
     * Display Purchase Invoice Payment Upload page index.
     */
    public function paymentUploadIndex()
    {
        return view('purchase.invoices.payment_upload');
    }

    /**
     * Fetch upload tracks for purchase invoices via AJAX.
     */
    public function list(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            if (!$companyId) {
                $user = Auth::user();
                $mapping = DB::table('role_user_company')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();
                $companyId = $mapping ? $mapping->company_id : 1;
            }

            $query = UploadTrack::where('upload_type', 'purchase_invoices')
                ->where('company_id', $companyId)
                ->with('user')
                ->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where('file_name', 'like', "%{$search}%");
            }

            $tracks = $query->paginate(10);

            $html = view('purchase.invoices.partials.upload_table', compact('tracks'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load upload tracks: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Purchase Invoice Excel import request.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            // Extension fallback check if MIME type detection varies on Windows/Excel CSV
            if ($request->hasFile('excel_file')) {
                $ext = strtolower($request->file('excel_file')->getClientOriginalExtension());
                if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The uploaded file must be a file of type: csv, xlsx, xls.'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
        }

        try {
            $user = Auth::user();
            $roleUserCompanyId = session('active_map_id');
            $companyId = session('active_company_id');

            if (!$companyId || !$roleUserCompanyId) {
                $mapping = DB::table('role_user_company')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if (!$mapping) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active role company mapping found.'
                    ]);
                }
                $companyId = $mapping->company_id;
                $roleUserCompanyId = $mapping->id;
            }

            $file = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();
            $fileName = 'purchase_invoices_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('purchase_uploads/invoices', $fileName, 'local');

            $track = UploadTrack::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'role_user_company_id' => $roleUserCompanyId,
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => 'purchase_invoices',
                'total_rows' => 0,
                'imported_rows' => 0,
                'failed_rows' => 0,
            ]);

            // Dispatch background queue job
            ProcessPurchaseInvoiceUpload::dispatch($track->id, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'Purchase invoices upload started in background. Monitor status in the history table below.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download Purchase Invoice Excel template CSV.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="purchase_invoice_upload_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'Supplier GST No',
                'User Invoice No',
                'Invoice Date',
                'Due Date',
                'Product Name',
                'Quantity',
                'Rate (Without GST)',
                'GST Percentage',
                'GST Type (intra/inter)'
            ]);

            $firstSupplier = \App\Models\Purchase\Supplier::first();
            $sampleGstin = $firstSupplier ? $firstSupplier->gstin : '22ADCD2874D1ZS';

            $firstProduct = \App\Models\Inventory\Product::first();
            $sampleProductName = $firstProduct ? $firstProduct->product_name : 'Jsw Neo Steel TMT BAR FE_550D 10 MM';

            $dueDate = date('Y-m-d', strtotime('+30 days'));

            // Sample row 1
            fputcsv($handle, [
                $sampleGstin,
                'PINV-2026-0001',
                date('Y-m-d'),
                $dueDate,
                $sampleProductName,
                '10',
                '45000.00',
                '18.00',
                'intra'
            ]);

            // Sample row 2 (Grouped with Row 1)
            fputcsv($handle, [
                $sampleGstin,
                'PINV-2026-0001',
                date('Y-m-d'),
                $dueDate,
                $sampleProductName,
                '5',
                '48000.00',
                '18.00',
                'intra'
            ]);

            // Sample row 3 (Another supplier invoice)
            fputcsv($handle, [
                $sampleGstin,
                'PINV-2026-0002',
                date('Y-m-d'),
                $dueDate,
                $sampleProductName,
                '20',
                '52000.00',
                '18.00',
                'inter'
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
