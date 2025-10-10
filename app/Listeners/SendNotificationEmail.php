<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Mail\AdminNotificationMail;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationCreated $event): void
    {
        try {
            $notification = $event->notification;
            $priority = $notification->payload['priority'] ?? 'medium';

            // Only send email for high priority notifications
            if (!in_array($priority, ['high', 'urgent'])) {
                Log::info('Skipping email notification for low priority', [
                    'notification_id' => $notification->id,
                    'priority' => $priority
                ]);
                return;
            }

            // Check if notification should be sent based on preferences
            if (!$this->notificationService->shouldSendNotification($notification->type, $priority)) {
                Log::info('Skipping email notification based on preferences', [
                    'notification_id' => $notification->id,
                    'type' => $notification->type
                ]);
                return;
            }

            $adminEmail = config('mail.admin_email', 'admin@soapyshop.com');
            
            if (!$adminEmail) {
                Log::warning('Admin email not configured, skipping email notification');
                return;
            }

            // Send email notification
            Mail::to($adminEmail)->send(new AdminNotificationMail($notification));

            Log::info('Email notification sent successfully', [
                'notification_id' => $notification->id,
                'admin_email' => $adminEmail,
                'priority' => $priority
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending email notification', [
                'notification_id' => $event->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't fail the job, just log the error
            $this->release(60); // Retry after 60 seconds
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationCreated $event, $exception): void
    {
        Log::error('Email notification job failed', [
            'notification_id' => $event->notification->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
