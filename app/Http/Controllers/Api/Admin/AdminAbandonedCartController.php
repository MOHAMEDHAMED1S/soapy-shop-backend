<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminAbandonedCartController extends Controller
{
    /**
     * Get all abandoned carts with filtering
     */
    public function index(Request $request)
    {
        try {
            $query = AbandonedCart::query();

            // Filter by conversion status
            if ($request->has('converted')) {
                if ($request->converted === 'true' || $request->converted === true) {
                    $query->whereNotNull('converted_to_order_id');
                } else {
                    $query->whereNull('converted_to_order_id');
                }
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            // Filter by minimum cart value
            if ($request->has('min_value') && $request->min_value) {
                $query->where('cart_total', '>=', $request->min_value);
            }

            // Filter by reminder status
            if ($request->has('reminded')) {
                if ($request->reminded === 'true' || $request->reminded === true) {
                    $query->whereNotNull('reminder_sent_at');
                } else {
                    $query->whereNull('reminder_sent_at');
                }
            }

            // Search by customer name or phone
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('customer_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('customer_email', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $carts = $query->paginate($perPage);

            // Add WhatsApp URL to each cart
            $carts->getCollection()->transform(function ($cart) {
                $cart->whatsapp_url = $cart->getWhatsAppUrl();
                $cart->items_count = $cart->items_count;
                $cart->hours_since_activity = $cart->hours_since_activity;
                return $cart;
            });

            // Get summary statistics
            $summary = $this->getStatistics();

            return response()->json([
                'success' => true,
                'data' => [
                    'carts' => $carts,
                    'summary' => $summary
                ],
                'message' => 'Abandoned carts retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving abandoned carts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single abandoned cart details
     */
    public function show($id)
    {
        try {
            $cart = AbandonedCart::with('order')->find($id);

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Abandoned cart not found'
                ], 404);
            }

            $cart->whatsapp_url = $cart->getWhatsAppUrl();
            $cart->items_count = $cart->items_count;
            $cart->hours_since_activity = $cart->hours_since_activity;

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Abandoned cart retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving abandoned cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete abandoned cart
     */
    public function destroy($id)
    {
        try {
            $cart = AbandonedCart::find($id);

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Abandoned cart not found'
                ], 404);
            }

            $cart->delete();

            return response()->json([
                'success' => true,
                'message' => 'Abandoned cart deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting abandoned cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark reminder as sent
     */
    public function markReminded($id)
    {
        try {
            $cart = AbandonedCart::find($id);

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Abandoned cart not found'
                ], 404);
            }

            $cart->markReminderSent();

            return response()->json([
                'success' => true,
                'data' => $cart,
                'message' => 'Reminder marked as sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking reminder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics helper
     */
    private function getStatistics(): array
    {
        $today = Carbon::today();
        $last7Days = Carbon::now()->subDays(7);
        $last30Days = Carbon::now()->subDays(30);

        return [
            'total_carts' => AbandonedCart::count(),
            'not_converted' => AbandonedCart::notConverted()->count(),
            'converted' => AbandonedCart::whereNotNull('converted_to_order_id')->count(),
            'today' => AbandonedCart::whereDate('created_at', $today)->count(),
            'last_7_days' => AbandonedCart::where('created_at', '>=', $last7Days)->count(),
            'last_30_days' => AbandonedCart::where('created_at', '>=', $last30Days)->count(),
            'total_value' => AbandonedCart::notConverted()->sum('cart_total'),
            'average_value' => AbandonedCart::notConverted()->avg('cart_total'),
            'reminded' => AbandonedCart::whereNotNull('reminder_sent_at')->count(),
            'not_reminded' => AbandonedCart::whereNull('reminder_sent_at')->notConverted()->count(),
            'conversion_rate' => $this->calculateConversionRate(),
        ];
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(): float
    {
        $total = AbandonedCart::count();
        if ($total === 0) {
            return 0;
        }

        $converted = AbandonedCart::whereNotNull('converted_to_order_id')->count();
        return round(($converted / $total) * 100, 2);
    }

    /**
     * Bulk delete old carts
     */
    public function cleanup(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            
            $deleted = AbandonedCart::where('created_at', '<', now()->subDays($days))
                ->whereNull('converted_to_order_id')
                ->delete();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => $deleted],
                'message' => "Deleted {$deleted} old abandoned carts"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during cleanup',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
