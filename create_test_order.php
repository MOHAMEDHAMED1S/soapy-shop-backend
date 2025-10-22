<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\DiscountCode;
use App\Services\DiscountService;

// Create a test product if not exists
$product = Product::first();
if (!$product) {
    $product = Product::create([
        'title' => 'Test Product',
        'description' => 'Test product for discount testing',
        'price' => 50.000,
        'stock_quantity' => 100,
        'category_id' => 1,
        'is_active' => true
    ]);
}

// Create a test discount code if not exists
$discountCode = DiscountCode::where('code', 'TEST10')->first();
if (!$discountCode) {
    $discountCode = DiscountCode::create([
        'code' => 'TEST10',
        'type' => 'percentage',
        'value' => 10.00,
        'minimum_order_amount' => 0,
        'maximum_discount_amount' => null,
        'usage_limit' => 100,
        'used_count' => 0,
        'is_active' => true,
        'starts_at' => now(),
        'expires_at' => now()->addDays(30)
    ]);
}

// Create a test order
$order = Order::create([
    'order_number' => 'TEST-' . time(),
    'customer_name' => 'Test Customer',
    'customer_phone' => '+96512345678',
    'customer_email' => 'test@example.com',
    'shipping_address' => json_encode([
        'area' => 'Test Area',
        'block' => '1',
        'street' => 'Test Street',
        'house' => '123',
        'floor' => '1',
        'apartment' => '1'
    ]),
    'total_amount' => $product->price,
    'status' => 'pending'
]);

// Create order item
OrderItem::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'product_price' => $product->price,
    'quantity' => 1,
    'product_snapshot' => json_encode([
        'title' => $product->title,
        'price' => $product->price
    ])
]);

echo 'Order created with ID: ' . $order->id . PHP_EOL;
echo 'Original total: ' . $order->total_amount . ' KWD' . PHP_EOL;

// Apply discount
$discountService = new DiscountService();
$result = $discountService->applyDiscountCodeToOrder($order, 'TEST10');

if ($result['success']) {
    $order->refresh();
    echo 'Discount applied successfully!' . PHP_EOL;
    echo 'New total after discount: ' . $order->total_amount . ' KWD' . PHP_EOL;
    echo 'Order ID for testing: ' . $order->id . PHP_EOL;
} else {
    echo 'Failed to apply discount: ' . $result['message'] . PHP_EOL;
}