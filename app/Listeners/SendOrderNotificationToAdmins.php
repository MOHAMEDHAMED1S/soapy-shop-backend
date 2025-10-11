<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderPaidNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationToAdmins implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            Log::info('OrderPaid event received', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number
            ]);

            // Get all admin users
            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            Log::info('Found admin users', [
                'admin_count' => $admins->count(),
                'admin_emails' => $admins->pluck('email')->toArray()
            ]);

            // Send email to each admin
            foreach ($admins as $admin) {
                Log::info('Sending email to admin', [
                    'admin_email' => $admin->email,
                    'admin_name' => $admin->name
                ]);
                
                Mail::to($admin->email)->send(new OrderPaidNotification($event->order, $admin));
                
                Log::info('Email sent successfully to admin', [
                    'admin_email' => $admin->email
                ]);
            }

            Log::info('Order paid notification sent to all admins', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'admin_count' => $admins->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order paid notification to admins', [
                'order_id' => $event->order->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
