<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Get inventory statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $stats = [
                'total_products_with_inventory' => Product::where('has_inventory', true)->count(),
                'total_products_without_inventory' => Product::where('has_inventory', false)->count(),
                'products_in_stock' => Product::where('has_inventory', true)->where('stock_quantity', '>', 0)->count(),
                'products_out_of_stock' => Product::where('has_inventory', true)->where('stock_quantity', '<=', 0)->count(),
                'products_low_stock' => Product::lowStock()->count(),
                'total_stock_value' => Product::where('has_inventory', true)
                    ->selectRaw('SUM(stock_quantity * price) as total')
                    ->value('total') ?? 0,
                'total_stock_quantity' => Product::where('has_inventory', true)->sum('stock_quantity') ?? 0,
            ];

            // Low stock products details
            $lowStockProducts = Product::lowStock()
                ->with('category')
                ->select('id', 'title', 'slug', 'stock_quantity', 'low_stock_threshold', 'price', 'category_id')
                ->orderBy('stock_quantity', 'asc')
                ->limit(10)
                ->get();

            // Out of stock products
            $outOfStockProducts = Product::outOfStock()
                ->with('category')
                ->select('id', 'title', 'slug', 'price', 'category_id')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'low_stock_products' => $lowStockProducts,
                    'out_of_stock_products' => $outOfStockProducts,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get inventory statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory transactions for a product.
     */
    public function productTransactions(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $perPage = $request->get('per_page', 15);
            
            $query = InventoryTransaction::where('product_id', $productId)
                ->with(['order:id,order_number', 'user:id,name']);

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by reason
            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'title' => $product->title,
                        'has_inventory' => $product->has_inventory,
                        'stock_quantity' => $product->stock_quantity,
                        'low_stock_threshold' => $product->low_stock_threshold,
                        'is_in_stock' => $product->is_in_stock,
                        'is_low_stock' => $product->is_low_stock,
                    ],
                    'transactions' => $transactions
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all inventory transactions.
     */
    public function allTransactions(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            $query = InventoryTransaction::with(['product:id,title,slug', 'order:id,order_number', 'user:id,name']);

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by reason
            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adjust inventory for a product.
     */
    public function adjustInventory(Request $request, $productId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:set,increase,decrease',
                'quantity' => 'required|integer|min:0',
                'reason' => 'nullable|in:purchase,return,adjustment,damage',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::findOrFail($productId);

            if (!$product->has_inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product does not track inventory'
                ], 400);
            }

            DB::beginTransaction();

            $userId = auth()->id();
            $result = false;

            switch ($request->action) {
                case 'set':
                    $result = $product->setStock(
                        $request->quantity,
                        $userId,
                        $request->notes
                    );
                    break;

                case 'increase':
                    $result = $product->increaseStock(
                        $request->quantity,
                        $request->reason ?? 'adjustment',
                        $userId,
                        $request->notes
                    );
                    break;

                case 'decrease':
                    $result = $product->decreaseStock(
                        $request->quantity,
                        null,
                        $userId,
                        $request->notes
                    );
                    break;
            }

            if (!$result) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to adjust inventory. Insufficient stock or invalid operation.'
                ], 400);
            }

            DB::commit();

            // Reload product to get updated values
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Inventory adjusted successfully',
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'title' => $product->title,
                        'stock_quantity' => $product->stock_quantity,
                        'is_in_stock' => $product->is_in_stock,
                        'is_low_stock' => $product->is_low_stock,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products with inventory (filterable).
     */
    public function products(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            $query = Product::where('has_inventory', true)
                ->with('category');

            // Filter by stock status
            if ($request->has('stock_status')) {
                switch ($request->stock_status) {
                    case 'in_stock':
                        $query->where('stock_quantity', '>', 0);
                        break;
                    case 'out_of_stock':
                        $query->where('stock_quantity', '<=', 0);
                        break;
                    case 'low_stock':
                        $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                              ->where('stock_quantity', '>', 0);
                        break;
                }
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // Sort
            $sortBy = $request->get('sort_by', 'stock_quantity');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['stock_quantity', 'title', 'price', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import inventory.
     */
    public function bulkImport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.stock_quantity' => 'required|integer|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $userId = auth()->id();
            $successCount = 0;
            $errors = [];

            foreach ($request->products as $item) {
                try {
                    $product = Product::find($item['product_id']);
                    
                    if (!$product->has_inventory) {
                        $errors[] = "Product {$product->title} does not track inventory";
                        continue;
                    }

                    $product->setStock(
                        $item['stock_quantity'],
                        $userId,
                        $request->notes ?? 'Bulk import'
                    );
                    
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to update product ID {$item['product_id']}: {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$successCount} products",
                'data' => [
                    'success_count' => $successCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk import inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

