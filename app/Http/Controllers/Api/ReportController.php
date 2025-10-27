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
     * Get dashboard overview
     */
    public function getDashboardOverview(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            // Define paid order statuses
            $paidStatuses = ['paid', 'shipped', 'delivered'];

            $stats = [
                // Products (not filtered by date as they are current inventory)
                'total_products' => Product::count(),
                'active_products' => Product::where('is_available', true)->count(),
                // Inventory: only products with inventory tracking
                'low_stock_products' => Product::where('has_inventory', true)
                    ->where('stock_quantity', '<=', DB::raw('low_stock_threshold'))
                    ->where('stock_quantity', '>', 0)
                    ->count(),
                'out_of_stock_products' => Product::where('has_inventory', true)
                    ->where('stock_quantity', 0)
                    ->count(),
                
                // Customers
                'total_customers' => Customer::count(), // إجمالي العملاء (غير مفلتر بالتاريخ)
                'new_customers' => Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'active_customers' => Customer::whereHas('orders', function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('created_at', [$dateFrom, $dateTo])
                          ->whereIn('status', ['paid', 'shipped', 'delivered']);
                })->count(),
                
                // Orders (filtered by date range)
                'total_orders' => Order::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'pending_orders' => Order::where('status', 'pending')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'awaiting_payment_orders' => Order::where('status', 'awaiting_payment')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'completed_orders' => Order::whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                
                // Revenue
                'total_revenue' => Order::whereIn('status', $paidStatuses)->sum('total_amount'),
                'period_revenue' => Order::whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total_amount'),
                'average_order_value' => Order::whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->avg('total_amount') ?? 0,
                
                // Add date range info for clarity
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
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
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            // Sales over time
            $salesOverTime = $this->getSalesOverTime($period, $dateFrom, $dateTo);
            
            // Top selling products
            $topProducts = $this->getTopSellingProducts($dateFrom, $dateTo, 10);
            
            // Sales by category
            $salesByCategory = $this->getSalesByCategory($dateFrom, $dateTo);
            
            // Payment methods distribution
            $paidStatuses = ['paid', 'shipped', 'delivered'];
            $paymentMethods = Order::select('payments.method')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(orders.total_amount) as total_amount')
                ->join('payments', 'orders.id', '=', 'payments.order_id')
                ->whereIn('orders.status', $paidStatuses)
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->whereNotNull('payments.method')
                ->groupBy('payments.method')
                ->get()
                ->map(function($item) {
                    return [
                        'method' => $item->method ?? 'unknown',
                        'count' => $item->count,
                        'total_amount' => (float) $item->total_amount
                    ];
                });

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
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            // Customer acquisition over time
            $customerAcquisition = Customer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top customers by orders (فقط الطلبات المدفوعة)
            $topCustomersByOrders = Customer::withCount(['orders' => function($query) use ($dateFrom, $dateTo, $paidStatuses) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo])
                      ->whereIn('status', $paidStatuses);
            }])
                ->having('orders_count', '>', 0)
                ->orderBy('orders_count', 'desc')
                ->limit(10)
                ->get();

            // Top customers by revenue (فقط الطلبات المدفوعة)
            $topCustomersByRevenue = Customer::select('customers.id', 'customers.name', 'customers.email', 'customers.phone')
                ->selectRaw('SUM(orders.total_amount) as total_spent')
                ->selectRaw('COUNT(orders.id) as orders_count')
                ->join('orders', 'customers.id', '=', 'orders.customer_id')
                ->whereIn('orders.status', $paidStatuses)
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            // Customer distribution by city (filtered by date range)
            $customersByCity = Customer::selectRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.city')) as city, COUNT(*) as count")
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(address, '$.city')) IS NOT NULL")
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
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            // Product performance (فقط الطلبات المدفوعة)
            $productPerformance = Product::select('products.id', 'products.title', 'products.price', 'products.slug', 'products.stock_quantity', 'products.has_inventory', 'products.category_id')
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
                ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.product_price), 0) as total_revenue')
                ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('orders', function($join) use ($dateFrom, $dateTo, $paidStatuses) {
                    $join->on('order_items.order_id', '=', 'orders.id')
                         ->whereIn('orders.status', $paidStatuses)
                         ->whereBetween('orders.created_at', [$dateFrom, $dateTo]);
                })
                ->groupBy('products.id', 'products.title', 'products.price', 'products.slug', 'products.stock_quantity', 'products.has_inventory', 'products.category_id')
                ->orderBy('total_sold', 'desc')
                ->limit(20)
                ->get();

            // Low stock alerts (فقط المنتجات التي تتبع المخزون)
            $lowStockProducts = Product::where('has_inventory', true)
                ->where('stock_quantity', '<=', DB::raw('low_stock_threshold'))
                ->where('stock_quantity', '>', 0)
                ->orderBy('stock_quantity')
                ->get();

            // Out of stock products (فقط المنتجات التي تتبع المخزون)
            $outOfStockProducts = Product::where('has_inventory', true)
                ->where('stock_quantity', 0)
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
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            // Orders by status
            $ordersByStatus = Order::selectRaw('status, COUNT(*) as count, SUM(total_amount) as total_amount')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->get();

            // Orders by payment status
            $ordersByPaymentStatus = Order::select('payments.status as payment_status')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(orders.total_amount) as total_amount')
                ->leftJoin('payments', 'orders.id', '=', 'payments.order_id')
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->groupBy('payments.status')
                ->get();

            // Average order processing time (من created_at إلى delivered)
            $avgProcessingTime = Order::selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->first();

            // Orders over time (فقط الطلبات المدفوعة)
            $ordersOverTime = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total_amount')
                ->whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Recent orders (filtered by date range)
            $recentOrders = Order::with(['customer'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
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
            
            // Store original dates for response
            $originalDateFrom = $dateFrom;
            $originalDateTo = $dateTo;
            
            // Only validate that dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            // Revenue breakdown (فقط الطلبات المدفوعة)
            $revenueBreakdown = Order::selectRaw('
                SUM(subtotal_amount) as total_subtotal,
                0 as total_tax,
                SUM(shipping_amount) as total_shipping,
                SUM(discount_amount) as total_discount,
                SUM(total_amount) as total_revenue,
                COUNT(*) as total_orders
            ')
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

            // Monthly revenue trend (فقط الطلبات المدفوعة)
            $monthlyRevenue = Order::selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(total_amount) as revenue,
                COUNT(*) as orders_count
            ')
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
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
                    ->sum('total_amount'),
                'refunded_orders' => Order::whereHas('payment', function($query) {
                        $query->where('status', 'refunded');
                    })
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->count(),
                'refunded_amount' => Order::whereHas('payment', function($query) {
                        $query->where('status', 'refunded');
                    })
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'revenue_breakdown' => $revenueBreakdown,
                    'monthly_revenue' => $monthlyRevenue,
                    'refunds_and_cancellations' => $refundsAndCancellations,
                    'date_range' => [
                        'from' => $originalDateFrom,
                        'to' => $originalDateTo,
                        'applied_from' => $dateFrom,
                        'applied_to' => $dateTo
                    ]
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
            
            // Validate dates - ensure dateTo is not in the future
            $currentDate = now()->format('Y-m-d');
            if ($dateTo > $currentDate) {
                $dateTo = $currentDate;
            }
            
            // Ensure dateFrom is not after dateTo
            if ($dateFrom > $dateTo) {
                $dateFrom = $dateTo;
            }

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            // Key performance indicators
            $kpis = [
                'conversion_rate' => $this->calculateConversionRate($dateFrom, $dateTo),
                'customer_lifetime_value' => $this->calculateCustomerLifetimeValue($dateFrom, $dateTo),
                'average_order_value' => Order::whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->avg('total_amount') ?? 0,
                'repeat_customer_rate' => $this->calculateRepeatCustomerRate($dateFrom, $dateTo),
                'cart_abandonment_rate' => $this->calculateCartAbandonmentRate($dateFrom, $dateTo),
            ];

            // Growth metrics
            $growthMetrics = $this->calculateGrowthMetrics($dateFrom, $dateTo);

            // Seasonal trends (فقط الطلبات المدفوعة)
            $seasonalTrends = Order::selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as orders')
                ->whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();

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

        $paidStatuses = ['paid', 'shipped', 'delivered'];

        return Order::selectRaw("DATE_FORMAT(created_at, '{$format}') as period, SUM(total_amount) as total, COUNT(*) as orders")
            ->whereIn('status', $paidStatuses)
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
        $paidStatuses = ['paid', 'shipped', 'delivered'];

        return Product::select('products.id', 'products.title', 'products.price')
            ->selectRaw('SUM(order_items.quantity) as total_sold')
            ->selectRaw('SUM(order_items.quantity * order_items.product_price) as total_revenue')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('products.id', 'products.title', 'products.price')
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
        $paidStatuses = ['paid', 'shipped', 'delivered'];

        return Category::select('categories.name')
            ->selectRaw('SUM(order_items.quantity * order_items.product_price) as total_revenue')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Helper method: Calculate conversion rate
     * معدل التحويل = (عدد الطلبات المدفوعة / عدد العملاء الجدد) * 100
     */
    private function calculateConversionRate(string $dateFrom, string $dateTo): float
    {
        $newCustomers = Customer::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        
        $paidStatuses = ['paid', 'shipped', 'delivered'];
        $paidOrders = Order::whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        return $newCustomers > 0 ? round(($paidOrders / $newCustomers) * 100, 2) : 0;
    }

    /**
     * Helper method: Calculate customer lifetime value
     * متوسط القيمة الدائمة للعميل
     */
    private function calculateCustomerLifetimeValue(string $dateFrom = null, string $dateTo = null): float
    {
        $paidStatuses = ['paid', 'shipped', 'delivered'];

        $query = Customer::select('customers.id')
            ->selectRaw('SUM(orders.total_amount) as total_spent')
            ->join('orders', 'customers.id', '=', 'orders.customer_id')
            ->whereIn('orders.status', $paidStatuses);
            
        if ($dateFrom && $dateTo) {
            $query->whereBetween('orders.created_at', [$dateFrom, $dateTo]);
        }
        
        return round($query->groupBy('customers.id')->avg('total_spent') ?? 0, 2);
    }

    /**
     * Helper method: Calculate repeat customer rate
     * معدل العملاء المتكررين = (عدد العملاء الذين لديهم أكثر من طلب / إجمالي العملاء) * 100
     */
    private function calculateRepeatCustomerRate(string $dateFrom, string $dateTo): float
    {
        $paidStatuses = ['paid', 'shipped', 'delivered'];
        
        // إجمالي العملاء الذين لديهم طلبات مدفوعة في الفترة
        $totalCustomersWithOrders = Customer::whereHas('orders', function($query) use ($dateFrom, $dateTo, $paidStatuses) {
            $query->whereIn('status', $paidStatuses)
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();
        
        // العملاء الذين لديهم أكثر من طلب مدفوع في الفترة
        $repeatCustomers = Customer::withCount(['orders' => function($query) use ($dateFrom, $dateTo, $paidStatuses) {
            $query->whereIn('status', $paidStatuses)
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
        }])
        ->having('orders_count', '>', 1)
        ->count();
        
        return $totalCustomersWithOrders > 0 ? round(($repeatCustomers / $totalCustomersWithOrders) * 100, 2) : 0;
    }

    /**
     * Helper method: Calculate cart abandonment rate
     * معدل التخلي عن السلة = (الطلبات المعلقة / إجمالي الطلبات) * 100
     */
    private function calculateCartAbandonmentRate(string $dateFrom, string $dateTo): float
    {
        $totalOrders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $abandonedOrders = Order::whereIn('status', ['pending', 'awaiting_payment'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        return $totalOrders > 0 ? round(($abandonedOrders / $totalOrders) * 100, 2) : 0;
    }

    /**
     * Helper method: Calculate growth metrics
     */
    private function calculateGrowthMetrics(string $dateFrom, string $dateTo): array
    {
        $currentPeriodStart = Carbon::parse($dateFrom);
        $currentPeriodEnd = Carbon::parse($dateTo);
        $periodLength = $currentPeriodStart->diffInDays($currentPeriodEnd);
        
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($periodLength + 1);
        $previousPeriodEnd = $currentPeriodStart->copy()->subDay();

        $paidStatuses = ['paid', 'shipped', 'delivered'];

        $currentRevenue = Order::whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('total_amount');
            
        $previousRevenue = Order::whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$previousPeriodStart->format('Y-m-d'), $previousPeriodEnd->format('Y-m-d')])
            ->sum('total_amount');

        $revenueGrowth = $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0;

        return [
            'revenue_growth' => $revenueGrowth,
            'current_period_revenue' => (float) $currentRevenue,
            'previous_period_revenue' => (float) $previousRevenue,
            'current_period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'previous_period' => [
                'from' => $previousPeriodStart->format('Y-m-d'),
                'to' => $previousPeriodEnd->format('Y-m-d')
            ]
        ];
    }

    /**
     * Helper method: Get seasonal trends
     */
    public function getSeasonalTrends(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subYear()->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            $paidStatuses = ['paid', 'shipped', 'delivered'];

            $seasonalData = Order::selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as orders')
                ->whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'seasonal_trends' => $seasonalData,
                    'period' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get seasonal trends: ' . $e->getMessage()
            ], 500);
        }
    }
}
