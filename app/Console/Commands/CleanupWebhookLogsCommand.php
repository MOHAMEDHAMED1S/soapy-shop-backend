<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupWebhookLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:cleanup {--days=30} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old webhook logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up webhook logs older than {$days} days...");

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
        }

        // Count logs to be deleted
        $cutoffDate = now()->subDays($days);
        $logsToDelete = WebhookLog::where('received_at', '<', $cutoffDate)->count();

        if ($logsToDelete === 0) {
            $this->info('✅ No webhook logs found to clean up.');
            return 0;
        }

        $this->info("Found {$logsToDelete} webhook logs to delete.");

        if ($dryRun) {
            $this->info('🔍 Would delete the following logs:');
            
            $logs = WebhookLog::where('received_at', '<', $cutoffDate)
                ->orderBy('received_at', 'desc')
                ->limit(10)
                ->get();

            $this->table(
                ['ID', 'Provider', 'Received At', 'Processed', 'Notes'],
                $logs->map(function ($log) {
                    return [
                        $log->id,
                        $log->provider,
                        $log->received_at->format('Y-m-d H:i:s'),
                        $log->processed ? '✅' : '❌',
                        substr($log->processing_notes ?? '', 0, 50) . '...'
                    ];
                })
            );

            if ($logsToDelete > 10) {
                $this->info("... and " . ($logsToDelete - 10) . " more logs.");
            }

            return 0;
        }

        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete {$logsToDelete} webhook logs?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        try {
            DB::beginTransaction();

            $deletedCount = WebhookLog::where('received_at', '<', $cutoffDate)->delete();

            DB::commit();

            $this->info("✅ Successfully deleted {$deletedCount} webhook logs.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error deleting webhook logs: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
