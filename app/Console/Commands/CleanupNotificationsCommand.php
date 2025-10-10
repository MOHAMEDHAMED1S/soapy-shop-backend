<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:cleanup {--days=30} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notifications';

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
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up notifications older than {$days} days...");

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
        }

        try {
            $deletedCount = $this->notificationService->cleanupOldNotifications($days);

            if ($dryRun) {
                $this->info("🔍 Would delete {$deletedCount} old notifications");
            } else {
                $this->info("✅ Successfully deleted {$deletedCount} old notifications");
            }

        } catch (\Exception $e) {
            $this->error('❌ Error cleaning up notifications: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
