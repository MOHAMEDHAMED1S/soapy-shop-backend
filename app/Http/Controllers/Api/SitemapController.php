<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate sitemap.xml for the website
     */
    public function index(Request $request)
    {
        try {
            // Get all available products with their categories
            $products = Product::with('category')
                ->where('is_available', true)
                ->orderBy('updated_at', 'desc')
                ->get();

            // Get all categories
            $categories = Category::orderBy('updated_at', 'desc')->get();

            // Base URL for the website
            $baseUrl = 'https://soapy-bubbles.com';

            // Generate XML content
            $xml = $this->generateSitemapXml($products, $categories, $baseUrl);

            // Return XML response with proper headers
            return response($xml, 200)
                ->header('Content-Type', 'application/xml; charset=utf-8')
                ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating sitemap',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate the XML content for sitemap
     */
    private function generateSitemapXml($products, $categories, $baseUrl)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Add homepage
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($baseUrl) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . now()->toISOString() . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";

        // Add static pages
        $staticPages = [
            ['url' => '/about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/products', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/categories', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        foreach ($staticPages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . $page['url']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . now()->toISOString() . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Add category pages
        foreach ($categories as $category) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . '/category/' . $category->slug) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $category->updated_at->toISOString() . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Add product pages
        foreach ($products as $product) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($baseUrl . '/product/' . $product->slug) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $product->updated_at->toISOString() . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.9</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate sitemap index for large websites (future use)
     */
    public function sitemapIndex(Request $request)
    {
        try {
            $baseUrl = 'https://soapy-bubbles.com';
            $apiUrl = 'https://api.soapy-bubbles.com';

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // Main sitemap
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($apiUrl . '/api/v1/sitemap.xml') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . now()->toISOString() . '</lastmod>' . "\n";
            $xml .= '  </sitemap>' . "\n";

            // Products sitemap (if needed for large product catalogs)
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($apiUrl . '/api/v1/sitemap/products.xml') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . now()->toISOString() . '</lastmod>' . "\n";
            $xml .= '  </sitemap>' . "\n";

            $xml .= '</sitemapindex>';

            return response($xml, 200)
                ->header('Content-Type', 'application/xml; charset=utf-8')
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating sitemap index',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate products-only sitemap
     */
    public function productsSitemap(Request $request)
    {
        try {
            // Get all available products
            $products = Product::where('is_available', true)
                ->orderBy('updated_at', 'desc')
                ->get();

            $baseUrl = 'https://soapy-bubbles.com';

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // Add product pages only
            foreach ($products as $product) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($baseUrl . '/product/' . $product->slug) . '</loc>' . "\n";
                $xml .= '    <lastmod>' . $product->updated_at->toISOString() . '</lastmod>' . "\n";
                $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                $xml .= '    <priority>0.9</priority>' . "\n";
                $xml .= '  </url>' . "\n";
            }

            $xml .= '</urlset>';

            return response($xml, 200)
                ->header('Content-Type', 'application/xml; charset=utf-8')
                ->header('Cache-Control', 'public, max-age=3600');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating products sitemap',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}