<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$client = new Client(['base_uri' => 'http://localhost:8001/api/v1/']);

echo "=== اختبار API مصاريف الشحن ===\n\n";

// 1. اختبار جلب مصاريف الشحن للمستخدم العادي
echo "1. اختبار جلب مصاريف الشحن للمستخدم العادي:\n";
try {
    $response = $client->get('shipping/cost');
    $data = json_decode($response->getBody(), true);
    echo "✅ نجح: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (RequestException $e) {
    echo "❌ فشل: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }
    echo "\n";
}

// 2. اختبار جلب جميع مصاريف الشحن للإدارة (يحتاج token)
echo "2. اختبار جلب جميع مصاريف الشحن للإدارة:\n";
try {
    // محاولة بدون token أولاً لرؤية الخطأ
    $response = $client->get('admin/shipping/');
    $data = json_decode($response->getBody(), true);
    echo "✅ نجح: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (RequestException $e) {
    echo "❌ متوقع (يحتاج authentication): " . $e->getResponse()->getStatusCode() . "\n";
    echo "Response: " . $e->getResponse()->getBody() . "\n\n";
}

// 3. اختبار جلب مصاريف الشحن النشطة للإدارة
echo "3. اختبار جلب مصاريف الشحن النشطة للإدارة:\n";
try {
    $response = $client->get('admin/shipping/active');
    $data = json_decode($response->getBody(), true);
    echo "✅ نجح: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (RequestException $e) {
    echo "❌ متوقع (يحتاج authentication): " . $e->getResponse()->getStatusCode() . "\n";
    echo "Response: " . $e->getResponse()->getBody() . "\n\n";
}

// 4. اختبار تحديث مصاريف الشحن للإدارة
echo "4. اختبار تحديث مصاريف الشحن للإدارة:\n";
try {
    $response = $client->put('admin/shipping/update', [
        'json' => [
            'cost' => 15.50
        ]
    ]);
    $data = json_decode($response->getBody(), true);
    echo "✅ نجح: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} catch (RequestException $e) {
    echo "❌ متوقع (يحتاج authentication): " . $e->getResponse()->getStatusCode() . "\n";
    echo "Response: " . $e->getResponse()->getBody() . "\n\n";
}

echo "=== انتهى الاختبار ===\n";