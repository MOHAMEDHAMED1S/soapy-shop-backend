<?php

namespace App\Http\Controllers;

use App\Models\ShippingCost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    /**
     * الحصول على مصاريف الشحن الحالية (للمستخدمين العاديين)
     */
    public function getCost(): JsonResponse
    {
        try {
            $cost = ShippingCost::getActiveCost();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'shipping_cost' => $cost
                ],
                'message' => 'تم جلب مصاريف الشحن بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مصاريف الشحن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على جميع مصاريف الشحن (للإدارة)
     */
    public function index(): JsonResponse
    {
        try {
            $shippingCosts = ShippingCost::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $shippingCosts,
                'message' => 'تم جلب جميع مصاريف الشحن بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مصاريف الشحن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث مصاريف الشحن (للإدارة)
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cost' => 'required|numeric|min:0|max:999999.99'
            ], [
                'cost.required' => 'مصاريف الشحن مطلوبة',
                'cost.numeric' => 'مصاريف الشحن يجب أن تكون رقم',
                'cost.min' => 'مصاريف الشحن يجب أن تكون أكبر من أو تساوي صفر',
                'cost.max' => 'مصاريف الشحن يجب أن تكون أقل من 999999.99'
            ]);

            $shippingCost = ShippingCost::updateCost($request->cost);

            return response()->json([
                'success' => true,
                'data' => $shippingCost,
                'message' => 'تم تحديث مصاريف الشحن بنجاح'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث مصاريف الشحن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على مصاريف الشحن النشطة (للإدارة)
     */
    public function getActive(): JsonResponse
    {
        try {
            $activeShipping = ShippingCost::where('is_active', true)->first();
            
            return response()->json([
                'success' => true,
                'data' => $activeShipping,
                'message' => 'تم جلب مصاريف الشحن النشطة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب مصاريف الشحن النشطة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}