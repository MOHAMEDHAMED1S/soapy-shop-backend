<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the image processing system.
    | You can configure image settings, storage, and optimization here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Image Storage
    |--------------------------------------------------------------------------
    |
    | Configure image storage settings.
    |
    */

    'storage' => [
        'disk' => env('IMAGE_STORAGE_DISK', 'public'),
        'base_path' => env('IMAGE_BASE_PATH', 'images'),
        'url' => env('IMAGE_URL', '/storage/images'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configure image processing settings.
    |
    */

    'processing' => [
        'driver' => env('IMAGE_DRIVER', 'gd'), // gd or imagick
        'quality' => env('IMAGE_QUALITY', 85),
        'max_width' => env('IMAGE_MAX_WIDTH', 4000),
        'max_height' => env('IMAGE_MAX_HEIGHT', 4000),
        'max_file_size' => env('IMAGE_MAX_FILE_SIZE', 10240), // KB
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/jpg',
            'image/gif',
            'image/webp',
        ],
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    |
    | Configure image optimization settings.
    |
    */

    'optimization' => [
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
        'quality' => env('IMAGE_OPTIMIZATION_QUALITY', 85),
        'progressive' => env('IMAGE_PROGRESSIVE', true),
        'strip_metadata' => env('IMAGE_STRIP_METADATA', true),
        'webp_conversion' => env('IMAGE_WEBP_CONVERSION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Resizing
    |--------------------------------------------------------------------------
    |
    | Configure image resizing settings.
    |
    */

    'resizing' => [
        'enabled' => env('IMAGE_RESIZING_ENABLED', true),
        'maintain_aspect_ratio' => env('IMAGE_MAINTAIN_ASPECT_RATIO', true),
        'upsize' => env('IMAGE_UP_SIZE', false),
        'crop' => env('IMAGE_CROP', false),
        'default_sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Watermarking
    |--------------------------------------------------------------------------
    |
    | Configure image watermarking settings.
    |
    */

    'watermarking' => [
        'enabled' => env('IMAGE_WATERMARKING_ENABLED', false),
        'image_path' => env('IMAGE_WATERMARK_PATH', 'watermarks/logo.png'),
        'position' => env('IMAGE_WATERMARK_POSITION', 'bottom-right'),
        'opacity' => env('IMAGE_WATERMARK_OPACITY', 50),
        'size' => env('IMAGE_WATERMARK_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Folders
    |--------------------------------------------------------------------------
    |
    | Configure default image folders.
    |
    */

    'folders' => [
        'products' => 'products',
        'categories' => 'categories',
        'users' => 'users',
        'general' => 'general',
        'temp' => 'temp',
        'watermarks' => 'watermarks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Cache
    |--------------------------------------------------------------------------
    |
    | Configure image caching settings.
    |
    */

    'cache' => [
        'enabled' => env('IMAGE_CACHE_ENABLED', true),
        'ttl' => env('IMAGE_CACHE_TTL', 31536000), // 1 year in seconds
        'prefix' => env('IMAGE_CACHE_PREFIX', 'image_cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Security
    |--------------------------------------------------------------------------
    |
    | Configure image security settings.
    |
    */

    'security' => [
        'scan_for_malware' => env('IMAGE_SCAN_MALWARE', false),
        'max_dimensions' => [
            'width' => env('IMAGE_MAX_DIMENSIONS_WIDTH', 4000),
            'height' => env('IMAGE_MAX_DIMENSIONS_HEIGHT', 4000),
        ],
        'min_dimensions' => [
            'width' => env('IMAGE_MIN_DIMENSIONS_WIDTH', 100),
            'height' => env('IMAGE_MIN_DIMENSIONS_HEIGHT', 100),
        ],
        'allowed_colorspaces' => [
            'RGB',
            'CMYK',
            'Grayscale',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Formats
    |--------------------------------------------------------------------------
    |
    | Configure supported image formats and their settings.
    |
    */

    'formats' => [
        'jpeg' => [
            'quality' => 85,
            'progressive' => true,
            'optimize' => true,
        ],
        'png' => [
            'compression' => 6,
            'optimize' => true,
        ],
        'webp' => [
            'quality' => 80,
            'lossless' => false,
        ],
        'gif' => [
            'optimize' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Cleanup
    |--------------------------------------------------------------------------
    |
    | Configure image cleanup settings.
    |
    */

    'cleanup' => [
        'enabled' => env('IMAGE_CLEANUP_ENABLED', true),
        'temp_files_ttl' => env('IMAGE_TEMP_FILES_TTL', 3600), // 1 hour
        'orphaned_files_ttl' => env('IMAGE_ORPHANED_FILES_TTL', 86400), // 1 day
        'cleanup_schedule' => env('IMAGE_CLEANUP_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image CDN
    |--------------------------------------------------------------------------
    |
    | Configure CDN settings for images.
    |
    */

    'cdn' => [
        'enabled' => env('IMAGE_CDN_ENABLED', false),
        'url' => env('IMAGE_CDN_URL'),
        'cache_ttl' => env('IMAGE_CDN_CACHE_TTL', 31536000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Backup
    |--------------------------------------------------------------------------
    |
    | Configure image backup settings.
    |
    */

    'backup' => [
        'enabled' => env('IMAGE_BACKUP_ENABLED', false),
        'disk' => env('IMAGE_BACKUP_DISK', 's3'),
        'path' => env('IMAGE_BACKUP_PATH', 'backups/images'),
        'schedule' => env('IMAGE_BACKUP_SCHEDULE', 'daily'),
    ],
];
