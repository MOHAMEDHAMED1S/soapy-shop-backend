<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of products with filtering and search
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with('category');
            
            // By default, only show available products unless explicitly requested
            if (!$request->has('is_available')) {
                $query->where('is_available', true);
            }

            // Filter by category (by slug or ID)
            if ($request->has('category') && $request->category) {
                $categoryParam = $request->category;
                
                // Support multiple categories (comma-separated)
                if (strpos($categoryParam, ',') !== false) {
                    $categorySlugs = array_map('trim', explode(',', $categoryParam));
                    $categories = Category::whereIn('slug', $categorySlugs)->get();
                    
                    if ($categories->count() > 0) {
                        $categoryIds = $categories->pluck('id')->toArray();
                        $query->whereIn('category_id', $categoryIds);
                    } else {
                        // If no categories found, return empty results
                        return response()->json([
                            'success' => true,
                            'data' => [
                                'current_page' => 1,
                                'data' => [],
                                'first_page_url' => $request->url() . '?page=1',
                                'from' => null,
                                'last_page' => 1,
                                'last_page_url' => $request->url() . '?page=1',
                                'links' => [],
                                'next_page_url' => null,
                                'path' => $request->url(),
                                'per_page' => $request->get('per_page', 15),
                                'prev_page_url' => null,
                                'to' => null,
                                'total' => 0
                            ],
                            'message' => 'No categories found'
                        ]);
                    }
                } else {
                    // Single category
                    $category = Category::where('slug', $categoryParam)->first();
                    
                    if ($category) {
                        $query->where('category_id', $category->id);
                    } else {
                        // If category slug not found, return empty results
                        return response()->json([
                            'success' => true,
                            'data' => [
                                'current_page' => 1,
                                'data' => [],
                                'first_page_url' => $request->url() . '?page=1',
                                'from' => null,
                                'last_page' => 1,
                                'last_page_url' => $request->url() . '?page=1',
                                'links' => [],
                                'next_page_url' => null,
                                'path' => $request->url(),
                                'per_page' => $request->get('per_page', 15),
                                'prev_page_url' => null,
                                'to' => null,
                                'total' => 0
                            ],
                            'message' => 'Category not found'
                        ]);
                    }
                }
            } elseif ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by availability
            if ($request->has('is_available')) {
                $query->where('is_available', $request->boolean('is_available'));
            }

            // Filter by price range
            if ($request->has('min_price') && $request->min_price) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price') && $request->max_price) {
                $query->where('price', '<=', $request->max_price);
            }

            // Search in title and description
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('short_description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            // Validate sort fields
            $allowedSortFields = ['created_at', 'updated_at', 'price', 'title', 'stock_quantity'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            // Legacy support for sort_by_price
            if ($request->has('sort_by_price')) {
                $sortDirection = $request->sort_by_price === 'desc' ? 'desc' : 'asc';
                $query->orderBy('price', $sortDirection);
            }

            // Pagination
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
     * Display the specified product by ID or slug
     */
    public function show(string $identifier)
    {
        try {
            $query = Product::with('category')->where('is_available', true);
            
            // Check if identifier is numeric (ID) or string (slug)
            if (is_numeric($identifier)) {
                $product = $query->where('id', $identifier)->first();
            } else {
                $product = $query->where('slug', $identifier)->first();
            }

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or not available'
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
     * Get featured products (for homepage)
     */
    public function featured(Request $request)
    {
        try {
            $limit = $request->get('limit', 8);
            
            $products = Product::with('category')
                ->where('is_available', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Featured products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category
     */
    public function byCategory(string $categorySlug, Request $request)
    {
        try {
            $category = Category::where('slug', $categorySlug)->first();
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $query = Product::with('category')
                ->where('category_id', $category->id)
                ->where('is_available', true);

            // Search within category
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort by price
            if ($request->has('sort_by_price')) {
                $sortDirection = $request->sort_by_price === 'desc' ? 'desc' : 'asc';
                $query->orderBy('price', $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'products' => $products
                ],
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
}
