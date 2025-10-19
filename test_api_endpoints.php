<?php

// اختبار API endpoints للتحقق من فلترة التاريخ

$baseUrl = 'http://localhost:8000/api/v1';
$dateFrom = '2025-09-25';
$dateTo = '2025-10-25';

echo "=== اختبار فلترة التاريخ في API endpoints ===\n\n";
echo "نطاق التواريخ: من $dateFrom إلى $dateTo\n";
echo "تاريخ اليوم: 19/10/2025\n";
echo "ملاحظة: هذا التاريخ لم يأتِ بعد، يجب أن تكون النتائج فارغة أو قليلة\n\n";

// دالة لإرسال طلب GET
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => $response ? json_decode($response, true) : null
    ];
}

// اختبار Financial Reports endpoint
echo "اختبار Financial Reports:\n";
$url = $baseUrl . "/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo";
echo "URL: $url\n";

$result = makeRequest($url);

if ($result['status_code'] === 200 && $result['response'] && $result['response']['success']) {
    echo "   ✅ نجح الاختبار (HTTP 200)\n";
    
    $data = $result['response']['data'];
    
    echo "   📊 تفاصيل الإيرادات:\n";
    if (isset($data['revenue_breakdown'])) {
        echo "      - إجمالي الإيرادات: " . $data['revenue_breakdown']['total_revenue'] . "\n";
        echo "      - إجمالي الطلبات: " . $data['revenue_breakdown']['total_orders'] . "\n";
        echo "      - إجمالي الشحن: " . $data['revenue_breakdown']['total_shipping'] . "\n";
        echo "      - إجمالي الخصم: " . $data['revenue_breakdown']['total_discount'] . "\n";
    }
    
    echo "   📊 الإيرادات الشهرية:\n";
    if (isset($data['monthly_revenue'])) {
        foreach ($data['monthly_revenue'] as $month) {
            echo "      - {$month['year']}/{$month['month']}: {$month['revenue']} ({$month['orders_count']} طلب)\n";
        }
        echo "      - عدد الشهور: " . count($data['monthly_revenue']) . "\n";
    }
    
    echo "   📊 الإلغاءات والاسترداد:\n";
    if (isset($data['refunds_and_cancellations'])) {
        echo "      - الطلبات الملغاة: " . $data['refunds_and_cancellations']['cancelled_orders'] . "\n";
        echo "      - قيمة الطلبات الملغاة: " . $data['refunds_and_cancellations']['cancelled_revenue'] . "\n";
        echo "      - الطلبات المستردة: " . $data['refunds_and_cancellations']['refunded_orders'] . "\n";
        echo "      - قيمة المبالغ المستردة: " . $data['refunds_and_cancellations']['refunded_amount'] . "\n";
    }
    
    // تحليل النتائج
    echo "\n   🔍 تحليل النتائج:\n";
    $totalRevenue = floatval($data['revenue_breakdown']['total_revenue'] ?? 0);
    $totalOrders = intval($data['revenue_breakdown']['total_orders'] ?? 0);
    
    if ($totalRevenue > 0 || $totalOrders > 0) {
        echo "      ⚠️  تحذير: يوجد بيانات للفترة المستقبلية!\n";
        echo "      ⚠️  هذا يشير إلى أن فلترة التاريخ لا تعمل بشكل صحيح\n";
        echo "      ⚠️  يجب أن تكون النتائج فارغة للتواريخ المستقبلية\n";
    } else {
        echo "      ✅ ممتاز: لا توجد بيانات للفترة المستقبلية\n";
        echo "      ✅ فلترة التاريخ تعمل بشكل صحيح\n";
    }
    
} else {
    echo "   ❌ فشل الاختبار (HTTP " . $result['status_code'] . ")\n";
    if ($result['response'] && isset($result['response']['message'])) {
        echo "   📝 الرسالة: " . $result['response']['message'] . "\n";
    }
}

echo "\n";

// اختبار Dashboard Overview
echo "اختبار Dashboard Overview:\n";
$url = $baseUrl . "/reports/dashboard/overview?date_from=$dateFrom&date_to=$dateTo";
echo "URL: $url\n";

$result = makeRequest($url);

if ($result['status_code'] === 200 && $result['response'] && $result['response']['success']) {
    echo "   ✅ نجح الاختبار (HTTP 200)\n";
    
    $data = $result['response']['data'];
    
    echo "   📊 إحصائيات Dashboard:\n";
    echo "      - إجمالي العملاء: " . ($data['total_customers'] ?? 0) . "\n";
    echo "      - إجمالي الطلبات: " . ($data['total_orders'] ?? 0) . "\n";
    echo "      - إجمالي الإيرادات: " . ($data['total_revenue'] ?? 0) . "\n";
    echo "      - العملاء النشطون: " . ($data['active_customers'] ?? 0) . "\n";
    
    if (isset($data['date_range'])) {
        echo "      - نطاق التاريخ: " . $data['date_range']['from'] . " إلى " . $data['date_range']['to'] . "\n";
    }
    
    // تحليل النتائج
    echo "\n   🔍 تحليل النتائج:\n";
    $totalRevenue = floatval($data['total_revenue'] ?? 0);
    $totalOrders = intval($data['total_orders'] ?? 0);
    $totalCustomers = intval($data['total_customers'] ?? 0);
    
    if ($totalRevenue > 0 || $totalOrders > 0 || $totalCustomers > 0) {
        echo "      ⚠️  تحذير: يوجد بيانات للفترة المستقبلية!\n";
        echo "      ⚠️  هذا يشير إلى أن فلترة التاريخ لا تعمل بشكل صحيح\n";
    } else {
        echo "      ✅ ممتاز: لا توجد بيانات للفترة المستقبلية\n";
        echo "      ✅ فلترة التاريخ تعمل بشكل صحيح\n";
    }
    
} else {
    echo "   ❌ فشل الاختبار (HTTP " . $result['status_code'] . ")\n";
    if ($result['response'] && isset($result['response']['message'])) {
        echo "   📝 الرسالة: " . $result['response']['message'] . "\n";
    }
}

echo "\n=== انتهى الاختبار ===\n";