<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Validate a discount code.
     */
    public function validateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->discountService->validateAndApplyDiscountCode(
                $request->code,
                $request->all()
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error validating discount code: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من كود الخصم',
                'error_code' => 'VALIDATION_ERROR'
            ], 500);
        }
    }

    /**
     * Get available discount codes for customer.
     */
    public function getAvailableCodes(Request $request)
    {
        try {
            $query = \App\Models\DiscountCode::valid()
                ->where('is_active', true)
                ->orderBy('created_at', 'desc');

            // Filter by type if specified
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Limit results
            $limit = min($request->get('limit', 10), 50);
            $discountCodes = $query->limit($limit)->get();

            // Format response for public display
            $formattedCodes = $discountCodes->map(function ($code) {
                return [
                    'code' => $code->code,
                    'name' => $code->name,
                    'description' => $code->description,
                    'type' => $code->type,
                    'value' => $code->value,
                    'minimum_order_amount' => $code->minimum_order_amount,
                    'expires_at' => $code->expires_at?->format('Y-m-d H:i:s'),
                    'usage_count' => $code->usage_count,
                    'usage_limit' => $code->usage_limit,
                    'remaining_usage' => $code->remaining_usage,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedCodes,
                'message' => 'تم جلب أكواد الخصم المتاحة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available discount codes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب أكواد الخصم',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }

    /**
     * Get discount code details.
     */
    public function getCodeDetails(Request $request, string $code)
    {
        try {
            $discountCode = \App\Models\DiscountCode::where('code', $code)->first();

            if (!$discountCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير موجود',
                    'error_code' => 'NOT_FOUND'
                ], 404);
            }

            // Check if code is valid
            if (!$discountCode->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'كود الخصم غير متاح حالياً',
                    'error_code' => 'INVALID'
                ], 400);
            }

            $details = [
                'code' => $discountCode->code,
                'name' => $discountCode->name,
                'description' => $discountCode->description,
                'type' => $discountCode->type,
                'value' => $discountCode->value,
                'minimum_order_amount' => $discountCode->minimum_order_amount,
                'maximum_discount_amount' => $discountCode->maximum_discount_amount,
                'expires_at' => $discountCode->expires_at?->format('Y-m-d H:i:s'),
                'usage_count' => $discountCode->usage_count,
                'usage_limit' => $discountCode->usage_limit,
                'remaining_usage' => $discountCode->remaining_usage,
                'first_time_customer_only' => $discountCode->first_time_customer_only,
                'new_customer_only' => $discountCode->new_customer_only,
            ];

            return response()->json([
                'success' => true,
                'data' => $details,
                'message' => 'تم جلب تفاصيل كود الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting discount code details: ' . $e->getMessage(), [
                'code' => $code,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تفاصيل كود الخصم',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }
}
