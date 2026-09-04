<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class SupplierController extends Controller
{
    public function index()
    {
        return view('purchase.suppliers.index');
    }

    public function list(Request $request)
    {
        try {
            $query = Supplier::query();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('gstin', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && $request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $page = $request->input('page', 1);
            $suppliers = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('purchase.suppliers.partials.table', compact('suppliers'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading suppliers.',
                'html' => '<div class="alert alert-danger">Failed to load suppliers. Please try again.</div>'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $input = $request->all();
            if (isset($input['gstin']) && trim($input['gstin']) === '') {
                $input['gstin'] = null;
            }
            if (isset($input['phone']) && trim($input['phone']) === '') {
                $input['phone'] = null;
            }

            $validator = Validator::make($input, [
                'name' => 'required|string|max:255',
                'gstin' => 'nullable|string|max:50|unique:suppliers,gstin',
                'phone' => 'nullable|string|max:20|unique:suppliers,phone',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $validatedData = $validator->validated();
            Supplier::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the supplier.'
            ], 500);
        }
    }

    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $supplier = Supplier::findOrFail($id);

            return response()->json([
                'success' => true,
                'supplier' => $supplier
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid ID'], 400);
        } catch (\Throwable $e) {
            Log::error('Supplier Edit Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        }
    }

    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $supplier = Supplier::findOrFail($id);

            $input = $request->all();
            if (isset($input['gstin']) && trim($input['gstin']) === '') {
                $input['gstin'] = null;
            }
            if (isset($input['phone']) && trim($input['phone']) === '') {
                $input['phone'] = null;
            }

            $validator = Validator::make($input, [
                'name' => 'required|string|max:255',
                'gstin' => 'nullable|string|max:50|unique:suppliers,gstin,' . $id,
                'phone' => 'nullable|string|max:20|unique:suppliers,phone,' . $id,
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $validatedData = $validator->validated();
            $supplier->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully.'
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid ID'], 400);
        } catch (\Throwable $e) {
            Log::error('Supplier Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the supplier.'
            ], 500);
        }
    }
}
