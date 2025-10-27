<?php

namespace App\Services;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VisitTrackingService
{
    /**
     * Track a visit from the request
     */
    public function trackVisit(Request $request): Visit
    {
        $visitData = $this->extractVisitData($request);
        
        // Check if this is a unique visitor
        $visitData['is_unique'] = $this->isUniqueVisitor(
            $visitData['ip_address'],
            $visitData['session_id']
        );

        return Visit::create($visitData);
    }

    /**
     * Extract visit data from request
     */
    private function extractVisitData(Request $request): array
    {
        $refererUrl = $request->header('referer') ?: $request->get('referer') ?: $request->get('referrer_url');
        $refererData = $this->analyzeReferer($refererUrl);
        
        return [
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $request->header('User-Agent'),
            'referer_url' => $refererUrl,
            'referer_domain' => $refererData['domain'],
            'referer_type' => $refererData['type'],
            'page_url' => $request->get('page_url') ?: $request->get('url'),
            'page_title' => $request->get('page_title'),
            'session_id' => $request->get('session_id') ?: ($request->hasSession() ? $request->session()->getId() : 'no-session-' . uniqid()),
            'country' => $this->getCountryFromIp($this->getClientIp($request)),
            'city' => $this->getCityFromIp($this->getClientIp($request)),
            'device_type' => $this->getDeviceType($request->header('User-Agent')),
            'browser' => $this->getBrowser($request->header('User-Agent')),
            'os' => $this->getOperatingSystem($request->header('User-Agent')),
            'visited_at' => Carbon::now()
        ];
    }

    /**
     * Analyze referer URL to determine type and domain
     */
    private function analyzeReferer(?string $refererUrl): array
    {
        if (empty($refererUrl)) {
            return ['domain' => null, 'type' => 'direct'];
        }

        $domain = $this->extractDomain($refererUrl);
        $type = $this->determineRefererType($domain);

        return [
            'domain' => $domain,
            'type' => $type
        ];
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);
        
        // Remove www. prefix
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Determine referer type based on domain using intelligent pattern matching
     */
    private function determineRefererType(?string $domain): string
    {
        if (empty($domain)) {
            return 'direct';
        }

        // Convert to lowercase for case-insensitive matching
        $domain = strtolower($domain);

        // Define intelligent patterns for each platform
        $platformPatterns = [
            'facebook' => [
                'exact_domains' => ['facebook.com', 'fb.com', 'm.facebook.com', 'www.facebook.com', 'mobile.facebook.com', 'touch.facebook.com', 'mbasic.facebook.com'],
                'keywords' => ['faceb', 'fb.', '.fb'],
                'contains' => ['facebook']
            ],
            'instagram' => [
                'exact_domains' => ['instagram.com', 'instagr.am', 'www.instagram.com', 'm.instagram.com'],
                'keywords' => ['insta', 'instagr'],
                'contains' => ['instagram']
            ],
            'twitter' => [
                'exact_domains' => ['twitter.com', 't.co', 'x.com', 'mobile.twitter.com', 'www.twitter.com', 'www.x.com', 'm.twitter.com'],
                'keywords' => ['twitt', 'twitter', 'x.com'],
                'contains' => ['twitter']
            ],
            'snapchat' => [
                'exact_domains' => ['snapchat.com', 'www.snapchat.com', 'm.snapchat.com', 'snap.com'],
                'keywords' => ['snap', 'snapch'],
                'contains' => ['snapchat', 'snap']
            ]
        ];

        // Check each platform using intelligent matching
        foreach ($platformPatterns as $platform => $patterns) {
            // 1. Check exact domain matches
            if (in_array($domain, $patterns['exact_domains'])) {
                return $platform;
            }

            // 2. Check if domain ends with any of the exact domains (for subdomains)
            foreach ($patterns['exact_domains'] as $exactDomain) {
                if (str_ends_with($domain, '.' . $exactDomain)) {
                    return $platform;
                }
            }

            // 3. Check keyword patterns (intelligent matching)
            foreach ($patterns['keywords'] as $keyword) {
                if (str_contains($domain, $keyword)) {
                    return $platform;
                }
            }

            // 4. Check contains patterns (broader matching)
            foreach ($patterns['contains'] as $containsPattern) {
                if (str_contains($domain, $containsPattern)) {
                    return $platform;
                }
            }
        }

        // Check for search engines
        $searchEngines = ['google.com', 'bing.com', 'yahoo.com', 'duckduckgo.com', 'yandex.com', 'baidu.com'];
        foreach ($searchEngines as $searchDomain) {
            if ($domain === $searchDomain || str_ends_with($domain, '.' . $searchDomain)) {
                return 'search';
            }
        }

        return 'other';
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to request IP or default
        return $request->ip() ?: '127.0.0.1';
    }

    /**
     * Check if this is a unique visitor
     */
    private function isUniqueVisitor(string $ipAddress, ?string $sessionId): bool
    {
        $cacheKey = "unique_visitor_{$ipAddress}_{$sessionId}";
        
        if (Cache::has($cacheKey)) {
            return false;
        }

        // Cache for 24 hours
        Cache::put($cacheKey, true, 60 * 24);
        
        return true;
    }

    /**
     * Get device type from user agent
     */
    private function getDeviceType(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            if (preg_match('/ipad|tablet/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get browser from user agent
     */
    private function getBrowser(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $browsers = [
            'Chrome' => '/chrome/i',
            'Firefox' => '/firefox/i',
            'Safari' => '/safari/i',
            'Edge' => '/edge/i',
            'Opera' => '/opera/i',
            'Internet Explorer' => '/msie|trident/i'
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Other';
    }

    /**
     * Get operating system from user agent
     */
    private function getOperatingSystem(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $systems = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os x/i',
            'Linux' => '/linux/i',
            'Android' => '/android/i',
            'iOS' => '/iphone|ipad|ipod/i'
        ];

        foreach ($systems as $system => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $system;
            }
        }

        return 'Other';
    }

    /**
     * Get country from IP (placeholder - would need GeoIP service)
     */
    private function getCountryFromIp(string $ip): ?string
    {
        // This would typically use a GeoIP service like MaxMind
        // For now, return null
        return null;
    }

    /**
     * Get city from IP (placeholder - would need GeoIP service)
     */
    private function getCityFromIp(string $ip): ?string
    {
        // This would typically use a GeoIP service like MaxMind
        // For now, return null
        return null;
    }

    /**
     * Get visit statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30);
        $endDate = $filters['end_date'] ?? Carbon::now();

        return [
            'total_visits' => Visit::dateRange($startDate, $endDate)->count(),
            // حساب الزوار الفريدين بناءً على عدد IP addresses الفريدة
            'unique_visitors' => Visit::dateRange($startDate, $endDate)
                ->selectRaw('COUNT(DISTINCT ip_address) as count')
                ->value('count') ?? 0,
            'visits_by_referer_type' => Visit::getVisitsByRefererType($startDate, $endDate),
            'top_referer_domains' => Visit::getTopRefererDomains(10, $startDate, $endDate),
            'daily_visits' => Visit::getDailyVisits($startDate, $endDate),
            'popular_pages' => Visit::getPopularPages(10, $startDate, $endDate),
            'date_range' => [
                'start' => Carbon::parse($startDate)->toDateString(),
                'end' => Carbon::parse($endDate)->toDateString()
            ]
        ];
    }
}