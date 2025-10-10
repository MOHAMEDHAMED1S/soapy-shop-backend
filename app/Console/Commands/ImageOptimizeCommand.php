<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImageOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:optimize {--folder=} {--quality=85} {--dry-run} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize images for better performance';

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
        $quality = (int) $this->option('quality');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No images will be modified');
        }

        $this->info("Starting image optimization with quality: {$quality}%...");

        try {
            $searchPath = 'images' . ($folder ? '/' . $folder : '');
            $files = Storage::files($searchPath);

            $optimizedCount = 0;
            $totalSizeSaved = 0;
            $errors = [];

            $progressBar = $this->output->createProgressBar(count($files));
            $progressBar->start();

            foreach ($files as $file) {
                try {
                    if ($this->isImageFile($file)) {
                        $originalSize = Storage::size($file);
                        
                        if (!$dryRun) {
                            // Get image info
                            $imageInfo = $this->imageService->getImageInfo($file);
                            
                            // Optimize image
                            $optimizedImage = $this->optimizeImage($file, $quality);
                            
                            // Replace original with optimized version
                            Storage::put($file, $optimizedImage);
                            
                            $newSize = Storage::size($file);
                            $sizeSaved = $originalSize - $newSize;
                            $totalSizeSaved += $sizeSaved;
                        } else {
                            // In dry run, estimate size reduction
                            $totalSizeSaved += $originalSize * 0.2; // Assume 20% reduction
                        }
                        
                        $optimizedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => basename($file),
                        'error' => $e->getMessage()
                    ];
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Results
            $this->info("✅ Optimization completed!");
            $this->info("Images processed: {$optimizedCount}");
            $this->info("Total size saved: " . $this->formatBytes($totalSizeSaved));

            if (!empty($errors)) {
                $this->newLine();
                $this->warn("⚠️ Errors encountered:");
                foreach ($errors as $error) {
                    $this->line("• {$error['file']}: {$error['error']}");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Error during optimization: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Optimize image
     */
    protected function optimizeImage(string $filePath, int $quality): string
    {
        $imageContent = Storage::get($filePath);
        $img = \Intervention\Image\Facades\Image::make($imageContent);
        
        // Optimize based on file type
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img->encode('jpg', $quality);
                break;
            case 'png':
                $img->encode('png', $quality);
                break;
            case 'webp':
                $img->encode('webp', $quality);
                break;
            default:
                $img->encode(null, $quality);
        }
        
        return $img->stream()->getContents();
    }

    /**
     * Check if file is an image
     */
    protected function isImageFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
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
