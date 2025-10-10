<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DiscountCodeController extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }

    /**
     * Display a listing of discount codes.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'status', 'type', 'search', 'date_from', 'date_to'
            ]);

            $result = $this->discountService->getDiscountCodesForAdmin($filters);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error retrieving discount codes: ' . $e->getMessage(), [
                'filters' => $request->all(),
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
     * Store a newly created discount code.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:discount_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount,free_shipping',
            'value' => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'applicable_customers' => 'nullable|array',
            'first_time_customer_only' => 'nullable|boolean',
            'new_customer_only' => 'nullable|boolean',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->discountService->createDiscountCode($request->all());

            return response()->json($result, $result['success'] ? 201 : 400);

        } catch (\Exception $e) {
            Log::error('Error creating discount code: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء كود الخصم',
                'error_code' => 'CREATION_ERROR'
            ], 500);
        }
    }

    /**
     * Display the specified discount code.
     */
    public function show(string $id)
    {
        try {
            $discountCode = DiscountCode::withCount('usage')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $discountCode,
                'message' => 'تم جلب كود الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving discount code: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير موجود',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }
    }

    /**
     * Update the specified discount code.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:discount_codes,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:percentage,fixed_amount,free_shipping',
            'value' => 'sometimes|required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'applicable_customers' => 'nullable|array',
            'first_time_customer_only' => 'nullable|boolean',
            'new_customer_only' => 'nullable|boolean',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->discountService->updateDiscountCode($id, $request->all());

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error updating discount code: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث كود الخصم',
                'error_code' => 'UPDATE_ERROR'
            ], 500);
        }
    }

    /**
     * Remove the specified discount code.
     */
    public function destroy(string $id)
    {
        try {
            $result = $this->discountService->deleteDiscountCode($id);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error deleting discount code: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف كود الخصم',
                'error_code' => 'DELETE_ERROR'
            ], 500);
        }
    }

    /**
     * Get discount code statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $statistics = $this->discountService->getDiscountCodeStatistics($period);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'تم جلب إحصائيات أكواد الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting discount code statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات أكواد الخصم',
                'error_code' => 'STATISTICS_ERROR'
            ], 500);
        }
    }

    /**
     * Toggle discount code status.
     */
    public function toggleStatus(string $id)
    {
        try {
            $discountCode = DiscountCode::findOrFail($id);
            $discountCode->update(['is_active' => !$discountCode->is_active]);

            return response()->json([
                'success' => true,
                'data' => $discountCode,
                'message' => $discountCode->is_active ? 'تم تفعيل كود الخصم' : 'تم إلغاء تفعيل كود الخصم'
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling discount code status: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة كود الخصم',
                'error_code' => 'TOGGLE_ERROR'
            ], 500);
        }
    }

    /**
     * Get discount code usage history.
     */
    public function usageHistory(Request $request, string $id)
    {
        try {
            $discountCode = DiscountCode::findOrFail($id);

            $query = $discountCode->usage()->with(['order', 'customer']);

            // Apply filters
            if ($request->has('date_from')) {
                $query->where('used_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('used_at', '<=', $request->date_to);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $usageHistory = $query->orderBy('used_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $usageHistory,
                'message' => 'تم جلب تاريخ استخدام كود الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting discount code usage history: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تاريخ استخدام كود الخصم',
                'error_code' => 'HISTORY_ERROR'
            ], 500);
        }
    }

    /**
     * Duplicate a discount code.
     */
    public function duplicate(string $id)
    {
        try {
            $originalCode = DiscountCode::findOrFail($id);
            
            $newCode = $originalCode->replicate();
            $newCode->code = DiscountCode::generateUniqueCode();
            $newCode->name = $originalCode->name . ' (نسخة)';
            $newCode->usage_count = 0;
            $newCode->is_active = false; // Start as inactive
            $newCode->save();

            return response()->json([
                'success' => true,
                'data' => $newCode,
                'message' => 'تم نسخ كود الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicating discount code: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء نسخ كود الخصم',
                'error_code' => 'DUPLICATE_ERROR'
            ], 500);
        }
    }
}
