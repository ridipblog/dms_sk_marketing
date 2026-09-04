<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Modules\ReuseModule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('inventory.categories.index');
    }

    public function list(Request $request)
    {
        try {
            $query = Category::where('company_id', session('active_company_id'));

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('category_name', 'like', "%{$search}%")
                        ->orWhere('category_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $page = $request->input('page', 1);
            $categories = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('inventory.categories.partials.table', compact('categories'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Category List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading categories.',
                'html' => '<div class="alert alert-danger">Failed to load categories. Please try again.</div>'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:255',
                'category_code' => 'required|string|max:100|unique:categories,category_code',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $validatedData = $validator->validated();
            $validatedData['company_id'] = session('active_company_id');

            Category::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Category Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the category.'
            ], 500);
        }
    }

    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $category = ReuseModule::getOwnedCategoryQuery($id)->firstOrFail();

            return response()->json([
                'success' => true,
                'category' => $category
            ]);
        } catch (\Throwable $e) {
            Log::error('Category Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the category.'
            ], 500);
        }
    }

    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $category = ReuseModule::getOwnedCategoryQuery($id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:255',
                'category_code' => 'required|string|max:100|unique:categories,category_code,' . $category->id,
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 200);
            }

            $category->update([
                'category_name' => $request->category_name,
                'category_code' => $request->category_code,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Category Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the category.'
            ], 500);
        }
    }
}
