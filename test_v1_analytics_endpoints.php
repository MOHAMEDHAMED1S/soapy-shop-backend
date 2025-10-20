<?php

/**
 * Test script for V1 Analytics Endpoints
 * Tests all public analytics endpoints under /api/v1/analytics/
 */

$baseUrl = 'http://localhost:8000';
$endpoints = [
    '/api/v1/analytics/statistics' => 'Statistics',
    '/api/v1/analytics/pages' => 'Popular Pages (pages)',
    '/api/v1/analytics/popular-pages' => 'Popular Pages (popular-pages)',
    '/api/v1/analytics/realtime' => 'Real-time Analytics (realtime)',
    '/api/v1/analytics/real-time' => 'Real-time Analytics (real-time)',
    '/api/v1/analytics/referer-types' => 'Referer Types',
    '/api/v1/analytics/top-referer-domains' => 'Top Referer Domains',
    '/api/v1/analytics/daily-visits' => 'Daily Visits',
    '/api/v1/analytics/device-stats' => 'Device Statistics'
];

$passedTests = 0;
$totalTests = count($endpoints);

echo "🧪 Testing V1 Analytics Endpoints\n";
echo "================================\n\n";

foreach ($endpoints as $endpoint => $name) {
    echo "Testing: {$name}\n";
    echo "Endpoint: {$endpoint}\n";
    
    // Test GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Origin: http://localhost:8080'
    ]);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    // Check if request was successful
    if ($httpCode === 200) {
        echo "✅ Status: 200 OK\n";
        
        // Check if response is valid JSON
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON response\n";
            
            // Check if response has expected structure
            if (isset($data['success']) && $data['success'] === true && isset($data['data'])) {
                echo "✅ Expected response structure\n";
                $passedTests++;
            } else {
                echo "❌ Unexpected response structure\n";
                echo "Response: " . substr($body, 0, 200) . "...\n";
            }
        } else {
            echo "❌ Invalid JSON response\n";
            echo "Response: " . substr($body, 0, 200) . "...\n";
        }
        
        // Check CORS headers
        if (strpos($headers, 'Access-Control-Allow-Origin') !== false) {
            echo "✅ CORS headers present\n";
        } else {
            echo "⚠️  CORS headers missing\n";
        }
        
    } else {
        echo "❌ Status: {$httpCode}\n";
        echo "Response: " . substr($body, 0, 200) . "...\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Summary
echo "📊 Test Results Summary\n";
echo "======================\n";
echo "Passed: {$passedTests}/{$totalTests} (" . round(($passedTests / $totalTests) * 100, 1) . "%)\n";

if ($passedTests === $totalTests) {
    echo "🎉 All tests passed! V1 Analytics endpoints are working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please check the endpoints above.\n";
}

echo "\n";