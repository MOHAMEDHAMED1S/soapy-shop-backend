<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\PaymentService;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "    اختبار التحقق من الدفع مع MyFatoorah\n";
echo str_repeat("=", 70) . "\n\n";

// Get last order with payment
$order = Order::with('payment')
    ->whereHas('payment', function($query) {
        $query->whereNotNull('invoice_reference');
    })
    ->orderBy('id', 'desc')
    ->first();

if (!$order) {
    echo "❌ لا توجد طلبات مع payment records\n";
    exit(1);
}

echo "📦 الطلب المختار:\n";
echo "   Order ID: {$order->id}\n";
echo "   Order Number: {$order->order_number}\n";
echo "   Order Status: {$order->status}\n";
echo "   Total Amount: {$order->total_amount} {$order->currency}\n";
echo "   Created: {$order->created_at->format('Y-m-d H:i:s')}\n";
echo "\n";

if (!$order->payment) {
    echo "❌ الطلب ليس له payment record\n";
    exit(1);
}

echo "💳 معلومات الدفع:\n";
echo "   Payment ID: {$order->payment->id}\n";
echo "   Payment Status: {$order->payment->status}\n";
echo "   Invoice Reference: {$order->payment->invoice_reference}\n";
echo "   Payment Method: " . ($order->payment->payment_method ?? 'N/A') . "\n";
echo "   Created: {$order->payment->created_at->format('Y-m-d H:i:s')}\n";
echo "   Updated: {$order->payment->updated_at->format('Y-m-d H:i:s')}\n";
echo "\n";

echo str_repeat("-", 70) . "\n";
echo "🔍 جاري التحقق من الدفع مع MyFatoorah...\n";
echo str_repeat("-", 70) . "\n\n";

$startTime = microtime(true);

try {
    $paymentService = app(PaymentService::class);
    
    echo "📡 إرسال طلب التحقق إلى MyFatoorah...\n";
    echo "   Invoice Reference: {$order->payment->invoice_reference}\n";
    echo "   API Endpoint: " . env('MYFATOORAH_API_URL', 'https://apitest.myfatoorah.com') . "\n";
    echo "\n";
    
    $result = $paymentService->verifyPayment($order->payment->invoice_reference);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "⏱️  Response Time: {$duration}ms\n\n";
    
    if ($result['success']) {
        echo "✅ التحقق نجح بنجاح!\n\n";
        
        $data = $result['data'];
        
        echo "📋 بيانات الفاتورة من MyFatoorah:\n";
        echo str_repeat("-", 70) . "\n";
        
        // Invoice Status
        $invoiceStatus = $data['InvoiceStatus'] ?? 'Unknown';
        $statusEmoji = $invoiceStatus === 'Paid' ? '✅' : '⚠️';
        echo "   {$statusEmoji} Invoice Status: {$invoiceStatus}\n";
        
        // Invoice ID
        if (isset($data['InvoiceId'])) {
            echo "   Invoice ID: {$data['InvoiceId']}\n";
        }
        
        // Invoice Reference
        if (isset($data['InvoiceReference'])) {
            echo "   Invoice Reference: {$data['InvoiceReference']}\n";
        }
        
        // Customer Name
        if (isset($data['CustomerName'])) {
            echo "   Customer Name: {$data['CustomerName']}\n";
        }
        
        // Invoice Value
        if (isset($data['InvoiceValue'])) {
            echo "   Invoice Value: {$data['InvoiceValue']} KWD\n";
        }
        
        // Payment Method
        if (isset($data['InvoiceTransactions'][0]['PaymentGateway'])) {
            echo "   Payment Gateway: {$data['InvoiceTransactions'][0]['PaymentGateway']}\n";
        }
        
        // Transaction Date
        if (isset($data['InvoiceTransactions'][0]['TransactionDate'])) {
            echo "   Transaction Date: {$data['InvoiceTransactions'][0]['TransactionDate']}\n";
        }
        
        // Payment ID
        if (isset($data['InvoiceTransactions'][0]['PaymentId'])) {
            echo "   Payment ID: {$data['InvoiceTransactions'][0]['PaymentId']}\n";
        }
        
        echo str_repeat("-", 70) . "\n\n";
        
        // Compare with database
        echo "🔍 مقارنة مع البيانات المحلية:\n";
        echo str_repeat("-", 70) . "\n";
        
        $dbStatus = $order->status;
        $dbPaymentStatus = $order->payment->status;
        $apiStatus = $invoiceStatus;
        
        echo "   Order Status (DB): {$dbStatus}\n";
        echo "   Payment Status (DB): {$dbPaymentStatus}\n";
        echo "   Invoice Status (API): {$apiStatus}\n";
        echo "\n";
        
        if ($apiStatus === 'Paid') {
            if ($dbStatus === 'paid' && $dbPaymentStatus === 'paid') {
                echo "   ✅ الطلب مدفوع في كلا الجانبين - كل شيء صحيح!\n";
            } elseif ($dbStatus === 'paid' && $dbPaymentStatus === 'Paid') {
                echo "   ✅ الطلب مدفوع (حالة مختلفة لكن صحيحة)\n";
            } else {
                echo "   ⚠️  الدفع مكتمل في MyFatoorah لكن الطلب في الDB:\n";
                echo "       - Order Status: {$dbStatus} (should be: paid)\n";
                echo "       - Payment Status: {$dbPaymentStatus} (should be: paid/Paid)\n";
                echo "       🔧 يمكن استخدام Payment Verification API لإصلاح هذا\n";
            }
        } else {
            if ($dbStatus === 'paid') {
                echo "   ⚠️  الطلب مدفوع في DB لكن ليس مدفوع في MyFatoorah!\n";
                echo "       - DB shows: paid\n";
                echo "       - MyFatoorah shows: {$apiStatus}\n";
            } else {
                echo "   ℹ️  الطلب غير مدفوع في كلا الجانبين - متوافق\n";
            }
        }
        
        echo str_repeat("-", 70) . "\n\n";
        
        // Full Response (for debugging)
        echo "📄 الاستجابة الكاملة (JSON):\n";
        echo str_repeat("-", 70) . "\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo str_repeat("-", 70) . "\n\n";
        
    } else {
        echo "❌ فشل التحقق من الدفع!\n\n";
        
        $error = $result['error'] ?? 'Unknown error';
        echo "   Error: {$error}\n\n";
        
        // Check for specific errors
        if (strpos($error, '429') !== false) {
            echo "   🚫 خطأ 429 - Too Many Requests\n";
            echo "   📝 تم حظرك مؤقتاً بسبب كثرة الطلبات\n";
            echo "   ⏳ انتظر قليلاً ثم حاول مرة أخرى\n\n";
        } elseif (strpos($error, '401') !== false || strpos($error, '403') !== false) {
            echo "   🚫 خطأ في المصادقة\n";
            echo "   📝 تحقق من API Token في .env\n";
            echo "   🔑 MYFATOORAH_API_KEY\n\n";
        } elseif (strpos($error, '404') !== false) {
            echo "   🚫 الفاتورة غير موجودة\n";
            echo "   📝 Invoice Reference: {$order->payment->invoice_reference}\n\n";
        } elseif (strpos($error, 'Connection') !== false || strpos($error, 'timeout') !== false) {
            echo "   🚫 مشكلة في الاتصال\n";
            echo "   📝 تحقق من الاتصال بالإنترنت\n";
            echo "   📝 أو MyFatoorah API قد يكون معطل\n\n";
        }
        
        // Show full error for debugging
        echo "   📋 تفاصيل الخطأ الكاملة:\n";
        echo "   " . str_repeat("-", 66) . "\n";
        echo "   " . $error . "\n";
        echo "   " . str_repeat("-", 66) . "\n\n";
    }
    
} catch (\Exception $e) {
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "❌ حدث خطأ في التحقق!\n\n";
    echo "   Response Time: {$duration}ms\n";
    echo "   Error Message: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}\n";
    echo "   Line: {$e->getLine()}\n\n";
    
    echo "   Stack Trace:\n";
    echo "   " . str_repeat("-", 66) . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "   " . str_repeat("-", 66) . "\n\n";
}

