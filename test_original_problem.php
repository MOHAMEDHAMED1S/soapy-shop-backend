<?php

echo "=== اختبار المشكلة الأصلية ===\n";
echo "فترة الاختبار: من 2025-09-25 إلى 2025-10-25\n";
echo "تاريخ اليوم: " . date('d/m/Y') . "\n";
echo "ملاحظة: يجب أن يتم تعديل التاريخ النهائي إلى اليوم الحالي\n\n";

// Test with the exact dates from the original problem
$baseUrl = 'http://localhost:8000/api/v1';
$dateFrom = '2025-09-25';
$dateTo = '2025-10-25';

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
            if ($name === 'Financial Reports') {
                $revenue = $data['data']['revenue_breakdown']['total_revenue'] ?? 0;
                $orders = $data['data']['revenue_breakdown']['total_orders'] ?? 0;
                $monthlyRevenue = $data['data']['monthly_revenue'] ?? [];
                
                echo "   📊 تفاصيل الإيرادات:\n";
                echo "      - إجمالي الإيرادات: $revenue\n";
                echo "      - إجمالي الطلبات: $orders\n";
                
                echo "   📊 الإيرادات الشهرية:\n";
                foreach ($monthlyRevenue as $month) {
                    $year = $month['year'] ?? 'N/A';
                    $monthNum = $month['month'] ?? 'N/A';
                    $monthRevenue = $month['revenue'] ?? 0;
                    $monthOrders = $month['orders_count'] ?? 0;
                    echo "      - $year/$monthNum: $monthRevenue ($monthOrders طلب)\n";
                    
                    // Check if this is future data
                    if ($year == 2025 && $monthNum == 10) {
                        $currentDate = date('Y-m-d');
                        if ($currentDate < '2025-10-25') {
                            echo "      ⚠️  تحذير: بيانات شهر أكتوبر تتضمن تواريخ مستقبلية!\n";
                        }
                    }
                }
                
                // Check date range in response
                if (isset($data['data']['date_range'])) {
                    $dateRange = $data['data']['date_range'];
                    echo "   📅 نطاق التاريخ المطبق: {$dateRange['from']} إلى {$dateRange['to']}\n";
                    
                    if ($dateRange['to'] > date('Y-m-d')) {
                        echo "   ⚠️  تحذير: التاريخ النهائي مستقبلي!\n";
                    } else {
                        echo "   ✅ التاريخ النهائي تم تعديله للتاريخ الحالي\n";
                    }
                }
            }
        }
    } else {
        echo "   ❌ فشل الاختبار (HTTP $httpCode)\n";
        echo "   Response: $response\n";
    }
    
    echo "\n";
}

// Test the exact endpoint from the original problem
testEndpoint("$baseUrl/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo", 'Financial Reports');

echo "=== انتهى الاختبار ===\n";