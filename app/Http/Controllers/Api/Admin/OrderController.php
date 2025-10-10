<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of orders for admin
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['orderItems.product', 'payment']);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search by order number, customer name, or phone
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage);

            // Add summary statistics
            $summary = [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'paid_orders' => Order::where('status', 'paid')->count(),
                'shipped_orders' => Order::where('status', 'shipped')->count(),
                'delivered_orders' => Order::where('status', 'delivered')->count(),
                'total_revenue' => Order::where('status', 'paid')->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'summary' => $summary
                ],
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
     * Display the specified order with full details
     */
    public function show(string $id)
    {
        try {
            $order = Order::with([
                'orderItems.product',
                'payment'
            ])->find($id);

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
     * Update order status
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,awaiting_payment,paid,shipped,delivered,cancelled,refunded',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Validate status transition
            $validTransitions = [
                'pending' => ['awaiting_payment', 'cancelled'],
                'awaiting_payment' => ['paid', 'cancelled'],
                'paid' => ['shipped', 'refunded', 'cancelled'],
                'shipped' => ['delivered', 'cancelled'],
                'delivered' => ['refunded', 'cancelled'],
                'cancelled' => [],
                'refunded' => []
            ];

            if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid status transition from {$oldStatus} to {$newStatus}"
                ], 400);
            }

            DB::beginTransaction();

            $order->update([
                'status' => $newStatus,
                'notes' => $request->notes ?: $order->notes
            ]);

            // Create admin notification for important status changes
            if (in_array($newStatus, ['paid', 'shipped', 'delivered'])) {
                $this->notificationService->createOrderNotification($order, "order_{$newStatus}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order status updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders statistics for dashboard
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = now()->subDays($period);

            $stats = [
                'total_orders' => Order::where('created_at', '>=', $startDate)->count(),
                'total_revenue' => Order::where('created_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'paid_orders' => Order::where('status', 'paid')->count(),
                'shipped_orders' => Order::where('status', 'shipped')->count(),
                'delivered_orders' => Order::where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                'average_order_value' => Order::where('created_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->avg('total_amount'),
                'orders_by_status' => Order::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status'),
                'recent_orders' => Order::with(['orderItems.product'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
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

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Order::with(['orderItems.product', 'payment']);

            // Apply same filters as index method
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            // Prepare CSV data
            $csvData = [];
            $csvData[] = [
                'Order Number',
                'Customer Name',
                'Customer Phone',
                'Customer Email',
                'Status',
                'Total Amount',
                'Currency',
                'Created At',
                'Items Count'
            ];

            foreach ($orders as $order) {
                $csvData[] = [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->customer_email,
                    $order->status,
                    $order->total_amount,
                    $order->currency,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->orderItems->count()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $csvData,
                'message' => 'Orders exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
