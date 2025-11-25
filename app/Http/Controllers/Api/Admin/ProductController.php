<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products for admin
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with('category');

            // Filter by category
            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by availability
            if ($request->has('is_available')) {
                $query->where('is_available', $request->boolean('is_available'));
            }

            // Search
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0',
                'weight_grams' => 'nullable|integer|min:0',
                'currency' => 'nullable|string|max:3',
                'is_available' => 'boolean',
                'has_inventory' => 'boolean',
                'stock_quantity' => 'nullable|integer|min:0|required_if:has_inventory,true',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'images' => 'required|array|min:1',
                'images.*' => 'string', // URLs or base64 strings
                'meta' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Generate unique slug
            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product = Product::create([
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'weight_grams' => $request->weight_grams,
                'currency' => $request->currency ?? 'KWD',
                'is_available' => $request->boolean('is_available', true),
                'has_inventory' => $request->boolean('has_inventory', false),
                'stock_quantity' => $request->has_inventory ? $request->stock_quantity : null,
                'low_stock_threshold' => $request->low_stock_threshold ?? 10,
                'stock_last_updated_at' => $request->has_inventory ? now() : null,
                'category_id' => $request->category_id,
                'images' => $request->images,
                'meta' => $request->meta ?? [],
            ]);

            // Create initial inventory transaction if has inventory
            if ($product->has_inventory && $product->stock_quantity > 0) {
                \App\Models\InventoryTransaction::create([
                    'product_id' => $product->id,
                    'type' => 'increase',
                    'quantity' => $product->stock_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $product->stock_quantity,
                    'reason' => 'initial_stock',
                    'notes' => 'Initial stock when product was created',
                    'user_id' => auth()->id(),
                ]);
            }

            $product->load('category');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show(string $id)
    {
        try {
            $product = Product::with('category')->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0',
                'weight_grams' => 'nullable|integer|min:0',
                'currency' => 'nullable|string|max:3',
                'is_available' => 'boolean',
                'has_inventory' => 'boolean',
                'stock_quantity' => 'nullable|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'images' => 'sometimes|required|array|min:1',
                'images.*' => 'string',
                'meta' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'title', 'description', 'short_description', 'price', 'weight_grams',
                'currency', 'is_available', 'category_id', 'images', 'meta'
            ]);

            // Update slug if title changed
            if ($request->has('title') && $request->title !== $product->title) {
                $slug = Str::slug($request->title);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $updateData['slug'] = $slug;
            }

            // Handle inventory changes
            if ($request->has('has_inventory')) {
                $updateData['has_inventory'] = $request->boolean('has_inventory');
                
                // If enabling inventory tracking
                if ($request->boolean('has_inventory') && !$product->has_inventory) {
                    $updateData['stock_quantity'] = $request->stock_quantity ?? 0;
                    $updateData['stock_last_updated_at'] = now();
                    
                    // Create initial inventory transaction
                    if ($updateData['stock_quantity'] > 0) {
                        \App\Models\InventoryTransaction::create([
                            'product_id' => $product->id,
                            'type' => 'increase',
                            'quantity' => $updateData['stock_quantity'],
                            'quantity_before' => 0,
                            'quantity_after' => $updateData['stock_quantity'],
                            'reason' => 'initial_stock',
                            'notes' => 'Inventory tracking enabled for product',
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
                // If disabling inventory tracking
                elseif (!$request->boolean('has_inventory') && $product->has_inventory) {
                    $updateData['stock_quantity'] = null;
                    $updateData['stock_last_updated_at'] = null;
                }
                // If updating stock quantity for product with inventory
                elseif ($request->boolean('has_inventory') && $product->has_inventory && $request->has('stock_quantity')) {
                    $oldQuantity = $product->stock_quantity ?? 0;
                    $newQuantity = $request->stock_quantity;
                    
                    if ($oldQuantity != $newQuantity) {
                        $product->setStock($newQuantity, auth()->id(), 'Stock updated via product edit');
                    }
                }
            }

            if ($request->has('low_stock_threshold')) {
                $updateData['low_stock_threshold'] = $request->low_stock_threshold;
            }

            $product->update($updateData);
            $product->load('category');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Check if product has orders
            $hasOrders = $product->orderItems()->exists();
            
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing orders. Consider marking it as unavailable instead.'
                ], 400);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle product availability
     */
    public function toggleAvailability(string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $product->update([
                'is_available' => !$product->is_available
            ]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product availability updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'available_products' => Product::where('is_available', true)->count(),
                'unavailable_products' => Product::where('is_available', false)->count(),
                'low_stock_products' => Product::where('is_available', true)
                    ->where('stock_quantity', '<=', 10)
                    ->count(),
                'out_of_stock_products' => Product::where('stock_quantity', 0)->count(),
                'products_by_category' => Product::selectRaw('category_id, COUNT(*) as count')
                    ->with('category:id,name')
                    ->groupBy('category_id')
                    ->get(),
                'average_price' => Product::avg('price'),
                'total_value' => Product::sum(DB::raw('price * stock_quantity')),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Product statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:products,id',
                'action' => 'required|in:activate,deactivate,delete,change_category,update_category,update_price',
                'category_id' => 'required_if:action,change_category,update_category|exists:categories,id',
                'price' => 'required_if:action,update_price|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productIds = $request->product_ids;
            $action = $request->action;
            $updatedCount = 0;

            DB::transaction(function () use ($productIds, $action, $request, &$updatedCount) {
                switch ($action) {
                    case 'activate':
                        $updatedCount = Product::whereIn('id', $productIds)
                            ->update(['is_available' => true]);
                        break;
                    
                    case 'deactivate':
                        $updatedCount = Product::whereIn('id', $productIds)
                            ->update(['is_available' => false]);
                        break;
                    
                    case 'delete':
                        $updatedCount = Product::whereIn('id', $productIds)->delete();
                        break;
                    
                    case 'change_category':
                    case 'update_category':
                        $updatedCount = Product::whereIn('id', $productIds)
                            ->update(['category_id' => $request->category_id]);
                        break;
                    
                    case 'update_price':
                        $updatedCount = Product::whereIn('id', $productIds)
                            ->update(['price' => $request->price]);
                        break;
                }
            });

            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $updatedCount],
                'message' => "Bulk {$action} completed successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate product
     */
    public function duplicate(string $id)
    {
        try {
            $originalProduct = Product::findOrFail($id);
            
            $newProduct = $originalProduct->replicate();
            $newProduct->title = $originalProduct->title . ' (Copy)';
            $newProduct->slug = Str::slug($newProduct->title);
            
            // Ensure unique slug
            $originalSlug = $newProduct->slug;
            $counter = 1;
            while (Product::where('slug', $newProduct->slug)->exists()) {
                $newProduct->slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $newProduct->is_available = false; // Start as unavailable
            $newProduct->save();
            
            $newProduct->load('category');

            return response()->json([
                'success' => true,
                'data' => $newProduct,
                'message' => 'Product duplicated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product images
     */
    public function updateImages(Request $request, string $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1',
                'images.*' => 'string', // URLs or base64 strings
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product->update(['images' => $request->images]);
            $product->load('category');

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product images updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category
     */
    public function byCategory(string $categoryId, Request $request)
    {
        try {
            $query = Product::with('category')
                ->where('category_id', $categoryId);

            // Apply same filters as index
            if ($request->has('is_available')) {
                $query->where('is_available', $request->boolean('is_available'));
            }

            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Category products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'json');
            $categoryId = $request->get('category_id');
            $isAvailable = $request->get('is_available');

            $query = Product::with('category');

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($isAvailable !== null) {
                $query->where('is_available', $isAvailable);
            }

            $products = $query->get();

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($products);
                case 'xlsx':
                    return $this->exportToXlsx($products);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $products,
                        'message' => 'Products exported successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToCsv($products)
    {
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Slug', 'Description', 'Price', 'Currency', 
                'Is Available', 'Category', 'Stock Quantity', 'Created At'
            ]);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->title,
                    $product->slug,
                    $product->description,
                    $product->price,
                    $product->currency,
                    $product->is_available ? 'Yes' : 'No',
                    $product->category->name ?? '',
                    $product->stock_quantity ?? 0,
                    $product->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToXlsx($products)
    {
        // This would require a package like PhpSpreadsheet
        // For now, return JSON
        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }
}