echo str_repeat("=", 70) . "\n";
echo "    اختبار مكتمل\n";
echo str_repeat("=", 70) . "\n\n";

// Additional checks
echo "🔧 فحوصات إضافية:\n";
echo str_repeat("-", 70) . "\n";

// Check API credentials
echo "1. بيانات API:\n";
echo "   MYFATOORAH_API_URL: " . (env('MYFATOORAH_API_URL') ? '✅ موجود' : '❌ غير موجود') . "\n";
echo "   MYFATOORAH_API_KEY: " . (env('MYFATOORAH_API_KEY') ? '✅ موجود (' . substr(env('MYFATOORAH_API_KEY'), 0, 10) . '...)' : '❌ غير موجود') . "\n";
echo "\n";

// Check recent orders
echo "2. إحصائيات الطلبات:\n";
$totalOrders = Order::count();
$paidOrders = Order::where('status', 'paid')->count();
$awaitingPayment = Order::where('status', 'awaiting_payment')->count();
$pendingOrders = Order::where('status', 'pending')->count();

echo "   إجمالي الطلبات: {$totalOrders}\n";
echo "   طلبات مدفوعة: {$paidOrders}\n";
echo "   في انتظار الدفع: {$awaitingPayment}\n";
echo "   قيد الانتظار: {$pendingOrders}\n";
echo "\n";

// Check last successful payment
$lastPaid = Order::where('status', 'paid')
    ->orderBy('id', 'desc')
    ->first();

if ($lastPaid) {
    echo "3. آخر طلب مدفوع:\n";
    echo "   Order: {$lastPaid->order_number}\n";
    echo "   Date: {$lastPaid->updated_at->format('Y-m-d H:i:s')}\n";
    echo "   Time Ago: {$lastPaid->updated_at->diffForHumans()}\n";
    echo "\n";
}

echo str_repeat("=", 70) . "\n\n";

echo "💡 نصائح:\n";
echo "   • إذا حصلت على خطأ 429، انتظر 30-60 ثانية\n";
echo "   • إذا فشل التحقق باستمرار، تحقق من API credentials\n";
echo "   • استخدم Payment Verification API لإصلاح الطلبات العالقة:\n";
echo "     GET /api/v1/admin/payments/verify-pending\n";
echo "\n";

