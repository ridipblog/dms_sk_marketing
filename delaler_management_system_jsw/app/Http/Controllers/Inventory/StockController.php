<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use App\Modules\ReuseModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class StockController extends Controller
{
    /**
     * Display current stock levels of products.
     */
    public function index(Request $request)
    {
        try {
            $companyId = session('active_company_id');
            $categories = Category::where('company_id', $companyId)->get();

            $query = ReuseModule::getOwnedProductQuery()->with('category');

            // Handle filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('sku_code', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('product_name', 'asc')->paginate(15)->appends($request->all());

            return view('inventory.stock.index', compact('products', 'categories'));
        } catch (\Throwable $e) {
            Log::error('Stock Index Error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Error loading stock ledger.');
        }
    }

    /**
     * Adjust product stock quantity manually.
     */
    public function adjust(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required',
                'adjustment_type' => 'required|in:add,subtract',
                'quantity' => 'required|numeric|min:0.001',
                'remarks' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $productId = Crypt::decryptString($request->product_id);
            $product = ReuseModule::getOwnedProductQuery($productId)->firstOrFail();

            DB::transaction(function () use ($product, $request) {
                if ($request->adjustment_type === 'add') {
                    $product->stock_quantity += $request->quantity;
                } else {
                    if ($product->stock_quantity < $request->quantity) {
                        throw new \Exception('Adjustment quantity exceeds available stock.');
                    }
                    $product->stock_quantity -= $request->quantity;
                }
                $product->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.'
            ]);
        } catch (DecryptException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid Product ID.']);
        } catch (\Throwable $e) {
            Log::error('Stock Adjustment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred during stock adjustment.'
            ]);
        }
    }
}
