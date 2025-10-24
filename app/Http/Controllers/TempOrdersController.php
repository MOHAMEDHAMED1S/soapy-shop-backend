<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TempOrdersController extends Controller
{
    /**
     * Get all orders with pagination and filtering (no authentication required)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['orderItems.product', 'payment']);

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%");
                });
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific order by ID (no authentication required)
     */
    public function show($id): JsonResponse
    {
        try {
            $order = Order::with(['orderItems.product', 'payment'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific order (no authentication required)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $order = Order::with(['orderItems', 'payment'])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Delete related order items first
            $order->orderItems()->delete();

            // Delete payment record if exists
            if ($order->payment) {
                $order->payment->delete();
            }

            // Delete the order
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status (no authentication required)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $validStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($request->status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Valid statuses are: ' . implode(', ', $validStatuses)
                ], 400);
            }

            $order->status = $request->status;
            $order->save();

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders statistics (no authentication required)
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            // Handle date filtering
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            
            // Default to last 30 days if no dates provided
            if (!$dateFrom && !$dateTo) {
                $period = $request->get('period', '30'); // days
                $startDate = now()->subDays($period);
                $endDate = now();
            } else {
                $startDate = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->subDays(30);
                $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();
            }

            // Base query for date filtering
            $baseQuery = Order::whereBetween('created_at', [$startDate, $endDate]);

            // Statuses that represent completed/paid orders for revenue calculation
            $revenueStatuses = ['paid', 'shipped', 'delivered'];

            $stats = [
                'total_orders' => (clone $baseQuery)->count(),
                'total_revenue' => (clone $baseQuery)->whereIn('status', $revenueStatuses)->sum('total_amount'),
                'pending_orders' => (clone $baseQuery)->where('status', 'pending')->count(),
                'paid_orders' => (clone $baseQuery)->where('status', 'paid')->count(),
                'shipped_orders' => (clone $baseQuery)->where('status', 'shipped')->count(),
                'delivered_orders' => (clone $baseQuery)->where('status', 'delivered')->count(),
                'cancelled_orders' => (clone $baseQuery)->where('status', 'cancelled')->count(),
                'average_order_value' => (clone $baseQuery)->whereIn('status', $revenueStatuses)->avg('total_amount'),
                'orders_by_status' => (clone $baseQuery)->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status'),
                'recent_orders' => (clone $baseQuery)->with(['orderItems.product'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Order statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}