<?php

echo "=== اختبار التواريخ المستقبلية بالكامل ===\n";
echo "فترة الاختبار: من 2025-11-01 إلى 2025-11-30\n";
echo "تاريخ اليوم: " . date('d/m/Y') . "\n";
echo "ملاحظة: هذا التاريخ مستقبلي بالكامل، يجب أن تكون النتائج فارغة\n\n";

// Test with completely future dates
$baseUrl = 'http://localhost:8000/api/v1';
$dateFrom = '2025-11-01';
$dateTo = '2025-11-30';

function testEndpoint($url, $name) {
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
    
    echo "اختبار $name:\n";
    echo "URL: $url\n";
    
    if ($httpCode === 200) {
        echo "   ✅ نجح الاختبار (HTTP 200)\n";
        $data = json_decode($response, true);
        
        if (isset($data['data'])) {
            // Check if there's any meaningful data
            $hasData = false;
            
            if ($name === 'Financial Reports') {
                $revenue = $data['data']['revenue_breakdown']['total_revenue'] ?? 0;
                $orders = $data['data']['revenue_breakdown']['total_orders'] ?? 0;
                
                echo "   📊 تفاصيل الإيرادات:\n";
                echo "      - إجمالي الإيرادات: $revenue\n";
                echo "      - إجمالي الطلبات: $orders\n";
                
                if ($revenue > 0 || $orders > 0) {
                    $hasData = true;
                }
            } elseif ($name === 'Dashboard Overview') {
                $customers = $data['data']['total_customers'] ?? 0;
                $orders = $data['data']['total_orders'] ?? 0;
                $revenue = $data['data']['total_revenue'] ?? 0;
                
                echo "   📊 إحصائيات Dashboard:\n";
                echo "      - إجمالي العملاء: $customers\n";
                echo "      - إجمالي الطلبات: $orders\n";
                echo "      - إجمالي الإيرادات: $revenue\n";
                
                if ($customers > 0 || $orders > 0 || $revenue > 0) {
                    $hasData = true;
                }
            }
            
            if ($hasData) {
                echo "   ⚠️  تحذير: يوجد بيانات للفترة المستقبلية!\n";
                echo "   ⚠️  هذا يشير إلى أن التحقق من التواريخ لا يعمل بشكل صحيح\n";
            } else {
                echo "   ✅ ممتاز: لا توجد بيانات للفترة المستقبلية\n";
                echo "   ✅ التحقق من التواريخ يعمل بشكل صحيح\n";
            }
        }
    } else {
        echo "   ❌ فشل الاختبار (HTTP $httpCode)\n";
        echo "   Response: $response\n";
    }
    
    echo "\n";
}

// Test endpoints with completely future dates
testEndpoint("$baseUrl/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo", 'Financial Reports');
testEndpoint("$baseUrl/reports/dashboard/overview?date_from=$dateFrom&date_to=$dateTo", 'Dashboard Overview');

echo "=== انتهى الاختبار ===\n";