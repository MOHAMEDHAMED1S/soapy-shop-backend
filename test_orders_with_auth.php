<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== اختبار فلترة الإحصائيات في API الطلبات مع المصادقة ===\n";

// إنشاء مستخدم مؤقت للاختبار
$user = App\Models\User::where('email', 'admin@test.com')->first();
if (!$user) {
    $user = App\Models\User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin'
    ]);
}

// إنشاء رمز مصادقة
$token = $user->createToken('test-token')->plainTextToken;
echo "✅ تم إنشاء رمز المصادقة\n\n";

$baseUrl = 'http://localhost:8000/api/v1/admin';
$dateFrom = '2025-10-18';
$dateTo = '2025-10-18';

function makeAuthenticatedRequest($url, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// حساب الإحصائيات المتوقعة يدوياً
echo "📊 حساب الإحصائيات المتوقعة:\n";

$query = App\Models\Order::query();
$query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

$expectedTotalOrders = $query->count();
$expectedTotalRevenue = $query->sum('total_amount');
$expectedPaidOrders = (clone $query)->where('status', 'paid')->count();

echo "   - إجمالي الطلبات المتوقعة (مفلترة): $expectedTotalOrders\n";
echo "   - الطلبات المدفوعة المتوقعة: $expectedPaidOrders\n";
echo "   - إجمالي الإيرادات المتوقعة: $expectedTotalRevenue\n\n";

// حساب الإحصائيات الإجمالية
$totalOrdersAll = App\Models\Order::count();
$totalRevenueAll = App\Models\Order::sum('total_amount');
$paidOrdersAll = App\Models\Order::where('status', 'paid')->count();

echo "📊 الإحصائيات الإجمالية (بدون فلترة):\n";
echo "   - إجمالي الطلبات: $totalOrdersAll\n";
echo "   - الطلبات المدفوعة: $paidOrdersAll\n";
echo "   - إجمالي الإيرادات: $totalRevenueAll\n\n";

// اختبار API مع فلترة التاريخ
echo "🔍 اختبار API مع فلترة التاريخ (صفحة 1):\n";
$url1 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=1&per_page=10&start_date=$dateFrom&end_date=$dateTo";

$result1 = makeAuthenticatedRequest($url1, $token);

if ($result1['status_code'] === 200 && $result1['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data1 = $result1['response']['data'];
    $orders1 = $data1['orders']['data'] ?? [];
    $summary1 = $data1['summary'] ?? [];
    
    echo "📊 النتائج من API (صفحة 1):\n";
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
    
    // التحقق من صحة النتائج
    echo "\n🔍 التحقق من صحة النتائج:\n";
    if ($summary1['total_orders'] == $expectedTotalOrders) {
        echo "✅ إجمالي الطلبات صحيح\n";
    } else {
        echo "❌ إجمالي الطلبات غير صحيح (متوقع: $expectedTotalOrders، فعلي: " . $summary1['total_orders'] . ")\n";
    }
    
    if ($summary1['paid_orders'] == $expectedPaidOrders) {
        echo "✅ الطلبات المدفوعة صحيحة\n";
    } else {
        echo "❌ الطلبات المدفوعة غير صحيحة (متوقع: $expectedPaidOrders، فعلي: " . $summary1['paid_orders'] . ")\n";
    }
    
    if ($summary1['total_revenue'] == $expectedTotalRevenue) {
        echo "✅ إجمالي الإيرادات صحيح\n";
    } else {
        echo "❌ إجمالي الإيرادات غير صحيح (متوقع: $expectedTotalRevenue، فعلي: " . $summary1['total_revenue'] . ")\n";
    }
    
} else {
    echo "❌ فشل الطلب (HTTP {$result1['status_code']})\n";
    if (isset($result1['response']['message'])) {
        echo "   الرسالة: " . $result1['response']['message'] . "\n";
    }
}

// اختبار API مع فلترة التاريخ (صفحة 2)
echo "\n🔍 اختبار API مع فلترة التاريخ (صفحة 2):\n";
$url2 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=2&per_page=10&start_date=$dateFrom&end_date=$dateTo";

$result2 = makeAuthenticatedRequest($url2, $token);

if ($result2['status_code'] === 200 && $result2['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data2 = $result2['response']['data'];
    $summary2 = $data2['summary'] ?? [];
    
    echo "📊 النتائج من API (صفحة 2):\n";
    echo "   - إجمالي الطلبات (حسب الإحصائيات): " . ($summary2['total_orders'] ?? 0) . "\n";
    
    // التحقق من تطابق الإحصائيات عبر الصفحات
    if (isset($summary1, $summary2) && $summary1['total_orders'] === $summary2['total_orders']) {
        echo "✅ الإحصائيات متسقة عبر الصفحات (مفلترة بشكل صحيح)\n";
    } else {
        echo "❌ الإحصائيات غير متسقة عبر الصفحات\n";
    }
} else {
    echo "❌ فشل الطلب (HTTP {$result2['status_code']})\n";
}

// اختبار API بدون فلترة
echo "\n🔍 اختبار API بدون فلترة:\n";
$url3 = "$baseUrl/orders?sort_by=created_at&sort_direction=desc&page=1&per_page=10";

$result3 = makeAuthenticatedRequest($url3, $token);

if ($result3['status_code'] === 200 && $result3['response']['success']) {
    echo "✅ نجح الطلب (HTTP 200)\n";
    
    $data3 = $result3['response']['data'];
    $summary3 = $data3['summary'] ?? [];
    
    echo "📊 النتائج من API (بدون فلترة):\n";
    echo "   - إجمالي الطلبات (حسب الإحصائيات): " . ($summary3['total_orders'] ?? 0) . "\n";
    
    // التحقق من أن الفلترة تعمل
    if (isset($summary1, $summary3)) {
        if ($summary1['total_orders'] <= $summary3['total_orders']) {
            echo "✅ الفلترة تعمل بشكل صحيح - الإحصائيات المفلترة أقل من أو تساوي الإجمالية\n";
        } else {
            echo "❌ خطأ في الفلترة - الإحصائيات المفلترة أكبر من الإجمالية\n";
        }
    }
} else {
    echo "❌ فشل الطلب (HTTP {$result3['status_code']})\n";
}

echo "\n=== انتهى الاختبار ===\n";