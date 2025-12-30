<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AbandonedCartController extends Controller
{
    /**
     * Sync abandoned cart from frontend
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|integer',
            'cart_items.*.title' => 'required|string',
            'cart_items.*.price' => 'required|numeric',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_total' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if customer has recent orders (cart might have been completed)
            $recentOrder = Order::where('customer_phone', $request->customer_phone)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereIn('status', ['paid', 'shipped', 'delivered'])
                ->first();

            if ($recentOrder) {
                // Customer just completed an order, delete the abandoned cart
                AbandonedCart::where('session_id', $request->session_id)->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cart cleared - recent order found',
                    'data' => null
                ]);
            }

            // Upsert abandoned cart
            $cart = AbandonedCart::updateOrCreate(
                ['session_id' => $request->session_id],
                [
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_email' => $request->customer_email,
                    'cart_items' => $request->cart_items,
                    'cart_total' => $request->cart_total,
                    'currency' => $request->currency ?? 'KWD',
                    'last_activity_at' => now(),
                ]
            );

            // Auto-cleanup: Delete carts older than 3 days (runs occasionally)
            if (rand(1, 10) === 1) { // 10% chance to run cleanup
                AbandonedCart::where('last_activity_at', '<', now()->subDays(3))
                    ->whereNull('converted_to_order_id')
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart synced successfully',
                'data' => $cart
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error syncing cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete abandoned cart (called after successful payment)
     */
    public function delete(Request $request, string $sessionId)
    {
        try {
            $cart = AbandonedCart::where('session_id', $sessionId)->first();

            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart not found or already deleted'
                ]);
            }

            // If order ID is provided, mark as converted instead of deleting
            if ($request->has('order_id')) {
                $cart->markConverted($request->order_id);
                return response()->json([
                    'success' => true,
                    'message' => 'Cart marked as converted'
                ]);
            }

            $cart->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark cart as converted when order is created
     */
    public function markConverted(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cart = AbandonedCart::where('session_id', $request->session_id)->first();

            if ($cart) {
                $cart->markConverted($request->order_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart marked as converted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking cart as converted',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
