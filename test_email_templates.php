<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Mail\OrderPaidNotification;
use App\Mail\CustomerOrderConfirmation;
use Illuminate\Support\Facades\Mail;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🧪 اختبار قوالب الإيميل الجديدة\n";
echo "================================\n\n";

try {
    // البحث عن طلب موجود للاختبار
    $order = Order::with(['customer', 'orderItems.product'])->where('status', 'paid')->first();
    
    if (!$order) {
        echo "❌ لم يتم العثور على أي طلبات في قاعدة البيانات\n";
        echo "يرجى إنشاء طلب أولاً قبل اختبار الإيميلات\n";
        exit(1);
    }
    
    echo "✅ تم العثور على الطلب رقم: {$order->id}\n";
    echo "العميل: {$order->customer->name}\n";
    echo "المبلغ الإجمالي: {$order->total_amount}\n\n";
    
    // اختبار إيميل تأكيد الطلب للعميل
    echo "📧 اختبار إيميل تأكيد الطلب للعميل...\n";
    
    $customerEmail = new CustomerOrderConfirmation($order);
    // إرسال الإيميل للعميل
    Mail::to('mmop9909@gmail.com')->send($customerEmail);
    echo "✅ تم إرسال إيميل تأكيد الطلب إلى: {$order->customer->email}\n\n";
    
    // اختبار إيميل إشعار الإدارة
    echo "📧 اختبار إيميل إشعار الإدارة...\n";
    
    // البحث عن مستخدم إداري
    $admin = User::where('role', 'admin')->first();
    
    if (!$admin) {
        // إنشاء إيميل اختبار للإدارة
        $adminEmail = 'mmop9909@gmail.com';
        echo "⚠️  لم يتم العثور على مستخدم إداري، سيتم الإرسال إلى: {$adminEmail}\n";
    } else {
        $adminEmail = $admin->email;
        echo "✅ تم العثور على المستخدم الإداري: {$adminEmail}\n";
    }
    
    // إنشاء كائن User للإدارة إذا لم يكن موجوداً
    if (!$admin) {
        $admin = new User();
        $admin->name = 'مدير النظام';
        $admin->email = $adminEmail;
    }
    
    $orderPaidNotification = new OrderPaidNotification($order, $admin);
    
    // إرسال الإيميل للإدارة
    Mail::to($adminEmail)->send($orderPaidNotification);
    echo "✅ تم إرسال إشعار الطلب المدفوع إلى الإدارة: {$adminEmail}\n\n";
    
    echo "🎉 تم إرسال جميع الإيميلات بنجاح!\n";
    echo "يرجى التحقق من صندوق الوارد للتأكد من التصميم الجديد\n\n";
    
    // عرض معلومات إضافية عن الطلب
    echo "📋 تفاصيل الطلب المستخدم في الاختبار:\n";
    echo "- رقم الطلب: {$order->id}\n";
    echo "- اسم العميل: {$order->customer->name}\n";
    echo "- إيميل العميل: {$order->customer->email}\n";
    echo "- المبلغ الإجمالي: {$order->total_amount}\n";
    echo "- حالة الطلب: {$order->status}\n";
    echo "- عدد المنتجات: " . $order->orderItems->count() . "\n";
    
    if ($order->orderItems->count() > 0) {
        echo "\n📦 المنتجات في الطلب:\n";
        foreach ($order->orderItems as $item) {
            echo "  - {$item->product->title} (الكمية: {$item->quantity}, السعر: {$item->product_price})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ حدث خطأ أثناء اختبار الإيميلات:\n";
    echo "الخطأ: " . $e->getMessage() . "\n";
    echo "الملف: " . $e->getFile() . "\n";
    echo "السطر: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n✨ انتهى الاختبار بنجاح!\n";