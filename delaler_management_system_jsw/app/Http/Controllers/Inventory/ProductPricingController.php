<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductPricing;
use App\Models\Inventory\Product;
use App\Modules\ReuseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class ProductPricingController extends Controller
{
    public function index()
    {
        try {
            $products = ReuseModule::getOwnedProductQuery()
                ->where('status', 'active')->get();

            return view('inventory.product_pricings.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Product Pricing Index Error: ' . $e->getMessage());
            return view('inventory.product_pricings.index', [
                'products' => collect(),
                'errorMessage' => 'An error occurred while loading the pricing data. Some features may not work correctly.'
            ]);
        }
    }

    public function list(Request $request)
    {
        try {
            $query = ReuseModule::getOwnedProductPricingQuery()->with('product');

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('product_id') && !empty($request->product_id)) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('min_price') && $request->min_price !== null && $request->min_price !== '') {
                $query->where('price_per_mt', '>=', $request->min_price);
            }

            if ($request->has('max_price') && $request->max_price !== null && $request->max_price !== '') {
                $query->where('price_per_mt', '<=', $request->max_price);
            }

            $page = $request->input('page', 1);
            $pricings = $query->latest()->paginate(10, ['*'], 'page', $page);

            $html = view('inventory.product_pricings.partials.table', compact('pricings'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Throwable $e) {
            Log::error('Pricing List Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading pricings.',
                'html' => '<div class="alert alert-danger">Failed to load pricings. Please try again.</div>'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!ReuseModule::getOwnedProductQuery($value)->exists()) {
                            $fail('The selected product is invalid or does not belong to your company.');
                        }
                    },
                ],
                'price_per_mt' => 'required|numeric|min:0',
                'gst_percentage' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'price_type' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 200);
            }

            $data = $validator->validated();

            $activePricingExists = ProductPricing::where('product_id', $data['product_id'])
                ->where('status', 'active')
                ->exists();

            if ($activePricingExists) {
                $data['status'] = 'inactive';
            } else {
                $data['status'] = 'active';
            }

            ProductPricing::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Product pricing created successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Pricing Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating product pricing.'
            ], 500);
        }
    }

    public function edit($encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $pricing = ReuseModule::getOwnedProductPricingQuery($id)->firstOrFail();

            $data = $pricing->toArray();

            return response()->json([
                'success' => true,
                'pricing' => $data
            ]);
        } catch (\Throwable $e) {
            Log::error('Pricing Edit Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching pricing.'
            ], 500);
        }
    }

    public function update(Request $request, $encrypted_id)
    {
        try {
            $id = Crypt::decryptString($encrypted_id);
            $pricing = ReuseModule::getOwnedProductPricingQuery($id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'product_id' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!ReuseModule::getOwnedProductQuery($value)->exists()) {
                            $fail('The selected product is invalid or does not belong to your company.');
                        }
                    },
                ],
                'price_per_mt' => 'required|numeric|min:0',
                'gst_percentage' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'price_type' => 'nullable|string|max:100',
                'status' => 'required|in:active,inactive,blocked'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 200);
            }

            if ($request->status === 'active') {
                $activePricingExists = ProductPricing::where('product_id', $request->product_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $id)
                    ->exists();

                if ($activePricingExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This product already has an active price. Only one active price is allowed at a time.'
                    ], 200);
                }
            }

            $pricing->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product pricing updated successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Pricing Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating product pricing.'
            ], 500);
        }
    }
}
