<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductCommentController extends Controller
{
    /**
     * Get approved comments for a product
     */
    public function index(int $productId)
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $comments = ProductComment::where('product_id', $productId)
                ->approved()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $comments,
                'count' => $comments->count(),
                'message' => 'Comments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new comment for a product
     */
    public function store(Request $request, int $productId)
    {
        $validator = Validator::make($request->all(), [
            'author_name' => 'required|string|min:2|max:100',
            'comment' => 'required|string|min:3|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $comment = ProductComment::create([
                'product_id' => $productId,
                'author_name' => $request->author_name,
                'comment' => $request->comment,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment submitted successfully. It will be visible after approval.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
