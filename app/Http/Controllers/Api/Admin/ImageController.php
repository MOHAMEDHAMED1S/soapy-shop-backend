<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload single image - Simple version
     */
    public function upload(Request $request)
    {
        try {
            // Simple validation
            $validator = Validator::make($request->all(), [
                'image' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'folder' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $image = $request->file('image');
            $folder = $request->get('folder', 'general');
            
            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = 'images/' . $folder . '/' . $filename;
            
            // Store file directly
            Storage::disk('public')->put($path, file_get_contents($image));
            
            // Get file info
            $fileSize = Storage::disk('public')->size($path);
            $fileUrl = Storage::disk('public')->url($path);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $fileUrl,
                    'path' => $path,
                    'filename' => $filename,
                    'size' => $fileSize,
                    'mime_type' => $image->getMimeType(),
                    'original_name' => $image->getClientOriginalName(),
                    'folder' => $folder,
                    'uploaded_at' => now()->toISOString()
                ],
                'message' => 'Image uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Image upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images - Simple version
     */
    public function uploadMultiple(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'folder' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $images = $request->file('images');
            $folder = $request->get('folder', 'general');
            
            $uploaded = [];
            $errors = [];

            foreach ($images as $index => $image) {
                try {
                    // Generate unique filename
                    $filename = time() . '_' . $index . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $path = 'images/' . $folder . '/' . $filename;
                    
                    // Store file directly
                    Storage::disk('public')->put($path, file_get_contents($image));
                    
                    // Get file info
                    $fileSize = Storage::disk('public')->size($path);
                    $fileUrl = Storage::disk('public')->url($path);
                    
                    $uploaded[] = [
                        'url' => $fileUrl,
                        'path' => $path,
                        'filename' => $filename,
                        'size' => $fileSize,
                        'mime_type' => $image->getMimeType(),
                        'original_name' => $image->getClientOriginalName(),
                        'folder' => $folder,
                        'uploaded_at' => now()->toISOString()
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'filename' => $image->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'uploaded' => $uploaded,
                    'errors' => $errors,
                    'total_uploaded' => count($uploaded),
                    'total_failed' => count($errors)
                ],
                'message' => "Successfully uploaded " . count($uploaded) . " images"
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Multiple images upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error uploading images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image by path
     */
    public function show(Request $request, $path)
    {
        try {
            $decodedPath = base64_decode($path);
            $imagePath = $this->imageService->getImagePath($decodedPath);

            if (!$imagePath || !Storage::exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $imageInfo = $this->imageService->getImageInfo($imagePath);

            return response()->json([
                'success' => true,
                'data' => $imageInfo,
                'message' => 'Image retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image file (serve image)
     */
    public function serve(Request $request, $path)
    {
        try {
            $decodedPath = base64_decode($path);
            $imagePath = $this->imageService->getImagePath($decodedPath);

            if (!$imagePath || !Storage::exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $mimeType = Storage::mimeType($imagePath);
            $content = Storage::get($imagePath);

            return response($content, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000'); // 1 year cache

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error serving image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resize image
     */
    public function resize(Request $request, $path)
    {
        try {
            $validator = Validator::make($request->all(), [
                'width' => 'required|integer|min:100|max:4000',
                'height' => 'required|integer|min:100|max:4000',
                'quality' => 'nullable|integer|min:10|max:100',
                'crop' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $decodedPath = base64_decode($path);
            $imagePath = $this->imageService->getImagePath($decodedPath);

            if (!$imagePath || !Storage::exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $width = $request->get('width');
            $height = $request->get('height');
            $quality = $request->get('quality', 85);
            $crop = $request->get('crop', false);

            $result = $this->imageService->resizeImage(
                $imagePath,
                $width,
                $height,
                $quality,
                $crop
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Image resized successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resizing image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image
     */
    public function destroy(Request $request, $path)
    {
        try {
            $decodedPath = base64_decode($path);
            $imagePath = $this->imageService->getImagePath($decodedPath);

            if (!$imagePath || !Storage::exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found'
                ], 404);
            }

            $deleted = $this->imageService->deleteImage($imagePath);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete image'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get images list
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'folder' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'sort_by' => 'nullable|in:name,size,created_at',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folder = $request->get('folder', '');
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $images = $this->imageService->getImagesList(
                $folder,
                $perPage,
                $search,
                $sortBy,
                $sortOrder
            );

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Images retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get folders list
     */
    public function folders()
    {
        try {
            $folders = $this->imageService->getFoldersList();

            return response()->json([
                'success' => true,
                'data' => $folders,
                'message' => 'Folders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving folders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create folder
     */
    public function createFolder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folderName = $request->get('name');
            $result = $this->imageService->createFolder($folderName);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => ['folder' => $folderName],
                    'message' => 'Folder created successfully'
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create folder'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating folder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete folder
     */
    public function deleteFolder(Request $request, $folderName)
    {
        try {
            $result = $this->imageService->deleteFolder($folderName);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Folder deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete folder'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting folder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->imageService->getStorageStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Storage statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving storage statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
