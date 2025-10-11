<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Events\OrderPaid;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing OrderPaid event dispatch...\n";
    
    // Get first order
    $order = Order::first();
    if (!$order) {
        echo "No orders found in database\n";
        exit(1);
    }
    
    echo "Found order: {$order->order_number}\n";
    
    // Dispatch OrderPaid event
    echo "Dispatching OrderPaid event...\n";
    
    Event::dispatch(new OrderPaid($order));
    
    echo "OrderPaid event dispatched successfully!\n";
    echo "Check the logs to see if the listener was triggered.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}