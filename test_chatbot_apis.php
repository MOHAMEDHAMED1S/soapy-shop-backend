<?php

/**
 * Comprehensive Chatbot API Testing Script
 * Tests all chatbot endpoints including public and admin APIs
 */

// Base URL for the API
$baseUrl = 'http://localhost:8000/api/v1';

// Test configuration
$testData = [
    'session_id' => 'test_session_' . time(),
    'user_message' => 'مرحبا، أريد معرفة المنتجات المتاحة',
    'admin_settings' => [
        'name' => 'Chatbot Assistant',
        'system_prompt' => 'أنت مساعد ذكي لمتجر الصابون. ساعد العملاء في العثور على المنتجات المناسبة.',
        'welcome_message' => 'مرحبا! كيف يمكنني مساعدتك اليوم؟',
        'is_active' => true,
        'product_access_type' => 'all',
        'max_conversation_length' => 50,
        'token_limit_per_message' => 1000
    ]
];

/**
 * Make HTTP request
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers),
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'raw_response' => $response
    ];
}

/**
 * Test result formatter
 */
function printTestResult($testName, $result, $expectedCode = 200) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST: $testName\n";
    echo str_repeat("-", 60) . "\n";
    
    if (isset($result['error'])) {
        echo "❌ ERROR: " . $result['error'] . "\n";
        return false;
    }
    
    $success = $result['http_code'] === $expectedCode;
    echo ($success ? "✅" : "❌") . " HTTP Code: " . $result['http_code'] . " (Expected: $expectedCode)\n";
    
    if (isset($result['data'])) {
        echo "Response Data:\n";
        echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Raw Response: " . $result['raw_response'] . "\n";
    }
    
    return $success;
}

echo "🤖 Starting Chatbot API Tests...\n";
echo "Base URL: $baseUrl\n";

$allTestsPassed = true;

// Test 1: Get Chatbot Settings
echo "\n📋 Testing Public APIs...\n";
$result = makeRequest("$baseUrl/chat/settings");
$testPassed = printTestResult("Get Chatbot Settings", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 2: Start Chat
$result = makeRequest("$baseUrl/chat/start", 'POST');
$testPassed = printTestResult("Start Chat", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Store conversation ID and session ID for later tests
$conversationId = null;
$sessionId = null;
if ($testPassed && isset($result['data']['data']['conversation_id'])) {
    $conversationId = $result['data']['data']['conversation_id'];
    $sessionId = $result['data']['data']['session_id'];
}

// Test 3: Send Message (if chat started successfully)
if ($conversationId && $sessionId) {
    $result = makeRequest("$baseUrl/chat/message", 'POST', [
        'session_id' => $sessionId,
        'message' => $testData['user_message']
    ]);
    $testPassed = printTestResult("Send Message", $result);
    $allTestsPassed = $allTestsPassed && $testPassed;
} else {
    echo "⚠️  Skipping Send Message test - no conversation ID or session ID\n";
}

// Test 4: Get Chat History
if ($conversationId && $sessionId) {
    $result = makeRequest("$baseUrl/chat/history?session_id=" . $sessionId);
    $testPassed = printTestResult("Get Chat History", $result);
    $allTestsPassed = $allTestsPassed && $testPassed;
} else {
    echo "⚠️  Skipping Chat History test - no conversation ID or session ID\n";
}

// Test 5: End Chat
if ($conversationId && $sessionId) {
    $result = makeRequest("$baseUrl/chat/end", 'POST', [
        'session_id' => $sessionId
    ]);
    $testPassed = printTestResult("End Chat", $result);
    $allTestsPassed = $allTestsPassed && $testPassed;
} else {
    echo "⚠️  Skipping End Chat test - no conversation ID\n";
}

// Admin API Tests
echo "\n👨‍💼 Testing Admin APIs...\n";

// Test 6: Get Admin Settings
$result = makeRequest("$baseUrl/admin/chatbot/settings");
$testPassed = printTestResult("Get Admin Settings", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 7: Update Admin Settings
$result = makeRequest("$baseUrl/admin/chatbot/settings", 'PUT', $testData['admin_settings']);
$testPassed = printTestResult("Update Admin Settings", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 8: Get Statistics
$result = makeRequest("$baseUrl/admin/chatbot/statistics");
$testPassed = printTestResult("Get Statistics", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 9: Get Conversations
$result = makeRequest("$baseUrl/admin/chatbot/conversations");
$testPassed = printTestResult("Get Conversations", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 10: Get Available Products
$result = makeRequest("$baseUrl/admin/chatbot/products");
$testPassed = printTestResult("Get Available Products", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 11: Test Configuration
$result = makeRequest("$baseUrl/admin/chatbot/test", 'POST', [
    'test_message' => 'مرحبا، هذا اختبار للتكوين'
]);
$testPassed = printTestResult("Test Configuration", $result);
$allTestsPassed = $allTestsPassed && $testPassed;

// Error Handling Tests
echo "\n🚨 Testing Error Handling...\n";

// Test 12: Invalid Session ID
$result = makeRequest("$baseUrl/chat/message", 'POST', [
    'session_id' => 'invalid_session',
    'message' => 'test message'
]);
$testPassed = printTestResult("Invalid Session ID", $result, 404);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 13: Missing Required Fields
$result = makeRequest("$baseUrl/chat/start", 'POST', []);
$testPassed = printTestResult("Missing Required Fields", $result, 422);
$allTestsPassed = $allTestsPassed && $testPassed;

// Test 14: Invalid Admin Settings
$result = makeRequest("$baseUrl/admin/chatbot/settings", 'PUT', [
    'name' => '', // Invalid empty name
    'is_active' => 'invalid_boolean'
]);
$testPassed = printTestResult("Invalid Admin Settings", $result, 422);
$allTestsPassed = $allTestsPassed && $testPassed;

// Final Results
echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";

if ($allTestsPassed) {
    echo "🎉 ALL TESTS PASSED! The chatbot API is working correctly.\n";
} else {
    echo "❌ SOME TESTS FAILED. Please check the errors above.\n";
}

echo "\n📝 Test completed at: " . date('Y-m-d H:i:s') . "\n";

// Additional Information
echo "\n📚 API Endpoints Tested:\n";
echo "Public APIs:\n";
echo "  - GET  /api/v1/chat/settings\n";
echo "  - POST /api/v1/chat/start\n";
echo "  - POST /api/v1/chat/message\n";
echo "  - GET  /api/v1/chat/history\n";
echo "  - POST /api/v1/chat/end\n";
echo "\nAdmin APIs:\n";
echo "  - GET  /api/v1/admin/chatbot/settings\n";
echo "  - PUT  /api/v1/admin/chatbot/settings\n";
echo "  - GET  /api/v1/admin/chatbot/statistics\n";
echo "  - GET  /api/v1/admin/chatbot/conversations\n";
echo "  - GET  /api/v1/admin/chatbot/products\n";
echo "  - POST /api/v1/admin/chatbot/test\n";

echo "\n🔧 Next Steps:\n";
echo "1. If tests passed: The API is ready for frontend integration\n";
echo "2. If tests failed: Check Laravel logs and fix any issues\n";
echo "3. Configure Gemini API key in .env if not already done\n";
echo "4. Test with real frontend integration\n";

?>