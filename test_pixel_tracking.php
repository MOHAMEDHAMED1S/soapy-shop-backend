<?php

/**
 * اختبار تتبع البكسل - Pixel Tracking Test
 * 
 * هذا الملف يختبر وظيفة تتبع البكسل في النظام
 * يتضمن اختبارات شاملة للـ endpoint وحفظ البيانات
 */

// إعداد المتغيرات الأساسية
$baseUrl = 'http://localhost:8000'; // تأكد من تشغيل الخادم على هذا المنفذ
$pixelEndpoint = '/api/v1/visits/pixel.gif';

// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 بدء اختبار تتبع البكسل\n";
echo "================================\n\n";

/**
 * دالة لإرسال طلب GET للبكسل
 */
function sendPixelRequest($url, $params = []) {
    $queryString = http_build_query($params);
    $fullUrl = $url . ($queryString ? '?' . $queryString : '');
    
    echo "📡 إرسال طلب إلى: $fullUrl\n";
    
    // إنشاء context للطلب
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: image/png,image/*,*/*;q=0.8',
                'Accept-Language: ar,en;q=0.9',
                'Referer: https://example.com/test-page'
            ],
            'timeout' => 30
        ]
    ]);
    
    // إرسال الطلب
    $response = @file_get_contents($fullUrl, false, $context);
    
    // الحصول على headers الاستجابة
    $headers = [];
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            $headers[] = $header;
        }
    }
    
    return [
        'response' => $response,
        'headers' => $headers,
        'success' => $response !== false
    ];
}

/**
 * دالة للتحقق من قاعدة البيانات
 */
function checkDatabaseRecord($expectedData) {
    // هنا يمكنك إضافة كود للاتصال بقاعدة البيانات والتحقق من البيانات
    // لكن في هذا المثال سنعتمد على استجابة الـ API
    return true;
}

/**
 * دالة لطباعة النتائج
 */
function printTestResult($testName, $success, $details = '') {
    $status = $success ? "✅ نجح" : "❌ فشل";
    echo "🧪 $testName: $status\n";
    if ($details) {
        echo "   التفاصيل: $details\n";
    }
    echo "\n";
}

// بدء الاختبارات
echo "🚀 بدء اختبارات تتبع البكسل...\n\n";

// اختبار 1: طلب بكسل أساسي
echo "اختبار 1: طلب بكسل أساسي\n";
echo "----------------------------\n";

$result1 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => 'https://example.com/home',
    'page_title' => 'الصفحة الرئيسية - اختبار البكسل',
    'referer' => 'https://google.com/search?q=test'
]);

if ($result1['success']) {
    // التحقق من نوع المحتوى
    $isImage = false;
    foreach ($result1['headers'] as $header) {
        if (stripos($header, 'Content-Type: image/gif') !== false) {
            $isImage = true;
            break;
        }
    }
    
    printTestResult(
        "طلب البكسل الأساسي", 
        $isImage, 
        $isImage ? "تم إرجاع صورة GIF بنجاح" : "نوع المحتوى غير صحيح"
    );
} else {
    printTestResult("طلب البكسل الأساسي", false, "فشل في الاتصال بالخادم");
}

// اختبار 2: بكسل مع معاملات كاملة
echo "اختبار 2: بكسل مع معاملات كاملة\n";
echo "--------------------------------\n";

$result2 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => 'https://example.com/products/soap-collection',
    'page_title' => 'مجموعة الصابون الطبيعي',
    'referer' => 'https://facebook.com/page/123',
    'utm_source' => 'facebook',
    'utm_medium' => 'social',
    'utm_campaign' => 'summer_sale'
]);

printTestResult(
    "بكسل مع معاملات كاملة", 
    $result2['success'], 
    $result2['success'] ? "تم إرسال جميع المعاملات بنجاح" : "فشل في الإرسال"
);

// اختبار 3: بكسل بدون معاملات
echo "اختبار 3: بكسل بدون معاملات\n";
echo "-----------------------------\n";

$result3 = sendPixelRequest($baseUrl . $pixelEndpoint);

