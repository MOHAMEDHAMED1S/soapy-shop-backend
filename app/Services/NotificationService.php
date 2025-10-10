<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class NotificationService
{
    /**
     * Create a new notification
     */
    public function createNotification(string $type, string $title, string $message, array $data = [], string $priority = 'medium')
    {
        try {
            $notification = AdminNotification::create([
                'type' => $type,
                'payload' => [
                    'title' => $title,
                    'message' => $message,
                    'priority' => $priority,
                    'data' => $data,
                    'created_at' => now()->toISOString()
                ],
                'created_at' => now()
            ]);

            // Fire notification created event
            event(new NotificationCreated($notification));

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'type' => $type,
                'priority' => $priority
            ]);

            return $notification;

        } catch (\Exception $e) {
            Log::error('Error creating notification', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create order-related notifications
     */
    public function createOrderNotification(Order $order, string $event, array $additionalData = [])
    {
        $notifications = [
            'order_created' => [
                'title' => 'طلب جديد تم إنشاؤه',
                'message' => "تم إنشاء طلب جديد رقم {$order->order_number} بقيمة {$order->total_amount} {$order->currency}",
                'priority' => 'medium'
            ],
            'order_paid' => [
                'title' => 'طلب جديد',
                'message' => "تم دفع الطلب رقم {$order->order_number} بنجاح",
                'priority' => 'high'
            ],
            'order_shipped' => [
                'title' => 'تم شحن الطلب',
                'message' => "تم شحن الطلب رقم {$order->order_number}",
                'priority' => 'medium'
            ],
            'order_delivered' => [
                'title' => 'تم تسليم الطلب',
                'message' => "تم تسليم الطلب رقم {$order->order_number} بنجاح",
                'priority' => 'medium'
            ],
            'order_cancelled' => [
                'title' => 'تم إلغاء الطلب',
                'message' => "تم إلغاء الطلب رقم {$order->order_number}",
                'priority' => 'high'
            ],
            'order_refunded' => [
                'title' => 'تم استرداد الطلب',
                'message' => "تم استرداد الطلب رقم {$order->order_number}",
                'priority' => 'high'
            ]
        ];

        if (!isset($notifications[$event])) {
            throw new \InvalidArgumentException("Unknown order event: {$event}");
        }

        $notificationData = $notifications[$event];
        $data = array_merge([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => $order->status,
            'created_at' => $order->created_at->toISOString()
        ], $additionalData);

        return $this->createNotification(
            $event,
            $notificationData['title'],
            $notificationData['message'],
            $data,
            $notificationData['priority']
        );
    }

    /**
     * Create payment-related notifications
     */
    public function createPaymentNotification(Payment $payment, string $event, array $additionalData = [])
    {
        $notifications = [
            'payment_initiated' => [
                'title' => 'تم بدء عملية الدفع',
                'message' => "تم بدء عملية الدفع للطلب رقم {$payment->order->order_number}",
                'priority' => 'medium'
            ],
            'payment_paid' => [
                'title' => 'تم الدفع بنجاح',
                'message' => "تم الدفع بنجاح للطلب رقم {$payment->order->order_number} بمبلغ {$payment->amount} {$payment->currency}",
                'priority' => 'high'
            ],
            'payment_failed' => [
                'title' => 'فشل في الدفع',
                'message' => "فشل في الدفع للطلب رقم {$payment->order->order_number}",
                'priority' => 'high'
            ],
            'payment_refunded' => [
                'title' => 'تم استرداد الدفع',
                'message' => "تم استرداد الدفع للطلب رقم {$payment->order->order_number}",
                'priority' => 'high'
            ]
        ];

        if (!isset($notifications[$event])) {
            throw new \InvalidArgumentException("Unknown payment event: {$event}");
        }

        $notificationData = $notifications[$event];
        $data = array_merge([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_number' => $payment->order->order_number,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'created_at' => $payment->created_at->toISOString()
        ], $additionalData);

        return $this->createNotification(
            $event,
            $notificationData['title'],
            $notificationData['message'],
            $data,
            $notificationData['priority']
        );
    }

    /**
     * Create product-related notifications
     */
    public function createProductNotification(Product $product, string $event, array $additionalData = [])
    {
        $notifications = [
            'product_low_stock' => [
                'title' => 'مخزون منخفض',
                'message' => "المنتج {$product->title} لديه مخزون منخفض",
                'priority' => 'high'
            ],
            'product_out_of_stock' => [
                'title' => 'نفد المخزون',
                'message' => "المنتج {$product->title} نفد من المخزون",
                'priority' => 'urgent'
            ],
            'product_created' => [
                'title' => 'منتج جديد',
                'message' => "تم إضافة منتج جديد: {$product->title}",
                'priority' => 'medium'
            ],
            'product_updated' => [
                'title' => 'تم تحديث المنتج',
                'message' => "تم تحديث المنتج: {$product->title}",
                'priority' => 'low'
            ]
        ];

        if (!isset($notifications[$event])) {
            throw new \InvalidArgumentException("Unknown product event: {$event}");
        }

        $notificationData = $notifications[$event];
        $data = array_merge([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'product_slug' => $product->slug,
            'product_price' => $product->price,
            'product_currency' => $product->currency,
            'is_available' => $product->is_available,
            'category_id' => $product->category_id,
            'created_at' => $product->created_at->toISOString()
        ], $additionalData);

        return $this->createNotification(
            $event,
            $notificationData['title'],
            $notificationData['message'],
            $data,
            $notificationData['priority']
        );
    }

    /**
     * Send real-time notification (WebSocket/Pusher)
     */
    private function sendRealTimeNotification(AdminNotification $notification)
    {
        try {
            // For now, we'll just log the real-time notification
            // In production, you would integrate with Pusher, Socket.IO, or similar
            Log::info('Real-time notification sent', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'payload' => $notification->payload
            ]);

            // Example Pusher integration (commented out):
            /*
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );

            $pusher->trigger('admin-notifications', 'new-notification', [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->payload['title'],
                'message' => $notification->payload['message'],
                'priority' => $notification->payload['priority'],
                'created_at' => $notification->created_at->toISOString()
            ]);
            */

        } catch (\Exception $e) {
            Log::error('Error sending real-time notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(AdminNotification $notification)
    {
        try {
            $adminEmail = config('mail.admin_email', 'admin@soapyshop.com');
            
            if (!$adminEmail) {
                Log::warning('Admin email not configured, skipping email notification');
                return;
            }

            // For now, we'll just log the email notification
            // In production, you would send actual emails
            Log::info('Email notification sent', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'admin_email' => $adminEmail,
                'title' => $notification->payload['title'],
                'message' => $notification->payload['message']
            ]);

            // Example email sending (commented out):
            /*
            Mail::to($adminEmail)->send(new AdminNotificationMail($notification));
            */

        } catch (\Exception $e) {
            Log::error('Error sending email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences()
    {
        return [
            'email_notifications' => config('notifications.email_enabled', true),
            'push_notifications' => config('notifications.push_enabled', true),
            'sms_notifications' => config('notifications.sms_enabled', false),
            'notification_types' => [
                'order_created' => config('notifications.types.order_created', true),
                'order_paid' => config('notifications.types.order_paid', true),
                'order_shipped' => config('notifications.types.order_shipped', true),
                'order_delivered' => config('notifications.types.order_delivered', true),
                'order_cancelled' => config('notifications.types.order_cancelled', true),
                'payment_failed' => config('notifications.types.payment_failed', true),
                'payment_refunded' => config('notifications.types.payment_refunded', true),
                'product_low_stock' => config('notifications.types.product_low_stock', true),
                'product_out_of_stock' => config('notifications.types.product_out_of_stock', true),
            ],
            'quiet_hours_start' => config('notifications.quiet_hours_start', '22:00'),
            'quiet_hours_end' => config('notifications.quiet_hours_end', '08:00'),
            'admin_email' => config('mail.admin_email', 'admin@soapyshop.com')
        ];
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(array $preferences)
    {
        // In a real application, you would save these preferences to a database
        // For now, we'll just return the updated preferences
        $currentPreferences = $this->getNotificationPreferences();
        
        return array_merge($currentPreferences, $preferences);
    }

    /**
     * Check if notification should be sent based on preferences and quiet hours
     */
    public function shouldSendNotification(string $type, string $priority = 'medium')
    {
        $preferences = $this->getNotificationPreferences();
        
        // Check if notification type is enabled
        if (!($preferences['notification_types'][$type] ?? true)) {
            return false;
        }

        // Check quiet hours
        $now = now();
        $quietStart = $preferences['quiet_hours_start'];
        $quietEnd = $preferences['quiet_hours_end'];
        
        if ($quietStart && $quietEnd) {
            $currentTime = $now->format('H:i');
            if ($currentTime >= $quietStart || $currentTime <= $quietEnd) {
                // Only send urgent notifications during quiet hours
                if ($priority !== 'urgent') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 30)
    {
        try {
            $cutoffDate = now()->subDays($days);
            $deletedCount = AdminNotification::where('created_at', '<', $cutoffDate)
                ->whereNotNull('read_at')
                ->delete();

            Log::info('Cleaned up old notifications', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error cleaning up old notifications', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
