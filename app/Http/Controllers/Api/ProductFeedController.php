<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductFeedController extends Controller
{
    /**
     * Generate RSS feed for products
     */
    public function rss(Request $request)
    {
        try {
            // Get all available products with category
            $products = Product::with('category')
                ->where('is_available', true)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get site information from config
            $siteName = config('app.name', 'Soapy Bubbles');
            $siteUrl = config('app.url', 'https://api.soapy-bubbles.com');
            $feedUrl = $siteUrl . '/api/v1/products/feed';
            
            // Override with production URL if in production
            if (config('app.env') === 'production') {
                $siteUrl = 'https://soapy-bubbles.com';
                $feedUrl = 'api.' . $siteUrl . '/api/v1/products/feed';
            }

            // Generate XML content
            $xml = $this->generateRssXml($products, $siteName, $siteUrl, $feedUrl);

            return response($xml, 200)
                ->header('Content-Type', 'text/xml; charset=utf-8')
                ->header('Content-Disposition', 'inline')
                ->header('Cache-Control', 'no-cache');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating RSS feed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate RSS XML content
     */
    private function generateRssXml($products, $siteName, $siteUrl, $feedUrl)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '    <description>Product Feed for Facebook</description>' . "\n";
        $xml .= '    <link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        $xml .= '    <atom:link href="' . htmlspecialchars($feedUrl) . '" rel="self" type="application/rss+xml" />' . "\n";
        $xml .= '' . "\n";

        foreach ($products as $product) {
            $xml .= '     ' . "\n";
            $xml .= '        <item>' . "\n";
            $xml .= '            <g:id>' . htmlspecialchars($product->id) . '</g:id>' . "\n";
            $xml .= '            <g:title>' . htmlspecialchars($product->title) . '</g:title>' . "\n";
            $xml .= '            <g:description>' . htmlspecialchars($product->description ?? $product->short_description ?? '') . '</g:description>' . "\n";
            $xml .= '            ' . "\n";
            $xml .= '            <g:link>' . htmlspecialchars($siteUrl . '/product/' . $product->slug) . '</g:link>' . "\n";
            $xml .= '' . "\n";
            
            // Handle product images
            if ($product->images && is_array($product->images) && count($product->images) > 0) {
                $firstImage = $product->images[0];
                if (is_array($firstImage) && isset($firstImage['url'])) {
                    $imageUrl = $firstImage['url'];
                } else {
                    $imageUrl = $firstImage;
                }
                $xml .= '            ' . "\n";
                $xml .= '            <g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>' . "\n";
                $xml .= '' . "\n";
            }
            
            $xml .= '' . "\n";
            $xml .= '            <g:brand>' . htmlspecialchars($siteName) . '</g:brand>' . "\n";
            $xml .= '            <g:condition>New</g:condition>' . "\n";
            $xml .= '' . "\n";
            $xml .= '            <g:availability>in stock</g:availability>' . "\n";
            $xml .= '' . "\n";
            $xml .= '            <g:price>' . number_format($product->price, 3) . '  ' . strtoupper($product->currency ?? 'AED') . '</g:price>' . "\n";
            $xml .= '        </item>' . "\n";
        }

        $xml .= '    ' . "\n";
        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }
}
