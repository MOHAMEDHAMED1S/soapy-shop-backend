<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('services.myfatoorah.api_key');
$baseUrl = config('services.myfatoorah.base_url');

echo "=== Step 1: Create Payment with UserDefinedField ===\n\n";

// إنشاء payment جديد مع UserDefinedField و CustomerReference
$sendPaymentResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post($baseUrl . '/v2/SendPayment', [
    'InvoiceValue' => 10,
    'CustomerName' => 'Test Customer',
    'NotificationOption' => 'LNK',
    'CustomerReference' => 'TEST-ORD-12345',  // ✅ order_number
    'UserDefinedField' => '999',               // ✅ order_id
    'DisplayCurrencyIso' => 'KWD',
    'CallBackUrl' => 'https://example.com/success',
    'ErrorUrl' => 'https://example.com/error'
]);

if (!$sendPaymentResponse->successful()) {
    echo "Failed to create payment!\n";
    echo $sendPaymentResponse->body() . "\n";
    exit;
}

$sendData = $sendPaymentResponse->json();
if (!$sendData['IsSuccess']) {
    echo "MyFatoorah error: " . $sendData['Message'] . "\n";
    exit;
}

$invoiceId = $sendData['Data']['InvoiceId'];
echo "✅ Payment created: InvoiceId = $invoiceId\n";
echo "   UserDefinedField sent: 999\n";
echo "   CustomerReference sent: TEST-ORD-12345\n\n";

// Wait a bit
sleep(2);

echo "=== Step 2: Get Payment Status ===\n\n";

$getStatusResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post($baseUrl . '/v2/GetPaymentStatus', [
    'Key' => $invoiceId,
    'KeyType' => 'InvoiceId'
]);

if ($getStatusResponse->successful()) {
    $statusData = $getStatusResponse->json();
    
    if ($statusData['IsSuccess']) {
        $data = $statusData['Data'];
        
        echo "✅ Status retrieved:\n";
        echo "   InvoiceId: " . $data['InvoiceId'] . "\n";
        echo "   InvoiceStatus: " . $data['InvoiceStatus'] . "\n";
        echo "   UserDefinedField: " . ($data['UserDefinedField'] ?? 'null') . "\n";
        echo "   CustomerReference: " . ($data['CustomerReference'] ?? 'null') . "\n\n";
        
        if ($data['UserDefinedField'] === '999' && $data['CustomerReference'] === 'TEST-ORD-12345') {
            echo "🎉 SUCCESS! MyFatoorah returns UserDefinedField and CustomerReference!\n";
        } else {
            echo "⚠️  Values don't match or are null\n";
        }
    } else {
        echo "Error: " . $statusData['Message'] . "\n";
    }
} else {
    echo "HTTP Error: " . $getStatusResponse->status() . "\n";
}

echo "\n=== Full Response ===\n";
echo json_encode($statusData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

