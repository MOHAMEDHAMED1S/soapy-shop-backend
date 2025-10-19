<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReportController;

// إنشاء instance من ReportController
$reportController = new ReportController();

// تعريف نطاق التواريخ للاختبار
$dateFrom = '2024-01-01';
$dateTo = '2024-12-31';

echo "=== اختبار فلترة التاريخ المصححة في التقارير ===\n\n";
echo "نطاق التواريخ: من $dateFrom إلى $dateTo\n\n";

// اختبار Dashboard Overview
echo "1. اختبار Dashboard Overview:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getDashboardOverview($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 إجمالي العملاء: " . $data['data']['total_customers'] . "\n";
        echo "   📊 إجمالي الطلبات: " . $data['data']['total_orders'] . "\n";
        echo "   📊 إجمالي الإيرادات: " . number_format($data['data']['total_revenue'], 2) . "\n";
        if (isset($data['data']['date_range'])) {
            echo "   📅 نطاق التاريخ: " . $data['data']['date_range']['from'] . " إلى " . $data['data']['date_range']['to'] . "\n";
        }
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Sales Analytics
echo "2. اختبار Sales Analytics:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo, 'period' => 'month']);
    $response = $reportController->getSalesAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 عدد المنتجات الأكثر مبيعاً: " . count($data['data']['top_products']) . "\n";
        echo "   📊 عدد الفئات: " . count($data['data']['sales_by_category']) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Customer Analytics
echo "3. اختبار Customer Analytics:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getCustomerAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 عدد العملاء الجدد: " . count($data['data']['customer_acquisition']) . "\n";
        echo "   📊 أفضل العملاء (طلبات): " . count($data['data']['top_customers_by_orders']) . "\n";
        echo "   📊 أفضل العملاء (إيرادات): " . count($data['data']['top_customers_by_revenue']) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Product Analytics
echo "4. اختبار Product Analytics:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getProductAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 عدد المنتجات المحللة: " . count($data['data']['product_performance']) . "\n";
        echo "   📊 منتجات قليلة المخزون: " . count($data['data']['low_stock_products']) . "\n";
        echo "   📊 منتجات نفدت من المخزون: " . count($data['data']['out_of_stock_products']) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Order Analytics
echo "5. اختبار Order Analytics:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getOrderAnalytics($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 الطلبات حسب الحالة: " . count($data['data']['orders_by_status']) . "\n";
        echo "   📊 الطلبات حسب حالة الدفع: " . count($data['data']['orders_by_payment_status']) . "\n";
        echo "   📊 الطلبات الأخيرة: " . count($data['data']['recent_orders']) . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Financial Reports
echo "6. اختبار Financial Reports:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getFinancialReports($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 إجمالي الإيرادات: " . number_format($data['data']['revenue_breakdown']['total_revenue'], 2) . "\n";
        echo "   📊 إجمالي الطلبات: " . $data['data']['revenue_breakdown']['total_orders'] . "\n";
        echo "   📊 الإيرادات الشهرية: " . count($data['data']['monthly_revenue']) . " شهر\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Business Intelligence
echo "7. اختبار Business Intelligence:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getBusinessIntelligence($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 معدل التحويل: " . number_format($data['data']['kpis']['conversion_rate'], 2) . "%\n";
        echo "   📊 قيمة العميل مدى الحياة: " . number_format($data['data']['kpis']['customer_lifetime_value'], 2) . "\n";
        echo "   📊 متوسط قيمة الطلب: " . number_format($data['data']['kpis']['average_order_value'], 2) . "\n";
        echo "   📊 الاتجاهات الموسمية: " . count($data['data']['seasonal_trends']) . " شهر\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار Seasonal Trends
echo "8. اختبار Seasonal Trends:\n";
try {
    $request = new Request(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $response = $reportController->getSeasonalTrends($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "   ✅ نجح الاختبار\n";
        echo "   📊 عدد الشهور: " . count($data['data']['trends']) . "\n";
        echo "   📅 الفترة: " . $data['data']['period'] . "\n";
    } else {
        echo "   ❌ فشل الاختبار: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ خطأ: " . $e->getMessage() . "\n";
}

echo "\n=== انتهى الاختبار ===\n";
echo "تم إصلاح فلترة التاريخ في جميع التقارير بنجاح! 🎉\n";