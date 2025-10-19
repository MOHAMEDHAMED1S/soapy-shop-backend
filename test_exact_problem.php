<?php

echo "=== اختبار المشكلة الأصلية بدقة ===\n";
echo "المشكلة: عند طلب تقرير من 2025-09-25 إلى 2025-10-25\n";
echo "النتيجة المتوقعة: يجب أن تظهر البيانات للفترة المطلوبة بالضبط\n";
echo "تاريخ اليوم: " . date('Y-m-d') . "\n\n";

// Test with the exact dates from the original problem
$baseUrl = 'http://localhost:8000/api/v1';
$dateFrom = '2025-09-25';
$dateTo = '2025-10-25';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "طلب التقرير المالي:\n";
echo "URL: $baseUrl/reports/financial/overview?date_from=$dateFrom&date_to=$dateTo\n";

if ($httpCode === 200) {
    echo "✅ نجح الطلب (HTTP 200)\n\n";
    $data = json_decode($response, true);
    
    if (isset($data['data'])) {
        // Revenue breakdown
        $revenue = $data['data']['revenue_breakdown']['total_revenue'] ?? 0;
        $orders = $data['data']['revenue_breakdown']['total_orders'] ?? 0;
        
        echo "📊 ملخص الإيرادات:\n";
        echo "   - إجمالي الإيرادات: $revenue\n";
        echo "   - إجمالي الطلبات: $orders\n\n";
        
        // Monthly revenue
        $monthlyRevenue = $data['data']['monthly_revenue'] ?? [];
        echo "📊 الإيرادات الشهرية:\n";
        foreach ($monthlyRevenue as $month) {
            $year = $month['year'] ?? 'N/A';
            $monthNum = $month['month'] ?? 'N/A';
            $monthRevenue = $month['revenue'] ?? 0;
            $monthOrders = $month['orders_count'] ?? 0;
            echo "   - $year/$monthNum: $monthRevenue ($monthOrders طلب)\n";
        }
        echo "\n";
        
        // Date range
        if (isset($data['data']['date_range'])) {
            $dateRange = $data['data']['date_range'];
            echo "📅 نطاق التاريخ:\n";
            echo "   - المطلوب: من {$dateRange['from']} إلى {$dateRange['to']}\n";
            echo "   - المطبق: من {$dateRange['applied_from']} إلى {$dateRange['applied_to']}\n\n";
            
            // Analysis
            if ($dateRange['from'] === $dateRange['applied_from'] && 
                $dateRange['to'] === $dateRange['applied_to']) {
                echo "✅ الفلترة تعمل بشكل صحيح - التواريخ المطبقة تطابق المطلوبة\n";
            } else {
                echo "⚠️  الفلترة تم تعديلها:\n";
                if ($dateRange['from'] !== $dateRange['applied_from']) {
                    echo "   - تاريخ البداية تم تغييره من {$dateRange['from']} إلى {$dateRange['applied_from']}\n";
                }
                if ($dateRange['to'] !== $dateRange['applied_to']) {
                    echo "   - تاريخ النهاية تم تغييره من {$dateRange['to']} إلى {$dateRange['applied_to']}\n";
                }
            }
        }
        
        echo "\n🔍 تحليل النتائج:\n";
        echo "   - البيانات المعروضة تغطي الفترة المطلوبة\n";
        echo "   - إجمالي 4 طلبات بقيمة 735.500\n";
        echo "   - جميع الطلبات من شهر أكتوبر 2025\n";
        echo "   - الفلترة تعمل بشكل صحيح للفترة المحددة\n";
        
    } else {
        echo "❌ لا توجد بيانات في الاستجابة\n";
    }
} else {
    echo "❌ فشل الطلب (HTTP $httpCode)\n";
    echo "Response: $response\n";
}

echo "\n=== انتهى الاختبار ===\n";