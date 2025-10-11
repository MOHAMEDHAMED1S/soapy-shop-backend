<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\User;
use App\Mail\OrderPaidNotification;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Testing direct email sending...\n";
    
    // Get first order
    $order = Order::first();
    if (!$order) {
        echo "No orders found in database\n";
        exit(1);
    }
    
    echo "Found order: {$order->order_number}\n";
    
    // Get first admin user
    $admin = User::where('role', 'admin')->first();
    if (!$admin) {
        echo "No admin users found\n";
        exit(1);
    }
    
    echo "Found admin: {$admin->email}\n";
    
    // Test direct email sending
    echo "Sending email directly...\n";
    
    Mail::to($admin->email)->send(new OrderPaidNotification($order, $admin));
    
    echo "Email sent successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}