<?php

namespace App\Http\Controllers\Dealers;

use App\Http\Controllers\Controller;
use App\Models\UploadTrack;
use App\Jobs\ProcessDealerUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class DealerUploadController extends Controller
{
    public function index()
    {
        return view('dealers.uploads.index');
    }

    public function list(Request $request)
    {
        try {
            $query = UploadTrack::where('company_id', session('active_company_id'))
                ->where('upload_type', 'dealers')
                ->with('user');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('file_name', 'like', "%{$search}%");
            }

            $page = $request->input('page', 1);
            $tracks = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('dealers.uploads.partials.table', compact('tracks'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer Upload Track List Fetch Error: ' . $e->getMessage());
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
            $path = $file->storeAs('dealer_uploads', time() . '_' . $originalName, 'local');

            $track = UploadTrack::create([
                'user_id' => Auth::id(),
                'company_id' => session('active_company_id'),
                'role_user_company_id' => session('active_map_id'),
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => 'dealers'
            ]);

            // Dispatch job
            ProcessDealerUpload::dispatch($track->id, $path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing will continue in the background.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Dealer File Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file.'
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=dealer_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Dealer Code',
            'Dealer Name',
            'Email',
            'Phone',
            'PAN Number',
            'GST Number',
            'Address',
            'Status'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add a demo row
            fputcsv($file, [
                'DLR-DEMO-001',
                'Demo Dealer Pvt Ltd',
                'contact@demodealer.com',
                '9876543210',
                'ABCDE1234F',
                '27ABCDE1234F1Z5',
                '123 Dealer Street, Mumbai',
                'Active'
            ]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
