<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /**
     * Get orders status
     * 
     * GET /api/v1/admin/site/orders-status
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
                    'message' => $ordersEnabled ? 'الطلبات مفتوحة' : 'الطلبات مغلقة'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving orders status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle orders status (enable/disable)
     * 
     * POST /api/v1/admin/site/toggle-orders
     */
    public function toggleOrders()
    {
        try {
            $newStatus = SiteSetting::toggleOrders();

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'تم فتح الطلبات بنجاح' : 'تم إغلاق الطلبات بنجاح',
                'data' => [
                    'orders_enabled' => $newStatus,
                    'status' => $newStatus ? 'open' : 'closed'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling orders status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set orders status (enable or disable)
     * 
     * POST /api/v1/admin/site/set-orders-status
     */
    public function setOrdersStatus(Request $request)
    {
        try {
            $request->validate([
                'enabled' => 'required|boolean'
            ]);

            $enabled = $request->enabled;
            SiteSetting::set('orders_enabled', $enabled, 'boolean');

            return response()->json([
                'success' => true,
                'message' => $enabled ? 'تم فتح الطلبات بنجاح' : 'تم إغلاق الطلبات بنجاح',
                'data' => [
                    'orders_enabled' => $enabled,
                    'status' => $enabled ? 'open' : 'closed'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error setting orders status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

