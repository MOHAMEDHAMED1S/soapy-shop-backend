<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['MYFATOORAH_API_KEY'] ?? '';
$baseUrl = $_ENV['MYFATOORAH_BASE_URL'] ?? 'https://apitest.myfatoorah.com/';

// Ensure base URL ends with slash
if (!str_ends_with($baseUrl, '/')) {
    $baseUrl .= '/';
}

echo "Testing MyFatoorah API Configuration:\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Base URL: " . $baseUrl . "\n\n";

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . 'v2/InitiatePayment',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'InvoiceAmount' => 1,
        'CurrencyIso' => 'KWD'
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($error) {
    echo "cURL Error: " . $error . "\n";
}

if ($httpCode === 401) {
    echo "\n❌ Authentication failed! Check your API key.\n";
} elseif ($httpCode === 200) {
    echo "\n✅ API key is working correctly!\n";
} else {
    echo "\n⚠️  Unexpected response code: " . $httpCode . "\n";
}