<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get first order
$order = \App\Models\Order::first();
echo "Testing notification for order: " . $order->order_number . "\n";

// Create NotificationService instance
$notificationService = new \App\Services\NotificationService();

// Create order_paid notification (this should trigger email)
$notification = $notificationService->createOrderNotification($order, 'order_paid');
echo "Order paid notification created with ID: " . $notification->id . "\n";
