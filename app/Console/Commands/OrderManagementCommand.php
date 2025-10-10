<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class OrderManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:manage {action} {--days=7} {--status=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage orders - cleanup, status updates, notifications';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $days = $this->option('days');
        $status = $this->option('status');
        $dryRun = $this->option('dry-run');

        $this->info("🔧 Order Management: {$action}");

        try {
            switch ($action) {
                case 'cleanup':
                    $this->cleanupOldOrders($days, $dryRun);
                    break;
                case 'status-report':
                    $this->generateStatusReport();
                    break;
                case 'pending-reminder':
                    $this->sendPendingReminders($days);
                    break;
                case 'overdue-check':
                    $this->checkOverdueOrders($days);
                    break;
                case 'statistics':
                    $this->showOrderStatistics($days);
                    break;
                default:
                    $this->error("❌ Unknown action: {$action}");
                    $this->info("Available actions: cleanup, status-report, pending-reminder, overdue-check, statistics");
                    return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Cleanup old orders
     */
    protected function cleanupOldOrders(int $days, bool $dryRun)
    {
        $this->info("🧹 Cleaning up orders older than {$days} days...");

        $thresholdDate = Carbon::now()->subDays($days);
        $query = Order::where('created_at', '<', $thresholdDate)
            ->where('status', 'cancelled');

        if ($dryRun) {
            $count = $query->count();
            $this->info("🔍 DRY RUN MODE - No data will be deleted");
            $this->info("🔍 Would delete {$count} cancelled orders");
        } else {
            $deletedCount = $query->delete();
            $this->info("✅ Successfully deleted {$deletedCount} cancelled orders");
        }
    }

    /**
     * Generate status report
     */
    protected function generateStatusReport()
    {
        $this->info("📊 Order Status Report");
        $this->newLine();

        $statusCounts = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $this->table(
            ['Status', 'Count'],
            $statusCounts->map(fn($count, $status) => [$status, $count])
        );

        $this->newLine();
        $this->info("📈 Recent Orders (Last 7 days):");
        
        $recentOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $this->table(
            ['Status', 'Count (7 days)'],
            $recentOrders->map(fn($count, $status) => [$status, $count])
        );
    }

    /**
     * Send pending reminders
     */
    protected function sendPendingReminders(int $days)
    {
        $this->info("📧 Sending pending order reminders...");

        $pendingOrders = Order::where('status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subDays($days))
            ->get();

        $this->info("Found {$pendingOrders->count()} pending orders older than {$days} days");

        foreach ($pendingOrders as $order) {
            $this->notificationService->createNotification(
                'pending_order_reminder',
                'تذكير طلب معلق',
                "الطلب رقم {$order->order_number} لا يزال في حالة انتظار منذ {$days} أيام",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'days_pending' => $days
                ],
                'high'
            );

            $this->line("📧 Sent reminder for order: {$order->order_number}");
        }

        $this->info("✅ Sent {$pendingOrders->count()} reminders");
    }

    /**
     * Check overdue orders
     */
    protected function checkOverdueOrders(int $days)
    {
        $this->info("⏰ Checking for overdue orders...");

        $overdueOrders = Order::where('status', 'paid')
            ->where('created_at', '<=', Carbon::now()->subDays($days))
            ->get();

        $this->info("Found {$overdueOrders->count()} paid orders older than {$days} days");

        foreach ($overdueOrders as $order) {
            $this->notificationService->createNotification(
                'overdue_order',
                'طلب متأخر',
                "الطلب رقم {$order->order_number} تم دفعه منذ {$days} أيام ولم يتم شحنه بعد",
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'days_overdue' => $days
                ],
                'urgent'
            );

            $this->line("⚠️  Overdue order: {$order->order_number}");
        }

        $this->info("✅ Checked {$overdueOrders->count()} orders");
    }

    /**
     * Show order statistics
     */
    protected function showOrderStatistics(int $days)
    {
        $this->info("📊 Order Statistics (Last {$days} days)");
        $this->newLine();

        $startDate = Carbon::now()->subDays($days);

        // Overall statistics
        $stats = [
            'Total Orders' => Order::count(),
            'Recent Orders' => Order::where('created_at', '>=', $startDate)->count(),
            'Total Revenue' => Order::where('status', 'paid')->sum('total_amount') . ' KWD',
            'Recent Revenue' => Order::where('status', 'paid')->where('created_at', '>=', $startDate)->sum('total_amount') . ' KWD',
            'Pending Orders' => Order::where('status', 'pending')->count(),
            'Paid Orders' => Order::where('status', 'paid')->count(),
            'Shipped Orders' => Order::where('status', 'shipped')->count(),
            'Delivered Orders' => Order::where('status', 'delivered')->count(),
            'Cancelled Orders' => Order::where('status', 'cancelled')->count(),
        ];

        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn($value, $key) => [$key, $value])
        );

        $this->newLine();
        $this->info("📈 Daily Orders (Last 7 days):");
        
        $dailyOrders = Order::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->table(
            ['Date', 'Orders', 'Revenue'],
            $dailyOrders->map(fn($day) => [
                $day->date,
                $day->count,
                $day->revenue . ' KWD'
            ])
        );

        $this->newLine();
        $this->info("🏆 Top Customers (Last {$days} days):");
        
        $topCustomers = Order::where('created_at', '>=', $startDate)
            ->selectRaw('customer_name, customer_phone, COUNT(*) as orders_count, SUM(total_amount) as total_spent')
            ->groupBy('customer_name', 'customer_phone')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get();

        $this->table(
            ['Customer', 'Orders', 'Total Spent'],
            $topCustomers->map(fn($customer) => [
                $customer->customer_name,
                $customer->orders_count,
                $customer->total_spent . ' KWD'
            ])
        );

        $this->newLine();
        $this->info("🛍️ Top Products (Last {$days} days):");
        
        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', 'paid')
            ->selectRaw('products.title, SUM(order_items.quantity) as total_quantity, SUM(order_items.product_price * order_items.quantity) as total_revenue')
            ->groupBy('products.id', 'products.title')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        $this->table(
            ['Product', 'Quantity', 'Revenue'],
            $topProducts->map(fn($product) => [
                $product->title,
                $product->total_quantity,
                $product->total_revenue . ' KWD'
            ])
        );
    }
}
