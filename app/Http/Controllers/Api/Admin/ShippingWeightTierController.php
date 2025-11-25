<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingWeightTier;
use App\Models\CountryShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShippingWeightTierController extends Controller
{
    /**
     * Display all tiers for a specific country
     */
    public function index(string $countryCode)
    {
        try {
            $countryCode = strtoupper($countryCode);
            
            // Verify country exists
            $country = CountryShippingRate::where('country_code', $countryCode)->first();
            if (!$country) {
                return response()->json([
                    'success' => false,
                    'message' => 'Country not found'
                ], 404);
            }

            $tiers = ShippingWeightTier::where('country_code', $countryCode)
                ->orderBy('max_weight_kg', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tiers,
                'message' => 'Tiers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving tiers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new tier for a country
     */
    public function store(Request $request, string $countryCode)
    {
        try {
            $countryCode = strtoupper($countryCode);

            $validator = Validator::make($request->all(), [
                'max_weight_kg' => 'required|numeric|min:0.001|max:999.999',
                'base_price' => 'required|numeric|min:0|max:99999.999',
                'additional_percentage' => 'required|numeric|min:0|max:999.99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if a tier with the same max_weight_kg exists
            $existing = ShippingWeightTier::where('country_code', $countryCode)
                ->where('max_weight_kg', $request->max_weight_kg)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'A tier with this weight already exists'
                ], 422);
            }

            $tier = ShippingWeightTier::create([
                'country_code' => $countryCode,
                'max_weight_kg' => $request->max_weight_kg,
                'base_price' => $request->base_price,
                'additional_percentage' => $request->additional_percentage
            ]);

            return response()->json([
                'success' => true,
                'data' => $tier,
                'message' => 'Tier created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating tier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a specific tier
     */
    public function show(string $countryCode, int $tierId)
    {
        try {
            $tier = ShippingWeightTier::where('country_code', strtoupper($countryCode))
                ->where('id', $tierId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $tier,
                'message' => 'Tier retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tier not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a specific tier
     */
    public function update(Request $request, string $countryCode, int $tierId)
    {
        try {
            $countryCode = strtoupper($countryCode);
            
            $tier = ShippingWeightTier::where('country_code', $countryCode)
                ->where('id', $tierId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'max_weight_kg' => 'sometimes|numeric|min:0.001|max:999.999',
                'base_price' => 'sometimes|numeric|min:0|max:99999.999',
                'additional_percentage' => 'sometimes|numeric|min:0|max:999.99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If updating max_weight_kg, check for duplicates
            if ($request->has('max_weight_kg')) {
                $existing = ShippingWeightTier::where('country_code', $countryCode)
                    ->where('max_weight_kg', $request->max_weight_kg)
                    ->where('id', '!=', $tierId)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A tier with this weight already exists'
                    ], 422);
                }
            }

            $tier->update($request->only(['max_weight_kg', 'base_price', 'additional_percentage']));

            return response()->json([
                'success' => true,
                'data' => $tier,
                'message' => 'Tier updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating tier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a tier
     */
    public function destroy(string $countryCode, int $tierId)
    {
        try {
            $tier = ShippingWeightTier::where('country_code', strtoupper($countryCode))
                ->where('id', $tierId)
                ->firstOrFail();
            
            $tier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tier deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update or create tiers for a country
     */
    public function bulkUpdate(Request $request, string $countryCode)
    {
        try {
            $countryCode = strtoupper($countryCode);

            $validator = Validator::make($request->all(), [
                'tiers' => 'required|array|min:1',
                'tiers.*.id' => 'sometimes|integer',
                'tiers.*.max_weight_kg' => 'required|numeric|min:0.001|max:999.999',
                'tiers.*.base_price' => 'required|numeric|min:0|max:99999.999',
                'tiers.*.additional_percentage' => 'required|numeric|min:0|max:999.99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Collect IDs of tiers that will be kept/updated
            $keepTierIds = [];
            $updatedTiers = [];
            
            foreach ($request->tiers as $tierData) {
                if (isset($tierData['id'])) {
                    // Update existing
                    $tier = ShippingWeightTier::where('country_code', $countryCode)
                        ->where('id', $tierData['id'])
                        ->first();
                    
                    if ($tier) {
                        $tier->update([
                            'max_weight_kg' => $tierData['max_weight_kg'],
                            'base_price' => $tierData['base_price'],
                            'additional_percentage' => $tierData['additional_percentage']
                        ]);
                        $updatedTiers[] = $tier;
                        $keepTierIds[] = $tier->id;
                    }
                } else {
                    // Create new
                    $tier = ShippingWeightTier::create([
                        'country_code' => $countryCode,
                        'max_weight_kg' => $tierData['max_weight_kg'],
                        'base_price' => $tierData['base_price'],
                        'additional_percentage' => $tierData['additional_percentage']
                    ]);
                    $updatedTiers[] = $tier;
                    $keepTierIds[] = $tier->id;
                }
            }

            // Delete tiers that are not in the request (were removed by user)
            if (!empty($keepTierIds)) {
                ShippingWeightTier::where('country_code', $countryCode)
                    ->whereNotIn('id', $keepTierIds)
                    ->delete();
            } else {
                // If no tiers to keep, delete all tiers for this country
                ShippingWeightTier::where('country_code', $countryCode)->delete();
            }

            return response()->json([
                'success' => true,
                'data' => $updatedTiers,
                'message' => 'Tiers updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating tiers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