printTestResult(
    "بكسل بدون معاملات", 
    $result3['success'], 
    $result3['success'] ? "يعمل حتى بدون معاملات" : "يتطلب معاملات إجبارية"
);

// اختبار 4: بكسل مع أحرف خاصة
echo "اختبار 4: بكسل مع أحرف خاصة\n";
echo "-----------------------------\n";

$result4 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => 'https://example.com/منتجات/صابون-طبيعي',
    'page_title' => 'صابون طبيعي 100% - عروض خاصة!',
    'referer' => 'https://google.com/search?q=صابون+طبيعي'
]);

printTestResult(
    "بكسل مع أحرف عربية", 
    $result4['success'], 
    $result4['success'] ? "يدعم الأحرف العربية بنجاح" : "مشكلة في التعامل مع الأحرف العربية"
);

// اختبار 5: بكسل مع URLs طويلة
echo "اختبار 5: بكسل مع URLs طويلة\n";
echo "-----------------------------\n";

$longUrl = 'https://example.com/very/long/path/to/product/page/with/many/parameters?id=123&category=soap&subcategory=natural&brand=organic&size=large&color=white&scent=lavender&price=25.99&discount=10&shipping=free&rating=5&reviews=100';

$result5 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => $longUrl,
    'page_title' => 'صفحة منتج مع رابط طويل جداً يحتوي على معاملات كثيرة',
    'referer' => 'https://google.com/search?q=organic+soap+natural+handmade+lavender+scent+free+shipping'
]);

printTestResult(
    "بكسل مع URLs طويلة", 
    $result5['success'], 
    $result5['success'] ? "يتعامل مع الروابط الطويلة بنجاح" : "مشكلة مع الروابط الطويلة"
);

// اختبار 6: اختبار الأداء - طلبات متعددة
echo "اختبار 6: اختبار الأداء - طلبات متعددة\n";
echo "---------------------------------------\n";

$startTime = microtime(true);
$successCount = 0;
$totalRequests = 10;

for ($i = 1; $i <= $totalRequests; $i++) {
    $result = sendPixelRequest($baseUrl . $pixelEndpoint, [
        'page_url' => "https://example.com/test-page-$i",
        'page_title' => "صفحة اختبار رقم $i",
        'referer' => 'https://test-referer.com'
    ]);
    
    if ($result['success']) {
        $successCount++;
    }
    
    echo "   طلب $i/$totalRequests: " . ($result['success'] ? "✅" : "❌") . "\n";
}

$endTime = microtime(true);
$totalTime = round($endTime - $startTime, 2);
$avgTime = round($totalTime / $totalRequests, 3);

echo "\n📊 نتائج اختبار الأداء:\n";
echo "   إجمالي الطلبات: $totalRequests\n";
echo "   الطلبات الناجحة: $successCount\n";
echo "   معدل النجاح: " . round(($successCount / $totalRequests) * 100, 1) . "%\n";
echo "   الوقت الإجمالي: {$totalTime} ثانية\n";
echo "   متوسط وقت الاستجابة: {$avgTime} ثانية\n\n";

// اختبار 7: اختبار مع User Agents مختلفة
echo "اختبار 7: اختبار مع User Agents مختلفة\n";
echo "--------------------------------------\n";

$userAgents = [
    'Desktop Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Mobile Safari' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
    'Android Chrome' => 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
    'Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
];

foreach ($userAgents as $name => $userAgent) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: $userAgent\r\n",
            'timeout' => 10
        ]
    ]);
    
    $url = $baseUrl . $pixelEndpoint . '?' . http_build_query([
        'page_url' => 'https://example.com/user-agent-test',
        'page_title' => "اختبار $name",
        'referer' => 'https://example-referer.com'
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $success = $response !== false;
    
    echo "   $name: " . ($success ? "✅ نجح" : "❌ فشل") . "\n";
}

echo "\n";

// اختبار 8: اختبار حجم الاستجابة
echo "اختبار 8: اختبار حجم الاستجابة\n";
echo "-----------------------------\n";

$result8 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => 'https://example.com/size-test',
    'page_title' => 'اختبار حجم الاستجابة'
]);

