<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductComment;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminProductCommentController extends Controller
{
    /**
     * Get all comments with filtering
     */
    public function index(Request $request)
    {
        try {
            $query = ProductComment::with(['product:id,title,slug', 'approver:id,name']);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by product
            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            // Search by author name or comment content
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('author_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('comment', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $comments = $query->paginate($perPage);

            // Get summary statistics
            $summary = $this->getStatistics();

            return response()->json([
                'success' => true,
                'data' => [
                    'comments' => $comments,
                    'summary' => $summary
                ],
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
     * Get single comment details
     */
    public function show($id)
    {
        try {
            $comment = ProductComment::with(['product', 'approver'])->find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a comment
     */
    public function approve(Request $request, $id)
    {
        try {
            $comment = ProductComment::find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            $userId = $request->user()?->id;
            $comment->approve($userId);

            return response()->json([
                'success' => true,
                'data' => $comment->fresh(['product', 'approver']),
                'message' => 'Comment approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a comment
     */
    public function reject(Request $request, $id)
    {
        try {
            $comment = ProductComment::find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            $userId = $request->user()?->id;
            $comment->reject($userId);

            return response()->json([
                'success' => true,
                'data' => $comment->fresh(['product', 'approver']),
                'message' => 'Comment rejected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function destroy($id)
    {
        try {
            $comment = ProductComment::find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a specific product (Admin)
     */
    public function productComments(Request $request, $productId)
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $query = ProductComment::where('product_id', $productId)
                ->with('approver:id,name');

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $comments = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'comments' => $comments,
                    'counts' => [
                        'total' => $comments->count(),
                        'pending' => $comments->where('status', 'pending')->count(),
                        'approved' => $comments->where('status', 'approved')->count(),
                        'rejected' => $comments->where('status', 'rejected')->count(),
                    ]
                ],
                'message' => 'Product comments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve comments
     */
    public function bulkApprove(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No comment IDs provided'
                ], 422);
            }

            $userId = $request->user()?->id;
            
            ProductComment::whereIn('id', $ids)->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' comments approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics helper
     */
    private function getStatistics(): array
    {
        $today = Carbon::today();
        $last7Days = Carbon::now()->subDays(7);

        return [
            'total_comments' => ProductComment::count(),
            'pending' => ProductComment::pending()->count(),
            'approved' => ProductComment::approved()->count(),
            'rejected' => ProductComment::rejected()->count(),
            'today' => ProductComment::whereDate('created_at', $today)->count(),
            'last_7_days' => ProductComment::where('created_at', '>=', $last7Days)->count(),
        ];
    }
}
