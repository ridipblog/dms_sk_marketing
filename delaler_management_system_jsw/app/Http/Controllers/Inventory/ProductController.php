<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use App\Modules\ReuseModule;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $categories = ReuseModule::getOwnedCategoryQuery()
                ->where('status', 'active')
                ->get();
            return view('inventory.products.index', compact('categories'));
        } catch (\Throwable $e) {
            Log::error('Product Index Error: ' . $e->getMessage());
            return view('inventory.products.index', [
                'categories' => collect(),
                'errorMessage' => 'An error occurred while loading the product data. Some features may not work correctly.'
            ]);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = ReuseModule::getOwnedProductQuery()->with('category');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku_code', 'like', "%{$search}%")
                        ->orWhere('hsn_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('category') && !empty($request->category)) {
                $query->where('category_id', $request->category);
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('min_price') && $request->min_price !== null && $request->min_price !== '') {
                $query->where('base_price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && $request->max_price !== null && $request->max_price !== '') {
                $query->where('base_price', '<=', $request->max_price);
            }

            $page = $request->input('page', 1);
            $products = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('inventory.products.partials.table', compact('products'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Product List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading products.',
                'html' => '<div class="alert alert-danger">Failed to load products. Please try again.</div>'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!ReuseModule::getOwnedCategoryQuery($value)->exists()) {
                            $fail('The selected category is invalid or does not belong to your company.');
                        }
                    },
                ],
                'product_name' => 'required|string|max:255',
                'sku_code' => 'required|string|max:100|unique:products,sku_code',
                'hsn_code' => 'nullable|string|max:50',
                'size' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'base_price' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            Product::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Product Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the product.'
            ], 500);
        }
    }

    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $product = ReuseModule::getOwnedProductQuery($id)->firstOrFail();

            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Throwable $e) {
            Log::error('Product Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the product.'
            ], 500);
        }
    }

    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $product = ReuseModule::getOwnedProductQuery($id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'category_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!ReuseModule::getOwnedCategoryQuery($value)->exists()) {
                            $fail('The selected category is invalid or does not belong to your company.');
                        }
                    },
                ],
                'product_name' => 'required|string|max:255',
                'sku_code' => 'required|string|max:100|unique:products,sku_code,' . $product->id,
                'hsn_code' => 'nullable|string|max:50',
                'size' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'base_price' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 200);
            }

            $product->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Product Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product.'
            ], 500);
        }
    }

    /**
     * Export products list to Excel/CSV.
     */
    public function export(Request $request)
    {
        try {
            $query = ReuseModule::getOwnedProductQuery()->with('category');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku_code', 'like', "%{$search}%")
                        ->orWhere('hsn_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('category') && !empty($request->category)) {
                $query->where('category_id', $request->category);
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('min_price') && $request->min_price !== null && $request->min_price !== '') {
                $query->where('base_price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && $request->max_price !== null && $request->max_price !== '') {
                $query->where('base_price', '<=', $request->max_price);
            }

            $products = $query->latest()->cursor();

            $fileName = 'products_list_' . date('Ymd_His') . '.csv';

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
                'HSN Code',
                'Category',
                'Size',
                'Unit',
                'Base Price (₹)',
                'Status'
            ];

            $callback = function () use ($products, $columns) {
                $file = fopen('php://output', 'w');
                // Output UTF-8 BOM so Microsoft Excel renders Rupee symbol (₹) correctly
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, $columns);

                $index = 1;
                foreach ($products as $product) {
                    fputcsv($file, [
                        $index++,
                        $product->product_name ?? 'N/A',
                        $product->sku_code ?? 'N/A',
                        $product->hsn_code ?? 'N/A',
                        $product->category->category_name ?? 'N/A',
                        $product->size ?? 'N/A',
                        $product->unit ?? 'N/A',
                        number_format($product->base_price ?? 0, 2, '.', ''),
                        ucfirst($product->status ?? 'active')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Product Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to export products list.');
        }
    }
}
