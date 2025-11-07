<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationToWhatsApp
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            $order = $event->order;

            // Ensure order items are available for message formatting
            if (!$order->relationLoaded('orderItems')) {
                $order->load('orderItems');
            }

            $whatsapp = app(WhatsAppService::class);

            Log::info('OrderPaid: sending WhatsApp notifications', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            // Notify admins
            $whatsapp->notifyAdminNewPaidOrder($order);

            // Notify delivery team if enabled
            $whatsapp->notifyDeliveryNewPaidOrder($order);
        } catch (\Exception $e) {
            Log::error('OrderPaid: WhatsApp notifications failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}