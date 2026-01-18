<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BogoOffer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AdminBogoOfferController extends Controller
{
    /**
     * List all BOGO offers with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BogoOffer::with(['buyProduct', 'getProduct']);

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('expires_at', '<', now());
                    break;
                case 'upcoming':
                    $query->where('starts_at', '>', now());
                    break;
            }
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by product
        if ($request->has('product_id')) {
            $productId = $request->product_id;
            $query->where(function ($q) use ($productId) {
                $q->where('buy_product_id', $productId)
                  ->orWhere('get_product_id', $productId);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $offers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $offers,
            'message' => 'BOGO offers retrieved successfully'
        ]);
    }

    /**
     * Get a single BOGO offer.
     */
    public function show(int $id): JsonResponse
    {
        $offer = BogoOffer::with(['buyProduct', 'getProduct'])->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'BOGO offer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $offer,
            'message' => 'BOGO offer retrieved successfully'
        ]);
    }

    /**
     * Create a new BOGO offer.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'buy_product_id' => 'required|exists:products,id',
            'buy_quantity' => 'required|integer|min:1',
            'get_product_id' => 'required|exists:products,id',
            'get_quantity' => 'required|integer|min:1',
            'get_discount_type' => 'required|in:free,percentage,fixed',
            'get_discount_value' => 'required|numeric|min:0',
            'max_uses_per_order' => 'nullable|integer|min:1',
            'total_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate discount value based on type
        if ($request->get_discount_type === 'percentage' && $request->get_discount_value > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%'
            ], 422);
        }

        $offer = BogoOffer::create($request->all());
        $offer->load(['buyProduct', 'getProduct']);

        return response()->json([
            'success' => true,
            'data' => $offer,
            'message' => 'BOGO offer created successfully'
        ], 201);
    }

    /**
     * Update a BOGO offer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $offer = BogoOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'BOGO offer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'buy_product_id' => 'exists:products,id',
            'buy_quantity' => 'integer|min:1',
            'get_product_id' => 'exists:products,id',
            'get_quantity' => 'integer|min:1',
            'get_discount_type' => 'in:free,percentage,fixed',
            'get_discount_value' => 'numeric|min:0',
            'max_uses_per_order' => 'nullable|integer|min:1',
            'total_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate discount value based on type
        $discountType = $request->input('get_discount_type', $offer->get_discount_type);
        $discountValue = $request->input('get_discount_value', $offer->get_discount_value);
        if ($discountType === 'percentage' && $discountValue > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Percentage discount cannot exceed 100%'
            ], 422);
        }

        $offer->update($request->all());
        $offer->load(['buyProduct', 'getProduct']);

        return response()->json([
            'success' => true,
            'data' => $offer,
            'message' => 'BOGO offer updated successfully'
        ]);
    }

    /**
     * Delete a BOGO offer.
     */
    public function destroy(int $id): JsonResponse
    {
        $offer = BogoOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'BOGO offer not found'
            ], 404);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'BOGO offer deleted successfully'
        ]);
    }

    /**
     * Toggle BOGO offer status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $offer = BogoOffer::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'BOGO offer not found'
            ], 404);
        }

        $offer->is_active = !$offer->is_active;
        $offer->save();
        $offer->load(['buyProduct', 'getProduct']);

        return response()->json([
            'success' => true,
            'data' => $offer,
            'message' => $offer->is_active ? 'BOGO offer activated' : 'BOGO offer deactivated'
        ]);
    }

    /**
     * Get BOGO statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalOffers = BogoOffer::count();
        $activeOffers = BogoOffer::active()->count();
        $totalUsage = BogoOffer::sum('usage_count');
        
        $topOffers = BogoOffer::with(['buyProduct', 'getProduct'])
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();

        $recentOffers = BogoOffer::with(['buyProduct', 'getProduct'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_offers' => $totalOffers,
                'active_offers' => $activeOffers,
                'inactive_offers' => $totalOffers - $activeOffers,
                'total_usage' => $totalUsage,
                'top_offers' => $topOffers,
                'recent_offers' => $recentOffers,
            ],
            'message' => 'BOGO statistics retrieved successfully'
        ]);
    }

    /**
     * Get all products for dropdown selection.
     */
    public function getProducts(): JsonResponse
    {
        $products = Product::where('is_available', true)
            ->select('id', 'title', 'price', 'images')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ]);
    }
}
