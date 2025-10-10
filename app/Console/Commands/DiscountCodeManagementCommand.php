<?php

namespace App\Console\Commands;

use App\Models\DiscountCode;
use App\Services\DiscountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiscountCodeManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:discount-code-management 
                            {action : Action to perform (list, create, stats, cleanup, test)}
                            {--code= : Discount code}
                            {--name= : Discount name}
                            {--type= : Discount type (percentage, fixed_amount, free_shipping)}
                            {--value= : Discount value}
                            {--expires= : Expiration date}
                            {--limit= : Usage limit}
                            {--min-order= : Minimum order amount}
                            {--active : Make discount active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage discount codes - create, list, get statistics, cleanup, and test';

    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        parent::__construct();
        $this->discountService = $discountService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listDiscountCodes();
                break;
            case 'create':
                $this->createDiscountCode();
                break;
            case 'stats':
                $this->showStatistics();
                break;
            case 'cleanup':
                $this->cleanupExpiredCodes();
                break;
            case 'test':
                $this->testDiscountCode();
                break;
            default:
                $this->error('Invalid action. Available actions: list, create, stats, cleanup, test');
                return 1;
        }

        return 0;
    }

    /**
     * List discount codes.
     */
    private function listDiscountCodes()
    {
        $this->info('🎫 قائمة أكواد الخصم:');
        $this->newLine();

        $codes = DiscountCode::withCount('usage')->orderBy('created_at', 'desc')->get();

        if ($codes->isEmpty()) {
            $this->comment('لا توجد أكواد خصم.');
            return;
        }

        $headers = ['ID', 'الكود', 'الاسم', 'النوع', 'القيمة', 'الاستخدام', 'الحالة', 'ينتهي في'];
        $rows = [];

        foreach ($codes as $code) {
            $status = $code->is_active ? '✅ نشط' : '❌ غير نشط';
            if ($code->isExpired()) {
                $status = '⏰ منتهي';
            }

            $expiresAt = $code->expires_at ? $code->expires_at->format('Y-m-d') : 'غير محدد';

            $rows[] = [
                $code->id,
                $code->code,
                $code->name,
                $this->getTypeLabel($code->type),
                $this->formatValue($code),
                $code->usage_count . '/' . ($code->usage_limit ?? '∞'),
                $status,
                $expiresAt,
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Create a new discount code.
     */
    private function createDiscountCode()
    {
        $this->info('🎫 إنشاء كود خصم جديد:');
        $this->newLine();

        $code = $this->option('code') ?: $this->ask('كود الخصم (اتركه فارغاً لإنشاء تلقائي)');
        $name = $this->option('name') ?: $this->ask('اسم كود الخصم');
        $type = $this->option('type') ?: $this->choice('نوع الخصم', ['percentage', 'fixed_amount', 'free_shipping'], 0);
        $value = $this->option('value') ?: $this->ask('قيمة الخصم');
        $expiresAt = $this->option('expires') ?: $this->ask('تاريخ الانتهاء (Y-m-d) - اتركه فارغاً لبدون انتهاء');
        $usageLimit = $this->option('limit') ?: $this->ask('حد الاستخدام (اتركه فارغاً لغير محدود)');
        $minOrderAmount = $this->option('min-order') ?: $this->ask('الحد الأدنى للطلب (اتركه فارغاً لبدون حد أدنى)');
        $isActive = $this->option('active') ?: $this->confirm('تفعيل الكود فوراً؟', true);

        $data = [
            'code' => $code ?: DiscountCode::generateUniqueCode(),
            'name' => $name,
            'type' => $type,
            'value' => (float) $value,
            'is_active' => $isActive,
        ];

        if ($expiresAt) {
            $data['expires_at'] = $expiresAt;
        }

        if ($usageLimit) {
            $data['usage_limit'] = (int) $usageLimit;
        }

        if ($minOrderAmount) {
            $data['minimum_order_amount'] = (float) $minOrderAmount;
        }

        $result = $this->discountService->createDiscountCode($data);

        if ($result['success']) {
            $this->info('✅ تم إنشاء كود الخصم بنجاح!');
            $this->table(
                ['المعلومة', 'القيمة'],
                [
                    ['الكود', $result['data']->code],
                    ['الاسم', $result['data']->name],
                    ['النوع', $this->getTypeLabel($result['data']->type)],
                    ['القيمة', $this->formatValue($result['data'])],
                    ['الحالة', $result['data']->is_active ? 'نشط' : 'غير نشط'],
                ]
            );
        } else {
            $this->error('❌ فشل في إنشاء كود الخصم: ' . $result['message']);
        }
    }

    /**
     * Show discount code statistics.
     */
    private function showStatistics()
    {
        $this->info('📊 إحصائيات أكواد الخصم:');
        $this->newLine();

        $period = $this->ask('الفترة بالأيام (افتراضي: 30)', 30);
        $stats = $this->discountService->getDiscountCodeStatistics($period);

        $this->comment('📈 نظرة عامة:');
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الأكواد', $stats['overview']['total_codes']],
                ['الأكواد النشطة', $stats['overview']['active_codes']],
                ['الأكواد المنتهية', $stats['overview']['expired_codes']],
                ['الأكواد المستخدمة', $stats['overview']['used_codes']],
            ]
        );

        $this->newLine();
        $this->comment("📊 إحصائيات الفترة ({$period} يوم):");
        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['إجمالي الاستخدام', $stats['period_stats']['total_usage']],
                ['إجمالي مبلغ الخصم', $stats['period_stats']['total_discount_amount'] . ' د.ك'],
                ['متوسط الخصم لكل استخدام', $stats['period_stats']['average_discount_per_usage'] . ' د.ك'],
            ]
        );

        if (!empty($stats['most_used_codes'])) {
            $this->newLine();
            $this->comment('🏆 أكثر الأكواد استخداماً:');
            $rows = [];
            foreach ($stats['most_used_codes'] as $code) {
                $rows[] = [
                    $code->code,
                    $code->name,
                    $code->usage_count,
                    $this->getTypeLabel($code->type),
                ];
            }
            $this->table(['الكود', 'الاسم', 'الاستخدام', 'النوع'], $rows);
        }
    }

    /**
     * Cleanup expired discount codes.
     */
    private function cleanupExpiredCodes()
    {
        $this->info('🧹 تنظيف أكواد الخصم المنتهية:');
        $this->newLine();

        $expiredCodes = DiscountCode::expired()->get();

        if ($expiredCodes->isEmpty()) {
            $this->comment('لا توجد أكواد خصم منتهية.');
            return;
        }

        $this->comment("تم العثور على {$expiredCodes->count()} كود خصم منتهي:");
        $this->table(
            ['ID', 'الكود', 'الاسم', 'تاريخ الانتهاء'],
            $expiredCodes->map(function ($code) {
                return [
                    $code->id,
                    $code->code,
                    $code->name,
                    $code->expires_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        );

        if ($this->confirm('هل تريد حذف هذه الأكواد؟', false)) {
            $deletedCount = 0;
            foreach ($expiredCodes as $code) {
                if ($code->usage_count == 0) {
                    $code->delete();
                    $deletedCount++;
                }
            }

            $this->info("✅ تم حذف {$deletedCount} كود خصم منتهي.");
        } else {
            $this->comment('تم إلغاء العملية.');
        }
    }

    /**
     * Test a discount code.
     */
    private function testDiscountCode()
    {
        $this->info('🧪 اختبار كود الخصم:');
        $this->newLine();

        $code = $this->option('code') ?: $this->ask('كود الخصم للاختبار');
        $orderAmount = $this->ask('مبلغ الطلب للاختبار', 100);

        $discountCode = DiscountCode::where('code', $code)->first();

        if (!$discountCode) {
            $this->error('❌ كود الخصم غير موجود.');
            return;
        }

        $this->comment('📋 تفاصيل كود الخصم:');
        $this->table(
            ['المعلومة', 'القيمة'],
            [
                ['الكود', $discountCode->code],
                ['الاسم', $discountCode->name],
                ['النوع', $this->getTypeLabel($discountCode->type)],
                ['القيمة', $this->formatValue($discountCode)],
                ['الحالة', $discountCode->is_active ? 'نشط' : 'غير نشط'],
                ['صالح', $discountCode->isValid() ? 'نعم' : 'لا'],
                ['منتهي', $discountCode->isExpired() ? 'نعم' : 'لا'],
                ['وصل للحد الأقصى', $discountCode->hasReachedUsageLimit() ? 'نعم' : 'لا'],
            ]
        );

        $this->newLine();
        $this->comment("🧮 حساب الخصم لمبلغ {$orderAmount} د.ك:");

        $discountAmount = $discountCode->calculateDiscountAmount($orderAmount);
        $finalAmount = $orderAmount - $discountAmount;

        $this->table(
            ['المعلومة', 'القيمة'],
            [
                ['مبلغ الطلب الأصلي', $orderAmount . ' د.ك'],
                ['مبلغ الخصم', $discountAmount . ' د.ك'],
                ['المبلغ النهائي', $finalAmount . ' د.ك'],
                ['نسبة الخصم', round(($discountAmount / $orderAmount) * 100, 2) . '%'],
            ]
        );
    }

    /**
     * Get type label in Arabic.
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'percentage' => 'نسبة مئوية',
            'fixed_amount' => 'مبلغ ثابت',
            'free_shipping' => 'شحن مجاني',
            default => $type,
        };
    }

    /**
     * Format value based on type.
     */
    private function formatValue(DiscountCode $code): string
    {
        return match ($code->type) {
            'percentage' => $code->value . '%',
            'fixed_amount' => $code->value . ' د.ك',
            'free_shipping' => 'مجاني',
            default => $code->value,
        };
    }
}