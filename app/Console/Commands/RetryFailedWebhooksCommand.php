<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Services\WebhookService;
use Illuminate\Console\Command;

class RetryFailedWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:retry-failed {--limit=10} {--provider=myfatoorah}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry processing failed webhooks';

    protected WebhookService $webhookService;

    /**
     * Create a new command instance.
     */
    public function __construct(WebhookService $webhookService)
    {
        parent::__construct();
        $this->webhookService = $webhookService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $provider = $this->option('provider');

        $this->info("Retrying failed webhooks for provider: {$provider} (limit: {$limit})");

        // Get failed webhooks
        $failedWebhooks = WebhookLog::where('provider', $provider)
            ->where('processed', false)
            ->orderBy('received_at', 'asc')
            ->limit($limit)
            ->get();

        if ($failedWebhooks->isEmpty()) {
            $this->info('✅ No failed webhooks found to retry.');
            return 0;
        }

        $this->info("Found {$failedWebhooks->count()} failed webhooks to retry.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($failedWebhooks as $webhook) {
            $this->line("Retrying webhook ID: {$webhook->id} (received at: {$webhook->received_at})");

            try {
                $result = $this->webhookService->retryWebhook($webhook);

                if ($result['success']) {
                    $this->info("  ✅ Success: {$result['message']}");
                    $successCount++;
                } else {
                    $this->error("  ❌ Failed: {$result['error']}");
                    $failureCount++;
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Exception: {$e->getMessage()}");
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("📊 Retry Summary:");
        $this->info("  ✅ Successful: {$successCount}");
        $this->info("  ❌ Failed: {$failureCount}");
        $this->info("  📝 Total processed: " . ($successCount + $failureCount));

        return 0;
    }
}
