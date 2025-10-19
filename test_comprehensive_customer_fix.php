<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Services\CustomerService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 بدء الاختبار الشامل لإصلاح البريد الإلكتروني المكرر\n\n";

try {
    $customerService = new CustomerService();
    
    // تنظيف البيانات السابقة
    echo "🧹 تنظيف البيانات السابقة...\n";
    Customer::where('phone', 'LIKE', '999%')->delete();
    
    // الاختبار 1: إنشاء عميل جديد ببريد إلكتروني جديد
    echo "\n📝 الاختبار 1: إنشاء عميل جديد ببريد إلكتروني جديد\n";
    $orderData1 = [
        'customer_name' => 'أحمد محمد',
        'customer_phone' => '999111222',
        'customer_email' => 'ahmed@test.com',
        'shipping_address' => json_encode(['street' => 'شارع الاختبار', 'city' => 'القاهرة'])
    ];
    
    $customer1 = $customerService->findOrCreateCustomerForOrder($orderData1);
    echo "✅ تم إنشاء العميل الأول بنجاح - ID: {$customer1->id}, Email: {$customer1->email}\n";
    
    // الاختبار 2: إنشاء عميل جديد برقم هاتف مختلف ونفس البريد الإلكتروني (يجب أن يفشل بدون خطأ)
    echo "\n📝 الاختبار 2: إنشاء عميل جديد برقم هاتف مختلف ونفس البريد الإلكتروني\n";
    $orderData2 = [
        'customer_name' => 'محمد أحمد',
        'customer_phone' => '999333444',
        'customer_email' => 'ahmed@test.com', // نفس البريد الإلكتروني
        'shipping_address' => json_encode(['street' => 'شارع آخر', 'city' => 'الإسكندرية'])
    ];
    
    $customer2 = $customerService->findOrCreateCustomerForOrder($orderData2);
    echo "✅ تم إنشاء العميل الثاني بنجاح - ID: {$customer2->id}, Email: " . ($customer2->email ?? 'NULL') . "\n";
    
    if ($customer2->email === null) {
        echo "✅ نجح الإصلاح: لم يتم تعيين البريد الإلكتروني المكرر للعميل الجديد\n";
    } else {
        echo "❌ فشل الإصلاح: تم تعيين البريد الإلكتروني المكرر\n";
    }
    
    // الاختبار 3: تحديث عميل موجود ببريد إلكتروني مكرر (من الإصلاح السابق)
    echo "\n📝 الاختبار 3: تحديث عميل موجود ببريد إلكتروني مكرر\n";
    $orderData3 = [
        'customer_name' => 'محمد أحمد المحدث',
        'customer_phone' => '999333444', // نفس رقم العميل الثاني
        'customer_email' => 'ahmed@test.com', // نفس البريد الإلكتروني للعميل الأول
        'shipping_address' => json_encode(['street' => 'شارع محدث', 'city' => 'الجيزة'])
    ];
    
    $customer3 = $customerService->findOrCreateCustomerForOrder($orderData3);
    echo "✅ تم العثور على العميل وتحديثه - ID: {$customer3->id}, Email: " . ($customer3->email ?? 'NULL') . "\n";
    
    if ($customer3->email === null) {
        echo "✅ نجح الإصلاح: لم يتم تحديث البريد الإلكتروني المكرر\n";
    } else {
        echo "❌ فشل الإصلاح: تم تحديث البريد الإلكتروني المكرر\n";
    }
    
    // الاختبار 4: إنشاء عميل جديد ببريد إلكتروني جديد مختلف
    echo "\n📝 الاختبار 4: إنشاء عميل جديد ببريد إلكتروني جديد مختلف\n";
    $orderData4 = [
        'customer_name' => 'سارة علي',
        'customer_phone' => '999555666',
        'customer_email' => 'sara@test.com', // بريد إلكتروني جديد
        'shipping_address' => json_encode(['street' => 'شارع سارة', 'city' => 'المنصورة'])
    ];
    
    $customer4 = $customerService->findOrCreateCustomerForOrder($orderData4);
    echo "✅ تم إنشاء العميل الرابع بنجاح - ID: {$customer4->id}, Email: {$customer4->email}\n";
    
    // الاختبار 5: تحديث عميل موجود ببريد إلكتروني جديد غير مكرر
    echo "\n📝 الاختبار 5: تحديث عميل موجود ببريد إلكتروني جديد غير مكرر\n";
    $orderData5 = [
        'customer_name' => 'سارة علي المحدثة',
        'customer_phone' => '999555666', // نفس رقم العميل الرابع
        'customer_email' => 'sara.updated@test.com', // بريد إلكتروني جديد غير مكرر
        'shipping_address' => json_encode(['street' => 'شارع سارة المحدث', 'city' => 'طنطا'])
    ];
    
    $customer5 = $customerService->findOrCreateCustomerForOrder($orderData5);
    echo "✅ تم العثور على العميل وتحديثه - ID: {$customer5->id}, Email: {$customer5->email}\n";
    
    if ($customer5->email === 'sara.updated@test.com') {
        echo "✅ نجح التحديث: تم تحديث البريد الإلكتروني الجديد بنجاح\n";
    } else {
        echo "❌ فشل التحديث: لم يتم تحديث البريد الإلكتروني الجديد\n";
    }
    
    // عرض ملخص النتائج
    echo "\n📊 ملخص النتائج:\n";
    $allCustomers = Customer::where('phone', 'LIKE', '999%')->get();
    foreach ($allCustomers as $customer) {
        echo "- العميل {$customer->id}: {$customer->name} | {$customer->phone} | " . ($customer->email ?? 'لا يوجد بريد إلكتروني') . "\n";
    }
    
    // التحقق من عدم وجود بريد إلكتروني مكرر
    $emailCounts = Customer::where('phone', 'LIKE', '999%')
        ->whereNotNull('email')
        ->groupBy('email')
        ->selectRaw('email, COUNT(*) as count')
        ->having('count', '>', 1)
        ->get();
    
    if ($emailCounts->isEmpty()) {
        echo "\n✅ نجح الاختبار الشامل: لا توجد بريدات إلكترونية مكررة\n";
    } else {
        echo "\n❌ فشل الاختبار: توجد بريدات إلكترونية مكررة:\n";
        foreach ($emailCounts as $emailCount) {
            echo "- {$emailCount->email}: {$emailCount->count} مرات\n";
        }
    }
    
    // تنظيف البيانات
    echo "\n🧹 تنظيف بيانات الاختبار...\n";
    Customer::where('phone', 'LIKE', '999%')->delete();
    echo "✅ تم تنظيف البيانات بنجاح\n";
    
    echo "\n🎉 انتهى الاختبار الشامل بنجاح!\n";
    
} catch (Exception $e) {
    echo "\n❌ حدث خطأ أثناء الاختبار: " . $e->getMessage() . "\n";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString() . "\n";
    
    // تنظيف البيانات في حالة الخطأ
    try {
        Customer::where('phone', 'LIKE', '999%')->delete();
        echo "🧹 تم تنظيف البيانات بعد الخطأ\n";
    } catch (Exception $cleanupError) {
        echo "❌ فشل في تنظيف البيانات: " . $cleanupError->getMessage() . "\n";
    }
}