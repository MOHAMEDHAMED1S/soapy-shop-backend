<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "    إصلاح الطلبات المدفوعة العالقة\n";
echo str_repeat("=", 70) . "\n\n";

// Get stuck orders
$awaitingOrders = Order::with('payment')
    ->where('status', 'awaiting_payment')
    ->whereHas('payment', function($query) {
        $query->whereNotNull('invoice_reference');
    })
    ->orderBy('id', 'desc')
    ->get();

if ($awaitingOrders->count() === 0) {
    echo "✅ لا توجد طلبات عالقة!\n\n";
    exit(0);
}

echo "📊 وُجد {$awaitingOrders->count()} طلب في awaiting_payment\n\n";

$paymentService = app(PaymentService::class);

$results = [
    'fixed' => [],
    'already_correct' => [],
    'errors' => []
];

foreach ($awaitingOrders as $index => $order) {
    $num = $index + 1;
    
    echo str_repeat("-", 70) . "\n";
    echo "[{$num}/{$awaitingOrders->count()}] طلب: {$order->order_number} (ID: {$order->id})\n";
    echo str_repeat("-", 70) . "\n";
    
    try {
        echo "🔍 التحقق من MyFatoorah...\n";
        
        $result = $paymentService->verifyPayment($order->payment->invoice_reference);
        
        if ($result['success']) {
            $invoiceStatus = $result['data']['InvoiceStatus'] ?? 'Unknown';
            $invoiceValue = $result['data']['InvoiceValue'] ?? 0;
            
            echo "   Invoice Status: {$invoiceStatus}\n";
            echo "   Invoice Value: {$invoiceValue} KWD\n";
            
            if ($invoiceStatus === 'Paid') {
                echo "\n   ✅ الطلب مدفوع في MyFatoorah!\n";
                echo "   🔧 جاري تحديث DB...\n";
                
                DB::beginTransaction();
                
                try {
                    // Update order status
                    $order->update(['status' => 'paid']);
                    
                    // Update payment status
                    $order->payment->update([
                        'status' => 'paid',
                        'response_raw' => array_merge(
                            $order->payment->response_raw ?? [],
                            ['manual_fix' => [
                                'fixed_at' => now()->toDateTimeString(),
                                'verified_with_myfatoorah' => true,
                                'invoice_status' => $invoiceStatus
                            ]]
                        )
                    ]);
                    
                    // Deduct inventory
                    $order->load('orderItems.product');
                    $order->deductInventory();
                    
                    DB::commit();
                    
                    echo "   ✅ تم التحديث بنجاح!\n";
                    echo "      Order Status: paid\n";
                    echo "      Payment Status: paid\n";
                    echo "      Inventory: deducted\n";
                    
                    $results['fixed'][] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'invoice_value' => $invoiceValue
                    ];
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    echo "   ❌ فشل التحديث: {$e->getMessage()}\n";
                    
                    $results['errors'][] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => 'DB Update failed: ' . $e->getMessage()
                    ];
                }
                
            } else {
                echo "\n   ℹ️  الطلب غير مدفوع (Status: {$invoiceStatus})\n";
                echo "   ✅ لا حاجة للتحديث\n";
                
                $results['already_correct'][] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $invoiceStatus
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
    if ($num < $awaitingOrders->count()) {
        usleep(500000);
    }
}

echo str_repeat("=", 70) . "\n";
echo "    ملخص النتائج\n";
echo str_repeat("=", 70) . "\n\n";

// Fixed
if (count($results['fixed']) > 0) {
    echo "✅ طلبات تم إصلاحها: " . count($results['fixed']) . "\n";
    echo str_repeat("-", 70) . "\n";
    $totalFixed = 0;
    foreach ($results['fixed'] as $item) {
        echo "   • Order {$item['order_number']} (ID: {$item['order_id']})\n";
        echo "     Value: {$item['invoice_value']} KWD\n";
        $totalFixed += $item['invoice_value'];
    }
    echo "\n   💰 إجمالي القيمة المُصلحة: {$totalFixed} KWD\n";
    echo "\n";
} else {
    echo "ℹ️  لا توجد طلبات تحتاج إصلاح\n\n";
}

// Already Correct
if (count($results['already_correct']) > 0) {
    echo "✅ طلبات صحيحة (غير مدفوعة): " . count($results['already_correct']) . "\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($results['already_correct'] as $item) {
        echo "   • Order {$item['order_number']} (Status: {$item['status']})\n";
    }
    echo "\n";
}

// Errors
if (count($results['errors']) > 0) {
    echo "⚠️  أخطاء: " . count($results['errors']) . "\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($results['errors'] as $item) {
        echo "   • Order {$item['order_number']} (ID: {$item['order_id']})\n";
        echo "     Error: {$item['error']}\n";
    }
    echo "\n";
}

echo str_repeat("=", 70) . "\n\n";

// Final stats
echo "📊 إحصائيات نهائية:\n";
echo "   تم فحص: {$awaitingOrders->count()} طلب\n";
echo "   تم إصلاحها: " . count($results['fixed']) . "\n";
echo "   صحيحة (لا تحتاج إصلاح): " . count($results['already_correct']) . "\n";
echo "   أخطاء: " . count($results['errors']) . "\n";
echo "\n";

// Check remaining
$remainingAwaiting = Order::where('status', 'awaiting_payment')->count();
$remainingPaid = Order::where('status', 'paid')->count();

echo "📈 حالة النظام بعد الإصلاح:\n";
echo "   طلبات مدفوعة: {$remainingPaid}\n";
echo "   في انتظار الدفع: {$remainingAwaiting}\n";
echo "\n";

if (count($results['fixed']) > 0) {
    echo "🎉 تم إصلاح الطلبات بنجاح!\n";
    echo "   يمكنك الآن التحقق من الطلبات في لوحة التحكم\n";
} else {
    echo "✅ كل الطلبات في الحالة الصحيحة!\n";
}

echo "\n";
echo str_repeat("=", 70) . "\n";

