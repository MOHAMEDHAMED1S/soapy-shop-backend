<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;

class DashboardStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:stats {--period=30} {--type=overview}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display dashboard statistics';

    protected AnalyticsService $analyticsService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $type = $this->option('type');

        $this->info("📊 Dashboard Statistics (Last {$period} days)");
        $this->newLine();

        try {
            switch ($type) {
                case 'overview':
                    $this->displayOverview($period);
                    break;
                case 'sales':
                    $this->displaySalesAnalytics($period);
                    break;
                case 'products':
                    $this->displayProductAnalytics($period);
                    break;
                case 'orders':
                    $this->displayOrderAnalytics($period);
                    break;
                case 'payments':
                    $this->displayPaymentAnalytics($period);
                    break;
                case 'customers':
                    $this->displayCustomerAnalytics($period);
                    break;
                default:
                    $this->displayOverview($period);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error retrieving dashboard statistics: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Display overview statistics
     */
    protected function displayOverview(int $period)
    {
        $this->info('📈 Overview Statistics:');
        
        // Basic stats
        $stats = [
            'Total Orders' => \App\Models\Order::count(),
            'Total Products' => \App\Models\Product::count(),
            'Total Categories' => \App\Models\Category::count(),
            'Total Revenue' => \App\Models\Order::where('status', 'paid')->sum('total_amount') . ' KWD',
            'Pending Orders' => \App\Models\Order::where('status', 'pending')->count(),
            'Unread Notifications' => \App\Models\AdminNotification::whereNull('read_at')->count(),
        ];

        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn($value, $key) => [$key, $value])
        );

        // Period stats
        $this->newLine();
        $this->info("📅 Last {$period} Days:");
        
        $startDate = now()->subDays($period);
        $periodStats = [
            'Orders Count' => \App\Models\Order::where('created_at', '>=', $startDate)->count(),
            'Revenue' => \App\Models\Order::where('status', 'paid')->where('created_at', '>=', $startDate)->sum('total_amount') . ' KWD',
            'New Products' => \App\Models\Product::where('created_at', '>=', $startDate)->count(),
            'Completed Orders' => \App\Models\Order::where('status', 'delivered')->where('created_at', '>=', $startDate)->count(),
        ];

        $this->table(
            ['Metric', 'Value'],
            collect($periodStats)->map(fn($value, $key) => [$key, $value])
        );
    }

    /**
     * Display sales analytics
     */
    protected function displaySalesAnalytics(int $period)
    {
        $this->info('💰 Sales Analytics:');
        
        $analytics = $this->analyticsService->getSalesAnalytics($period);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Revenue', $analytics['summary']['total_revenue'] . ' KWD'],
                ['Total Orders', $analytics['summary']['total_orders']],
                ['Average Order Value', $analytics['summary']['average_order_value'] . ' KWD'],
            ]
        );

        $this->newLine();
        $this->info('📊 Daily Sales (Last 7 days):');
        
        $recentSales = $analytics['sales_data']->take(7);
        $this->table(
            ['Date', 'Orders', 'Revenue'],
            $recentSales->map(fn($sale) => [
                $sale->date,
                $sale->orders_count,
                $sale->revenue . ' KWD'
            ])
        );
    }

    /**
     * Display product analytics
     */
    protected function displayProductAnalytics(int $period)
    {
        $this->info('🛍️ Product Analytics:');
        
        $analytics = $this->analyticsService->getProductAnalytics($period, 10);
        
        $this->info('🏆 Top Products by Revenue:');
        $this->table(
            ['Product', 'Revenue', 'Quantity', 'Orders'],
            $analytics['top_products']->take(5)->map(fn($product) => [
                $product->title,
                $product->total_revenue . ' KWD',
                $product->total_quantity,
                $product->orders_count
            ])
        );

        $this->newLine();
        $this->info('📁 Category Performance:');
        $this->table(
            ['Category', 'Products', 'Revenue', 'Orders'],
            $analytics['category_performance']->take(5)->map(fn($category) => [
                $category->name,
                $category->products_count,
                $category->total_revenue . ' KWD',
                $category->orders_count
            ])
        );
    }

    /**
     * Display order analytics
     */
    protected function displayOrderAnalytics(int $period)
    {
        $this->info('📦 Order Analytics:');
        
        $analytics = $this->analyticsService->getOrderAnalytics($period);
        
        $this->info('📊 Order Status Distribution:');
        $this->table(
            ['Status', 'Count'],
            collect($analytics['status_distribution'])->map(fn($count, $status) => [$status, $count])
        );

        $this->newLine();
        $this->info('💰 Order Value Distribution:');
        $this->table(
            ['Range', 'Count'],
            $analytics['value_distribution']->map(fn($item) => [$item->range, $item->count])
        );
    }

    /**
     * Display payment analytics
     */
    protected function displayPaymentAnalytics(int $period)
    {
        $this->info('💳 Payment Analytics:');
        
        $analytics = $this->analyticsService->getPaymentAnalytics($period);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Success Rate', $analytics['success_rate'] . '%'],
                ['Total Payments', $analytics['total_payments']],
                ['Successful Payments', $analytics['successful_payments']],
            ]
        );

        $this->newLine();
        $this->info('💳 Payment Method Distribution:');
        $this->table(
            ['Method', 'Count', 'Amount'],
            $analytics['method_distribution']->map(fn($method) => [
                $method->payment_method,
                $method->count,
                $method->total_amount . ' KWD'
            ])
        );
    }

    /**
     * Display customer analytics
     */
    protected function displayCustomerAnalytics(int $period)
    {
        $this->info('👥 Customer Analytics:');
        
        $analytics = $this->analyticsService->getCustomerAnalytics($period);
        
        $this->info('🏆 Top Customers:');
        $this->table(
            ['Customer', 'Orders', 'Total Spent', 'Avg Order Value'],
            $analytics['top_customers']->take(5)->map(fn($customer) => [
                $customer->customer_name,
                $customer->orders_count,
                $customer->total_spent . ' KWD',
                round($customer->avg_order_value, 3) . ' KWD'
            ])
        );

        $this->newLine();
        $this->info('📊 Customer Distribution:');
        $this->table(
            ['Type', 'Count'],
            collect($analytics['customer_distribution'])->map(fn($count, $type) => [$type, $count])
        );
    }
}
