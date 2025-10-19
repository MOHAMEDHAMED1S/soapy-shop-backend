<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

            // Also support start_date and end_date parameters
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
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

            // Create base query for statistics (without status filter for individual counts)
            $baseStatsQuery = Order::with(['orderItems.product', 'payment']);

            // Apply date and search filters only (not status filter for base query)
            if ($request->has('date_from') && $request->date_from) {
                $baseStatsQuery->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $baseStatsQuery->whereDate('created_at', '<=', $request->date_to);
            }

            // Also support start_date and end_date parameters for statistics
            if ($request->has('start_date') && $request->start_date) {
                $baseStatsQuery->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $baseStatsQuery->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $baseStatsQuery->where(function ($q) use ($searchTerm) {
                    $q->where('order_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Create a separate query that includes status filter for total count
            $filteredStatsQuery = clone $baseStatsQuery;
            if ($request->has('status') && $request->status) {
                $filteredStatsQuery->where('status', $request->status);
            }

            // Calculate filtered statistics
            $summary = [
                'total_orders' => $filteredStatsQuery->count(),
                'pending_orders' => (clone $baseStatsQuery)->where('status', 'pending')->count(),
                'paid_orders' => (clone $baseStatsQuery)->where('status', 'paid')->count(),
                'shipped_orders' => (clone $baseStatsQuery)->where('status', 'shipped')->count(),
                'delivered_orders' => (clone $baseStatsQuery)->where('status', 'delivered')->count(),
                'cancelled_orders' => (clone $baseStatsQuery)->where('status', 'cancelled')->count(),
                'awaiting_payment_orders' => (clone $baseStatsQuery)->where('status', 'awaiting_payment')->count(),
                'total_revenue' => (clone $baseStatsQuery)->where('status', 'paid')->sum('total_amount'),
                'average_order_value' => (clone $baseStatsQuery)->where('status', 'paid')->avg('total_amount'),
                'filters_applied' => [
                    'status' => $request->status ?? null,
                    'date_from' => $request->date_from ?? null,
                    'date_to' => $request->date_to ?? null,
                    'start_date' => $request->start_date ?? null,
                    'end_date' => $request->end_date ?? null,
                    'search' => $request->search ?? null
                ]
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
            'pending' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'awaiting_payment' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'paid' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'shipped' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'delivered' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'cancelled' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'],
            'refunded' => ['pending', 'awaiting_payment', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded']
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
            // Handle date filtering
            $dateFrom = $request->get('date_from') ?: $request->get('start_date');
            $dateTo = $request->get('date_to') ?: $request->get('end_date');
            
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

            $stats = [
                'total_orders' => $baseQuery->count(),
                'total_revenue' => $baseQuery->where('status', 'paid')->sum('total_amount'),
                'pending_orders' => $baseQuery->where('status', 'pending')->count(),
                'paid_orders' => $baseQuery->where('status', 'paid')->count(),
                'shipped_orders' => $baseQuery->where('status', 'shipped')->count(),
                'delivered_orders' => $baseQuery->where('status', 'delivered')->count(),
                'cancelled_orders' => $baseQuery->where('status', 'cancelled')->count(),
                'average_order_value' => $baseQuery->where('status', 'paid')->avg('total_amount'),
                'orders_by_status' => $baseQuery->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status'),
                'recent_orders' => $baseQuery->with(['orderItems.product'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                'filters_applied' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'period' => $request->get('period'),
                    'date_range' => [
                        'start' => $startDate->toDateString(),
                        'end' => $endDate->toDateString()
                    ]
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
