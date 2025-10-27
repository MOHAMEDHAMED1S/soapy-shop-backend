<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ReportController;

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "    اختبار نوع البيانات في الإحصائيات المالية\n";
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

$period = $data['data']['revenue_breakdown']['period'];
$allTime = $data['data']['revenue_breakdown']['all_time'];

echo "1️⃣  التحقق من نوع البيانات (Period):\n";
echo str_repeat("-", 80) . "\n";

$checks = [
    'total_subtotal' => $period['total_subtotal'],
    'total_tax' => $period['total_tax'],
    'total_shipping' => $period['total_shipping'],
    'total_discount' => $period['total_discount'],
    'total_revenue' => $period['total_revenue'],
    'total_orders' => $period['total_orders'],
];

foreach ($checks as $field => $value) {
    $type = gettype($value);
    $expectedType = ($field === 'total_orders') ? 'integer' : 'double';
    
    $icon = ($type === $expectedType) ? '✅' : '❌';
    echo "   {$icon} {$field}: {$value} ({$type})";
    
    if ($type !== $expectedType) {
        echo " ← يجب أن يكون {$expectedType}";
    }
    echo "\n";
}

echo "\n2️⃣  التحقق من نوع البيانات (All Time):\n";
echo str_repeat("-", 80) . "\n";

$checks = [
    'total_subtotal' => $allTime['total_subtotal'],
    'total_tax' => $allTime['total_tax'],
    'total_shipping' => $allTime['total_shipping'],
    'total_discount' => $allTime['total_discount'],
    'total_revenue' => $allTime['total_revenue'],
    'total_orders' => $allTime['total_orders'],
];

foreach ($checks as $field => $value) {
    $type = gettype($value);
    $expectedType = ($field === 'total_orders') ? 'integer' : 'double';
    
    $icon = ($type === $expectedType) ? '✅' : '❌';
    echo "   {$icon} {$field}: {$value} ({$type})";
    
    if ($type !== $expectedType) {
        echo " ← يجب أن يكون {$expectedType}";
    }
    echo "\n";
}

echo "\n3️⃣  JSON Response Sample:\n";
echo str_repeat("-", 80) . "\n";
echo json_encode([
    'revenue_breakdown' => [
        'period' => [
            'total_revenue' => $period['total_revenue'],
            'total_orders' => $period['total_orders'],
        ],
        'all_time' => [
            'total_revenue' => $allTime['total_revenue'],
            'total_orders' => $allTime['total_orders'],
        ]
    ]
], JSON_PRETTY_PRINT) . "\n\n";

echo str_repeat("=", 80) . "\n";
echo "    النتيجة\n";
echo str_repeat("=", 80) . "\n\n";

$allCorrect = true;
foreach ($checks as $field => $value) {
    $type = gettype($value);
    $expectedType = ($field === 'total_orders') ? 'integer' : 'double';
    if ($type !== $expectedType) {
        $allCorrect = false;
        break;
    }
}

if ($allCorrect) {
    echo "✅ جميع الحقول بالنوع الصحيح!\n";
    echo "   • الأرقام المالية: double/float ✅\n";
    echo "   • عدد الطلبات: integer ✅\n\n";
} else {
    echo "❌ يوجد حقول بنوع خاطئ\n\n";
}

