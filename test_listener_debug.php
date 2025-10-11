<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Events\OrderPaid;
use App\Listeners\SendOrderNotificationToAdmins;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing OrderPaid listener directly...\n";
    
    // Get first order
    $order = Order::first();
    if (!$order) {
        echo "No orders found in database\n";
        exit(1);
    }
    
    echo "Found order: {$order->order_number}\n";
    
    // Create OrderPaid event
    $event = new OrderPaid($order);
    echo "Created OrderPaid event\n";
    
    // Create listener instance and call handle method directly
    $listener = new SendOrderNotificationToAdmins();
    echo "Created listener instance\n";
    
    echo "Calling listener handle method...\n";
    $listener->handle($event);
    
    echo "Listener handle method completed!\n";
    echo "Check the logs to see if emails were sent.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}