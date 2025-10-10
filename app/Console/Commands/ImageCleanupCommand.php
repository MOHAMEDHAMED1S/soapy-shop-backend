<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImageCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:cleanup {--dry-run} {--temp-only} {--orphaned-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary and orphaned images';

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
        $dryRun = $this->option('dry-run');
        $tempOnly = $this->option('temp-only');
        $orphanedOnly = $this->option('orphaned-only');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be deleted');
        }

        $this->info('Starting image cleanup...');

        $totalDeleted = 0;
        $totalSize = 0;

        // Clean up temp files
        if (!$orphanedOnly) {
            $this->info('Cleaning up temporary files...');
            $tempResult = $this->cleanupTempFiles($dryRun);
            $totalDeleted += $tempResult['count'];
            $totalSize += $tempResult['size'];
        }

        // Clean up orphaned files
        if (!$tempOnly) {
            $this->info('Cleaning up orphaned files...');
            $orphanedResult = $this->cleanupOrphanedFiles($dryRun);
            $totalDeleted += $orphanedResult['count'];
            $totalSize += $orphanedResult['size'];
        }

        $this->info("✅ Cleanup completed!");
        $this->info("Total files: {$totalDeleted}");
        $this->info("Total size freed: " . $this->formatBytes($totalSize));

        return 0;
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles(bool $dryRun): array
    {
        $tempPath = 'images/temp';
        $deletedCount = 0;
        $deletedSize = 0;

        if (!Storage::exists($tempPath)) {
            $this->warn('Temp directory does not exist');
            return ['count' => 0, 'size' => 0];
        }

        $files = Storage::files($tempPath);
        $cutoffTime = now()->subHours(config('image.cleanup.temp_files_ttl', 3600) / 3600);

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            $fileTime = now()->createFromTimestamp($lastModified);

            if ($fileTime->lt($cutoffTime)) {
                $fileSize = Storage::size($file);
                
                if (!$dryRun) {
                    Storage::delete($file);
                }

                $deletedCount++;
                $deletedSize += $fileSize;
                
                $this->line("Deleted: " . basename($file) . " (" . $this->formatBytes($fileSize) . ")");
            }
        }

        $this->info("Temp files cleaned: {$deletedCount} files, " . $this->formatBytes($deletedSize));
        return ['count' => $deletedCount, 'size' => $deletedSize];
    }

    /**
     * Clean up orphaned files
     */
    protected function cleanupOrphanedFiles(bool $dryRun): array
    {
        $deletedCount = 0;
        $deletedSize = 0;

        // This is a simplified version - in a real application, you would
        // check for files that are not referenced in the database
        $this->warn('Orphaned file cleanup not fully implemented');
        $this->warn('In a real application, you would check database references');

        return ['count' => $deletedCount, 'size' => $deletedSize];
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
