<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UploadTrack;
use App\Jobs\ProcessPaymentReceiptUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptUploadController extends Controller
{
    /**
     * Display Payment Receipt Upload page index.
     */
    public function index()
    {
        return view('accounts.invoices.payment_receipt_upload');
    }

    /**
     * Fetch upload tracks for payment receipts via AJAX.
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

            $query = UploadTrack::where('upload_type', 'accounts_vouchers')
                ->where('company_id', $companyId)
                ->with('user')
                ->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where('file_name', 'like', "%{$search}%");
            }

            $tracks = $query->paginate(10);

            $html = view('accounts.invoices.partials.payment_receipt_upload_table', compact('tracks'))->render();

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
     * Handle payment receipt Excel import request.
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
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
            $fileName = 'payment_receipts_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('accounts_uploads/payment_receipts', $fileName, 'local');

            $track = UploadTrack::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'role_user_company_id' => $roleUserCompanyId,
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => 'accounts_vouchers',
                'total_rows' => 0,
                'imported_rows' => 0,
                'failed_rows' => 0,
            ]);

            // Dispatch background queue job
            ProcessPaymentReceiptUpload::dispatch($track->id, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'Payment receipts upload started in background. Monitor status in the history table below.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download Payment Receipt Excel template CSV.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payment_receipt_upload_template.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'User Invoice No',
                'Payment Date',
                'Amount',
                'Payment Mode',
                'Transaction Ref No',
                'Remarks'
            ]);

            // Sample rows
            fputcsv($handle, [
                'INV-2026-0001',
                date('d-m-Y'),
                '15000.00',
                'Bank Transfer',
                'UTR9876543210',
                'Payment received via NEFT'
            ]);

            fputcsv($handle, [
                'INV-2026-0002',
                date('d-m-Y'),
                '25000.00',
                'Cheque',
                'CHQ-458921',
                'Cheque payment against invoice'
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
