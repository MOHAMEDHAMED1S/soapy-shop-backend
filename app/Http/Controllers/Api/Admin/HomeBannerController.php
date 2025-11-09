<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class HomeBannerController extends Controller
{
    /**
     * Get the home banner (for admin)
     */
    public function show()
    {
        try {
            $banner = HomeBanner::first();

            if (!$banner) {
                // Create default banner if none exists
                $banner = HomeBanner::create([
                    'title' => 'عروض خاصة',
                    'subtitle' => 'استمتع بأفضل العروض والخصومات',
                    'is_active' => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $banner,
                'message' => 'Home banner retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving home banner: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البانر',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }

    /**
     * Update the home banner
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $banner = HomeBanner::first();

            if (!$banner) {
                $banner = HomeBanner::create($request->only(['title', 'subtitle', 'is_active']));
            } else {
                $banner->update($request->only(['title', 'subtitle', 'is_active']));
            }

            return response()->json([
                'success' => true,
                'data' => $banner,
                'message' => 'Home banner updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating home banner: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البانر',
                'error_code' => 'UPDATE_ERROR'
            ], 500);
        }
    }
}
