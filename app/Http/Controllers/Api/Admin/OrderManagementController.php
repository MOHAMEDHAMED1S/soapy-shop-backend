<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrderManagementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get orders with advanced filtering and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['orderItems.product', 'payment']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('customer_name')) {
                $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
            }

            if ($request->has('customer_phone')) {
                $query->where('customer_phone', 'like', '%' . $request->customer_phone . '%');
            }

            if ($request->has('order_number')) {
                $query->where('order_number', 'like', '%' . $request->order_number . '%');
            }

            if ($request->has('min_amount')) {
                $query->where('total_amount', '>=', $request->min_amount);
            }

            if ($request->has('max_amount')) {
                $query->where('total_amount', '<=', $request->max_amount);
            }

            if ($request->has('payment_status')) {
                $query->whereHas('payment', function ($q) use ($request) {
                    $q->where('status', $request->payment_status);
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage);

            // Add summary statistics
            $summary = $this->getOrdersSummary($request);

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
     * Get order details with full information
     */
    public function show(string $id)
    {
        try {
            $order = Order::with([
                'orderItems.product.category',
                'payment'
            ])->findOrFail($id);

            // Add order timeline
            $timeline = $this->getOrderTimeline($order);

            // Add related orders (same customer)
            $relatedOrders = Order::where('customer_phone', $order->customer_phone)
                ->where('id', '!=', $order->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'timeline' => $timeline,
                    'related_orders' => $relatedOrders
                ],
                'message' => 'Order details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update order status with validation and notifications
     */
    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,awaiting_payment,paid,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:1000',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_date' => 'nullable|date',
            'delivery_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Validate status transition
            if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition from ' . $oldStatus . ' to ' . $newStatus
                ], 422);
            }

            // Update order
            $updateData = ['status' => $newStatus];
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->has('tracking_number')) {
                $updateData['tracking_number'] = $request->tracking_number;
            }

            if ($request->has('shipping_date')) {
                $updateData['shipping_date'] = $request->shipping_date;
            }

            if ($request->has('delivery_date')) {
                $updateData['delivery_date'] = $request->delivery_date;
            }

            $order->update($updateData);

            // Create notification
            $this->notificationService->createNotification(
                'order_status_updated',
                'تحديث حالة الطلب',
                "تم تحديث حالة الطلب رقم {$order->order_number} من {$oldStatus} إلى {$newStatus}",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'customer_name' => $order->customer_name
                ],
                'medium'
            );

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
     * Bulk update order statuses
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
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

        try {
            $orderIds = $request->order_ids;
            $newStatus = $request->status;
            $notes = $request->notes;

            $updatedOrders = [];
            $failedOrders = [];

            foreach ($orderIds as $orderId) {
                try {
                    $order = Order::findOrFail($orderId);
                    $oldStatus = $order->status;

                    if ($this->isValidStatusTransition($oldStatus, $newStatus)) {
                        $order->update([
                            'status' => $newStatus,
                            'notes' => $notes ? $order->notes . "\n" . $notes : $order->notes
                        ]);
                        $updatedOrders[] = $order;
                    } else {
                        $failedOrders[] = [
                            'order_id' => $orderId,
                            'order_number' => $order->order_number,
                            'reason' => 'Invalid status transition'
                        ];
                    }
                } catch (\Exception $e) {
                    $failedOrders[] = [
                        'order_id' => $orderId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Create notification for bulk update
            if (count($updatedOrders) > 0) {
                $this->notificationService->createNotification(
                    'bulk_order_status_updated',
                    'تحديث مجمع لحالات الطلبات',
                    "تم تحديث حالة " . count($updatedOrders) . " طلب إلى {$newStatus}",
                    [
                        'updated_count' => count($updatedOrders),
                        'failed_count' => count($failedOrders),
                        'new_status' => $newStatus,
                        'updated_orders' => collect($updatedOrders)->pluck('order_number')->toArray()
                    ],
                    'medium'
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_orders' => $updatedOrders,
                    'failed_orders' => $failedOrders,
                    'summary' => [
                        'total_requested' => count($orderIds),
                        'successfully_updated' => count($updatedOrders),
                        'failed' => count($failedOrders)
                    ]
                ],
                'message' => 'Bulk status update completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk status update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order statistics and analytics
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $startDate = Carbon::now()->subDays($period);

            $stats = [
                'overview' => [
                    'total_orders' => Order::count(),
                    'pending_orders' => Order::where('status', 'pending')->count(),
                    'paid_orders' => Order::where('status', 'paid')->count(),
                    'shipped_orders' => Order::where('status', 'shipped')->count(),
                    'delivered_orders' => Order::where('status', 'delivered')->count(),
                    'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                ],
                'period_stats' => [
                    'orders_count' => Order::where('created_at', '>=', $startDate)->count(),
                    'total_revenue' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                        ->where('created_at', '>=', $startDate)
                        ->sum('total_amount'),
                    'average_order_value' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                        ->where('created_at', '>=', $startDate)
                        ->avg('total_amount'),
                    'completion_rate' => $this->calculateCompletionRate($startDate),
                ],
                'status_distribution' => Order::where('created_at', '>=', $startDate)
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status'),
                'daily_trends' => Order::where('created_at', '>=', $startDate)
                    ->whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'top_customers' => Order::where('created_at', '>=', $startDate)
                    ->whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->selectRaw('customer_name, customer_phone, COUNT(*) as orders_count, SUM(total_amount) as total_spent')
                    ->groupBy('customer_name', 'customer_phone')
                    ->orderBy('total_spent', 'desc')
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
     * Export orders data
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $filters = $request->except(['format']);

            $query = Order::with(['orderItems.product', 'payment']);

            // Apply same filters as index method
            $this->applyFilters($query, $filters);

            $orders = $query->get();

            $exportData = $this->formatExportData($orders, $format);

            $filename = 'orders_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
            $filePath = storage_path('app/public/exports/' . $filename);

            file_put_contents($filePath, $exportData);

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'download_url' => url('storage/exports/' . $filename),
                    'total_records' => $orders->count()
                ],
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

    /**
     * Get order timeline
     */
    public function timeline(string $id)
    {
        try {
            $order = Order::findOrFail($id);
            $timeline = $this->getOrderTimeline($order);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'message' => 'Order timeline retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search orders
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            $limit = $request->get('limit', 10);

            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No search query provided'
                ]);
            }

            $orders = Order::where(function ($q) use ($query) {
                $q->where('order_number', 'like', '%' . $query . '%')
                  ->orWhere('customer_name', 'like', '%' . $query . '%')
                  ->orWhere('customer_phone', 'like', '%' . $query . '%')
                  ->orWhere('customer_email', 'like', '%' . $query . '%');
            })
            ->with(['orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Search results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders summary for current filters
     */
    private function getOrdersSummary(Request $request)
    {
        $query = Order::query();

        // Apply same filters as main query
        $this->applyFilters($query, $request->all());

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])->sum('total_amount'),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'paid_orders' => $query->where('status', 'paid')->count(),
            'shipped_orders' => $query->where('status', 'shipped')->count(),
            'delivered_orders' => $query->where('status', 'delivered')->count(),
            'cancelled_orders' => $query->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $filters['customer_name'] . '%');
        }

        if (isset($filters['customer_phone'])) {
            $query->where('customer_phone', 'like', '%' . $filters['customer_phone'] . '%');
        }

        if (isset($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }
    }

    /**
     * Validate status transition
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'pending' => ['awaiting_payment', 'cancelled'],
            'awaiting_payment' => ['paid', 'cancelled'],
            'paid' => ['shipped', 'refunded'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => ['refunded'],
            'cancelled' => [],
            'refunded' => []
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Get order timeline
     */
    private function getOrderTimeline(Order $order): array
    {
        $timeline = [
            [
                'event' => 'order_created',
                'title' => 'تم إنشاء الطلب',
                'description' => 'تم إنشاء الطلب بنجاح',
                'timestamp' => $order->created_at,
                'status' => 'completed'
            ]
        ];

        if ($order->payment) {
            $timeline[] = [
                'event' => 'payment_initiated',
                'title' => 'بدء عملية الدفع',
                'description' => 'تم بدء عملية الدفع',
                'timestamp' => $order->payment->created_at,
                'status' => 'completed'
            ];

            if ($order->payment->status === 'paid') {
                $timeline[] = [
                    'event' => 'payment_completed',
                    'title' => 'تم الدفع بنجاح',
                    'description' => 'تم إتمام عملية الدفع',
                    'timestamp' => $order->payment->updated_at,
                    'status' => 'completed'
                ];
            }
        }

        if ($order->status === 'shipped') {
            $timeline[] = [
                'event' => 'order_shipped',
                'title' => 'تم شحن الطلب',
                'description' => 'تم شحن الطلب بنجاح',
                'timestamp' => $order->updated_at,
                'status' => 'completed'
            ];
        }

        if ($order->status === 'delivered') {
            $timeline[] = [
                'event' => 'order_delivered',
                'title' => 'تم تسليم الطلب',
                'description' => 'تم تسليم الطلب بنجاح',
                'timestamp' => $order->updated_at,
                'status' => 'completed'
            ];
        }

        return $timeline;
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(Carbon $startDate): float
    {
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $completedOrders = Order::where('status', 'delivered')
            ->where('created_at', '>=', $startDate)
            ->count();

        return $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0;
    }

    /**
     * Format export data
     */
    private function formatExportData($orders, string $format): string
    {
        switch ($format) {
            case 'csv':
                return $this->formatAsCsv($orders);
            case 'json':
                return json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            default:
                return $this->formatAsCsv($orders);
        }
    }

    /**
     * Format data as CSV
     */
    private function formatAsCsv($orders): string
    {
        $csv = "Order Number,Customer Name,Customer Phone,Customer Email,Status,Total Amount,Currency,Created At,Payment Status\n";
        
        foreach ($orders as $order) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $order->order_number,
                $order->customer_name,
                $order->customer_phone,
                $order->customer_email ?? '',
                $order->status,
                $order->total_amount,
                $order->currency,
                $order->created_at->format('Y-m-d H:i:s'),
                $order->payment ? $order->payment->status : 'N/A'
            );
        }

        return $csv;
    }
}
