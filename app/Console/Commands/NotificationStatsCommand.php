<?php

namespace App\Console\Commands;

use App\Models\AdminNotification;
use Illuminate\Console\Command;

class NotificationStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:stats {--period=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display notification statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->option('period');
        $startDate = now()->subDays($period);

        $this->info("📊 Notification Statistics (Last {$period} days)");
        $this->newLine();

        // Overall statistics
        $totalNotifications = AdminNotification::count();
        $unreadNotifications = AdminNotification::whereNull('read_at')->count();
        $readNotifications = AdminNotification::whereNotNull('read_at')->count();
        $recentNotifications = AdminNotification::where('created_at', '>=', $startDate)->count();

        $this->info('📈 Overall Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Notifications', $totalNotifications],
                ['Unread Notifications', $unreadNotifications],
                ['Read Notifications', $readNotifications],
                ['Recent Notifications', $recentNotifications],
                ['Read Rate', $totalNotifications > 0 ? 
                    round(($readNotifications / $totalNotifications) * 100, 2) . '%' : '0%']
            ]
        );

        // By type
        $notificationsByType = AdminNotification::where('created_at', '>=', $startDate)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        if ($notificationsByType->isNotEmpty()) {
            $this->newLine();
            $this->info('🏷️ By Type:');
            $this->table(
                ['Type', 'Count'],
                $notificationsByType->map(fn($item) => [$item->type, $item->count])
            );
        }

        // By priority
        $notificationsByPriority = AdminNotification::where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function ($notification) {
                return $notification->payload['priority'] ?? 'medium';
            })
            ->map(fn($group) => $group->count())
            ->sortDesc();

        if ($notificationsByPriority->isNotEmpty()) {
            $this->newLine();
            $this->info(' By Priority:');
            $this->table(
                ['Priority', 'Count'],
                $notificationsByPriority->map(fn($count, $priority) => [$priority, $count])
            );
        }

        // Daily statistics
        $dailyStats = AdminNotification::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        if ($dailyStats->isNotEmpty()) {
            $this->newLine();
            $this->info('📅 Daily Statistics (Last 7 days):');
            $this->table(
                ['Date', 'Count'],
                $dailyStats->map(fn($stat) => [$stat->date, $stat->count])
            );
        }

        // Recent notifications
        $recentNotifications = AdminNotification::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentNotifications->isNotEmpty()) {
            $this->newLine();
            $this->info('🕒 Recent Notifications:');
            $this->table(
                ['ID', 'Type', 'Priority', 'Created At', 'Read'],
                $recentNotifications->map(function ($notification) {
                    return [
                        $notification->id,
                        $notification->type,
                        $notification->payload['priority'] ?? 'medium',
                        $notification->created_at->format('Y-m-d H:i:s'),
                        $notification->read_at ? '✅' : '❌'
                    ];
                })
            );
        }

        // Unread notifications by type
        $unreadByType = AdminNotification::whereNull('read_at')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        if ($unreadByType->isNotEmpty()) {
            $this->newLine();
            $this->info('❌ Unread Notifications by Type:');
            $this->table(
                ['Type', 'Unread Count'],
                $unreadByType->map(fn($item) => [$item->type, $item->count])
            );
        }

        return 0;
    }
}
