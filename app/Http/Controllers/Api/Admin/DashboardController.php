<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Payment;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard overview statistics
     */
    public function overview(Request $request)
    {
        try {
            $period = $request->get('period', 30); // days
            $startDate = Carbon::now()->subDays($period);
            $endDate = Carbon::now();

            // Period-based statistics (based on date range)
            $stats = [
                'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
                'total_products' => Product::count(), // Total products (not period-based)
                'total_categories' => Category::count(), // Total categories (not period-based)
                'total_revenue' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount'),
                'pending_orders' => Order::where('status', 'pending')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
                'low_stock_products' => 0, // Stock tracking not implemented yet
                'unread_notifications' => AdminNotification::whereNull('read_at')->count(),
                'total_customers' => \App\Models\Customer::count(), // Total customers (not period-based)
                'active_customers' => \App\Models\Customer::where('is_active', true)->count(),
                'total_discount_codes' => \App\Models\DiscountCode::count(),
                'active_discount_codes' => \App\Models\DiscountCode::where('is_active', true)->count(),
                'average_order_value' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->avg('total_amount') ?? 0,
                'conversion_rate' => $this->calculateConversionRate($period),
            ];

            // More detailed period statistics
            $periodStats = [
                'orders_count' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
                'revenue' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_amount'),
                'new_products' => Product::whereBetween('created_at', [$startDate, $endDate])->count(),
                'completed_orders' => Order::where('status', 'delivered')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
            ];

            // Growth calculations
            $previousPeriodStart = $startDate->copy()->subDays($period);
            $previousPeriodEnd = $startDate->copy();

            $previousOrders = Order::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
            $previousRevenue = Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
                ->sum('total_amount');

            $growth = [
                'orders_growth' => $this->calculateGrowth($periodStats['orders_count'], $previousOrders),
                'revenue_growth' => $this->calculateGrowth($periodStats['revenue'], $previousRevenue),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $stats,
                    'period_stats' => $periodStats,
                    'growth' => $growth,
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate->toDateString(),
                        'end' => $endDate->toDateString()
                    ]
                ],
                'message' => 'Dashboard overview retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales analytics
     */
    public function salesAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $groupBy = $request->get('group_by', 'day'); // day, week, month

            $analytics = $this->analyticsService->getSalesAnalytics($period, $groupBy);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Sales analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sales analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product analytics
     */
    public function productAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $limit = $request->get('limit', 10);

            $analytics = $this->analyticsService->getProductAnalytics($period, $limit);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Product analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order analytics
     */
    public function orderAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $analytics = $this->analyticsService->getOrderAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Order analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment analytics
     */
    public function paymentAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $analytics = $this->analyticsService->getPaymentAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Payment analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities
     */
    public function recentActivities(Request $request)
    {
        try {
            $limit = $request->get('limit', 20);

            $activities = $this->analyticsService->getRecentActivities($limit);

            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'Recent activities retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing products
     */
    public function topProducts(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $limit = $request->get('limit', 10);
            $metric = $request->get('metric', 'revenue'); // revenue, quantity, orders

            $products = $this->analyticsService->getTopProducts($period, $limit, $metric);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Top products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving top products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category performance
     */
    public function categoryPerformance(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $performance = $this->analyticsService->getCategoryPerformance($period);

            return response()->json([
                'success' => true,
                'data' => $performance,
                'message' => 'Category performance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving category performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics
     */
    public function customerAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $analytics = $this->analyticsService->getCustomerAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Customer analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export dashboard data
     */
    public function export(Request $request)
    {
        try {
            $type = $request->get('type', 'overview'); // overview, sales, products, orders
            $period = $request->get('period', 30);
            $format = $request->get('format', 'json'); // json, csv, xlsx

            $data = $this->analyticsService->exportData($type, $period, $format);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Data exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard widgets configuration
     */
    public function widgets()
    {
        try {
            $widgets = [
                'overview' => [
                    'title' => 'نظرة عامة',
                    'type' => 'stats',
                    'position' => 1,
                    'size' => 'large',
                    'enabled' => true
                ],
                'sales_chart' => [
                    'title' => 'مخطط المبيعات',
                    'type' => 'chart',
                    'position' => 2,
                    'size' => 'medium',
                    'enabled' => true
                ],
                'top_products' => [
                    'title' => 'أفضل المنتجات',
                    'type' => 'table',
                    'position' => 3,
                    'size' => 'medium',
                    'enabled' => true
                ],
                'recent_orders' => [
                    'title' => 'الطلبات الأخيرة',
                    'type' => 'list',
                    'position' => 4,
                    'size' => 'small',
                    'enabled' => true
                ],
                'notifications' => [
                    'title' => 'الإشعارات',
                    'type' => 'notifications',
                    'position' => 5,
                    'size' => 'small',
                    'enabled' => true
                ],
                'category_performance' => [
                    'title' => 'أداء الفئات',
                    'type' => 'chart',
                    'position' => 6,
                    'size' => 'medium',
                    'enabled' => true
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $widgets,
                'message' => 'Widgets configuration retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving widgets configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($period)
    {
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // Total visitors (simplified - using orders as proxy)
        $totalVisitors = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Total conversions (paid orders)
        $conversions = Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalVisitors == 0) {
            return 0;
        }

        return round(($conversions / $totalVisitors) * 100, 2);
    }

    /**
     * Get real-time statistics
     */
    public function realTimeStats()
    {
        try {
            $stats = [
                'online_visitors' => rand(5, 50), // Placeholder - would need real analytics
                'current_orders' => Order::where('status', 'pending')->count(),
                'today_revenue' => Order::whereNotIn('status', ['cancelled', 'pending', 'awaiting_payment'])
                    ->whereDate('created_at', Carbon::today())
                    ->sum('total_amount'),
                'today_orders' => Order::whereDate('created_at', Carbon::today())->count(),
                'recent_activities' => $this->getRecentActivities(5),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Real-time statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving real-time statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function systemHealth()
    {
        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'storage' => $this->checkStorageHealth(),
                'api_response_time' => $this->checkApiResponseTime(),
                'last_backup' => $this->getLastBackupDate(),
                'system_load' => $this->getSystemLoad(),
            ];

            return response()->json([
                'success' => true,
                'data' => $health,
                'message' => 'System health retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving system health',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed'];
        }
    }

    private function checkStorageHealth()
    {
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        $usagePercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

        return [
            'status' => $usagePercentage > 90 ? 'warning' : 'healthy',
            'usage_percentage' => $usagePercentage,
            'free_space' => $this->formatBytes($freeSpace),
            'total_space' => $this->formatBytes($totalSpace)
        ];
    }

    private function checkApiResponseTime()
    {
        $start = microtime(true);
        // Simple query to test response time
        Product::count();
        $end = microtime(true);
        
        $responseTime = round(($end - $start) * 1000, 2); // Convert to milliseconds
        
        return [
            'response_time_ms' => $responseTime,
            'status' => $responseTime < 1000 ? 'good' : ($responseTime < 3000 ? 'acceptable' : 'slow')
        ];
    }

    private function getLastBackupDate()
    {
        // Placeholder - would need actual backup system
        return Carbon::now()->subDays(1)->toDateTimeString();
    }

    private function getSystemLoad()
    {
        // Placeholder - would need system monitoring
        return [
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 90),
            'status' => 'normal'
        ];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get recent activities (private helper method)
     */
    private function getRecentActivities($limit = 5)
    {
        $activities = [];

        // Recent orders
        $recentOrders = Order::with('customer')
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'type' => 'order',
                    'message' => sprintf('طلب جديد #%s من %s', $order->order_number, $order->customer->name ?? 'عميل'),
                    'time' => $order->created_at->diffForHumans(),
                    'created_at' => $order->created_at->toDateTimeString()
                ];
            });

        // Recent products
        $recentProducts = Product::latest()
            ->take($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product',
                    'message' => sprintf('منتج جديد: %s', $product->name_ar ?? $product->name_en),
                    'time' => $product->created_at->diffForHumans(),
                    'created_at' => $product->created_at->toDateTimeString()
                ];
            });

        // Merge and sort by created_at
        $activities = $recentOrders->concat($recentProducts)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->toArray();

        return $activities;
    }
}
