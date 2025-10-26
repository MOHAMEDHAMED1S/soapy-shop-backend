<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PaymentService;
use App\Models\Order;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "    تشخيص مشكلة verification_failed\n";
echo str_repeat("=", 70) . "\n\n";

// Check if there's an order_id parameter
$orderId = $_SERVER['argv'][1] ?? null;

if ($orderId) {
    $order = Order::with('payment')->find($orderId);
    
    if (!$order) {
        echo "❌ Order #{$orderId} not found\n\n";
        exit(1);
    }
} else {
    // Get the most recent order with payment
    $order = Order::with('payment')
        ->whereHas('payment', function($query) {
            $query->whereNotNull('invoice_reference');
        })
        ->orderBy('id', 'desc')
        ->first();
        
    if (!$order) {
        echo "❌ No orders with payment found\n\n";
        exit(1);
    }
}

echo "📦 الطلب:\n";
echo "   Order ID: {$order->id}\n";
echo "   Order Number: {$order->order_number}\n";
echo "   Status: {$order->status}\n";
echo "   Total: {$order->total_amount} {$order->currency}\n";
echo "\n";

if (!$order->payment) {
    echo "❌ لا يوجد payment record لهذا الطلب\n\n";
    exit(1);
}

echo "💳 الدفع:\n";
echo "   Payment ID: {$order->payment->id}\n";
echo "   Status: {$order->payment->status}\n";
echo "   Invoice Reference: {$order->payment->invoice_reference}\n";
echo "\n";

echo str_repeat("-", 70) . "\n";
echo "🔍 محاولة التحقق من الدفع (كما يفعل handleSuccessCallback)...\n";
echo str_repeat("-", 70) . "\n\n";

$paymentService = app(PaymentService::class);

try {
    echo "📡 جاري الاتصال بـ MyFatoorah...\n";
    echo "   Invoice Reference: {$order->payment->invoice_reference}\n";
    echo "   API URL: " . env('MYFATOORAH_API_URL', 'https://apitest.myfatoorah.com') . "\n";
    echo "\n";
    
    $startTime = microtime(true);
    $result = $paymentService->verifyPayment($order->payment->invoice_reference);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "⏱️  Response Time: {$duration}ms\n\n";
    
    if ($result['success']) {
        echo "✅ التحقق نجح!\n\n";
        
        $invoiceData = $result['data'];
        $invoiceStatus = $invoiceData['InvoiceStatus'] ?? 'Unknown';
        
        echo "📋 حالة الفاتورة: {$invoiceStatus}\n";
        echo "   Invoice ID: {$invoiceData['InvoiceId']}\n";
        echo "   Invoice Value: {$invoiceData['InvoiceValue']} KWD\n";
        
        if (isset($invoiceData['InvoiceTransactions']) && count($invoiceData['InvoiceTransactions']) > 0) {
            echo "\n📝 آخر معاملة:\n";
            $lastTx = end($invoiceData['InvoiceTransactions']);
            echo "   Payment Gateway: {$lastTx['PaymentGateway']}\n";
            echo "   Transaction Status: {$lastTx['TransactionStatus']}\n";
            echo "   Transaction Date: {$lastTx['TransactionDate']}\n";
            if (isset($lastTx['Error']) && $lastTx['Error']) {
                echo "   Error: {$lastTx['Error']}\n";
            }
        }
        
        echo "\n";
        echo str_repeat("=", 70) . "\n";
        echo "✅ لا توجد مشكلة في التحقق!\n";
        echo "   الخطأ verification_failed لن يحدث لهذا الطلب.\n";
        echo str_repeat("=", 70) . "\n\n";
        
    } else {
        echo "❌ التحقق فشل!\n\n";
        echo "⚠️  هذا هو سبب ظهور verification_failed\n\n";
        
        $error = $result['error'] ?? 'Unknown error';
        echo "❌ الخطأ: {$error}\n\n";
        
        echo str_repeat("=", 70) . "\n";
        echo "🔧 الحل:\n";
        echo str_repeat("=", 70) . "\n\n";
        
        // Analyze the error
        if (strpos($error, '429') !== false) {
            echo "⚠️  خطأ 429 - Too Many Requests\n";
            echo "   السبب: كثرة الطلبات لـ MyFatoorah\n";
            echo "   الحل: انتظر 1-2 دقيقة ثم حاول مرة أخرى\n";
            echo "   الكود يحتوي على delays لكن ربما تحتاج زيادتها\n\n";
            
        } elseif (strpos($error, '401') !== false || strpos($error, '403') !== false) {
            echo "⚠️  خطأ في المصادقة\n";
            echo "   السبب: API Token غير صحيح أو منتهي\n";
            echo "   الحل: تحقق من MYFATOORAH_API_KEY في .env\n\n";
            echo "   Current Key: " . substr(env('MYFATOORAH_API_KEY', ''), 0, 20) . "...\n\n";
            
        } elseif (strpos($error, '404') !== false) {
            echo "⚠️  الفاتورة غير موجودة\n";
            echo "   السبب: Invoice Reference غير موجود في MyFatoorah\n";
            echo "   Invoice Reference: {$order->payment->invoice_reference}\n";
            echo "   الحل: تحقق من الرقم في MyFatoorah Dashboard\n\n";
            
        } elseif (strpos($error, 'Connection') !== false || strpos($error, 'timeout') !== false) {
            echo "⚠️  مشكلة في الاتصال\n";
            echo "   السبب: فشل الاتصال بـ MyFatoorah API\n";
            echo "   الحل:\n";
            echo "   - تحقق من الإنترنت\n";
            echo "   - تحقق من أن السيرفر يسمح بالاتصال الخارجي\n";
            echo "   - جرّب الاتصال يدوياً:\n";
            echo "     curl -H 'Authorization: Bearer YOUR_KEY' \\\n";
            echo "          https://apitest.myfatoorah.com/v2/GetPaymentStatus\n\n";
            
        } else {
            echo "⚠️  خطأ غير متوقع\n";
            echo "   الخطأ الكامل:\n";
            echo "   " . str_repeat("-", 66) . "\n";
            echo "   {$error}\n";
            echo "   " . str_repeat("-", 66) . "\n\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ حدث Exception!\n\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    
    echo "   Stack Trace:\n";
    echo "   " . str_repeat("-", 66) . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "   " . str_repeat("-", 66) . "\n\n";
}

echo "\n💡 للاستخدام:\n";
echo "   php debug_verification_failed.php [order_id]\n";
echo "   مثال: php debug_verification_failed.php 29\n";
echo "\n";

