<?php

namespace App\Http\Controllers;

use App\Services\VisitTrackingService;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $visitTrackingService;

    public function __construct(VisitTrackingService $visitTrackingService)
    {
        $this->visitTrackingService = $visitTrackingService;
    }

    /**
     * Get visit statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'referer_type' => 'nullable|in:facebook,instagram,twitter,other,direct'
        ]);

        $filters = [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'referer_type' => $request->get('referer_type')
        ];

        $statistics = $this->visitTrackingService->getStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get visits by referer type
     */
    public function visitsByRefererType(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $visits = Visit::getVisitsByRefererType($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $visits
        ]);
    }

    /**
     * Get top referer domains
     */
    public function topRefererDomains(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());
        $limit = $request->get('limit', 10);

        $domains = Visit::getTopRefererDomains($limit, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $domains
        ]);
    }

    /**
     * Get daily visits
     */
    public function dailyVisits(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $visits = Visit::getDailyVisits($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $visits
        ]);
    }

    /**
     * Get popular pages
     */
    public function popularPages(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());
        $limit = $request->get('limit', 10);

        $pages = Visit::getPopularPages($limit, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $pages
        ]);
    }

    /**
     * Get real-time statistics (last 24 hours)
     */
    public function realTime(): JsonResponse
    {
        $yesterday = Carbon::now()->subDay();
        $now = Carbon::now();

        $statistics = [
            'total_visits_24h' => Visit::dateRange($yesterday, $now)->count(),
            'unique_visitors_24h' => Visit::dateRange($yesterday, $now)->uniqueVisitors()->count(),
            'visits_by_referer_type' => Visit::getVisitsByRefererType($yesterday, $now),
            'hourly_visits' => Visit::selectRaw('HOUR(visited_at) as hour, COUNT(*) as visits')
                ->dateRange($yesterday, $now)
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
            'top_pages_24h' => Visit::getPopularPages(5, $yesterday, $now)
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get device and browser statistics
     */
    public function deviceStats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());

        $deviceStats = Visit::selectRaw('device_type, COUNT(*) as visits')
            ->dateRange($startDate, $endDate)
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->get();

        $browserStats = Visit::selectRaw('browser, COUNT(*) as visits')
            ->dateRange($startDate, $endDate)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $osStats = Visit::selectRaw('os, COUNT(*) as visits')
            ->dateRange($startDate, $endDate)
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'devices' => $deviceStats,
                'browsers' => $browserStats,
                'operating_systems' => $osStats
            ]
        ]);
    }
}