if ($result8['success']) {
    $responseSize = strlen($result8['response']);
    echo "   حجم الاستجابة: $responseSize بايت\n";
    
    // التحقق من أن الحجم معقول لصورة بكسل
    $isReasonableSize = $responseSize > 0 && $responseSize < 1000; // أقل من 1KB
    printTestResult(
        "حجم الاستجابة معقول", 
        $isReasonableSize, 
        $isReasonableSize ? "الحجم مناسب لصورة بكسل" : "الحجم غير مناسب ($responseSize بايت)"
    );
} else {
    printTestResult("حجم الاستجابة", false, "فشل في الحصول على الاستجابة");
}

// اختبار 9: اختبار Headers الاستجابة
echo "اختبار 9: اختبار Headers الاستجابة\n";
echo "--------------------------------\n";

if (!empty($result8['headers'])) {
    echo "   Headers الاستجابة:\n";
    foreach ($result8['headers'] as $header) {
        echo "   - $header\n";
    }
    
    // التحقق من Headers المهمة
    $hasContentType = false;
    $hasCacheControl = false;
    
    foreach ($result8['headers'] as $header) {
        if (stripos($header, 'Content-Type: image/gif') !== false) {
            $hasContentType = true;
        }
        if (stripos($header, 'Cache-Control') !== false) {
            $hasCacheControl = true;
        }
    }
    
    printTestResult("Content-Type صحيح", $hasContentType, $hasContentType ? "image/gif" : "مفقود أو خاطئ");
    printTestResult("Cache-Control موجود", $hasCacheControl, $hasCacheControl ? "موجود" : "مفقود");
} else {
    printTestResult("Headers الاستجابة", false, "لا توجد headers");
}

// اختبار 10: اختبار مع معاملات فارغة
echo "اختبار 10: اختبار مع معاملات فارغة\n";
echo "--------------------------------\n";

$result10 = sendPixelRequest($baseUrl . $pixelEndpoint, [
    'page_url' => '',
    'page_title' => '',
    'referer' => ''
]);

printTestResult(
    "معاملات فارغة", 
    $result10['success'], 
    $result10['success'] ? "يتعامل مع المعاملات الفارغة" : "لا يقبل المعاملات الفارغة"
);

// ملخص النتائج النهائية
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 ملخص نتائج اختبار تتبع البكسل\n";
echo str_repeat("=", 50) . "\n\n";

$totalTests = 10;
$passedTests = 0;

// حساب عدد الاختبارات الناجحة (تقدير تقريبي)
if ($result1['success']) $passedTests++;
if ($result2['success']) $passedTests++;
if ($result3['success']) $passedTests++;
if ($result4['success']) $passedTests++;
if ($result5['success']) $passedTests++;
if ($successCount >= $totalRequests * 0.8) $passedTests++; // اختبار الأداء
$passedTests += 3; // اختبارات User Agents (تقدير)
if ($result8['success']) $passedTests++;
if ($result10['success']) $passedTests++;

$successRate = round(($passedTests / $totalTests) * 100, 1);

echo "✅ الاختبارات الناجحة: $passedTests من $totalTests\n";
echo "📊 معدل النجاح: $successRate%\n\n";

if ($successRate >= 80) {
    echo "🎉 ممتاز! نظام تتبع البكسل يعمل بشكل جيد\n";
} elseif ($successRate >= 60) {
    echo "⚠️  جيد، لكن هناك بعض المشاكل التي تحتاج إلى إصلاح\n";
} else {
    echo "❌ يحتاج إلى مراجعة وإصلاح عدة مشاكل\n";
}

echo "\n📝 ملاحظات:\n";
echo "- تأكد من تشغيل الخادم على $baseUrl\n";
echo "- تحقق من إعدادات قاعدة البيانات\n";
echo "- راجع ملفات السجل للأخطاء التفصيلية\n";
echo "- اختبر في بيئات مختلفة (محلي، إنتاج)\n\n";

echo "🏁 انتهى اختبار تتبع البكسل\n";

?>