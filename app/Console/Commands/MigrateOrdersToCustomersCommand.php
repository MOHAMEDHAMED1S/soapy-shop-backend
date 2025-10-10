<?php

namespace App\Console\Commands;

use App\Services\CustomerService;
use Illuminate\Console\Command;

class MigrateOrdersToCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:migrate-orders {--dry-run : Show what would be migrated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing orders to customers';

    protected CustomerService $customerService;

    /**
     * Create a new command instance.
     */
    public function __construct(CustomerService $customerService)
    {
        parent::__construct();
        $this->customerService = $customerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Migrating existing orders to customers...');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        try {
            if ($dryRun) {
                $this->showMigrationPreview();
            } else {
                $results = $this->customerService->migrateExistingOrdersToCustomers();
                $this->displayResults($results);
            }

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show migration preview
     */
    protected function showMigrationPreview()
    {
        $ordersWithoutCustomer = \App\Models\Order::whereNull('customer_id')->count();
        $uniquePhones = \App\Models\Order::whereNull('customer_id')
            ->distinct('customer_phone')
            ->count('customer_phone');

        $this->info("📊 Migration Preview:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Orders without customer', $ordersWithoutCustomer],
                ['Unique phone numbers', $uniquePhones],
                ['New customers to create', $uniquePhones],
            ]
        );

        $this->newLine();
        $this->info("📋 Sample orders to migrate:");
        
        $sampleOrders = \App\Models\Order::whereNull('customer_id')
            ->with(['orderItems.product'])
            ->limit(5)
            ->get();

        $this->table(
            ['Order Number', 'Customer Name', 'Phone', 'Total Amount', 'Items'],
            $sampleOrders->map(function ($order) {
                return [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->total_amount . ' KWD',
                    $order->orderItems->count()
                ];
            })
        );
    }

    /**
     * Display migration results
     */
    protected function displayResults(array $results)
    {
        $this->newLine();
        $this->info("✅ Migration completed successfully!");
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Orders processed', $results['processed']],
                ['Customers created', $results['created']],
                ['Customers updated', $results['updated']],
                ['Errors', $results['errors']],
            ]
        );

        if ($results['errors'] > 0) {
            $this->warn("⚠️  {$results['errors']} orders had errors during migration. Check the logs for details.");
        }

        $this->newLine();
        $this->info("🎉 Migration completed! All orders now have associated customers.");
    }
}