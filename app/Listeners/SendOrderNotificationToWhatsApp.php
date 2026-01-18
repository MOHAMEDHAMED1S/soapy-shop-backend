<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\TwilioWhatsAppService;
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

            $twilio = app(TwilioWhatsAppService::class);

            Log::info('OrderPaid: sending Twilio WhatsApp notifications', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            // Notify customer about order confirmation
            try {
                $twilio->notifyCustomerOrderCreated($order);
                Log::info('OrderPaid: customer notification sent', [
                    'order_id' => $order->id,
                    'phone' => $order->customer_phone,
                ]);
            } catch (\Exception $e) {
                Log::error('OrderPaid: customer notification failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Notify admins
            try {
                $twilio->notifyAdminNewPaidOrder($order);
            } catch (\Exception $e) {
                Log::error('OrderPaid: admin notification failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Notify delivery team
            try {
                $twilio->notifyDeliveryNewPaidOrder($order);
            } catch (\Exception $e) {
                Log::error('OrderPaid: delivery notification failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('OrderPaid: WhatsApp notifications failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}