<?php

echo "=== اختبار فلترة الإحصائيات في API الطلبات ===\n";
echo "الهدف: التأكد من أن الإحصائيات مفلترة حسب التاريخ والبحث وليس حسب الصفحة\n\n";

$baseUrl = 'http://localhost:8000/api/v1/admin';

// Test dates
$dateFrom = '2025-10-18';
$dateTo = '2025-10-18';

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
        'response' => json_decode($response, true)
    ];
}

echo "🔍 اختبار 1: جلب الطلبات بفلترة التاريخ (صفحة 1)\n";
$url1 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=1&per_page=10&start_date=$dateFrom&end_date=$dateTo";
echo "URL: $url1\n";

$result1 = makeRequest($url1);

if ($result1['status_code'] === 200 && $result1['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data1 = $result1['response']['data'];
    $orders1 = $data1['orders']['data'] ?? [];
    $summary1 = $data1['summary'] ?? [];
    
    echo "📊 النتائج (صفحة 1):\n";
    echo "   - عدد الطلبات في الصفحة: " . count($orders1) . "\n";
    echo "   - إجمالي الطلبات (حسب الإحصائيات): " . ($summary1['total_orders'] ?? 0) . "\n";
    echo "   - الطلبات المدفوعة: " . ($summary1['paid_orders'] ?? 0) . "\n";
    echo "   - إجمالي الإيرادات: " . ($summary1['total_revenue'] ?? 0) . "\n";
    
    if (isset($summary1['filters_applied'])) {
        echo "   - الفلاتر المطبقة:\n";
        foreach ($summary1['filters_applied'] as $key => $value) {
            if ($value !== null) {
                echo "     * $key: $value\n";
            }
        }
    }
} else {
    echo "❌ فشل الطلب (HTTP {$result1['status_code']})\n";
    if (isset($result1['response']['message'])) {
        echo "   الرسالة: " . $result1['response']['message'] . "\n";
    }
}

echo "\n🔍 اختبار 2: جلب الطلبات بفلترة التاريخ (صفحة 2)\n";
$url2 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=2&per_page=10&start_date=$dateFrom&end_date=$dateTo";
echo "URL: $url2\n";

$result2 = makeRequest($url2);

if ($result2['status_code'] === 200 && $result2['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data2 = $result2['response']['data'];
    $orders2 = $data2['orders']['data'] ?? [];
    $summary2 = $data2['summary'] ?? [];
    
    echo "📊 النتائج (صفحة 2):\n";
    echo "   - عدد الطلبات في الصفحة: " . count($orders2) . "\n";
    echo "   - إجمالي الطلبات (حسب الإحصائيات): " . ($summary2['total_orders'] ?? 0) . "\n";
    echo "   - الطلبات المدفوعة: " . ($summary2['paid_orders'] ?? 0) . "\n";
    echo "   - إجمالي الإيرادات: " . ($summary2['total_revenue'] ?? 0) . "\n";
} else {
    echo "❌ فشل الطلب (HTTP {$result2['status_code']})\n";
}

echo "\n🔍 اختبار 3: جلب جميع الطلبات بدون فلترة (صفحة 1)\n";
$url3 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=1&per_page=10";
echo "URL: $url3\n";

$result3 = makeRequest($url3);

if ($result3['status_code'] === 200 && $result3['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data3 = $result3['response']['data'];
    $orders3 = $data3['orders']['data'] ?? [];
    $summary3 = $data3['summary'] ?? [];
    
    echo "📊 النتائج (بدون فلترة):\n";
    echo "   - عدد الطلبات في الصفحة: " . count($orders3) . "\n";
    echo "   - إجمالي الطلبات (حسب الإحصائيات): " . ($summary3['total_orders'] ?? 0) . "\n";
    echo "   - الطلبات المدفوعة: " . ($summary3['paid_orders'] ?? 0) . "\n";
    echo "   - إجمالي الإيرادات: " . ($summary3['total_revenue'] ?? 0) . "\n";
} else {
    echo "❌ فشل الطلب (HTTP {$result3['status_code']})\n";
}

echo "\n📋 تحليل النتائج:\n";

if (isset($summary1, $summary2, $summary3)) {
    // Check if filtered statistics are consistent across pages
    if ($summary1['total_orders'] === $summary2['total_orders']) {
        echo "✅ الإحصائيات متسقة عبر الصفحات المختلفة (مفلترة بشكل صحيح)\n";
    } else {
        echo "❌ الإحصائيات غير متسقة عبر الصفحات\n";
    }
    
    // Check if filtering is working
    if ($summary1['total_orders'] < $summary3['total_orders']) {
        echo "✅ الفلترة تعمل بشكل صحيح - الإحصائيات المفلترة أقل من الإجمالية\n";
    } else if ($summary1['total_orders'] === $summary3['total_orders']) {
        echo "ℹ️  الإحصائيات المفلترة تساوي الإجمالية (قد تكون جميع الطلبات في نفس التاريخ)\n";
    } else {
        echo "❌ خطأ في الفلترة - الإحصائيات المفلترة أكبر من الإجمالية\n";
    }
    
    echo "\n📊 مقارنة الإحصائيات:\n";
    echo "   - مفلترة (التاريخ): " . $summary1['total_orders'] . " طلب\n";
    echo "   - غير مفلترة: " . $summary3['total_orders'] . " طلب\n";
    echo "   - الفرق: " . ($summary3['total_orders'] - $summary1['total_orders']) . " طلب\n";
}

echo "\n=== انتهى الاختبار ===\n";