<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;

class SiteController extends Controller
{
    /**
     * Get orders status (Public API)
     * 
     * GET /api/v1/site/orders-status
     */
    public function getOrdersStatus()
    {
        try {
            $ordersEnabled = SiteSetting::areOrdersEnabled();

            return response()->json([
                'success' => true,
                'data' => [
                    'orders_enabled' => $ordersEnabled,
                    'status' => $ordersEnabled ? 'open' : 'closed',
                    'message' => $ordersEnabled ? 'الطلبات مفتوحة حالياً' : 'الطلبات مغلقة حالياً، يرجى المحاولة لاحقاً'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'orders_enabled' => true,
                    'status' => 'open',
                    'message' => 'الطلبات مفتوحة حالياً'
                ]
            ]);
        }
    }
}

