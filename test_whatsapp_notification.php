<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\WhatsAppService;

echo "=== Testing WhatsApp Notification ===\n\n";

// Get a paid order to test
$order = Order::where('status', 'paid')
    ->with(['orderItems', 'payment'])
    ->first();

if (!$order) {
    echo "❌ No paid orders found to test.\n";
    echo "Creating a test order...\n\n";
    
    // Create test order
    $order = Order::create([
        'order_number' => 'TEST-WA-' . rand(100000, 999999),
        'customer_name' => 'أحمد محمد',
        'customer_email' => 'test@example.com',
        'customer_phone' => '+96512345678',
        'status' => 'paid',
        'total_amount' => 45.500,
        'discount_amount' => 5.000,
        'currency' => 'KWD',
        'shipping_address' => 'الكويت، السالمية، شارع الخليج، بناية 123',
    ]);
    
    echo "✅ Test order created: #{$order->id}\n";
    echo "⚠️ Note: Test order has no items, message will show '(لا توجد تفاصيل المنتجات)'\n\n";
}

// Ensure order items are loaded
if (!$order->relationLoaded('orderItems')) {
    $order->load('orderItems');
}

echo "Testing WhatsApp notification for:\n";
echo "  Order #" . $order->id . " - " . $order->order_number . "\n";
echo "  Customer: " . $order->customer_name . "\n";
echo "  Amount: " . $order->total_amount . " " . $order->currency . "\n";
echo "  Order Items: " . $order->orderItems->count() . "\n\n";

// Test WhatsApp Service
$whatsappService = app(WhatsAppService::class);

echo "📱 Sending WhatsApp notification...\n";
echo "   To: " . env('ADMIN_WHATSAPP_PHONE', '201062532581') . "\n";
echo "   API: " . env('WHATSAPP_API_URL', 'http://localhost:3000') . "\n";
echo "   Image: https://soapy-bubbles.com/logo.png\n\n";

$result = $whatsappService->notifyAdminNewPaidOrder($order);

if ($result['success']) {
    echo "✅ WhatsApp notification sent successfully!\n";
    if (isset($result['data'])) {
        echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "❌ Failed to send WhatsApp notification\n";
    echo "Error: " . $result['error'] . "\n\n";
    
    // Show what the message would look like
    echo "📝 Preview of the message that was attempted:\n";
    echo str_repeat("=", 60) . "\n";
    
    // Get the formatted message
    $reflection = new ReflectionClass($whatsappService);
    $method = $reflection->getMethod('formatOrderMessage');
    $method->setAccessible(true);
    $messagePreview = $method->invoke($whatsappService, $order);
    
    echo $messagePreview . "\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "💡 Troubleshooting:\n";
    echo "1. Make sure WhatsApp API server is running on: " . env('WHATSAPP_API_URL', 'http://localhost:3000') . "\n";
    echo "2. Check your .env file has:\n";
    echo "   WHATSAPP_API_URL=http://localhost:3000\n";
    echo "   ADMIN_WHATSAPP_PHONE=201062532581\n";
    echo "3. Test the API manually:\n";
    echo "   curl -X POST " . env('WHATSAPP_API_URL') . "/api/send/image-url \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     -d '{\"to\": \"201062532581\", \"imageUrl\": \"https://soapy-bubbles.com/logo.png\", \"caption\": \"Test\"}'\n";
}

echo "\n=== Test Complete ===\n";

// Check logs
echo "\n📋 Recent WhatsApp logs:\n";
echo "Check: storage/logs/laravel.log | grep WhatsApp\n";

