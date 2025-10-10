<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;

class ImageStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:stats {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display image storage statistics';

    protected ImageService $imageService;

    /**
     * Create a new command instance.
     */
    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = $this->option('folder');

        $this->info('📊 Image Storage Statistics');
        $this->newLine();

        try {
            $stats = $this->imageService->getStorageStatistics();

            // Overall statistics
            $this->info('📈 Overall Statistics:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Images', $stats['total_images']],
                    ['Total Size', $this->formatBytes($stats['total_size'])],
                    ['Total Size (MB)', $stats['total_size_mb'] . ' MB'],
                    ['Folders Count', $stats['folders_count']],
                    ['Storage Usage', $stats['storage_usage']['percentage'] . '%'],
                ]
            );

            // Storage usage
            $this->newLine();
            $this->info('💾 Storage Usage:');
            $this->table(
                ['Type', 'Size'],
                [
                    ['Used Space', $this->formatBytes($stats['storage_usage']['used'])],
                    ['Available Space', $this->formatBytes($stats['storage_usage']['available'])],
                    ['Usage Percentage', $stats['storage_usage']['percentage'] . '%'],
                ]
            );

            // Folders statistics
            if (!empty($stats['folders'])) {
                $this->newLine();
                $this->info('📁 Folders Statistics:');
                $this->table(
                    ['Folder', 'Images', 'Size', 'Size (MB)'],
                    array_map(function ($folder) {
                        return [
                            $folder['name'],
                            $folder['image_count'],
                            $this->formatBytes($folder['total_size']),
                            round($folder['total_size'] / (1024 * 1024), 2) . ' MB'
                        ];
                    }, $stats['folders'])
                );
            }

            // If specific folder requested
            if ($folder) {
                $this->newLine();
                $this->info("📂 Folder '{$folder}' Details:");
                
                $folderStats = collect($stats['folders'])->firstWhere('name', $folder);
                
                if ($folderStats) {
                    $this->table(
                        ['Property', 'Value'],
                        [
                            ['Name', $folderStats['name']],
                            ['Path', $folderStats['path']],
                            ['Image Count', $folderStats['image_count']],
                            ['Total Size', $this->formatBytes($folderStats['total_size'])],
                            ['Created At', date('Y-m-d H:i:s', $folderStats['created_at'])],
                        ]
                    );
                } else {
                    $this->warn("Folder '{$folder}' not found");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Error retrieving image statistics: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
