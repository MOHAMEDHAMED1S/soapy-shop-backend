<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Visit extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'referer_url',
        'referer_domain',
        'referer_type',
        'page_url',
        'page_title',
        'session_id',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
        'is_unique',
        'visited_at'
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'is_unique' => 'boolean'
    ];

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('visited_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope for filtering by referer type
     */
    public function scopeByRefererType(Builder $query, string $type): Builder
    {
        return $query->where('referer_type', $type);
    }

    /**
     * Scope for unique visitors only
     */
    public function scopeUniqueVisitors(Builder $query): Builder
    {
        return $query->where('is_unique', true);
    }

    /**
     * Scope for today's visits
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('visited_at', Carbon::today());
    }

    /**
     * Scope for this week's visits
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('visited_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's visits
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('visited_at', Carbon::now()->month)
                    ->whereYear('visited_at', Carbon::now()->year);
    }

    /**
     * Get visits grouped by referer type
     */
    public static function getVisitsByRefererType($startDate = null, $endDate = null)
    {
        $query = self::selectRaw('referer_type, COUNT(*) as count')
                     ->groupBy('referer_type');

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->get()->pluck('count', 'referer_type');
    }

    /**
     * Get top referer domains
     */
    public static function getTopRefererDomains($limit = 10, $startDate = null, $endDate = null)
    {
        $query = self::selectRaw('referer_domain, COUNT(*) as count')
                     ->whereNotNull('referer_domain')
                     ->groupBy('referer_domain')
                     ->orderByDesc('count')
                     ->limit($limit);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->get();
    }

    /**
     * Get daily visits for a date range
     */
    public static function getDailyVisits($startDate, $endDate)
    {
        return self::selectRaw('DATE(visited_at) as date, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors')
                   ->dateRange($startDate, $endDate)
                   ->groupByRaw('DATE(visited_at)')
                   ->orderBy('date')
                   ->get();
    }

    /**
     * Get popular pages
     */
    public static function getPopularPages($limit = 10, $startDate = null, $endDate = null)
    {
        $query = self::selectRaw('page_url, page_title, COUNT(*) as visits')
                     ->groupBy('page_url', 'page_title')
                     ->orderByDesc('visits')
                     ->limit($limit);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->get();
    }
}
