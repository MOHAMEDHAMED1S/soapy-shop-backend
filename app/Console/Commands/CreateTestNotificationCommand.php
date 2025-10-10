<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CreateTestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {--type=order_created} {--priority=medium} {--title=} {--message=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test notification';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $priority = $this->option('priority');
        $title = $this->option('title') ?: $this->getDefaultTitle($type);
        $message = $this->option('message') ?: $this->getDefaultMessage($type);

        $this->info('Creating test notification...');
        $this->table(['Property', 'Value'], [
            ['Type', $type],
            ['Priority', $priority],
            ['Title', $title],
            ['Message', $message]
        ]);

        try {
            $notification = $this->notificationService->createNotification(
                $type,
                $title,
                $message,
                [
                    'test_data' => true,
                    'created_by' => 'command',
                    'timestamp' => now()->toISOString()
                ],
                $priority
            );

            $this->info('✅ Test notification created successfully!');
            $this->info("Notification ID: {$notification->id}");
            $this->info("Created at: {$notification->created_at}");

        } catch (\Exception $e) {
            $this->error('❌ Error creating test notification: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function getDefaultTitle(string $type): string
    {
        $titles = [
            'order_created' => 'طلب جديد تم إنشاؤه',
            'order_paid' => 'تم دفع الطلب',
            'order_shipped' => 'تم شحن الطلب',
            'order_delivered' => 'تم تسليم الطلب',
            'order_cancelled' => 'تم إلغاء الطلب',
            'payment_failed' => 'فشل في الدفع',
            'payment_refunded' => 'تم استرداد الدفع',
            'product_low_stock' => 'مخزون منخفض',
            'product_out_of_stock' => 'نفد المخزون',
            'new_customer' => 'عميل جديد',
        ];

        return $titles[$type] ?? 'إشعار تجريبي';
    }

    private function getDefaultMessage(string $type): string
    {
        $messages = [
            'order_created' => 'تم إنشاء طلب جديد رقم ORD-TEST-001 بقيمة 50.000 KWD',
            'order_paid' => 'تم دفع الطلب رقم ORD-TEST-001 بنجاح',
            'order_shipped' => 'تم شحن الطلب رقم ORD-TEST-001',
            'order_delivered' => 'تم تسليم الطلب رقم ORD-TEST-001 بنجاح',
            'order_cancelled' => 'تم إلغاء الطلب رقم ORD-TEST-001',
            'payment_failed' => 'فشل في الدفع للطلب رقم ORD-TEST-001',
            'payment_refunded' => 'تم استرداد الدفع للطلب رقم ORD-TEST-001',
            'product_low_stock' => 'المنتج "صابون طبيعي" لديه مخزون منخفض',
            'product_out_of_stock' => 'المنتج "صابون طبيعي" نفد من المخزون',
            'new_customer' => 'تم تسجيل عميل جديد: أحمد محمد',
        ];

        return $messages[$type] ?? 'هذا إشعار تجريبي للاختبار';
    }
}
