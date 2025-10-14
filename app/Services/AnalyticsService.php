<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Payment;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get sales analytics
     */
    public function getSalesAnalytics(int $period = 30, string $groupBy = 'day'): array
    {
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        $query = Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        switch ($groupBy) {
            case 'day':
                $sales = $query->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'week':
                $sales = $query->selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, COUNT(*) as orders_count, SUM(total_amount) as revenue')
                    ->groupBy('year', 'week')
                    ->orderBy('year', 'week')
                    ->get();
                break;
            case 'month':
                $sales = $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as orders_count, SUM(total_amount) as revenue')
                    ->groupBy('year', 'month')
                    ->orderBy('year', 'month')
                    ->get();
                break;
            default:
                $sales = collect();
        }

        // Calculate totals
        $totalRevenue = $sales->sum('revenue');
        $totalOrders = $sales->sum('orders_count');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return [
            'sales_data' => $sales,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'average_order_value' => round($averageOrderValue, 3),
                'period' => $period,
                'group_by' => $groupBy
            ]
        ];
    }

    /**
     * Get product analytics
     */
    public function getProductAnalytics(int $period = 30, int $limit = 10): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Top selling products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'paid')
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('products.id, products.title, products.slug, SUM(order_items.quantity) as total_quantity, SUM(order_items.product_price * order_items.quantity) as total_revenue, COUNT(DISTINCT orders.id) as orders_count')
            ->groupBy('products.id', 'products.title', 'products.slug')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        // Product performance by category
        $categoryPerformance = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'paid')
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('categories.id, categories.name, COUNT(DISTINCT products.id) as products_count, SUM(order_items.quantity) as total_quantity, SUM(order_items.product_price * order_items.quantity) as total_revenue')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Product availability
        $availabilityStats = [
            'total_products' => Product::count(),
            'available_products' => Product::where('is_available', true)->count(),
            'unavailable_products' => Product::where('is_available', false)->count(),
        ];

        return [
            'top_products' => $topProducts,
            'category_performance' => $categoryPerformance,
            'availability_stats' => $availabilityStats,
            'period' => $period
        ];
    }

    /**
     * Get order analytics
     */
    public function getOrderAnalytics(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Order status distribution
        $statusDistribution = Order::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Daily order trends - only include paid orders for revenue calculation
        $dailyTrends = Order::where('created_at', '>=', $startDate)
            ->whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Average order processing time
        $processingTime = Order::where('created_at', '>=', $startDate)
            ->whereNotNull('updated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_processing_hours')
            ->first();

        // Order value distribution
        $valueDistribution = Order::where('created_at', '>=', $startDate)
            ->selectRaw('
                CASE 
                    WHEN total_amount < 10 THEN "0-10"
                    WHEN total_amount < 25 THEN "10-25"
                    WHEN total_amount < 50 THEN "25-50"
                    WHEN total_amount < 100 THEN "50-100"
                    ELSE "100+"
                END as range,
                COUNT(*) as count
            ')
            ->groupBy('range')
            ->get();

        return [
            'status_distribution' => $statusDistribution,
            'daily_trends' => $dailyTrends,
            'avg_processing_hours' => $processingTime->avg_processing_hours ?? 0,
            'value_distribution' => $valueDistribution,
            'period' => $period
        ];
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Payment method distribution
        $methodDistribution = Payment::where('created_at', '>=', $startDate)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get();

        // Payment status distribution
        $statusDistribution = Payment::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Daily payment trends
        $dailyTrends = Payment::where('created_at', '>=', $startDate)
            ->where('status', 'paid')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as payments_count, SUM(amount) as total_amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Success rate
        $totalPayments = Payment::where('created_at', '>=', $startDate)->count();
        $successfulPayments = Payment::where('created_at', '>=', $startDate)
            ->where('status', 'paid')
            ->count();
        $successRate = $totalPayments > 0 ? ($successfulPayments / $totalPayments) * 100 : 0;

        return [
            'method_distribution' => $methodDistribution,
            'status_distribution' => $statusDistribution,
            'daily_trends' => $dailyTrends,
            'success_rate' => round($successRate, 2),
            'total_payments' => $totalPayments,
            'successful_payments' => $successfulPayments,
            'period' => $period
        ];
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 20): array
    {
        $activities = collect();

        // Recent orders
        $recentOrders = Order::with('orderItems.product')
            ->orderBy('created_at', 'desc')
            ->limit($limit / 2)
            ->get()
            ->map(function ($order) {
                return [
                    'type' => 'order',
                    'id' => $order->id,
                    'title' => 'طلب جديد',
                    'description' => "طلب رقم {$order->order_number} بقيمة {$order->total_amount} {$order->currency}",
                    'customer' => $order->customer_name,
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'icon' => 'shopping-cart'
                ];
            });

        // Recent notifications
        $recentNotifications = AdminNotification::orderBy('created_at', 'desc')
            ->limit($limit / 2)
            ->get()
            ->map(function ($notification) {
                return [
                    'type' => 'notification',
                    'id' => $notification->id,
                    'title' => $notification->payload['title'] ?? 'إشعار',
                    'description' => $notification->payload['message'] ?? '',
                    'priority' => $notification->payload['priority'] ?? 'medium',
                    'created_at' => $notification->created_at,
                    'icon' => 'bell'
                ];
            });

        $activities = $activities->merge($recentOrders)->merge($recentNotifications);

        return $activities->sortByDesc('created_at')->take($limit)->values()->toArray();
    }

    /**
     * Get top products
     */
    public function getTopProducts(int $period = 30, int $limit = 10, string $metric = 'revenue'): array
    {
        $startDate = Carbon::now()->subDays($period);

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'paid')
            ->where('orders.created_at', '>=', $startDate);

        switch ($metric) {
            case 'revenue':
                $query->selectRaw('products.id, products.title, products.slug, products.price, SUM(order_items.product_price * order_items.quantity) as total_revenue, SUM(order_items.quantity) as total_quantity, COUNT(DISTINCT orders.id) as orders_count')
                    ->orderBy('total_revenue', 'desc');
                break;
            case 'quantity':
                $query->selectRaw('products.id, products.title, products.slug, products.price, SUM(order_items.quantity) as total_quantity, SUM(order_items.product_price * order_items.quantity) as total_revenue, COUNT(DISTINCT orders.id) as orders_count')
                    ->orderBy('total_quantity', 'desc');
                break;
            case 'orders':
                $query->selectRaw('products.id, products.title, products.slug, products.price, COUNT(DISTINCT orders.id) as orders_count, SUM(order_items.quantity) as total_quantity, SUM(order_items.product_price * order_items.quantity) as total_revenue')
                    ->orderBy('orders_count', 'desc');
                break;
        }

        $products = $query->groupBy('products.id', 'products.title', 'products.slug', 'products.price')
            ->limit($limit)
            ->get();

        return [
            'products' => $products,
            'metric' => $metric,
            'period' => $period
        ];
    }

    /**
     * Get category performance
     */
    public function getCategoryPerformance(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);

        $performance = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', 'paid')
            ->where('orders.created_at', '>=', $startDate)
            ->selectRaw('
                categories.id,
                categories.name,
                categories.slug,
                COUNT(DISTINCT products.id) as products_count,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.product_price * order_items.quantity) as total_revenue,
                COUNT(DISTINCT orders.id) as orders_count,
                AVG(order_items.product_price) as avg_product_price
            ')
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return [
            'categories' => $performance,
            'period' => $period
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Customer order statistics - only include paid orders
        $customerStats = Order::where('created_at', '>=', $startDate)
            ->whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
            ->selectRaw('
                customer_name,
                customer_phone,
                COUNT(*) as orders_count,
                SUM(total_amount) as total_spent,
                AVG(total_amount) as avg_order_value,
                MAX(created_at) as last_order_date
            ')
            ->groupBy('customer_name', 'customer_phone')
            ->orderBy('total_spent', 'desc')
            ->limit(20)
            ->get();

        // Customer distribution by order count
        $orderDistribution = Order::where('created_at', '>=', $startDate)
            ->selectRaw('
                CASE 
                    WHEN COUNT(*) = 1 THEN "عملاء جدد"
                    WHEN COUNT(*) BETWEEN 2 AND 5 THEN "عملاء متكررين"
                    ELSE "عملاء VIP"
                END as customer_type,
                COUNT(DISTINCT customer_phone) as customers_count
            ')
            ->groupBy('customer_phone')
            ->get()
            ->groupBy('customer_type')
            ->map(function ($group) {
                return $group->count();
            });

        return [
            'top_customers' => $customerStats,
            'customer_distribution' => $orderDistribution,
            'period' => $period
        ];
    }

    /**
     * Export data
     */
    public function exportData(string $type, int $period, string $format = 'json'): array
    {
        $startDate = Carbon::now()->subDays($period);
        $data = [];

        switch ($type) {
            case 'overview':
                $data = $this->getSalesAnalytics($period);
                break;
            case 'sales':
                $data = $this->getSalesAnalytics($period);
                break;
            case 'products':
                $data = $this->getProductAnalytics($period);
                break;
            case 'orders':
                $data = $this->getOrderAnalytics($period);
                break;
            case 'payments':
                $data = $this->getPaymentAnalytics($period);
                break;
            case 'customers':
                $data = $this->getCustomerAnalytics($period);
                break;
        }

        return [
            'type' => $type,
            'period' => $period,
            'format' => $format,
            'data' => $data,
            'exported_at' => now()->toISOString(),
            'total_records' => is_array($data) ? count($data) : 0
        ];
    }
}
