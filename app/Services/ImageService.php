<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    protected $disk;
    protected $basePath;
    protected $allowedMimes;
    protected $maxFileSize;

    protected $imageManager;

    public function __construct()
    {
        $this->disk = 'public'; // Use public disk for images
        $this->basePath = 'images';
        $this->allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload image with optimization
     */
    public function uploadImage(
        UploadedFile $image,
        string $folder = 'general',
        bool $optimize = true,
        array $resize = null,
        int $quality = 85
    ): array {
        try {
            // Validate image
            $this->validateImage($image);

            // Generate unique filename
            $filename = $this->generateFilename($image);
            $path = $this->basePath . '/' . $folder . '/' . $filename;

            // Process image
            $processedImage = $this->processImage($image, $resize, $quality, $optimize);

            // Store image
            Storage::disk($this->disk)->put($path, $processedImage);

            // Get image info
            $imageInfo = $this->getImageInfo($path);

            Log::info('Image uploaded successfully', [
                'path' => $path,
                'size' => $imageInfo['size'],
                'dimensions' => $imageInfo['dimensions']
            ]);

            return [
                'path' => base64_encode($path),
                'url' => $this->getImageUrl($path),
                'filename' => $filename,
                'original_name' => $image->getClientOriginalName(),
                'size' => $imageInfo['size'],
                'dimensions' => $imageInfo['dimensions'],
                'mime_type' => $imageInfo['mime_type'],
                'folder' => $folder,
                'uploaded_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'filename' => $image->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    /**
     * Process image (resize, optimize, etc.)
     */
    protected function processImage(
        UploadedFile $image,
        array $resize = null,
        int $quality = 85,
        bool $optimize = true
    ): string {
        $img = $this->imageManager->read($image);

        // Resize if requested
        if ($resize && isset($resize['width']) && isset($resize['height'])) {
            $img->scale($resize['width'], $resize['height']);
        }

        // Encode image
        if ($optimize) {
            $encoded = $img->toJpeg($quality);
        } else {
            $encoded = $img->encode();
        }

        return $encoded->toString();
    }

    /**
     * Resize existing image
     */
    public function resizeImage(
        string $imagePath,
        int $width,
        int $height,
        int $quality = 85,
        bool $crop = false
    ): array {
        try {
            if (!Storage::exists($imagePath)) {
                throw new \Exception('Image not found');
            }

            $imageContent = Storage::get($imagePath);
            $img = $this->imageManager->read($imageContent);

            if ($crop) {
                $img->cover($width, $height);
            } else {
                $img->scale($width, $height);
            }

            $img->toJpeg($quality);

            // Generate new filename for resized image
            $pathInfo = pathinfo($imagePath);
            $newFilename = $pathInfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathInfo['extension'];
            $newPath = $pathInfo['dirname'] . '/' . $newFilename;

            // Store resized image
            Storage::put($newPath, $img->toDataUri());

            $imageInfo = $this->getImageInfo($newPath);

            return [
                'path' => base64_encode($newPath),
                'url' => $this->getImageUrl($newPath),
                'filename' => $newFilename,
                'size' => $imageInfo['size'],
                'dimensions' => $imageInfo['dimensions'],
                'mime_type' => $imageInfo['mime_type'],
                'resized_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error('Image resize failed', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            throw $e;
        }
    }

    /**
     * Delete image
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            if (!Storage::exists($imagePath)) {
                return false;
            }

            $deleted = Storage::delete($imagePath);

            if ($deleted) {
                Log::info('Image deleted successfully', ['path' => $imagePath]);
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            return false;
        }
    }

    /**
     * Get image information
     */
    public function getImageInfo(string $imagePath): array
    {
        try {
            if (!Storage::disk($this->disk)->exists($imagePath)) {
                throw new \Exception('Image not found at: ' . $imagePath);
            }

            $imageContent = Storage::disk($this->disk)->get($imagePath);
            $img = $this->imageManager->read($imageContent);

            return [
                'size' => Storage::disk($this->disk)->size($imagePath),
                'dimensions' => [
                    'width' => $img->width(),
                    'height' => $img->height()
                ],
                'mime_type' => Storage::disk($this->disk)->mimeType($imagePath),
                'created_at' => Storage::disk($this->disk)->lastModified($imagePath)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get image info', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            throw $e;
        }
    }

    /**
     * Get image URL
     */
    public function getImageUrl(string $imagePath): string
    {
        return Storage::disk($this->disk)->url($imagePath);
    }

    /**
     * Get image path from encoded path
     */
    public function getImagePath(string $encodedPath): ?string
    {
        try {
            $decodedPath = base64_decode($encodedPath);
            return Storage::exists($decodedPath) ? $decodedPath : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get images list
     */
    public function getImagesList(
        string $folder = '',
        int $perPage = 20,
        string $search = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): array {
        try {
            $searchPath = $this->basePath . ($folder ? '/' . $folder : '');
            $files = Storage::files($searchPath);

            $images = [];

            foreach ($files as $file) {
                if ($this->isImageFile($file)) {
                    $imageInfo = $this->getImageInfo($file);
                    
                    $imageData = [
                        'path' => base64_encode($file),
                        'url' => $this->getImageUrl($file),
                        'filename' => basename($file),
                        'size' => $imageInfo['size'],
                        'dimensions' => $imageInfo['dimensions'],
                        'mime_type' => $imageInfo['mime_type'],
                        'created_at' => $imageInfo['created_at'],
                        'folder' => $folder
                    ];

                    // Apply search filter
                    if ($search && !str_contains(strtolower($imageData['filename']), strtolower($search))) {
                        continue;
                    }

                    $images[] = $imageData;
                }
            }

            // Sort images
            usort($images, function ($a, $b) use ($sortBy, $sortOrder) {
                $valueA = $a[$sortBy] ?? 0;
                $valueB = $b[$sortBy] ?? 0;

                if ($sortOrder === 'asc') {
                    return $valueA <=> $valueB;
                } else {
                    return $valueB <=> $valueA;
                }
            });

            // Paginate
            $total = count($images);
            $page = request()->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedImages = array_slice($images, $offset, $perPage);

            return [
                'images' => $paginatedImages,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get images list', [
                'error' => $e->getMessage(),
                'folder' => $folder
            ]);
            throw $e;
        }
    }

    /**
     * Get folders list
     */
    public function getFoldersList(): array
    {
        try {
            $directories = Storage::directories($this->basePath);
            $folders = [];

            foreach ($directories as $directory) {
                $folderName = basename($directory);
                $imageCount = count(Storage::files($directory));
                $totalSize = 0;

                foreach (Storage::files($directory) as $file) {
                    $totalSize += Storage::size($file);
                }

                $folders[] = [
                    'name' => $folderName,
                    'path' => $directory,
                    'image_count' => $imageCount,
                    'total_size' => $totalSize,
                    'created_at' => Storage::lastModified($directory)
                ];
            }

            return $folders;

        } catch (\Exception $e) {
            Log::error('Failed to get folders list', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create folder
     */
    public function createFolder(string $folderName): bool
    {
        try {
            $folderPath = $this->basePath . '/' . $folderName;
            
            if (Storage::exists($folderPath)) {
                return false; // Folder already exists
            }

            return Storage::makeDirectory($folderPath);

        } catch (\Exception $e) {
            Log::error('Failed to create folder', [
                'error' => $e->getMessage(),
                'folder' => $folderName
            ]);
            return false;
        }
    }

    /**
     * Delete folder
     */
    public function deleteFolder(string $folderName): bool
    {
        try {
            $folderPath = $this->basePath . '/' . $folderName;
            
            if (!Storage::exists($folderPath)) {
                return false; // Folder doesn't exist
            }

            // Delete all files in folder first
            $files = Storage::files($folderPath);
            foreach ($files as $file) {
                Storage::delete($file);
            }

            return Storage::deleteDirectory($folderPath);

        } catch (\Exception $e) {
            Log::error('Failed to delete folder', [
                'error' => $e->getMessage(),
                'folder' => $folderName
            ]);
            return false;
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStatistics(): array
    {
        try {
            $totalImages = 0;
            $totalSize = 0;
            $folders = $this->getFoldersList();

            foreach ($folders as $folder) {
                $totalImages += $folder['image_count'];
                $totalSize += $folder['total_size'];
            }

            return [
                'total_images' => $totalImages,
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'folders_count' => count($folders),
                'folders' => $folders,
                'storage_usage' => [
                    'used' => $totalSize,
                    'available' => $this->getAvailableStorage(),
                    'percentage' => $this->getStorageUsagePercentage($totalSize)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get storage statistics', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate image file
     */
    protected function validateImage(UploadedFile $image): void
    {
        if (!in_array($image->getMimeType(), $this->allowedMimes)) {
            throw new \Exception('Invalid image type. Allowed types: ' . implode(', ', $this->allowedMimes));
        }

        if ($image->getSize() > $this->maxFileSize) {
            throw new \Exception('Image size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        
        return $timestamp . '_' . $random . '.' . $extension;
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
     * Get available storage space
     */
    protected function getAvailableStorage(): int
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            return $freeSpace;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get storage usage percentage
     */
    protected function getStorageUsagePercentage(int $usedSpace): float
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            return round(($usedSpace / $totalSpace) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
