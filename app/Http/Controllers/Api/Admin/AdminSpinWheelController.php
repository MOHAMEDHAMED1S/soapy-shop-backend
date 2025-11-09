<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpinWheelItem;
use App\Models\SpinWheelResult;
use App\Services\SpinWheelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminSpinWheelController extends Controller
{
    protected $spinWheelService;

    public function __construct(SpinWheelService $spinWheelService)
    {
        $this->spinWheelService = $spinWheelService;
    }

    /**
     * Display a listing of spin wheel items.
     */
    public function index(Request $request)
    {
        try {
            $query = SpinWheelItem::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'order');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortBy, $sortDirection);

            $items = $query->get();

            return response()->json([
                'success' => true,
                'data' => $items,
                'message' => 'Spin wheel items retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving spin wheel items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عناصر عجلة الحظ',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }

    /**
     * Store a newly created spin wheel item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:255',
            'discount_code' => 'nullable|string|max:50',
            'probability' => 'required|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item = SpinWheelItem::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'تم إنشاء عنصر عجلة الحظ بنجاح'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating spin wheel item: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء عنصر عجلة الحظ',
                'error_code' => 'CREATE_ERROR'
            ], 500);
        }
    }

    /**
     * Display the specified spin wheel item.
     */
    public function show($id)
    {
        try {
            $item = SpinWheelItem::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Spin wheel item retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'عنصر عجلة الحظ غير موجود',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }
    }

    /**
     * Update the specified spin wheel item.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'sometimes|required|string|max:255',
            'discount_code' => 'nullable|string|max:50',
            'probability' => 'sometimes|required|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item = SpinWheelItem::findOrFail($id);
            $item->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'تم تحديث عنصر عجلة الحظ بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating spin wheel item: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث عنصر عجلة الحظ',
                'error_code' => 'UPDATE_ERROR'
            ], 500);
        }
    }

    /**
     * Remove the specified spin wheel item.
     */
    public function destroy($id)
    {
        try {
            $item = SpinWheelItem::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف عنصر عجلة الحظ بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting spin wheel item: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف عنصر عجلة الحظ',
                'error_code' => 'DELETE_ERROR'
            ], 500);
        }
    }

    /**
     * Get spin wheel results (records).
     */
    public function getResults(Request $request)
    {
        try {
            $query = SpinWheelResult::with('spinWheelItem');

            // Search by name or phone
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('user_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('user_phone', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('discount_code', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 50);
            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Spin wheel results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving spin wheel results: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب سجلات عجلة الحظ',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }

    /**
     * Get statistics for spin wheel.
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->spinWheelService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving spin wheel statistics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error_code' => 'FETCH_ERROR'
            ], 500);
        }
    }
}

