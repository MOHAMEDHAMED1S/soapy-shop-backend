<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HomeMediaController extends Controller
{
    /**
     * Get all home media
     */
    public function index(Request $request)
    {
        try {
            $query = HomeMedia::with('product:id,title,slug')->ordered();
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            $media = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $media
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching home media', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching home media'
            ], 500);
        }
    }

    /**
     * Store new home media
     */
    public function store(Request $request)
    {
        try {
            // Convert string 'true'/'false' to boolean for FormData compatibility
            $data = $request->all();
            if (isset($data['is_active'])) {
                $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            $validator = Validator::make($data, [
                'type' => 'required|in:hero_slide,video',
                'media_url' => 'required_without:media_file|string|nullable',
                'media_file' => 'required_without:media_url|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm|max:51200',
                'is_active' => 'boolean',
                'title_ar' => 'nullable|string|max:255',
                'title_en' => 'nullable|string|max:255',
                'subtitle_ar' => 'nullable|string|max:255',
                'subtitle_en' => 'nullable|string|max:255',
                'link_type' => 'nullable|in:none,product,custom_url',
                'product_id' => 'nullable|exists:products,id',
                'link_url' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build data array with converted is_active
            $createData = [
                'type' => $data['type'],
                'is_active' => $data['is_active'] ?? true,
                'title_ar' => $data['title_ar'] ?? null,
                'title_en' => $data['title_en'] ?? null,
                'subtitle_ar' => $data['subtitle_ar'] ?? null,
                'subtitle_en' => $data['subtitle_en'] ?? null,
                'link_type' => $data['link_type'] ?? 'none',
                'product_id' => ($data['link_type'] ?? 'none') === 'product' ? ($data['product_id'] ?? null) : null,
                'link_url' => ($data['link_type'] ?? 'none') === 'custom_url' ? ($data['link_url'] ?? null) : null,
            ];

            // Handle file upload
            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $folder = $data['type'] === 'video' ? 'home-videos' : 'home-slides';
                $path = $file->store($folder, 'public');
                $createData['media_url'] = config('app.url') . '/storage/' . $path;
            } else {
                $createData['media_url'] = $data['media_url'] ?? '';
            }

            // Set sort order (last position)
            $maxOrder = HomeMedia::where('type', $data['type'])->max('sort_order') ?? 0;
            $createData['sort_order'] = $maxOrder + 1;

            $media = HomeMedia::create($createData);

            Log::info('Home media created', ['id' => $media->id, 'type' => $media->type]);

            return response()->json([
                'success' => true,
                'message' => 'Home media created successfully',
                'data' => $media
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating home media', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating home media'
            ], 500);
        }
    }

    /**
     * Get single home media
     */
    public function show($id)
    {
        try {
            $media = HomeMedia::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $media
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Home media not found'
            ], 404);
        }
    }

    /**
     * Update home media
     */
    public function update(Request $request, $id)
    {
        try {
            $media = HomeMedia::findOrFail($id);

            // Convert string 'true'/'false' to boolean for FormData compatibility
            $inputData = $request->all();
            if (isset($inputData['is_active'])) {
                $inputData['is_active'] = filter_var($inputData['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            $validator = Validator::make($inputData, [
                'type' => 'in:hero_slide,video',
                'media_url' => 'nullable|string',
                'media_file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm|max:51200',
                'is_active' => 'boolean',
                'title_ar' => 'nullable|string|max:255',
                'title_en' => 'nullable|string|max:255',
                'subtitle_ar' => 'nullable|string|max:255',
                'subtitle_en' => 'nullable|string|max:255',
                'link_type' => 'nullable|in:none,product,custom_url',
                'product_id' => 'nullable|exists:products,id',
                'link_url' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = [];
            if (isset($inputData['type'])) $data['type'] = $inputData['type'];
            if (isset($inputData['is_active'])) $data['is_active'] = $inputData['is_active'];
            if (isset($inputData['title_ar'])) $data['title_ar'] = $inputData['title_ar'];
            if (isset($inputData['title_en'])) $data['title_en'] = $inputData['title_en'];
            if (isset($inputData['subtitle_ar'])) $data['subtitle_ar'] = $inputData['subtitle_ar'];
            if (isset($inputData['subtitle_en'])) $data['subtitle_en'] = $inputData['subtitle_en'];
            
            // Handle link fields
            if (isset($inputData['link_type'])) {
                $data['link_type'] = $inputData['link_type'];
                $data['product_id'] = $inputData['link_type'] === 'product' ? ($inputData['product_id'] ?? null) : null;
                $data['link_url'] = $inputData['link_type'] === 'custom_url' ? ($inputData['link_url'] ?? null) : null;
            }

            // Handle file upload
            if ($request->hasFile('media_file')) {
                $file = $request->file('media_file');
                $type = $inputData['type'] ?? $media->type;
                $folder = $type === 'video' ? 'home-videos' : 'home-slides';
                $path = $file->store($folder, 'public');
                $data['media_url'] = config('app.url') . '/storage/' . $path;
            } elseif (isset($inputData['media_url'])) {
                $data['media_url'] = $inputData['media_url'];
            }

            $media->update($data);

            Log::info('Home media updated', ['id' => $media->id]);

            return response()->json([
                'success' => true,
                'message' => 'Home media updated successfully',
                'data' => $media->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating home media', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating home media'
            ], 500);
        }
    }

    /**
     * Delete home media
     */
    public function destroy($id)
    {
        try {
            $media = HomeMedia::findOrFail($id);
            $media->delete();

            Log::info('Home media deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Home media deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting home media', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting home media'
            ], 500);
        }
    }

    /**
     * Reorder home media
     */
    public function reorder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.id' => 'required|exists:home_media,id',
                'items.*.sort_order' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            foreach ($request->items as $item) {
                HomeMedia::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }

            Log::info('Home media reordered');

            return response()->json([
                'success' => true,
                'message' => 'Home media reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error reordering home media', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error reordering home media'
            ], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        try {
            $media = HomeMedia::findOrFail($id);
            $media->update(['is_active' => !$media->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Active status toggled',
                'data' => $media->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling status'
            ], 500);
        }
    }
}
