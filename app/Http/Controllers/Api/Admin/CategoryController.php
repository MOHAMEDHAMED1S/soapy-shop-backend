<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with filtering and search
     */
    public function index(Request $request)
    {
        try {
            $query = Category::with(['parent', 'children']);

            // Search
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by parent
            if ($request->has('parent_id')) {
                if ($request->parent_id === 'null' || $request->parent_id === null) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $request->parent_id);
                }
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $categories = $query->paginate($perPage);

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
     * Store a newly created category
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string|max:500',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate slug
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category = Category::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'image' => $request->image,
                'is_active' => $request->get('is_active', true),
                'sort_order' => $request->get('sort_order', 0),
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            $category->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category
     */
    public function show(string $id)
    {
        try {
            $category = Category::with(['parent', 'children', 'products'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, string $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string|max:500',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for circular reference
            if ($request->parent_id && $request->parent_id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category cannot be its own parent'
                ], 422);
            }

            // Generate new slug if name changed
            $slug = $category->slug;
            if ($request->name !== $category->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            $category->update([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'image' => $request->image,
                'is_active' => $request->get('is_active', $category->is_active),
                'sort_order' => $request->get('sort_order', $category->sort_order),
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            $category->load(['parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(string $id)
    {
        try {
            $category = Category::findOrFail($id);

            // Check if category has children
            if ($category->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with subcategories. Please delete subcategories first.'
                ], 422);
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with products. Please move or delete products first.'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting category',
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
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $tree = $this->buildTree($categories);

            return response()->json([
                'success' => true,
                'data' => $tree,
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
     * Toggle category status
     */
    public function toggleStatus(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->update(['is_active' => !$category->is_active]);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating category status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category sort order
     */
    public function updateSortOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'categories' => 'required|array',
                'categories.*.id' => 'required|exists:categories,id',
                'categories.*.sort_order' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::transaction(function () use ($request) {
                foreach ($request->categories as $categoryData) {
                    Category::where('id', $categoryData['id'])
                        ->update(['sort_order' => $categoryData['sort_order']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Category sort order updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating sort order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category statistics
     */
    public function statistics()
    {
        try {
            $stats = [
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
                'data' => $stats,
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

    /**
     * Build category tree structure
     */
    private function buildTree($categories, $parentId = null)
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $categoryData = $category->toArray();
                $categoryData['children'] = $children;
                $tree[] = $categoryData;
            }
        }
        
        return $tree;
    }
}
