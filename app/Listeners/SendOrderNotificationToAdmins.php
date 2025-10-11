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
            // Get all admin users
            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            // Send email to each admin
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new OrderPaidNotification($event->order, $admin));
            }

            Log::info('Order paid notification sent to admins', [
                'order_id' => $event->order->id,
                'order_number' => $event->order->order_number,
                'admin_count' => $admins->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order paid notification to admins', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
