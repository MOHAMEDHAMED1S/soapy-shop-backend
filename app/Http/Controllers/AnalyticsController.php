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
            // حساب الزوار الفريدين بناءً على عدد IP addresses الفريدة
            'unique_visitors_24h' => Visit::dateRange($yesterday, $now)
                ->selectRaw('COUNT(DISTINCT ip_address) as count')
                ->value('count') ?? 0,
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
     * Get visits from specific social media platforms
     */
    public function socialVisits(Request $request): JsonResponse
    {
        $request->validate([
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:facebook,instagram,twitter,snapchat,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:platform,date,both'
        ]);

        $platforms = $request->get('platforms', ['facebook', 'instagram', 'twitter', 'snapchat', 'other']);
        $startDate = $request->get('start_date', Carbon::now()->subDays(30));
        $endDate = $request->get('end_date', Carbon::now());
        $groupBy = $request->get('group_by', 'platform');

        // Build the query for social media visits
        $query = Visit::dateRange($startDate, $endDate);

        // Filter by social media platforms using intelligent referrer filtering
        $query->where(function ($q) use ($platforms) {
            foreach ($platforms as $platform) {
                switch ($platform) {
                    case 'facebook':
                        $q->orWhere('referer_url', 'LIKE', '%facebook.com%')
                          ->orWhere('referer_url', 'LIKE', '%fb.com%')
                          ->orWhere('referer_url', 'LIKE', '%m.facebook.com%')
                          ->orWhere('referer_url', 'LIKE', '%l.facebook.com%');
                        break;
                    case 'instagram':
                        $q->orWhere('referer_url', 'LIKE', '%instagram.com%')
                          ->orWhere('referer_url', 'LIKE', '%instagr.am%');
                        break;
                    case 'twitter':
                        $q->orWhere('referer_url', 'LIKE', '%twitter.com%')
                          ->orWhere('referer_url', 'LIKE', '%t.co%')
                          ->orWhere('referer_url', 'LIKE', '%x.com%');
                        break;
                    case 'snapchat':
                        $q->orWhere('referer_url', 'LIKE', '%snapchat.com%')
                          ->orWhere('referer_url', 'LIKE', '%snap.com%');
                        break;
                    case 'other':
                        $q->orWhere('referer_type', 'other');
                        break;
                }
            }
        });

        // Group results based on the group_by parameter
        switch ($groupBy) {
            case 'date':
                $results = $query->selectRaw('DATE(visited_at) as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            
            case 'both':
                $results = $query->selectRaw('
                    DATE(visited_at) as date,
                    CASE 
                        WHEN referer_url LIKE "%facebook.com%" OR referer_url LIKE "%fb.com%" OR referer_url LIKE "%m.facebook.com%" OR referer_url LIKE "%l.facebook.com%" THEN "facebook"
                        WHEN referer_url LIKE "%instagram.com%" OR referer_url LIKE "%instagr.am%" THEN "instagram"
                        WHEN referer_url LIKE "%twitter.com%" OR referer_url LIKE "%t.co%" OR referer_url LIKE "%x.com%" THEN "twitter"
                        WHEN referer_url LIKE "%snapchat.com%" OR referer_url LIKE "%snap.com%" THEN "snapchat"
                        ELSE "other"
                    END as platform,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                ')
                    ->groupBy('date', 'platform')
                    ->orderBy('date')
                    ->orderBy('platform')
                    ->get();
                break;
            
            default: // platform
                $results = $query->selectRaw('
                    CASE 
                        WHEN referer_url LIKE "%facebook.com%" OR referer_url LIKE "%fb.com%" OR referer_url LIKE "%m.facebook.com%" OR referer_url LIKE "%l.facebook.com%" THEN "facebook"
                        WHEN referer_url LIKE "%instagram.com%" OR referer_url LIKE "%instagr.am%" THEN "instagram"
                        WHEN referer_url LIKE "%twitter.com%" OR referer_url LIKE "%t.co%" OR referer_url LIKE "%x.com%" THEN "twitter"
                        WHEN referer_url LIKE "%snapchat.com%" OR referer_url LIKE "%snap.com%" THEN "snapchat"
                        ELSE "other"
                    END as platform,
                    COUNT(*) as visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                ')
                    ->groupBy('platform')
                    ->orderByDesc('visits')
                    ->get();
                break;
        }

        // Get summary statistics
        $totalVisits = Visit::dateRange($startDate, $endDate)
            ->where(function ($q) use ($platforms) {
                foreach ($platforms as $platform) {
                    switch ($platform) {
                        case 'facebook':
                            $q->orWhere('referer_url', 'LIKE', '%facebook.com%')
                              ->orWhere('referer_url', 'LIKE', '%fb.com%')
                              ->orWhere('referer_url', 'LIKE', '%m.facebook.com%')
                              ->orWhere('referer_url', 'LIKE', '%l.facebook.com%');
                            break;
                        case 'instagram':
                            $q->orWhere('referer_url', 'LIKE', '%instagram.com%')
                              ->orWhere('referer_url', 'LIKE', '%instagr.am%');
                            break;
                        case 'twitter':
                            $q->orWhere('referer_url', 'LIKE', '%twitter.com%')
                              ->orWhere('referer_url', 'LIKE', '%t.co%')
                              ->orWhere('referer_url', 'LIKE', '%x.com%');
                            break;
                        case 'snapchat':
                            $q->orWhere('referer_url', 'LIKE', '%snapchat.com%')
                              ->orWhere('referer_url', 'LIKE', '%snap.com%');
                            break;
                        case 'other':
                            $q->orWhere('referer_type', 'other');
                            break;
                    }
                }
            })
            ->count();
            
        // حساب الزوار الفريدين بشكل صحيح باستخدام COUNT(DISTINCT)
        $uniqueVisitors = Visit::dateRange($startDate, $endDate)
            ->where(function ($q) use ($platforms) {
                foreach ($platforms as $platform) {
                    switch ($platform) {
                        case 'facebook':
                            $q->orWhere('referer_url', 'LIKE', '%facebook.com%')
                              ->orWhere('referer_url', 'LIKE', '%fb.com%')
                              ->orWhere('referer_url', 'LIKE', '%m.facebook.com%')
                              ->orWhere('referer_url', 'LIKE', '%l.facebook.com%');
                            break;
                        case 'instagram':
                            $q->orWhere('referer_url', 'LIKE', '%instagram.com%')
                              ->orWhere('referer_url', 'LIKE', '%instagr.am%');
                            break;
                        case 'twitter':
                            $q->orWhere('referer_url', 'LIKE', '%twitter.com%')
                              ->orWhere('referer_url', 'LIKE', '%t.co%')
                              ->orWhere('referer_url', 'LIKE', '%x.com%');
                            break;
                        case 'snapchat':
                            $q->orWhere('referer_url', 'LIKE', '%snapchat.com%')
                              ->orWhere('referer_url', 'LIKE', '%snap.com%');
                            break;
                        case 'other':
                            $q->orWhere('referer_type', 'other');
                            break;
                    }
                }
            })
            ->selectRaw('COUNT(DISTINCT ip_address) as count')
            ->value('count') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'summary' => [
                    'total_visits' => $totalVisits,
                    'unique_visitors' => $uniqueVisitors,
                    'date_range' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ],
                    'platforms_filtered' => $platforms
                ]
            ]
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
