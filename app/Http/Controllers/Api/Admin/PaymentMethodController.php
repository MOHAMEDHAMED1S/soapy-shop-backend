<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethodSetting;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all payment methods with their status
     * GET /api/v1/admin/payment-methods
     */
    public function index(): JsonResponse
    {
        try {
            // Get payment methods from MyFatoorah
            $myFatoorahMethods = $this->paymentService->getPaymentMethods();
            
            if (!$myFatoorahMethods['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment methods from MyFatoorah',
                    'error' => $myFatoorahMethods['message'] ?? 'Unknown error'
                ], 500);
            }

            // Get current settings from database
            $settings = PaymentMethodSetting::all()->keyBy('payment_method_code');

            // Merge MyFatoorah data with settings
            $paymentMethods = collect($myFatoorahMethods['data'])->map(function ($method) use ($settings) {
                $code = $method['PaymentMethodCode'];
                $setting = $settings->get($code);
                
                return [
                    'PaymentMethodId' => $method['PaymentMethodId'],
                    'PaymentMethodAr' => $method['PaymentMethodAr'],
                    'PaymentMethodEn' => $method['PaymentMethodEn'],
                    'PaymentMethodCode' => $code,
                    'IsDirectPayment' => $method['IsDirectPayment'],
                    'ServiceCharge' => $method['ServiceCharge'],
                    'TotalAmount' => $method['TotalAmount'],
                    'CurrencyIso' => $method['CurrencyIso'],
                    'ImageUrl' => $method['ImageUrl'],
                    'IsEmbeddedSupported' => $method['IsEmbeddedSupported'],
                    'PaymentCurrencyIso' => $method['PaymentCurrencyIso'],
                    'is_enabled' => $setting ? $setting->is_enabled : true, // Default to enabled
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $paymentMethods,
                'message' => 'Payment methods retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle payment method status
     * PUT /api/v1/admin/payment-methods/{code}/toggle
     */
    public function toggle(string $code): JsonResponse
    {
        try {
            $isEnabled = PaymentMethodSetting::toggle($code);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_method_code' => $code,
                    'is_enabled' => $isEnabled
                ],
                'message' => $isEnabled ? 'Payment method enabled successfully' : 'Payment method disabled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle payment method status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync payment methods from MyFatoorah
     * POST /api/v1/admin/payment-methods/sync
     */
    public function sync(): JsonResponse
    {
        try {
            // Get payment methods from MyFatoorah
            $myFatoorahMethods = $this->paymentService->getPaymentMethods();
            
            if (!$myFatoorahMethods['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch payment methods from MyFatoorah',
                    'error' => $myFatoorahMethods['message'] ?? 'Unknown error'
                ], 500);
            }

            // Sync with database
            PaymentMethodSetting::syncFromMyFatoorah($myFatoorahMethods['data']);

            return response()->json([
                'success' => true,
                'message' => 'Payment methods synced successfully',
                'synced_count' => count($myFatoorahMethods['data'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment method settings
     * PUT /api/v1/admin/payment-methods/{code}
     */
    public function update(Request $request, string $code): JsonResponse
    {
        try {
            $request->validate([
                'is_enabled' => 'required|boolean',
                'payment_method_name_ar' => 'nullable|string|max:255',
                'payment_method_name_en' => 'nullable|string|max:255',
            ]);

            $setting = PaymentMethodSetting::updateOrCreate(
                ['payment_method_code' => $code],
                $request->only(['is_enabled', 'payment_method_name_ar', 'payment_method_name_en'])
            );

            return response()->json([
                'success' => true,
                'data' => $setting,
                'message' => 'Payment method updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
