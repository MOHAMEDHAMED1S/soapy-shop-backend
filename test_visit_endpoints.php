<?php

echo "🚀 اختبار endpoints تتبع الزيارات\n";
echo "=====================================\n\n";

$baseUrl = 'http://localhost:8000/api/v1';
$testData = [
    'page_url' => 'http://localhost:8080/',
    'userAgent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1',
    'timestamp' => '2025-10-20T13:47:16.495Z',
    'sessionId' => 'session_' . time() . '_test',
    'deviceType' => 'mobile',
    'browser' => 'Safari',
    'os' => 'macOS'
];

// Test 1: POST /visits/track
echo "📊 اختبار 1: POST /visits/track\n";
echo "------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/visits/track');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "✅ تم تتبع الزيارة بنجاح\n";
        echo "   - الصفحة: {$data['data']['page_url']}\n";
        echo "   - المصدر: {$data['data']['referer_type']}\n";
        echo "   - IP: {$data['data']['ip_address']}\n";
    } else {
        echo "❌ فشل في تتبع الزيارة: " . $data['message'] . "\n";
    }
} else {
    echo "❌ خطأ HTTP: $httpCode\n";
    echo "Response: $response\n";
}

echo "\n";

// Test 2: POST /visits/pixel
echo "📊 اختبار 2: POST /visits/pixel\n";
echo "------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/visits/pixel');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ تم تتبع البكسل بنجاح\n";
    echo "   - HTTP Code: $httpCode\n";
    echo "   - Content-Type: $contentType\n";
    echo "   - Response Length: " . strlen($response) . " bytes\n";
} else {
    echo "❌ خطأ HTTP: $httpCode\n";
    echo "Response: $response\n";
}

echo "\n";

// Test 3: GET /visits/pixel.gif (original endpoint)
echo "📊 اختبار 3: GET /visits/pixel.gif\n";
echo "--------------------------------\n";

$params = http_build_query([
    'url' => $testData['page_url'],
    'ref' => 'https://google.com',
    'sid' => $testData['sessionId']
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/visits/pixel.gif?' . $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ تم تتبع البكسل (GET) بنجاح\n";
    echo "   - HTTP Code: $httpCode\n";
    echo "   - Content-Type: $contentType\n";
    echo "   - Response Length: " . strlen($response) . " bytes\n";
} else {
    echo "❌ خطأ HTTP: $httpCode\n";
    echo "Response: $response\n";
}

echo "\n🎉 تم إكمال جميع الاختبارات!\n";
echo "=====================================\n";