<?php

/**
 * Test script for Order Statistics API fixes
 * 
 * This script tests:
 * 1. Status filter is applied correctly
 * 2. Date filter is applied correctly
 * 3. Revenue and average order value are calculated for paid, shipped, and delivered orders
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use Carbon\Carbon;

echo "=== Testing Order Statistics Fixes ===\n\n";

// Test 1: Status filter
echo "Test 1: Status Filter\n";
echo "----------------------\n";

$paidOrders = Order::where('status', 'paid')
    ->whereBetween('created_at', [
        Carbon::parse('2025-10-17')->startOfDay(),
        Carbon::parse('2025-10-24')->endOfDay()
    ])
    ->count();

echo "Paid orders between 2025-10-17 and 2025-10-24: {$paidOrders}\n\n";

// Test 2: Date filter
echo "Test 2: Date Filter\n";
echo "-------------------\n";

$ordersInRange = Order::whereBetween('created_at', [
    Carbon::parse('2025-10-17')->startOfDay(),
    Carbon::parse('2025-10-24')->endOfDay()
])->count();

echo "Total orders between 2025-10-17 and 2025-10-24: {$ordersInRange}\n\n";

// Test 3: Revenue calculation (paid, shipped, delivered)
echo "Test 3: Revenue Calculation\n";
echo "---------------------------\n";

$revenueStatuses = ['paid', 'shipped', 'delivered'];

$totalRevenue = Order::whereIn('status', $revenueStatuses)
    ->whereBetween('created_at', [
        Carbon::parse('2025-10-17')->startOfDay(),
        Carbon::parse('2025-10-24')->endOfDay()
    ])
    ->sum('total_amount');

$averageOrderValue = Order::whereIn('status', $revenueStatuses)
    ->whereBetween('created_at', [
        Carbon::parse('2025-10-17')->startOfDay(),
        Carbon::parse('2025-10-24')->endOfDay()
    ])
    ->avg('total_amount');

echo "Total revenue (paid + shipped + delivered): {$totalRevenue} KWD\n";
echo "Average order value (paid + shipped + delivered): " . number_format($averageOrderValue, 3) . " KWD\n\n";

// Test 4: Orders by status breakdown
echo "Test 4: Orders by Status Breakdown\n";
echo "-----------------------------------\n";

$ordersByStatus = Order::whereBetween('created_at', [
    Carbon::parse('2025-10-17')->startOfDay(),
    Carbon::parse('2025-10-24')->endOfDay()
])
->selectRaw('status, COUNT(*) as count, SUM(total_amount) as revenue')
->groupBy('status')
->get();

foreach ($ordersByStatus as $statusGroup) {
    echo "{$statusGroup->status}: {$statusGroup->count} orders, {$statusGroup->revenue} KWD\n";
}

echo "\n=== Test Complete ===\n";
echo "\nYou can now test the API endpoint:\n";
echo "GET /api/v1/admin/orders/statistics?status=paid&start_date=2025-10-17&end_date=2025-10-24\n";

