<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeMedia;
use Illuminate\Http\Request;

class HomeMediaController extends Controller
{
    /**
     * Get active home media for public display
     */
    public function index()
    {
        try {
            $heroSlides = HomeMedia::active()
                ->heroSlides()
                ->ordered()
                ->with('product:id,title,slug')
                ->get(['id', 'media_url', 'sort_order', 'link_type', 'product_id', 'link_url']);

            $video = HomeMedia::active()
                ->video()
                ->first(['id', 'media_url', 'title_ar', 'title_en', 'subtitle_ar', 'subtitle_en']);

            return response()->json([
                'success' => true,
                'data' => [
                    'hero_slides' => $heroSlides,
                    'video' => $video
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching home media'
            ], 500);
        }
    }
}
