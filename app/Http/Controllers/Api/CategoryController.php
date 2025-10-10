<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with their products count
     */
    public function index(Request $request)
    {
        try {
            $query = Category::withCount(['products' => function ($query) {
                $query->where('is_available', true);
            }]);

            // Get only parent categories or all categories
            if ($request->get('parents_only', false)) {
                $query->whereNull('parent_id');
            }

            // Include children categories
            if ($request->get('with_children', false)) {
                $query->with(['children' => function ($query) {
                    $query->withCount(['products' => function ($query) {
                        $query->where('is_available', true);
                    }]);
                }]);
            }

            $categories = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category with its products
     */
    public function show(string $slug, Request $request)
    {
        try {
            $category = Category::where('slug', $slug)
                ->withCount(['products' => function ($query) {
                    $query->where('is_available', true);
                }])
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            // Include products if requested
            if ($request->get('with_products', false)) {
                $productsQuery = $category->products()
                    ->where('is_available', true)
                    ->orderBy('created_at', 'desc');

                $perPage = $request->get('per_page', 15);
                $products = $productsQuery->paginate($perPage);
                
                $category->products_paginated = $products;
            }

            // Include children categories
            if ($request->get('with_children', false)) {
                $category->load(['children' => function ($query) {
                    $query->withCount(['products' => function ($query) {
                        $query->where('is_available', true);
                    }]);
                }]);
            }

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category tree structure
     */
    public function tree()
    {
        try {
            $categories = Category::where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->withCount(['products' => function ($query) {
                            $query->where('is_available', true);
                        }]);
                }])
                ->withCount(['products' => function ($query) {
                    $query->where('is_available', true);
                }])
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Category tree retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category tree',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category statistics for admin
     */
    public function statistics()
    {
        try {
            $statistics = [
                'total_categories' => Category::count(),
                'active_categories' => Category::where('is_active', true)->count(),
                'inactive_categories' => Category::where('is_active', false)->count(),
                'root_categories' => Category::whereNull('parent_id')->count(),
                'subcategories' => Category::whereNotNull('parent_id')->count(),
                'categories_with_products' => Category::has('products')->count(),
                'empty_categories' => Category::doesntHave('products')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Category statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
