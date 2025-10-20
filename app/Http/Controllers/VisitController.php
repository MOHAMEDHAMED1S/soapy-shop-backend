<?php

namespace App\Http\Controllers;

use App\Services\VisitTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    protected $visitTrackingService;

    public function __construct(VisitTrackingService $visitTrackingService)
    {
        $this->visitTrackingService = $visitTrackingService;
    }

    /**
     * Track a visit
     */
    public function track(Request $request): JsonResponse
    {
        try {
            $visit = $this->visitTrackingService->trackVisit($request);
            
            return response()->json([
                'success' => true,
                'message' => 'Visit tracked successfully',
                'data' => [
                    'visit_id' => $visit->id,
                    'is_unique' => $visit->is_unique,
                    'referer_type' => $visit->referer_type,
                    'page_url' => $visit->page_url,
                    'ip_address' => $visit->ip_address
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track visit',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Track visit via pixel (for image-based tracking)
     */
    public function pixel(Request $request)
    {
        try {
            $this->visitTrackingService->trackVisit($request);
        } catch (\Exception $e) {
            // Silently fail for pixel tracking
        }

        // Return a 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel)
            ->header('Content-Type', 'image/gif')
            ->header('Content-Length', strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
