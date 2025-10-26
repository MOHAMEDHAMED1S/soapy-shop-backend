<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\PaymentService;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "    فحص الطلبات العالقة في awaiting_payment\n";
echo str_repeat("=", 70) . "\n\n";

$awaitingOrders = Order::with('payment')
    ->where('status', 'awaiting_payment')
    ->whereHas('payment', function($query) {
        $query->whereNotNull('invoice_reference');
    })
    ->orderBy('id', 'desc')
    ->limit(5)  // فحص أول 5 فقط لعدم التحميل الزائد
    ->get();

if ($awaitingOrders->count() === 0) {
    echo "✅ لا توجد طلبات عالقة!\n\n";
    exit(0);
}

echo "📊 وُجد {$awaitingOrders->count()} طلب (أول 5 فقط)\n\n";

$paymentService = app(PaymentService::class);

$results = [
    'actually_paid' => [],
    'not_paid' => [],
    'errors' => []
];

foreach ($awaitingOrders as $index => $order) {
    $num = $index + 1;
    
    echo str_repeat("-", 70) . "\n";
    echo "طلب #{$num}: {$order->order_number} (ID: {$order->id})\n";
    echo str_repeat("-", 70) . "\n";
    echo "Order Status: {$order->status}\n";
    echo "Payment Status: {$order->payment->status}\n";
    echo "Invoice Reference: {$order->payment->invoice_reference}\n";
    echo "Created: {$order->created_at->format('Y-m-d H:i:s')} ({$order->created_at->diffForHumans()})\n";
    echo "\n";
    
    echo "🔍 التحقق من MyFatoorah...\n";
    
    try {
        $result = $paymentService->verifyPayment($order->payment->invoice_reference);
        
        if ($result['success']) {
            $invoiceStatus = $result['data']['InvoiceStatus'] ?? 'Unknown';
            
            if ($invoiceStatus === 'Paid') {
                echo "   ✅ الطلب مدفوع فعلاً في MyFatoorah!\n";
                echo "   ⚠️  لكنه عالق في awaiting_payment في DB\n";
                echo "   🔧 يحتاج إلى تحديث\n";
                
                $results['actually_paid'][] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_reference' => $order->payment->invoice_reference,
                    'myfatoorah_status' => $invoiceStatus
                ];
            } else {
                echo "   ❌ الطلب غير مدفوع في MyFatoorah\n";
                echo "   Status: {$invoiceStatus}\n";
                
                $results['not_paid'][] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_reference' => $order->payment->invoice_reference,
                    'myfatoorah_status' => $invoiceStatus
                ];
            }
        } else {
            echo "   ❌ فشل التحقق: {$result['error']}\n";
            
            $results['errors'][] = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $result['error']
            ];
        }
        
    } catch (\Exception $e) {
        echo "   ❌ خطأ: {$e->getMessage()}\n";
        
        $results['errors'][] = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    
    // تأخير 500ms بين الطلبات لتجنب rate limiting
    usleep(500000);
}

echo str_repeat("=", 70) . "\n";
echo "    ملخص النتائج\n";
echo str_repeat("=", 70) . "\n\n";

// Actually Paid
if (count($results['actually_paid']) > 0) {
    echo "🟢 طلبات مدفوعة فعلاً لكن عالقة في DB: " . count($results['actually_paid']) . "\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($results['actually_paid'] as $item) {
        echo "   • Order {$item['order_number']} (ID: {$item['order_id']})\n";
        echo "     Invoice: {$item['invoice_reference']}\n";
        echo "     Status: {$item['myfatoorah_status']}\n";
    }
    echo "\n";
} else {
    echo "✅ لا توجد طلبات مدفوعة عالقة\n\n";
}

// Not Paid
if (count($results['not_paid']) > 0) {
    echo "🔴 طلبات غير مدفوعة (طبيعي): " . count($results['not_paid']) . "\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($results['not_paid'] as $item) {
        echo "   • Order {$item['order_number']} (ID: {$item['order_id']})\n";
        echo "     Status: {$item['myfatoorah_status']}\n";
    }
    echo "\n";
}

// Errors
if (count($results['errors']) > 0) {
    echo "⚠️  أخطاء في التحقق: " . count($results['errors']) . "\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($results['errors'] as $item) {
        echo "   • Order {$item['order_number']} (ID: {$item['order_id']})\n";
        echo "     Error: {$item['error']}\n";
    }
    echo "\n";
}

echo str_repeat("=", 70) . "\n\n";

// Recommendations
echo "💡 التوصيات:\n";
echo str_repeat("-", 70) . "\n";

if (count($results['actually_paid']) > 0) {
    echo "🔧 لديك طلبات مدفوعة لكن عالقة!\n\n";
    echo "استخدم Payment Verification API لإصلاحها:\n";
    echo "   GET /api/v1/admin/payments/verify-pending\n\n";
    echo "أو يمكنك إصلاحها يدوياً:\n";
    foreach ($results['actually_paid'] as $item) {
        echo "   php artisan tinker\n";
        echo "   \$order = App\\Models\\Order::find({$item['order_id']});\n";
        echo "   \$order->update(['status' => 'paid']);\n";
        echo "   \$order->payment->update(['status' => 'paid']);\n\n";
    }
} else {
    echo "✅ كل الطلبات العالقة هي فعلاً غير مدفوعة (طبيعي)\n";
    echo "   العملاء لم يكملوا الدفع أو ألغوا\n";
}

echo str_repeat("=", 70) . "\n\n";

echo "📊 إحصائيات كاملة:\n";
$totalAwaiting = Order::where('status', 'awaiting_payment')->count();
echo "   إجمالي الطلبات في awaiting_payment: {$totalAwaiting}\n";
echo "   تم فحص: {$awaitingOrders->count()}\n";
echo "   مدفوعة فعلاً: " . count($results['actually_paid']) . "\n";
echo "   غير مدفوعة: " . count($results['not_paid']) . "\n";
echo "   أخطاء: " . count($results['errors']) . "\n";
echo "\n";

if ($totalAwaiting > 5) {
    echo "⚠️  لم يتم فحص كل الطلبات العالقة ({$totalAwaiting} طلب)\n";
    echo "   لفحص الكل، استخدم:\n";
    echo "   GET /api/v1/admin/payments/verify-pending\n";
    echo "\n";
}

echo str_repeat("=", 70) . "\n";

