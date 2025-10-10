<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\WebhookLog;

class WebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WebhookLog $webhookLog;
    public array $webhookData;
    public string $provider;

    /**
     * Create a new event instance.
     */
    public function __construct(WebhookLog $webhookLog, array $webhookData, string $provider = 'myfatoorah')
    {
        $this->webhookLog = $webhookLog;
        $this->webhookData = $webhookData;
        $this->provider = $provider;
    }
}
