<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\UploadTrack;
use App\Jobs\ProcessInventoryUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class InventoryUploadController extends Controller
{
    public function index()
    {
        return view('inventory.uploads.index');
    }

    public function list(Request $request)
    {
        try {
            $query = UploadTrack::where('company_id', session('active_company_id'))
                ->where('upload_type', 'inventory')
                ->with('user');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('file_name', 'like', "%{$search}%");
            }

            $page = $request->input('page', 1);
            $tracks = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('inventory.uploads.partials.table', compact('tracks'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Upload Track List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the upload tracking list.',
                'html' => '<div class="alert alert-danger">Failed to load data. Please try again.</div>'
            ], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'excel_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240' // 10MB max, added txt for csv parsing fallback
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $file = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();

            // Store the file securely in storage/app/private/inventory_uploads
            $path = $file->storeAs('inventory_uploads', time() . '_' . $originalName, 'local');

            $track = UploadTrack::create([
                'user_id' => Auth::id(),
                'company_id' => session('active_company_id'),
                'role_user_company_id' => session('active_map_id'),
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => 'inventory'
            ]);

            // Dispatch job
            ProcessInventoryUpload::dispatch($track->id, $path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing will continue in the background.'
            ]);
        } catch (\Throwable $e) {
            Log::error('File Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file.'
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        // Simple CSV generation using PHP native fputcsv
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=inventory_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Category Name',
            'Category Code',
            'Category Desc',
            'Product Name',
            'SKU Code',
            'HSN Code',
            'Size',
            'Unit',
            'Base Price',
            'Pricing Per MT',
            'Pricing Type',
            'GST %',
            'Discount',
            'Effective From',
            'Effective To'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add a demo row
            fputcsv($file, [
                'Demo Category',
                'CAT-DEMO-01',
                'This is a demo category',
                'Demo Product',
                'SKU-DEMO-001',
                '8544',
                '10mm',
                'MT',
                '500',
                '1000',
                'Standard',
                '18',
                '50',
                now()->format('Y-m-d'),
                now()->addYear()->format('Y-m-d')
            ]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
