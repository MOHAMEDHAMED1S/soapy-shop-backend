<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Services\NotificationService;

// Get the first order
$order = Order::first();

if (!$order) {
    echo "No orders found in database\n";
    exit(1);
}

echo "Testing synchronous email sending for Order ID: {$order->id}\n";
echo "Order Number: {$order->order_number}\n";

// Create notification service instance
$notificationService = new NotificationService();

// Create order notification (this should now send emails immediately)
echo "Creating order notification...\n";
$notification = $notificationService->createOrderNotification($order, 'order_paid');

echo "Notification created with ID: {$notification->id}\n";
echo "Check your email inbox - emails should have been sent immediately!\n";
echo "Also check the Laravel log for confirmation.\n";