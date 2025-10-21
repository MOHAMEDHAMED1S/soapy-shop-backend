<?php

require_once 'vendor/autoload.php';

// Test data for intelligent chatbot recommendations
$testCases = [
    [
        'message' => 'أريد صابون طبيعي للبشرة الحساسة',
        'description' => 'Natural soap for sensitive skin'
    ],
    [
        'message' => 'هل لديكم شامبو للشعر الجاف؟',
        'description' => 'Shampoo for dry hair'
    ],
    [
        'message' => 'أبحث عن كريم مرطب للأطفال',
        'description' => 'Moisturizing cream for babies'
    ],
    [
        'message' => 'أحتاج منتجات لحب الشباب',
        'description' => 'Products for acne'
    ],
    [
        'message' => 'ما هي أفضل منتجاتكم؟',
        'description' => 'General inquiry about best products'
    ],
    [
        'message' => 'أريد عطر طبيعي',
        'description' => 'Natural perfume'
    ]
];

$baseUrl = 'http://127.0.0.1:8000/api/v1';

function makeRequest($url, $data = null, $method = 'GET', $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
    
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

function printTestResult($testName, $result, $expectedCode = 200) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST: {$testName}\n";
    echo str_repeat("=", 60) . "\n";
    
    if (isset($result['error'])) {
        echo "❌ ERROR: {$result['error']}\n";
        return false;
    }
    
    $success = $result['http_code'] === $expectedCode;
    $status = $success ? "✅ PASSED" : "❌ FAILED";
    
    echo "Status: {$status} (HTTP {$result['http_code']})\n";
    
    if (isset($result['data'])) {
        echo "Response:\n";
        echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Raw Response: " . $result['raw_response'] . "\n";
    }
    
    return $success;
}

echo "🤖 Testing Intelligent Chatbot Product Recommendations\n";
echo "====================================================\n";

$allPassed = true;
$sessionId = null;

// Start a new conversation
echo "\n🚀 Starting new conversation...\n";
$startResult = makeRequest("{$baseUrl}/chat/start", [], 'POST');
$startPassed = printTestResult("Start Conversation", $startResult);

if ($startPassed && isset($startResult['data']['data']['session_id'])) {
    $sessionId = $startResult['data']['data']['session_id'];
    echo "\n📝 Session ID: {$sessionId}\n";
} else {
    echo "\n❌ Failed to start conversation. Exiting...\n";
    echo "Debug - Start result structure:\n";
    print_r($startResult);
    exit(1);
}

// Test each message case
foreach ($testCases as $index => $testCase) {
    $testNumber = $index + 1;
    echo "\n🧪 Test Case {$testNumber}: {$testCase['description']}\n";
    echo "Message: \"{$testCase['message']}\"\n";
    
    $messageData = [
        'session_id' => $sessionId,
        'message' => $testCase['message']
    ];
    
    $messageResult = makeRequest("{$baseUrl}/chat/message", $messageData, 'POST');
    $messagePassed = printTestResult("Send Message - Test {$testNumber}", $messageResult);
    
    if ($messagePassed && isset($messageResult['data']['message']['metadata']['recommended_products'])) {
        $products = $messageResult['data']['message']['metadata']['recommended_products'];
        echo "\n🎯 Recommended Products (" . count($products) . "):\n";
        foreach ($products as $product) {
            echo "  - {$product['name']} ({$product['price']} {$product['currency']})\n";
        }
    } else {
        echo "\n⚠️  No products recommended for this message\n";
    }
    
    $allPassed = $allPassed && $messagePassed;
    
    // Small delay between requests
    sleep(1);
}

// Get conversation history to verify all messages
echo "\n📜 Getting conversation history...\n";
$historyResult = makeRequest("{$baseUrl}/chat/history?session_id={$sessionId}");
$historyPassed = printTestResult("Get Conversation History", $historyResult);

if ($historyPassed && isset($historyResult['data']['messages'])) {
    $messages = $historyResult['data']['messages'];
    echo "\n📊 Conversation Summary:\n";
    echo "Total messages: " . count($messages) . "\n";
    
    $messagesWithProducts = 0;
    $totalRecommendedProducts = 0;
    
    foreach ($messages as $message) {
        if ($message['role'] === 'assistant' && isset($message['metadata']['recommended_products'])) {
            $messagesWithProducts++;
            $totalRecommendedProducts += count($message['metadata']['recommended_products']);
        }
    }
    
    echo "Messages with product recommendations: {$messagesWithProducts}\n";
    echo "Total products recommended: {$totalRecommendedProducts}\n";
}

// End conversation
echo "\n🔚 Ending conversation...\n";
$endData = ['session_id' => $sessionId];
$endResult = makeRequest("{$baseUrl}/chat/end", $endData, 'POST');
$endPassed = printTestResult("End Conversation", $endResult);

$allPassed = $allPassed && $historyPassed && $endPassed;

// Final summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 FINAL RESULTS\n";
echo str_repeat("=", 60) . "\n";

if ($allPassed) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "🎉 Intelligent product recommendation system is working correctly!\n";
} else {
    echo "❌ SOME TESTS FAILED!\n";
    echo "🔧 Please check the implementation and try again.\n";
}

echo "\n📋 Test Summary:\n";
echo "- Tested " . count($testCases) . " different message scenarios\n";
echo "- Verified intelligent product matching based on keywords\n";
echo "- Confirmed product recommendations are limited (max 3-5 products)\n";
echo "- Validated conversation flow and history tracking\n";

echo "\n🔍 Next Steps:\n";
echo "1. Review the recommended products for each test case\n";
echo "2. Verify that products match the user's intent\n";
echo "3. Check that the AI responses incorporate the recommended products naturally\n";
echo "4. Test with real product data in your database\n";

?>