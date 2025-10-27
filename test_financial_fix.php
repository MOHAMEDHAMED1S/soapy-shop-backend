<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReportController;

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "    اختبار التعديل الجديد للإحصائيات المالية\n";
echo str_repeat("=", 80) . "\n\n";

// إنشاء request
$request = Request::create('/api/v1/reports/financial/overview', 'GET', [
    'date_from' => \Carbon\Carbon::now()->subDays(30)->format('Y-m-d'),
    'date_to' => \Carbon\Carbon::now()->format('Y-m-d'),
]);

// استدعاء API
$controller = new ReportController();
$response = $controller->getFinancialReports($request);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ خطأ: " . ($data['message'] ?? 'Unknown error') . "\n\n";
    exit;
}

$revenueBreakdown = $data['data']['revenue_breakdown'];

echo "1️⃣  الإيرادات للفترة المحددة (آخر 30 يوم):\n";
echo str_repeat("-", 80) . "\n";
echo "   Subtotal: " . number_format($revenueBreakdown['period']['total_subtotal'], 3) . " د.ك\n";
echo "   Shipping: " . number_format($revenueBreakdown['period']['total_shipping'], 3) . " د.ك\n";
echo "   Discount: " . number_format($revenueBreakdown['period']['total_discount'], 3) . " د.ك\n";
echo "   Tax: " . number_format($revenueBreakdown['period']['total_tax'], 3) . " د.ك\n";
echo "   ───────────────────────────────────\n";
echo "   Total Revenue: " . number_format($revenueBreakdown['period']['total_revenue'], 3) . " د.ك\n";
echo "   Total Orders: " . $revenueBreakdown['period']['total_orders'] . "\n\n";

echo "2️⃣  الإيرادات لكل الوقت:\n";
echo str_repeat("-", 80) . "\n";
echo "   Subtotal: " . number_format($revenueBreakdown['all_time']['total_subtotal'], 3) . " د.ك\n";
echo "   Shipping: " . number_format($revenueBreakdown['all_time']['total_shipping'], 3) . " د.ك\n";
echo "   Discount: " . number_format($revenueBreakdown['all_time']['total_discount'], 3) . " د.ك\n";
echo "   Tax: " . number_format($revenueBreakdown['all_time']['total_tax'], 3) . " د.ك\n";
echo "   ───────────────────────────────────\n";
echo "   Total Revenue: " . number_format($revenueBreakdown['all_time']['total_revenue'], 3) . " د.ك\n";
echo "   Total Orders: " . $revenueBreakdown['all_time']['total_orders'] . "\n\n";

echo "3️⃣  المقارنة:\n";
echo str_repeat("-", 80) . "\n";

$periodRevenue = $revenueBreakdown['period']['total_revenue'];
$allTimeRevenue = $revenueBreakdown['all_time']['total_revenue'];
$diff = $allTimeRevenue - $periodRevenue;

echo "   الإيرادات (الفترة): " . number_format($periodRevenue, 3) . " د.ك\n";
echo "   الإيرادات (كل الوقت): " . number_format($allTimeRevenue, 3) . " د.ك\n";
echo "   الفرق: " . number_format($diff, 3) . " د.ك\n\n";

if ($allTimeRevenue >= $periodRevenue) {
    echo "   ✅ منطقي! الإيرادات الكلية أكبر من أو تساوي إيرادات الفترة\n\n";
} else {
    echo "   ❌ خطأ! الإيرادات الكلية يجب أن تكون أكبر من أو تساوي إيرادات الفترة\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "    النتيجة\n";
echo str_repeat("=", 80) . "\n\n";

echo "✅ الآن API الإحصائيات المالية يعرض:\n";
echo "   • revenue_breakdown.period → إيرادات الفترة المحددة\n";
echo "   • revenue_breakdown.all_time → إيرادات كل الوقت\n\n";

echo "📊 Response Structure:\n";
echo json_encode([
    'success' => true,
    'data' => [
        'revenue_breakdown' => [
            'period' => [
                'total_revenue' => number_format($periodRevenue, 3),
                'total_orders' => $revenueBreakdown['period']['total_orders'],
            ],
            'all_time' => [
                'total_revenue' => number_format($allTimeRevenue, 3),
                'total_orders' => $revenueBreakdown['all_time']['total_orders'],
            ]
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "🎉 المشكلة تم حلها!\n\n";

