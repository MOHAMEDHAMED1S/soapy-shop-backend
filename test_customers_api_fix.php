<?php

/**
 * Test script for Customers API fixes
 * 
 * This script tests:
 * 1. Each customer has total_orders calculated
 * 2. Each customer has total_spent calculated
 * 3. Each customer has average_order_value calculated
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Order;

echo "=== Testing Customers API Fixes ===\n\n";

// Test 1: Check if customers have orders
echo "Test 1: Checking Customer Order Counts\n";
echo "---------------------------------------\n";

$customers = Customer::with('orders')->take(5)->get();

foreach ($customers as $customer) {
    $orderCount = $customer->orders->count();
    $totalSpent = $customer->orders->sum('total_amount');
    $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
    
    echo "Customer: {$customer->name}\n";
    echo "  Phone: {$customer->phone}\n";
    echo "  Total Orders: {$orderCount}\n";
    echo "  Total Spent: " . number_format($totalSpent, 3) . " KWD\n";
    echo "  Average Order Value: " . number_format($avgOrderValue, 3) . " KWD\n";
    echo "\n";
}

// Test 2: Check using withCount, withSum, withAvg
echo "\nTest 2: Using Laravel withCount/withSum/withAvg\n";
echo "------------------------------------------------\n";

$customersWithStats = Customer::withCount('orders as total_orders')
    ->withSum('orders as calculated_total_spent', 'total_amount')
    ->withAvg('orders as calculated_average_order_value', 'total_amount')
    ->take(5)
    ->get();

foreach ($customersWithStats as $customer) {
    echo "Customer: {$customer->name}\n";
    echo "  Total Orders: {$customer->total_orders}\n";
    echo "  Total Spent: " . number_format($customer->calculated_total_spent ?? 0, 3) . " KWD\n";
    echo "  Average Order Value: " . number_format($customer->calculated_average_order_value ?? 0, 3) . " KWD\n";
    echo "\n";
}

// Test 3: Check overall statistics
echo "\nTest 3: Overall Statistics\n";
echo "--------------------------\n";

$totalCustomers = Customer::count();
$customersWithOrders = Customer::has('orders')->count();
$totalOrders = Order::count();
$totalRevenue = Order::whereIn('status', ['paid', 'shipped', 'delivered'])->sum('total_amount');

echo "Total Customers: {$totalCustomers}\n";
echo "Customers with Orders: {$customersWithOrders}\n";
echo "Total Orders: {$totalOrders}\n";
echo "Total Revenue: " . number_format($totalRevenue, 3) . " KWD\n";

// Test 4: Check for customers without order data
echo "\nTest 4: Customers Without Order Data\n";
echo "-------------------------------------\n";

$customersWithoutOrders = Customer::doesntHave('orders')->count();
echo "Customers without orders: {$customersWithoutOrders}\n";

if ($customersWithoutOrders > 0) {
    echo "Note: These customers will show 0 for all order-related metrics.\n";
}

echo "\n=== Test Complete ===\n";
echo "\nYou can now test the API endpoint:\n";
echo "GET /api/v1/admin/customers?page=1&per_page=15\n";
echo "\nExpected response for each customer:\n";
echo "{\n";
echo "  \"id\": 1,\n";
echo "  \"name\": \"Customer Name\",\n";
echo "  \"phone\": \"+96512345678\",\n";
echo "  \"total_orders\": 5,           // ✅ Should show actual count\n";
echo "  \"total_spent\": \"125.500\",    // ✅ Should show actual sum\n";
echo "  \"average_order_value\": \"25.100\" // ✅ Should show actual average\n";
echo "}\n";

