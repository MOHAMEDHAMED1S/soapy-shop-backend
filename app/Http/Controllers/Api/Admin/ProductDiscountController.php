<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDiscount;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductDiscountController extends Controller
{
    /**
     * Display a listing of product discounts
     */
    public function index(Request $request)
    {
        try {
            $query = ProductDiscount::with(['products']);

            // Filter by status
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->active();
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'expired':
                        $query->where('expires_at', '<', Carbon::now());
                        break;
                    case 'upcoming':
                        $query->where('starts_at', '>', Carbon::now());
                        break;
                }
            }

            // Filter by discount type
            if ($request->has('discount_type')) {
                $query->where('discount_type', $request->discount_type);
            }

            // Filter by apply_to
            if ($request->has('apply_to')) {
                $query->where('apply_to', $request->apply_to);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'priority');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $discounts = $query->paginate($perPage);

            // Add statistics
            $summary = [
                'total_discounts' => ProductDiscount::count(),
                'active_discounts' => ProductDiscount::active()->count(),
                'all_products_discounts' => ProductDiscount::active()->forAllProducts()->count(),
                'specific_products_discounts' => ProductDiscount::active()->forSpecificProducts()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'discounts' => $discounts,
                    'summary' => $summary
                ],
                'message' => 'Product discounts retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product discounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created discount
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'required|in:percentage,fixed',
                'discount_value' => 'required|numeric|min:0',
                'apply_to' => 'required|in:all_products,specific_products',
                'product_ids' => 'required_if:apply_to,specific_products|array',
                'product_ids.*' => 'exists:products,id',
                'is_active' => 'boolean',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'priority' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create discount
            $discount = ProductDiscount::create([
                'name' => $request->name,
                'description' => $request->description,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'apply_to' => $request->apply_to,
                'is_active' => $request->get('is_active', true),
                'starts_at' => $request->starts_at ? Carbon::parse($request->starts_at) : null,
                'expires_at' => $request->expires_at ? Carbon::parse($request->expires_at) : null,
                'priority' => $request->get('priority', 0),
            ]);

            // Attach products if specific products
            if ($request->apply_to === 'specific_products' && $request->product_ids) {
                $discount->products()->attach($request->product_ids);
            }

            $discount->load('products');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'Product discount created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating product discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified discount
     */
    public function show(string $id)
    {
        try {
            $discount = ProductDiscount::with(['products'])->find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'Product discount retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified discount
     */
    public function update(Request $request, string $id)
    {
        try {
            $discount = ProductDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'sometimes|in:percentage,fixed',
                'discount_value' => 'sometimes|numeric|min:0',
                'apply_to' => 'sometimes|in:all_products,specific_products',
                'product_ids' => 'required_if:apply_to,specific_products|array',
                'product_ids.*' => 'exists:products,id',
                'is_active' => 'boolean',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'priority' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update discount
            $discount->update($request->only([
                'name',
                'description',
                'discount_type',
                'discount_value',
                'apply_to',
                'is_active',
                'priority',
            ]));

            if ($request->has('starts_at')) {
                $discount->starts_at = $request->starts_at ? Carbon::parse($request->starts_at) : null;
            }

            if ($request->has('expires_at')) {
                $discount->expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : null;
            }

            $discount->save();

            // Update products if necessary
            if ($request->has('apply_to')) {
                if ($request->apply_to === 'specific_products' && $request->has('product_ids')) {
                    $discount->products()->sync($request->product_ids);
                } elseif ($request->apply_to === 'all_products') {
                    $discount->products()->detach();
                }
            }

            $discount->load('products');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'Product discount updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating product discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified discount
     */
    public function destroy(string $id)
    {
        try {
            $discount = ProductDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product discount deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle discount active status
     */
    public function toggleStatus(string $id)
    {
        try {
            $discount = ProductDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            $discount->is_active = !$discount->is_active;
            $discount->save();

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'Discount status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling discount status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics about discounts
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_discounts' => ProductDiscount::count(),
                'active_discounts' => ProductDiscount::active()->count(),
                'inactive_discounts' => ProductDiscount::where('is_active', false)->count(),
                'expired_discounts' => ProductDiscount::where('expires_at', '<', Carbon::now())->count(),
                'upcoming_discounts' => ProductDiscount::where('starts_at', '>', Carbon::now())->count(),
                'all_products_discounts' => ProductDiscount::forAllProducts()->count(),
                'specific_products_discounts' => ProductDiscount::forSpecificProducts()->count(),
                'percentage_discounts' => ProductDiscount::where('discount_type', 'percentage')->count(),
                'fixed_discounts' => ProductDiscount::where('discount_type', 'fixed')->count(),
                'products_with_discounts' => Product::whereHas('discounts', function ($query) {
                    $query->where('is_active', true);
                })->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Discount statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving discount statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products affected by a discount
     */
    public function affectedProducts(string $id)
    {
        try {
            $discount = ProductDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            if ($discount->apply_to === 'all_products') {
                $products = Product::where('is_available', true)
                    ->with('category')
                    ->paginate(20);
            } else {
                $products = $discount->products()
                    ->where('is_available', true)
                    ->with('category')
                    ->paginate(20);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'discount' => $discount,
                    'products' => $products
                ],
                'message' => 'Affected products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving affected products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a discount
     */
    public function duplicate(string $id)
    {
        try {
            $originalDiscount = ProductDiscount::with('products')->find($id);

            if (!$originalDiscount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product discount not found'
                ], 404);
            }

            DB::beginTransaction();

            // Create new discount
            $newDiscount = ProductDiscount::create([
                'name' => $originalDiscount->name . ' (نسخة)',
                'description' => $originalDiscount->description,
                'discount_type' => $originalDiscount->discount_type,
                'discount_value' => $originalDiscount->discount_value,
                'apply_to' => $originalDiscount->apply_to,
                'is_active' => false, // Set as inactive by default
                'starts_at' => null,
                'expires_at' => null,
                'priority' => $originalDiscount->priority,
            ]);

            // Copy products if specific products
            if ($originalDiscount->apply_to === 'specific_products') {
                $productIds = $originalDiscount->products->pluck('id')->toArray();
                $newDiscount->products()->attach($productIds);
            }

            $newDiscount->load('products');

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $newDiscount,
                'message' => 'Product discount duplicated successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error duplicating product discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

