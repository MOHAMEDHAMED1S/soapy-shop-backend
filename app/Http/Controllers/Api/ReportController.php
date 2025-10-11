<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function getDashboardOverview(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            $stats = [
                'total_products' => Product::count(),
                'active_products' => Product::where('is_available', true)->count(),
                'low_stock_products' => Product::where('stock_quantity', '<=', 10)->count(),
                'out_of_stock_products' => Product::where('stock_quantity', 0)->count(),
                
                'total_customers' => Customer::count(),
                'active_customers' => Customer::count(), // Remove status filter as it doesn't exist
                'new_customers' => Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'completed_orders' => Order::where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'period_revenue' => Order::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total_amount'),
                'average_order_value' => Order::where('payment_status', 'paid')->avg('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard overview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales analytics
     */
    public function getSalesAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Sales over time
            $salesOverTime = $this->getSalesOverTime($period, $dateFrom, $dateTo);
            
            // Top selling products
            $topProducts = $this->getTopSellingProducts($dateFrom, $dateTo, 10);
            
            // Sales by category
            $salesByCategory = $this->getSalesByCategory($dateFrom, $dateTo);
            
            // Payment methods distribution
            $paymentMethods = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total_amount')
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'sales_over_time' => $salesOverTime,
                    'top_products' => $topProducts,
                    'sales_by_category' => $salesByCategory,
                    'payment_methods' => $paymentMethods,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sales analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Customer acquisition over time
            $customerAcquisition = Customer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top customers by orders
            $topCustomersByOrders = Customer::withCount(['orders'])
                ->orderBy('orders_count', 'desc')
                ->limit(10)
                ->get();

            // Top customers by revenue
            $topCustomersByRevenue = Customer::select('customers.*')
                ->selectRaw('SUM(orders.total) as total_spent')
                ->join('orders', 'customers.id', '=', 'orders.customer_id')
                ->where('orders.payment_status', 'paid')
                ->groupBy('customers.id')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            // Customer distribution by city
            $customersByCity = Customer::selectRaw('city, COUNT(*) as count')
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_acquisition' => $customerAcquisition,
                    'top_customers_by_orders' => $topCustomersByOrders,
                    'top_customers_by_revenue' => $topCustomersByRevenue,
                    'customers_by_city' => $customersByCity,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customer analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product analytics
     */
    public function getProductAnalytics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Product performance
            $productPerformance = Product::select('products.*')
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
                ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0) as total_revenue')
                ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
                ->where(function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                          ->orWhereNull('orders.created_at');
                })
                ->groupBy('products.id')
                ->orderBy('total_sold', 'desc')
                ->limit(20)
                ->get();

            // Low stock alerts
            $lowStockProducts = Product::where('stock_quantity', '<=', 10)
                ->where('stock_quantity', '>', 0)
                ->orderBy('stock_quantity')
                ->get();

            // Out of stock products
            $outOfStockProducts = Product::where('stock_quantity', 0)
                ->get();

            // Products by category
            $productsByCategory = Category::withCount(['products'])
                ->orderBy('products_count', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'product_performance' => $productPerformance,
                    'low_stock_products' => $lowStockProducts,
                    'out_of_stock_products' => $outOfStockProducts,
                    'products_by_category' => $productsByCategory,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get product analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order analytics
     */
    public function getOrderAnalytics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Orders by status
            $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->get();

            // Orders by payment status
            $ordersByPaymentStatus = Order::selectRaw('payment_status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('payment_status')
                ->get();

            // Average order processing time
            $avgProcessingTime = Order::selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->first();

            // Orders over time
            $ordersOverTime = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total_amount')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Recent orders
            $recentOrders = Order::with(['customer'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders_by_status' => $ordersByStatus,
                    'orders_by_payment_status' => $ordersByPaymentStatus,
                    'average_processing_time_hours' => $avgProcessingTime->avg_hours ?? 0,
                    'orders_over_time' => $ordersOverTime,
                    'recent_orders' => $recentOrders,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get order analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get financial reports
     */
    public function getFinancialReports(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Revenue breakdown
            $revenueBreakdown = Order::selectRaw('
                SUM(subtotal) as total_subtotal,
                SUM(tax) as total_tax,
                SUM(shipping) as total_shipping,
                SUM(discount) as total_discount,
                SUM(total) as total_revenue,
                COUNT(*) as total_orders
            ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

            // Monthly revenue trend
            $monthlyRevenue = Order::selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(total) as revenue,
                COUNT(*) as orders_count
            ')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [now()->subYear(), now()])
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

            // Refunds and cancellations
            $refundsAndCancellations = [
                'cancelled_orders' => Order::where('status', 'cancelled')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->count(),
                'cancelled_revenue' => Order::where('status', 'cancelled')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total'),
                'refunded_orders' => Order::where('payment_status', 'refunded')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->count(),
                'refunded_amount' => Order::where('payment_status', 'refunded')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_breakdown' => $revenueBreakdown,
                    'monthly_revenue' => $monthlyRevenue,
                    'refunds_and_cancellations' => $refundsAndCancellations,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get financial reports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive business intelligence report
     */
    public function getBusinessIntelligence(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // Key performance indicators
            $kpis = [
                'conversion_rate' => $this->calculateConversionRate($dateFrom, $dateTo),
                'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
                'average_order_value' => Order::where('payment_status', 'paid')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->avg('total'),
                'repeat_customer_rate' => $this->calculateRepeatCustomerRate($dateFrom, $dateTo),
                'cart_abandonment_rate' => $this->calculateCartAbandonmentRate($dateFrom, $dateTo),
            ];

            // Growth metrics
            $growthMetrics = $this->calculateGrowthMetrics($dateFrom, $dateTo);

            // Seasonal trends
            $seasonalTrends = $this->getSeasonalTrends();

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'growth_metrics' => $growthMetrics,
                    'seasonal_trends' => $seasonalTrends,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get business intelligence: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method: Get sales over time
     */
    private function getSalesOverTime(string $period, string $dateFrom, string $dateTo): array
    {
        $format = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };

        return Order::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(total) as total, COUNT(*) as orders")
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Helper method: Get top selling products
     */
    private function getTopSellingProducts(string $dateFrom, string $dateTo, int $limit): array
    {
        return Product::select('products.*')
            ->selectRaw('SUM(order_items.quantity) as total_sold')
            ->selectRaw('SUM(order_items.quantity * order_items.price) as total_revenue')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('products.id')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Helper method: Get sales by category
     */
    private function getSalesByCategory(string $dateFrom, string $dateTo): array
    {
        return Category::select('categories.name')
            ->selectRaw('SUM(order_items.quantity * order_items.price) as total_revenue')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Helper method: Calculate conversion rate
     */
    private function calculateConversionRate(string $dateFrom, string $dateTo): float
    {
        $totalVisitors = Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $totalOrders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        
        return $totalVisitors > 0 ? ($totalOrders / $totalVisitors) * 100 : 0;
    }

    /**
     * Helper method: Calculate customer lifetime value
     */
    private function calculateCustomerLifetimeValue(): float
    {
        return Customer::select('customers.id')
            ->selectRaw('SUM(orders.total) as total_spent')
            ->join('orders', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('customers.id')
            ->avg('total_spent') ?? 0;
    }

    /**
     * Helper method: Calculate repeat customer rate
     */
    private function calculateRepeatCustomerRate(string $dateFrom, string $dateTo): float
    {
        $totalCustomers = Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $repeatCustomers = Customer::whereHas('orders', function($query) {
            $query->havingRaw('COUNT(*) > 1');
        })->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        
        return $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;
    }

    /**
     * Helper method: Calculate cart abandonment rate
     */
    private function calculateCartAbandonmentRate(string $dateFrom, string $dateTo): float
    {
        // This is a simplified calculation - in a real scenario, you'd track cart creation events
        $totalOrders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $completedOrders = Order::where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        return $totalOrders > 0 ? (($totalOrders - $completedOrders) / $totalOrders) * 100 : 0;
    }

    /**
     * Helper method: Calculate growth metrics
     */
    private function calculateGrowthMetrics(string $dateFrom, string $dateTo): array
    {
        $currentPeriodStart = Carbon::parse($dateFrom);
        $currentPeriodEnd = Carbon::parse($dateTo);
        $periodLength = $currentPeriodStart->diffInDays($currentPeriodEnd);
        
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($periodLength);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();

        $currentRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('total');
            
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('total');

        $revenueGrowth = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        return [
            'revenue_growth' => $revenueGrowth,
            'current_period_revenue' => $currentRevenue,
            'previous_period_revenue' => $previousRevenue,
        ];
    }

    /**
     * Helper method: Get seasonal trends
     */
    private function getSeasonalTrends(): array
    {
        return Order::selectRaw('MONTH(created_at) as month, SUM(total) as revenue, COUNT(*) as orders')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
