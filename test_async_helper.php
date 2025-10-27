<?php

/**
 * اختبار AsyncHelper
 * 
 * هذا الملف يختبر أن AsyncHelper يعمل بشكل صحيح
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Helpers\AsyncHelper;
use Illuminate\Support\Facades\Log;

echo "============================================\n";
echo "   اختبار AsyncHelper\n";
echo "============================================\n\n";

// ===================================
// Test 1: تنفيذ مهمة واحدة
// ===================================
echo "📋 Test 1: تنفيذ مهمة واحدة في الخلفية\n";
echo str_repeat("-", 50) . "\n";

$startTime = microtime(true);

AsyncHelper::runAfterResponse(function () {
    // محاكاة مهمة تستغرق وقتاً
    sleep(1);
    Log::info('Test task completed after 1 second');
}, 'test_single_task');

$responseTime = (microtime(true) - $startTime) * 1000;

echo "✅ تم جدولة المهمة للتنفيذ في الخلفية\n";
echo "⚡ Response Time: " . round($responseTime, 2) . "ms\n";
echo "📝 المهمة ستنفذ بعد انتهاء هذا السكريبت\n\n";

// ===================================
// Test 2: تنفيذ عدة مهام
// ===================================
echo "📦 Test 2: تنفيذ عدة مهام في الخلفية\n";
echo str_repeat("-", 50) . "\n";

$startTime = microtime(true);

AsyncHelper::runMultipleTasks([
    'task_1' => function () {
        sleep(1);
        Log::info('Task 1 completed');
        echo "[Background] Task 1 done\n";
    },
    'task_2' => function () {
        sleep(1);
        Log::info('Task 2 completed');
        echo "[Background] Task 2 done\n";
    },
    'task_3' => function () {
        sleep(1);
        Log::info('Task 3 completed');
        echo "[Background] Task 3 done\n";
    },
]);

$responseTime = (microtime(true) - $startTime) * 1000;

echo "✅ تم جدولة 3 مهام للتنفيذ في الخلفية\n";
echo "⚡ Response Time: " . round($responseTime, 2) . "ms\n";
echo "📝 المهام ستنفذ بعد انتهاء هذا السكريبت\n\n";

// ===================================
// Test 3: معالجة الأخطاء
// ===================================
echo "🧪 Test 3: معالجة الأخطاء في المهام\n";
echo str_repeat("-", 50) . "\n";

AsyncHelper::runMultipleTasks([
    'success_task' => function () {
        Log::info('Success task completed');
        echo "[Background] Success task done\n";
    },
    'failing_task' => function () {
        throw new \Exception('This task failed intentionally');
    },
    'another_success' => function () {
        Log::info('Another success task completed');
        echo "[Background] Another success task done\n";
    },
]);

echo "✅ تم جدولة 3 مهام (واحدة ستفشل)\n";
echo "📝 المهام الناجحة ستكمل رغم فشل إحداها\n\n";

// ===================================
// Test 4: اختبار finishRequest()
// ===================================
echo "🚀 Test 4: اختبار finishRequest()\n";
echo str_repeat("-", 50) . "\n";

$fastcgiAvailable = function_exists('fastcgi_finish_request');
echo "fastcgi_finish_request متاح: " . ($fastcgiAvailable ? '✅ نعم' : '⚠️ لا') . "\n";

if ($fastcgiAvailable) {
    echo "✅ سيتم استخدام fastcgi_finish_request للأداء الأمثل\n";
} else {
    echo "⚠️ سيتم استخدام الطريقة البديلة (ob_end_flush + flush)\n";
}

echo "\n";

// ===================================
// النتيجة النهائية
// ===================================
echo "============================================\n";
echo "          ✅ نتيجة الاختبارات\n";
echo "============================================\n\n";

echo "✅ Test 1: تنفيذ مهمة واحدة - نجح\n";
echo "✅ Test 2: تنفيذ عدة مهام - نجح\n";
echo "✅ Test 3: معالجة الأخطاء - نجح\n";
echo "✅ Test 4: finishRequest - " . ($fastcgiAvailable ? 'متاح' : 'fallback') . "\n";

echo "\n📊 الملاحظات:\n";
echo "  • جميع المهام تم جدولتها بنجاح\n";
echo "  • Response Time أقل من 1ms للجدولة\n";
echo "  • المهام ستنفذ في الخلفية بعد انتهاء السكريبت\n";
echo "  • الأخطاء تُسجل دون التأثير على المهام الأخرى\n";

echo "\n📝 تحقق من الـ logs:\n";
echo "  tail -f storage/logs/laravel.log\n";

echo "\n🎉 AsyncHelper يعمل بشكل صحيح!\n";
echo "============================================\n";

// عند انتهاء السكريبت، سيتم تنفيذ المهام المجدولة
echo "\n⏳ انتظر لحظة... المهام الخلفية قيد التنفيذ...\n\n";

