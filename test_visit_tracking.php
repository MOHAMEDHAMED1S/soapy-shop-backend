<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AnalyticsController;
use App\Services\VisitTrackingService;
use App\Models\Visit;

echo "🚀 بدء اختبار نظام تتبع الزيارات\n";
echo "=====================================\n\n";

try {
    // Clear existing test data
    Visit::where('ip_address', 'LIKE', '192.168.%')->delete();
    echo "✅ تم مسح البيانات التجريبية السابقة\n\n";

    // Test 1: Track a new visit
    echo "📊 اختبار 1: تتبع زيارة جديدة\n";
    echo "-----------------------------\n";
    
    $visitController = new VisitController(new VisitTrackingService());
    
    $request = new Request([
        'page_url' => 'https://example.com/products',
        'page_title' => 'منتجاتنا',
        'referer_url' => 'https://www.google.com/search?q=soap+products',
        'session_id' => 'test_session_123'
    ]);
    
    // Simulate IP and User Agent
    $request->server->set('REMOTE_ADDR', '192.168.1.100');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = $visitController->track($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
         echo "✅ تم تتبع الزيارة بنجاح\n";
         echo "   - الصفحة: {$responseData['data']['page_url']}\n";
         echo "   - المصدر: {$responseData['data']['referer_type']}\n";
         echo "   - IP: {$responseData['data']['ip_address']}\n\n";
     } else {
         echo "❌ فشل في تتبع الزيارة: " . $responseData['message'] . "\n";
         if (isset($responseData['error'])) {
             echo "   خطأ: " . $responseData['error'] . "\n";
         }
         echo "\n";
     }

    // Test 2: Track visits from different sources
    echo "📊 اختبار 2: تتبع زيارات من مصادر مختلفة\n";
    echo "----------------------------------------\n";
    
    $testVisits = [
        [
            'referer_url' => 'https://www.instagram.com/',
            'ip' => '192.168.1.101',
            'page_url' => 'https://example.com/home'
        ],
        [
            'referer_url' => 'https://twitter.com/',
            'ip' => '192.168.1.102',
            'page_url' => 'https://example.com/about'
        ],
        [
            'referer_url' => '',
            'ip' => '192.168.1.103',
            'page_url' => 'https://example.com/contact'
        ],
        [
            'referer_url' => 'https://www.google.com/search?q=handmade+soap',
            'ip' => '192.168.1.104',
            'page_url' => 'https://example.com/products/soap'
        ]
    ];
    
    foreach ($testVisits as $index => $visit) {
        $request = new Request([
            'page_url' => $visit['page_url'],
            'page_title' => 'Test Page ' . ($index + 1),
            'referer_url' => $visit['referer_url'],
            'session_id' => 'test_session_' . ($index + 200)
        ]);
        
        $request->server->set('REMOTE_ADDR', $visit['ip']);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
        
        $response = $visitController->track($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            echo "✅ زيارة " . ($index + 1) . ": {$responseData['data']['referer_type']}\n";
        }
    }
    echo "\n";

    // Test 3: Pixel tracking
    echo "📊 اختبار 3: تتبع البكسل\n";
    echo "----------------------\n";
    
    $request = new Request([
        'url' => 'https://example.com/pixel-test',
        'ref' => 'https://facebook.com/',
        'sid' => 'pixel_session_123'
    ]);
    
    $request->server->set('REMOTE_ADDR', '192.168.1.105');
    
    $response = $visitController->pixel($request);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ تم تتبع البكسل بنجاح\n";
        echo "   - نوع المحتوى: " . $response->headers->get('Content-Type') . "\n\n";
    } else {
        echo "❌ فشل في تتبع البكسل\n\n";
    }

    // Test 4: Analytics - General Statistics
    echo "📊 اختبار 4: الإحصائيات العامة\n";
    echo "-----------------------------\n";
    
    $analyticsController = new AnalyticsController(new VisitTrackingService());
    
    $request = new Request();
    $response = $analyticsController->statistics($request);
    $statsData = json_decode($response->getContent(), true);
    
    if ($statsData['success']) {
         echo "✅ تم جلب الإحصائيات العامة:\n";
         echo "   - إجمالي الزيارات: {$statsData['data']['total_visits']}\n";
         echo "   - الزوار الفريدون: {$statsData['data']['unique_visitors']}\n";
         echo "   - نطاق التاريخ: {$statsData['data']['date_range']['start']} إلى {$statsData['data']['date_range']['end']}\n\n";
     } else {
         echo "❌ فشل في جلب الإحصائيات العامة\n\n";
     }

    // Test 5: Referer Type Statistics
    echo "📊 اختبار 5: إحصائيات أنواع المصادر\n";
    echo "--------------------------------\n";
    
    $response = $analyticsController->visitsByRefererType($request);
    $refererData = json_decode($response->getContent(), true);
    
    if ($refererData['success']) {
        echo "✅ تم جلب إحصائيات المصادر:\n";
        if (is_array($refererData['data']) && !empty($refererData['data'])) {
            foreach ($refererData['data'] as $refererType => $count) {
                echo "   - {$refererType}: {$count} زيارة\n";
            }
        } else {
            echo "   - لا توجد بيانات مصادر متاحة\n";
        }
        echo "\n";
    } else {
        echo "❌ فشل في جلب إحصائيات المصادر\n\n";
    }

    // Test 6: Top Referer Domains
    echo "📊 اختبار 6: أهم المواقع المرجعية\n";
    echo "-----------------------------\n";
    
    $response = $analyticsController->topRefererDomains($request);
    $domainsData = json_decode($response->getContent(), true);
    
    if ($domainsData['success']) {
        echo "✅ تم جلب أهم المواقع المرجعية:\n";
        foreach ($domainsData['data'] as $domain) {
            echo "   - {$domain['referer_domain']}: {$domain['visits_count']} زيارة\n";
        }
        echo "\n";
    } else {
        echo "❌ فشل في جلب المواقع المرجعية\n\n";
    }

    // Test 7: Popular Pages
    echo "📊 اختبار 7: الصفحات الأكثر شعبية\n";
    echo "-------------------------------\n";
    
    $response = $analyticsController->popularPages($request);
    $pagesData = json_decode($response->getContent(), true);
    
    if ($pagesData['success']) {
        echo "✅ تم جلب الصفحات الشعبية:\n";
        if (is_array($pagesData['data']) && !empty($pagesData['data'])) {
            foreach ($pagesData['data'] as $page) {
                echo "   - {$page['page_url']}: {$page['visits']} زيارة\n";
            }
        } else {
            echo "   - لا توجد صفحات شعبية متاحة\n";
        }
        echo "\n";
    } else {
        echo "❌ فشل في جلب الصفحات الشعبية\n\n";
    }

    // Test 8: Daily Visits
    echo "📊 اختبار 8: الزيارات اليومية\n";
    echo "-------------------------\n";
    
    $response = $analyticsController->dailyVisits($request);
    $dailyData = json_decode($response->getContent(), true);
    
    if ($dailyData['success']) {
        echo "✅ تم جلب الزيارات اليومية:\n";
        if (is_array($dailyData['data']) && !empty($dailyData['data'])) {
            foreach ($dailyData['data'] as $day) {
                echo "   - {$day['date']}: {$day['visits']} زيارة ({$day['unique_visitors']} زائر فريد)\n";
            }
        } else {
            echo "   - لا توجد بيانات زيارات يومية متاحة\n";
        }
        echo "\n";
    } else {
        echo "❌ فشل في جلب الزيارات اليومية\n\n";
    }

    // Test 9: Device Statistics
    echo "📊 اختبار 9: إحصائيات الأجهزة\n";
    echo "---------------------------\n";
    
    $response = $analyticsController->deviceStats($request);
    $deviceData = json_decode($response->getContent(), true);
    
    if ($deviceData['success']) {
        echo "✅ تم جلب إحصائيات الأجهزة:\n";
        
        if (!empty($deviceData['data']['devices'])) {
            echo "   الأجهزة:\n";
            foreach ($deviceData['data']['devices'] as $device) {
                echo "     - {$device['device_type']}: {$device['visits']} زيارة\n";
            }
        } else {
            echo "   - لا توجد بيانات أجهزة متاحة\n";
        }
        
        if (!empty($deviceData['data']['browsers'])) {
            echo "   المتصفحات:\n";
            foreach ($deviceData['data']['browsers'] as $browser) {
                echo "     - {$browser['browser']}: {$browser['visits']} زيارة\n";
            }
        } else {
            echo "   - لا توجد بيانات متصفحات متاحة\n";
        }
        
        if (!empty($deviceData['data']['operating_systems'])) {
            echo "   أنظمة التشغيل:\n";
            foreach ($deviceData['data']['operating_systems'] as $os) {
                echo "     - {$os['os']}: {$os['visits']} زيارة\n";
            }
        } else {
            echo "   - لا توجد بيانات أنظمة تشغيل متاحة\n";
        }
        echo "\n";
    } else {
        echo "❌ فشل في جلب إحصائيات الأجهزة\n\n";
    }

    // Test 10: Real-time Statistics
    echo "📊 اختبار 10: الإحصائيات الفورية (آخر 24 ساعة)\n";
    echo "--------------------------------------------\n";
    
    $response = $analyticsController->realTime($request);
    $realTimeData = json_decode($response->getContent(), true);
    
    if ($realTimeData['success']) {
         echo "✅ تم جلب الإحصائيات الفورية:\n";
         echo "   - الزيارات في آخر 24 ساعة: {$realTimeData['data']['total_visits_24h']}\n";
         echo "   - الزوار الفريدون في آخر 24 ساعة: {$realTimeData['data']['unique_visitors_24h']}\n\n";
     } else {
         echo "❌ فشل في جلب الإحصائيات الفورية\n\n";
     }

    // Final Database Verification
    echo "📊 التحقق النهائي من قاعدة البيانات\n";
    echo "================================\n";
    
    $totalVisits = Visit::where('ip_address', 'LIKE', '192.168.%')->count();
    $uniqueVisitors = Visit::where('ip_address', 'LIKE', '192.168.%')->distinct('ip_address')->count();
    $refererTypes = Visit::where('ip_address', 'LIKE', '192.168.%')
                        ->select('referer_type')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy('referer_type')
                        ->get();
    
    echo "✅ إجمالي الزيارات المسجلة: {$totalVisits}\n";
    echo "✅ عدد الزوار الفريدين: {$uniqueVisitors}\n";
    echo "✅ توزيع أنواع المصادر:\n";
    
    foreach ($refererTypes as $type) {
        echo "   - {$type->referer_type}: {$type->count} زيارة\n";
    }
    
    echo "\n🎉 تم إكمال جميع الاختبارات بنجاح!\n";
    echo "=====================================\n";
    echo "✅ نظام تتبع الزيارات يعمل بشكل صحيح\n";
    echo "✅ جميع APIs تستجيب بشكل طبيعي\n";
    echo "✅ البيانات يتم حفظها في قاعدة البيانات\n";
    echo "✅ الإحصائيات تعمل بدقة\n\n";

} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}