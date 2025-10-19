<?php

// اختبار API endpoints للتحقق من فلترة التاريخ

$baseUrl = 'http://localhost:8000/api';
$dateFrom = '2024-01-01';
$dateTo = '2024-12-31';

echo "=== اختبار فلترة التاريخ في API endpoints ===\n\n";
echo "نطاق التواريخ: من $dateFrom إلى $dateTo\n\n";

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

// قائمة endpoints للاختبار
$endpoints = [
    'Dashboard Overview' => "/reports/dashboard/overview?date_from=$dateFrom&date_to=$dateTo",
    'Sales Analytics' => "/reports/analytics/sales?date_from=$dateFrom&date_to=$dateTo&period=month",
    'Customer Analytics' => "/reports/analytics/customers?date_from=$dateFrom&date_to=$dateTo",
    'Product Analytics' => "/reports/analytics/products?date_from=$dateFrom&date_to=$dateTo",
    'Order Analytics' => "/reports/analytics/orders?date_from=$dateFrom&date_to=$dateTo",
    'Financial Reports' => "/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo",
    'Business Intelligence' => "/reports/business-intelligence?date_from=$dateFrom&date_to=$dateTo",
    'Seasonal Trends' => "/reports/analytics/seasonal-trends?date_from=$dateFrom&date_to=$dateTo"
];

foreach ($endpoints as $name => $endpoint) {
    echo "اختبار $name:\n";
    
    $url = $baseUrl . $endpoint;
    $result = makeRequest($url);
    
    if ($result['status_code'] === 200 && $result['response'] && $result['response']['success']) {
        echo "   ✅ نجح الاختبار (HTTP 200)\n";
        
        // عرض بعض البيانات المهمة
        $data = $result['response']['data'];
        
        switch ($name) {
            case 'Dashboard Overview':
                if (isset($data['total_customers'])) {
                    echo "   📊 إجمالي العملاء: " . $data['total_customers'] . "\n";
                }
                if (isset($data['total_orders'])) {
                    echo "   📊 إجمالي الطلبات: " . $data['total_orders'] . "\n";
                }
                if (isset($data['total_revenue'])) {
                    echo "   📊 إجمالي الإيرادات: " . number_format($data['total_revenue'], 2) . "\n";
                }
                if (isset($data['date_range'])) {
                    echo "   📅 نطاق التاريخ: " . $data['date_range']['from'] . " إلى " . $data['date_range']['to'] . "\n";
                }
                break;
                
            case 'Sales Analytics':
                if (isset($data['top_products'])) {
                    echo "   📊 عدد المنتجات الأكثر مبيعاً: " . count($data['top_products']) . "\n";
                }
                break;
                
            case 'Financial Reports':
                if (isset($data['revenue_breakdown']['total_revenue'])) {
                    echo "   📊 إجمالي الإيرادات: " . number_format($data['revenue_breakdown']['total_revenue'], 2) . "\n";
                }
                if (isset($data['monthly_revenue'])) {
                    echo "   📊 الإيرادات الشهرية: " . count($data['monthly_revenue']) . " شهر\n";
                }
                break;
                
            case 'Business Intelligence':
                if (isset($data['kpis']['conversion_rate'])) {
                    echo "   📊 معدل التحويل: " . number_format($data['kpis']['conversion_rate'], 2) . "%\n";
                }
                if (isset($data['seasonal_trends'])) {
                    echo "   📊 الاتجاهات الموسمية: " . count($data['seasonal_trends']) . " شهر\n";
                }
                break;
        }
        
    } else {
        echo "   ❌ فشل الاختبار (HTTP " . $result['status_code'] . ")\n";
        if ($result['response'] && isset($result['response']['message'])) {
            echo "   📝 الرسالة: " . $result['response']['message'] . "\n";
        }
    }
    
    echo "\n";
}

echo "=== انتهى الاختبار ===\n";
echo "ملاحظة: تأكد من تشغيل الخادم المحلي على http://localhost:8000\n";