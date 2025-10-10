<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Services\WebhookService;
use Illuminate\Console\Command;

class WebhookStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:stats {--period=30} {--provider=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display webhook statistics';

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
        $period = $this->option('period');
        $provider = $this->option('provider');

        $this->info("📊 Webhook Statistics (Last {$period} days)");
        $this->newLine();

        // Get statistics
        $stats = $this->webhookService->getWebhookStatistics($period);

        // Filter by provider if specified
        if ($provider) {
            $stats['webhooks_by_provider'] = collect($stats['webhooks_by_provider'])
                ->filter(fn($count, $prov) => $prov === $provider)
                ->toArray();
        }

        // Display overall statistics
        $this->info('📈 Overall Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Webhooks', $stats['total_webhooks']],
                ['Processed Successfully', $stats['processed_webhooks']],
                ['Failed Processing', $stats['failed_webhooks']],
                ['Success Rate', $stats['total_webhooks'] > 0 ? 
                    round(($stats['processed_webhooks'] / $stats['total_webhooks']) * 100, 2) . '%' : '0%']
            ]
        );

        // Display by provider
        if (!empty($stats['webhooks_by_provider'])) {
            $this->newLine();
            $this->info('🏢 By Provider:');
            $this->table(
                ['Provider', 'Count'],
                collect($stats['webhooks_by_provider'])->map(fn($count, $provider) => [$provider, $count])
            );
        }

        // Display recent webhooks
        if (!empty($stats['recent_webhooks'])) {
            $this->newLine();
            $this->info('🕒 Recent Webhooks:');
            $this->table(
                ['ID', 'Provider', 'Received At', 'Processed', 'Notes'],
                collect($stats['recent_webhooks'])->map(function ($log) {
                    return [
                        $log->id,
                        $log->provider,
                        $log->received_at->format('Y-m-d H:i:s'),
                        $log->processed ? '✅' : '❌',
                        substr($log->processing_notes ?? '', 0, 30) . '...'
                    ];
                })
            );
        }

        // Additional detailed statistics
        $this->newLine();
        $this->info('📋 Detailed Statistics:');

        // Failed webhooks by provider
        $failedByProvider = WebhookLog::where('received_at', '>=', now()->subDays($period))
            ->where('processed', false)
            ->selectRaw('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->get()
            ->pluck('count', 'provider');

        if ($failedByProvider->isNotEmpty()) {
            $this->line('❌ Failed Webhooks by Provider:');
            $this->table(
                ['Provider', 'Failed Count'],
                $failedByProvider->map(fn($count, $provider) => [$provider, $count])
            );
        }

        // Daily webhook count
        $dailyStats = WebhookLog::where('received_at', '>=', now()->subDays($period))
            ->selectRaw('DATE(received_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        if ($dailyStats->isNotEmpty()) {
            $this->newLine();
            $this->line('📅 Daily Webhook Count (Last 7 days):');
            $this->table(
                ['Date', 'Count'],
                $dailyStats->map(fn($stat) => [$stat->date, $stat->count])
            );
        }

        return 0;
    }
}
