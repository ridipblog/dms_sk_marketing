<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\UploadTrack;
use App\Jobs\ProcessUserUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserUploadController extends Controller
{
    public function index()
    {
        return view('users.uploads.index');
    }

    public function list(Request $request)
    {
        try {
            $query = UploadTrack::where('company_id', session('active_company_id'))
                ->where('upload_type', 'users')
                ->with('user');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('file_name', 'like', "%{$search}%");
            }

            $page = $request->input('page', 1);
            $tracks = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('users.uploads.partials.table', compact('tracks'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('User Upload Track List Fetch Error: ' . $e->getMessage());
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

            // Ensure upload directory exists with proper permissions
            $dirPath = Storage::disk('local')->path('user_uploads');
            if (!file_exists($dirPath)) {
                @mkdir($dirPath, 0775, true);
                @chmod($dirPath, 0775);
            }

            // Store the file securely
            $path = $file->storeAs('user_uploads', time() . '_' . $originalName, 'local');
            @chmod(Storage::disk('local')->path($path), 0664);

            $track = UploadTrack::create([
                'user_id' => Auth::id(),
                'company_id' => session('active_company_id'),
                'role_user_company_id' => session('active_map_id'),
                'file_name' => $originalName,
                'status' => 'pending',
                'upload_type' => 'users'
            ]);

            // Dispatch job
            ProcessUserUpload::dispatch($track->id, $path);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing will continue in the background.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User File Upload Error: ' . $e->getMessage());
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
            "Content-Disposition" => "attachment; filename=user_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Name',
            'Phone',
            'Email',
            'Designation',
            'Status',
            'Company Code',
            'Role Name',
            'Reporting Phone/Email'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add a demo row
            fputcsv($file, [
                'John Doe',
                '9876543210',
                'john.doe@example.com',
                'Area Manager',
                'Active',
                'COMP01',
                'Area Sales Manager (ASM)',
                'branch.manager@example.com'
            ]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

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
            Log::error('Download User Error Report Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to download error report.');
        }
    }
}
