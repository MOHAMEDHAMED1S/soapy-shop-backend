<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CountryShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingCalculationController extends Controller
{
    /**
     * حساب تكلفة الشحن بناءً على المنتجات والكميات والدولة
     */
    public function calculate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'required|exists:products,id',
                'quantities' => 'required|array',
                'quantities.*' => 'required|integer|min:1',
                'country_code' => 'required|string|size:2',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productIds = $request->product_ids;
            $quantities = $request->quantities;
            $countryCode = strtoupper($request->country_code);

            // Get all products
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // Calculate total weight
            $totalWeightGrams = 0;
            foreach ($productIds as $index => $productId) {
                $product = $products->get($productId);
                if (!$product) {
                    continue;
                }

                $quantity = $quantities[$index] ?? 1;
                // استخدام وزن افتراضي (100 جرام) إذا لم يكن للمنتج وزن
                $weight = $product->weight_grams ?? 100;
                $totalWeightGrams += $weight * $quantity;
            }

            // Calculate shipping cost using new tiered system
            $result = CountryShippingRate::calculateShipping($totalWeightGrams, $countryCode);

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping rate not found for this country',
                    'country_code' => $countryCode
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_weight_grams' => $totalWeightGrams,
                    'total_weight_kg' => round($totalWeightGrams / 1000, 3),
                    'shipping_cost' => $result['shipping_cost'],
                    'breakdown' => $result['breakdown'],
                    'country_code' => $countryCode,
                    'currency' => 'KWD'
                ],
                'message' => 'Shipping cost calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating shipping cost',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active shipping rates
     */
    public function getRates()
    {
        try {
            $rates = CountryShippingRate::where('is_active', true)
                ->orderBy('country_code')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $rates,
                'message' => 'Shipping rates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving shipping rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
