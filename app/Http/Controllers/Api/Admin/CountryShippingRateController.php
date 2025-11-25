<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryShippingRateController extends Controller
{
    /**
     * Display a listing of shipping rates
     */
    public function index()
    {
        try {
            $rates = CountryShippingRate::with('tiers')->orderBy('country_code')->get();

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

    /**
     * Store a newly created shipping rate
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'country_code' => 'required|string|size:2|unique:country_shipping_rates,country_code',
                'rate_per_kg' => 'required|numeric|min:0',
                'rate_type' => 'in:fixed,weight',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rate = CountryShippingRate::create([
                'country_code' => strtoupper($request->country_code),
                'rate_per_kg' => $request->rate_per_kg,
                'rate_type' => $request->input('rate_type', 'weight'),
                'is_active' => $request->boolean('is_active', true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Shipping rate created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating shipping rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified shipping rate
     */
    public function show(string $countryCode)
    {
        try {
            $rate = CountryShippingRate::with('tiers')
                ->where('country_code', strtoupper($countryCode))
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Shipping rate retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping rate not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified shipping rate (or create if doesn't exist)
     */
    public function update(Request $request, string $countryCode)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Use updateOrCreate to handle both update and create cases
            $rate = CountryShippingRate::updateOrCreate(
                ['country_code' => strtoupper($countryCode)],
                [
                    'is_active' => $request->boolean('is_active', true)
                ]
            );

            // Load the tiers relationship
            $rate->load('tiers');

            return response()->json([
                'success' => true,
                'data' => $rate,
                'message' => 'Shipping rate updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating shipping rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified shipping rate
     */
    public function destroy(string $countryCode)
    {
        try {
            $rate = CountryShippingRate::where('country_code', strtoupper($countryCode))->firstOrFail();
            $rate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shipping rate deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting shipping rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update shipping rates
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rates' => 'required|array',
                'rates.*.country_code' => 'required|string|size:2',
                'rates.*.rate_per_kg' => 'required|numeric|min:0',
                'rates.*.rate_type' => 'in:fixed,weight'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedCount = 0;
            foreach ($request->rates as $rateData) {
                CountryShippingRate::updateOrCreate(
                    ['country_code' => strtoupper($rateData['country_code'])],
                    [
                        'rate_per_kg' => $rateData['rate_per_kg'],
                        'rate_type' => $rateData['rate_type'] ?? 'weight',
                        'is_active' => true
                    ]
                );
                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $updatedCount],
                'message' => 'Shipping rates updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating shipping rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
