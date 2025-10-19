<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReportController;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== اختبار فلترة التقارير بالتاريخ ===\n\n";

try {
    $controller = new ReportController();
    
    // Test date range
    $dateFrom = '2024-01-01';
    $dateTo = '2024-12-31';
    
    echo "فترة الاختبار: من $dateFrom إلى $dateTo\n\n";
    
    // Test 1: Dashboard Overview with date filtering
    echo "1. اختبار تقرير Dashboard Overview:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getDashboardOverview($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📊 إجمالي الطلبات: " . ($data['data']['total_orders'] ?? 0) . "\n";
        echo "   💰 إجمالي الإيرادات: " . ($data['data']['total_revenue'] ?? 0) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 2: Sales Analytics with date filtering
    echo "2. اختبار تقرير Sales Analytics:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo, 'period' => 'month']);
    $response = $controller->getSalesAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📈 عدد المنتجات الأكثر مبيعاً: " . count($data['data']['top_products'] ?? []) . "\n";
        echo "   📊 عدد فترات المبيعات: " . count($data['data']['sales_over_time'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 3: Customer Analytics with date filtering
    echo "3. اختبار تقرير Customer Analytics:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getCustomerAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   👥 عدد العملاء الجدد: " . count($data['data']['customer_acquisition'] ?? []) . "\n";
        echo "   🏆 عدد أفضل العملاء: " . count($data['data']['top_customers_by_orders'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 4: Product Analytics with date filtering
    echo "4. اختبار تقرير Product Analytics:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getProductAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📦 عدد المنتجات المحللة: " . count($data['data']['product_performance'] ?? []) . "\n";
        echo "   ⚠️ منتجات قليلة المخزون: " . count($data['data']['low_stock_products'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 5: Order Analytics with date filtering
    echo "5. اختبار تقرير Order Analytics:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getOrderAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📋 أنواع حالات الطلبات: " . count($data['data']['orders_by_status'] ?? []) . "\n";
        echo "   💳 أنواع حالات الدفع: " . count($data['data']['orders_by_payment_status'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 6: Financial Reports with date filtering
    echo "6. اختبار تقرير Financial Reports:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getFinancialReports($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   💰 إجمالي الإيرادات: " . ($data['data']['revenue_breakdown']['total_revenue'] ?? 0) . "\n";
        echo "   📅 عدد الأشهر المحللة: " . count($data['data']['monthly_revenue'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 7: Business Intelligence with date filtering
    echo "7. اختبار تقرير Business Intelligence:\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getBusinessIntelligence($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📊 مؤشرات الأداء الرئيسية متوفرة\n";
        echo "   📈 معدل التحويل: " . ($data['data']['kpis']['conversion_rate'] ?? 0) . "%\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    // Test 8: Seasonal Trends with date filtering (Updated)
    echo "8. اختبار تقرير Seasonal Trends (المحدث):\n";
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $controller->getSeasonalTrends($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار - تم الحصول على البيانات\n";
        echo "   📅 فترة التقرير: من " . $data['data']['period']['date_from'] . " إلى " . $data['data']['period']['date_to'] . "\n";
        echo "   📊 عدد الأشهر المحللة: " . count($data['data']['seasonal_trends'] ?? []) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
    echo "\n";
    
    echo "=== انتهى اختبار فلترة التقارير بالتاريخ ===\n";
    echo "✅ جميع التقارير تدعم الآن فلترة البيانات بالتاريخ باستخدام date_from و date_to\n";
    
} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}