<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "🧪 اختبار إصلاح مشكلة البريد الإلكتروني المكرر\n";
echo "==========================================\n\n";

try {
    $customerService = new CustomerService();
    
    // إنشاء عميل تجريبي أول
    echo "1️⃣ إنشاء عميل تجريبي أول...\n";
    $customer1 = Customer::create([
        'name' => 'أحمد محمد',
        'phone' => '+96512345678',
        'email' => 'mmop9909@gmail.com',
        'address' => ['street' => 'شارع الكويت', 'city' => 'الكويت'],
        'is_active' => true,
    ]);
    echo "✅ تم إنشاء العميل الأول - ID: {$customer1->id}, البريد: {$customer1->email}\n\n";
    
    // إنشاء عميل تجريبي ثاني برقم هاتف مختلف
    echo "2️⃣ إنشاء عميل تجريبي ثاني برقم هاتف مختلف...\n";
    $customer2 = Customer::create([
        'name' => 'سارة أحمد',
        'phone' => '+96587654321',
        'email' => 'sara@example.com',
        'address' => ['street' => 'شارع الأحمدي', 'city' => 'الأحمدي'],
        'is_active' => true,
    ]);
    echo "✅ تم إنشاء العميل الثاني - ID: {$customer2->id}, البريد: {$customer2->email}\n\n";
    
    // محاولة إنشاء طلب جديد بنفس البريد الإلكتروني للعميل الأول ولكن برقم هاتف العميل الثاني
    echo "3️⃣ محاولة إنشاء طلب جديد بنفس البريد الإلكتروني للعميل الأول ولكن برقم هاتف العميل الثاني...\n";
    
    $orderData = [
        'customer_name' => 'سارة أحمد المحدثة',
        'customer_phone' => '+96587654321', // رقم هاتف العميل الثاني
        'customer_email' => 'mmop9909@gmail.com', // بريد إلكتروني العميل الأول
        'shipping_address' => [
            'street' => 'شارع جديد',
            'city' => 'حولي',
            'governorate' => 'محافظة حولي'
        ]
    ];
    
    $resultCustomer = $customerService->findOrCreateCustomerForOrder($orderData);
    
    echo "✅ تم العثور على العميل - ID: {$resultCustomer->id}\n";
    echo "الاسم: {$resultCustomer->name}\n";
    echo "الهاتف: {$resultCustomer->phone}\n";
    echo "البريد الإلكتروني: " . ($resultCustomer->email ?? 'غير محدد') . "\n";
    echo "العنوان: " . json_encode($resultCustomer->address, JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // التحقق من أن البريد الإلكتروني لم يتم تحديثه
    if ($resultCustomer->email === 'sara@example.com') {
        echo "✅ نجح الإصلاح: لم يتم تحديث البريد الإلكتروني لتجنب التكرار\n";
    } else {
        echo "❌ فشل الإصلاح: تم تحديث البريد الإلكتروني بشكل خاطئ\n";
    }
    
    // اختبار حالة أخرى: تحديث بريد إلكتروني جديد غير مكرر
    echo "\n4️⃣ اختبار تحديث بريد إلكتروني جديد غير مكرر...\n";
    
    $orderData2 = [
        'customer_name' => 'سارة أحمد',
        'customer_phone' => '+96587654321',
        'customer_email' => 'sara.new@example.com', // بريد إلكتروني جديد غير مكرر
        'shipping_address' => [
            'street' => 'شارع آخر',
            'city' => 'الجهراء',
            'governorate' => 'محافظة الجهراء'
        ]
    ];
    
    $resultCustomer2 = $customerService->findOrCreateCustomerForOrder($orderData2);
    
    echo "✅ تم العثور على العميل - ID: {$resultCustomer2->id}\n";
    echo "البريد الإلكتروني الجديد: " . ($resultCustomer2->email ?? 'غير محدد') . "\n";
    
    if ($resultCustomer2->email === 'sara.new@example.com') {
        echo "✅ نجح التحديث: تم تحديث البريد الإلكتروني الجديد بنجاح\n";
    } else {
        echo "❌ فشل التحديث: لم يتم تحديث البريد الإلكتروني الجديد\n";
    }
    
    echo "\n🎉 انتهى الاختبار بنجاح!\n";
    
    // تنظيف البيانات التجريبية
    echo "\n🧹 تنظيف البيانات التجريبية...\n";
    $customer1->delete();
    $customer2->delete();
    echo "✅ تم حذف البيانات التجريبية\n";
    
} catch (\Exception $e) {
    echo "❌ حدث خطأ أثناء الاختبار: " . $e->getMessage() . "\n";
    echo "التفاصيل: " . $e->getTraceAsString() . "\n";
}