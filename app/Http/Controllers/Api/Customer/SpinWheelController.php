<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\SpinWheelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SpinWheelController extends Controller
{
    protected $spinWheelService;

    public function __construct(SpinWheelService $spinWheelService)
    {
        $this->spinWheelService = $spinWheelService;
    }

    /**
     * Get active spin wheel items (for display).
     */
    public function getItems()
    {
        try {
            $items = $this->spinWheelService->getActiveItems();

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
     * Check if user has already spun the wheel.
     */
    public function checkPreviousSpin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->spinWheelService->checkPreviousSpin($request->user_phone);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'has_previous_spin' => true,
                    'data' => $result['result'],
                    'message' => 'User has already spun the wheel'
                ]);
            }

            return response()->json([
                'success' => true,
                'has_previous_spin' => false,
                'message' => 'User has not spun the wheel before'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking previous spin: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من الدوران السابق',
                'error_code' => 'CHECK_ERROR'
            ], 500);
        }
    }

    /**
     * Perform a spin (this is where the result is determined on the backend).
     */
    public function spin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|max:255',
            'user_phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user has already spun
            $previousSpin = $this->spinWheelService->checkPreviousSpin($request->user_phone);
            if ($previousSpin) {
                return response()->json([
                    'success' => false,
                    'has_previous_spin' => true,
                    'message' => 'لقد قمت بالمشاركة سابقاً',
                    'previous_result' => $previousSpin['result'],
                    'error_code' => 'ALREADY_SPUN'
                ], 400);
            }

            $result = $this->spinWheelService->performSpin(
                $request->user_name,
                $request->user_phone
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error performing spin: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'حدث خطأ أثناء تنفيذ الدوران',
                'error_code' => 'SPIN_ERROR'
            ], 500);
        }
    }
}

