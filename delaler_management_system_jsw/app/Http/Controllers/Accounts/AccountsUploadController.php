<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UploadTrack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProcessAccountsInvoiceUpload;
use App\Jobs\ProcessAccountsVoucherUpload;
use Illuminate\Support\Facades\Storage;

class AccountsUploadController extends Controller
{
    /**
     * Display the upload excel form for invoices and vouchers.
     */
    public function index()
    {
        return view('accounts.upload.index');
    }

    /**
     * List recent Invoice uploads via AJAX
     */
    public function invoicesList(Request $request)
    {
        return $this->getUploadTrackList($request, 'accounts_invoices');
    }

    /**
     * List recent Voucher uploads via AJAX
     */
    public function vouchersList(Request $request)
    {
        return $this->getUploadTrackList($request, 'accounts_vouchers');
    }

    /**
     * Common method to fetch Upload Tracks
     */
    private function getUploadTrackList(Request $request, $uploadType)
    {
        try {
            $query = UploadTrack::where('company_id', session('active_company_id'))
                ->where('upload_type', $uploadType)
                ->with('user');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('file_name', 'like', "%{$search}%");
            }

            $page = $request->input('page', 1);
            $tracks = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('accounts.upload.partials.table', compact('tracks'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Accounts Upload Track List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the upload tracking list.',
                'html' => '<div class="alert alert-danger mx-3 my-3">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }

    /**
     * Handle Invoices Upload
     */
    public function importInvoices(Request $request)
    {
        return $this->processUpload($request, 'accounts_invoices', ProcessAccountsInvoiceUpload::class);
    }

    /**
     * Handle Vouchers Upload
     */
    public function importVouchers(Request $request)
    {
        return $this->processUpload($request, 'accounts_vouchers', ProcessAccountsVoucherUpload::class);
    }

    /**
     * Common file upload handler
     */
    private function processUpload(Request $request, $uploadType, $jobClass)
    {
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $file = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();

            // Store the file securely
            $path = $file->storeAs('accounts_uploads', time() . '_' . $originalName, 'local');

            $track = UploadTrack::create([
                'user_id' => Auth::id(),
                'company_id' => session('active_company_id'),
                'role_user_company_id' => session('active_map_id'),
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => $uploadType
            ]);

            // Dispatch job
            $jobClass::dispatch($track->id, $path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing will continue in the background.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Accounts File Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file.'
            ], 500);
        }
    }

    /**
     * Download Template for Invoices
     */
    public function downloadInvoiceTemplate()
    {
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=invoice_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Invoice No',
            'Buyer Code',
            'Invoice Date',
            'Product Code',
            'Quantity',
            'manual_upload',
            'credit',
            'debit'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Demo Row 1 (Auto-generate new invoice)
            fputcsv($file, ['', 'DLR-DEMO-001', date('Y-m-d'), 'PRD-001', '10', '0', '', '']);
            // Demo Row 2 (Grouped with Row 1 if same date and buyer)
            fputcsv($file, ['', 'DLR-DEMO-001', date('Y-m-d'), 'PRD-002', '5', '0', '', '']);
            // Demo Row 3 (Adding to existing invoice)
            fputcsv($file, ['INV-2023-0001', 'DLR-DEMO-001', date('Y-m-d'), 'PRD-003', '20', '0', '', '']);
            // Demo Row 4 (Manual upload example)
            fputcsv($file, ['', 'DLR-DEMO-001', '2026-04-01', '', '50000.00', '1', '', '']);
            // Demo Row 5 (Manual upload example with debit amount)
            fputcsv($file, ['', 'DLR-DEMO-001', '2026-04-01', '', '', '1', '', '50000.00']);
            // Demo Row 6 (Manual upload example with credit amount)
            fputcsv($file, ['', 'DLR-DEMO-001', '2026-04-01', '', '', '1', '30000.00', '']);

            fclose($file);
        };


        return Response::stream($callback, 200, $headers);
    }

    /**
     * Download Template for Vouchers
     */
    public function downloadVoucherTemplate()
    {
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=voucher_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Invoice No',
            'Voucher Type',
            'Amount',
            'Payment Mode',
            'Transaction Date',
            'Number of Days'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Demo Row
            fputcsv($file, ['INV-2023-0001', 'RECEIPT', '1500.50', 'UPI', date('Y-m-d'), '']);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Download the error report CSV for a specific upload track.
     */
    public function downloadErrorReport($track_id)
    {
        try {
            $track = UploadTrack::where('company_id', session('active_company_id'))
                ->where('id', $track_id)
                ->firstOrFail();

            if (!$track->error_file_path || !Storage::disk('local')->exists($track->error_file_path)) {
                return back()->with('error', 'Error report file not found.');
            }

            return Storage::disk('local')->download($track->error_file_path, 'error_report_' . $track->file_name);
        } catch (\Throwable $e) {
            Log::error('Download Error Report Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to download error report.');
        }
    }
}
