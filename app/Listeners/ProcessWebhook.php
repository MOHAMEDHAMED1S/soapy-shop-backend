<?php

namespace App\Listeners;

use App\Events\WebhookReceived;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    protected WebhookService $webhookService;

    /**
     * Create the event listener.
     */
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        try {
            Log::info('Processing webhook asynchronously', [
                'webhook_log_id' => $event->webhookLog->id,
                'provider' => $event->provider
            ]);

            // Process the webhook
            $result = $this->webhookService->processMyFatoorahWebhook(
                $event->webhookData,
                $event->webhookLog
            );

            if ($result['success']) {
                Log::info('Webhook processed successfully', [
                    'webhook_log_id' => $event->webhookLog->id,
                    'result' => $result
                ]);
            } else {
                Log::error('Webhook processing failed', [
                    'webhook_log_id' => $event->webhookLog->id,
                    'error' => $result['error']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Webhook listener error', [
                'webhook_log_id' => $event->webhookLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark webhook as failed
            $event->webhookLog->update([
                'processed' => false,
                'processing_notes' => 'Listener error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(WebhookReceived $event, $exception): void
    {
        Log::error('Webhook processing job failed', [
            'webhook_log_id' => $event->webhookLog->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Mark webhook as failed
        $event->webhookLog->update([
            'processed' => false,
            'processing_notes' => 'Job failed: ' . $exception->getMessage()
        ]);
    }
}
