<?php

/**
 * Test CORS functionality for Visit Analytics endpoints
 * This script tests all visit analytics endpoints to ensure CORS is working properly
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000/api/v1/admin';
$testOrigin = 'https://admin.example.com';

// Visit Analytics endpoints to test
$endpoints = [
    '/analytics/visits/statistics',
    '/analytics/visits/referer-types',
    '/analytics/visits/top-referers',
    '/analytics/visits/daily',
    '/analytics/visits/popular-pages',
    '/analytics/visits/real-time',
    '/analytics/visits/devices'
];

echo "=== Testing CORS for Visit Analytics Endpoints ===\n\n";

$totalTests = 0;
$passedTests = 0;

foreach ($endpoints as $endpoint) {
    $totalTests++;
    echo "Testing endpoint: $endpoint\n";
    
    // Test OPTIONS request (CORS preflight)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Origin: ' . $testOrigin,
        'Access-Control-Request-Method: GET',
        'Access-Control-Request-Headers: Authorization, Content-Type'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ CURL Error: $error\n";
        continue;
    }
    
    echo "  HTTP Status: $httpCode\n";
    
    // Check if OPTIONS request returns 204 (No Content)
    if ($httpCode === 204) {
        echo "  ✅ OPTIONS request successful\n";
        $passedTests++;
        
        // Parse headers
        $headerLines = explode("\n", $response);
        $corsHeaders = [];
        foreach ($headerLines as $line) {
            if (stripos($line, 'access-control-') === 0) {
                $corsHeaders[] = trim($line);
            }
        }
        
        if (!empty($corsHeaders)) {
            echo "  📋 CORS Headers found:\n";
            foreach ($corsHeaders as $header) {
                echo "    - $header\n";
            }
        } else {
            echo "  ⚠️  No CORS headers found in response\n";
        }
    } else {
        echo "  ❌ OPTIONS request failed (expected 204, got $httpCode)\n";
    }
    
    echo "\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Total endpoints tested: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

if ($passedTests === $totalTests) {
    echo "🎉 All CORS tests passed! Visit analytics endpoints are properly configured.\n";
} else {
    echo "⚠️  Some CORS tests failed. Please check the configuration.\n";
}

// Additional test: Check if server is running
echo "\n=== Server Status Check ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Server not accessible: $error\n";
    echo "Please make sure the Laravel server is running: php artisan serve\n";
} else {
    echo "✅ Server is running on http://127.0.0.1:8000 (HTTP $httpCode)\n";
}

?>