<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\Api\Admin\OrderController;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "=== اختبار فلتر التاريخ في إحصائيات الطلبات ===\n\n";

try {
    // إنشاء instance من OrderController
    $notificationService = new NotificationService();
    $controller = new OrderController($notificationService);
    
    // محاكاة request مع فلاتر التاريخ
    $request = new Request();
    $request->merge([
        'start_date' => '2025-10-18',
        'end_date' => '2025-10-18'
    ]);
    
    echo "معاملات الطلب:\n";
    echo "start_date: " . $request->get('start_date') . "\n";
    echo "end_date: " . $request->get('end_date') . "\n\n";
    
    // استدعاء method statistics
    $response = $controller->statistics($request);
    $data = json_decode($response->getContent(), true);
    
    echo "حالة الاستجابة: " . ($data['success'] ? 'نجح' : 'فشل') . "\n";
    echo "رسالة الاستجابة: " . $data['message'] . "\n\n";
    
    if ($data['success'] && isset($data['data'])) {
        $stats = $data['data'];
        
        echo "=== إحصائيات الطلبات ===\n";
        echo "إجمالي الطلبات: " . ($stats['total_orders'] ?? 0) . "\n";
        echo "الطلبات المدفوعة: " . ($stats['paid_orders'] ?? 0) . "\n";
        echo "الطلبات المعلقة: " . ($stats['pending_orders'] ?? 0) . "\n";
        echo "إجمالي الإيرادات: " . ($stats['total_revenue'] ?? 0) . "\n";
        echo "متوسط قيمة الطلب: " . ($stats['average_order_value'] ?? 0) . "\n\n";
        
        echo "=== الفلاتر المطبقة ===\n";
        if (isset($stats['filters_applied'])) {
            $filters = $stats['filters_applied'];
            echo "date_from: " . ($filters['date_from'] ?? 'null') . "\n";
            echo "date_to: " . ($filters['date_to'] ?? 'null') . "\n";
            echo "start_date: " . ($filters['start_date'] ?? 'null') . "\n";
            echo "end_date: " . ($filters['end_date'] ?? 'null') . "\n";
            
            if (isset($filters['date_range'])) {
                echo "نطاق التاريخ المطبق:\n";
                echo "  البداية: " . $filters['date_range']['start'] . "\n";
                echo "  النهاية: " . $filters['date_range']['end'] . "\n";
            }
        }
        
        echo "\n=== التحقق من تطبيق الفلاتر ===\n";
        $filtersApplied = isset($stats['filters_applied']) && 
                         ($stats['filters_applied']['start_date'] === '2025-10-18' || 
                          $stats['filters_applied']['date_from'] === '2025-10-18');
        
        echo "هل تم تطبيق فلاتر التاريخ؟ " . ($filtersApplied ? 'نعم ✓' : 'لا ✗') . "\n";
        
        if ($filtersApplied) {
            echo "\n✅ نجح الاختبار: تم تطبيق فلاتر التاريخ بشكل صحيح\n";
        } else {
            echo "\n❌ فشل الاختبار: لم يتم تطبيق فلاتر التاريخ\n";
        }
    } else {
        echo "❌ فشل في الحصول على البيانات\n";
        if (isset($data['error'])) {
            echo "خطأ: " . $data['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ خطأ في الاختبار: " . $e->getMessage() . "\n";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString() . "\n";
}

echo "\n=== انتهى الاختبار ===\n";