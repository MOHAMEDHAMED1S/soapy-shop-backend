<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    /**
     * Get the active home banner (for public)
     */
    public function show()
    {
        try {
            $banner = HomeBanner::active()->first();

            if (!$banner) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active banner found'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $banner,
                'message' => 'Home banner retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving home banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
