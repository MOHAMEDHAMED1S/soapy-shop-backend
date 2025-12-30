<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomerDiscountController extends Controller
{
    /**
     * Get all customer discounts with customer info.
     */
    public function index(Request $request)
    {
        try {
            $query = CustomerDiscount::with(['customer', 'createdBy']);

            // Filter by type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('is_active', $request->status === 'active');
            }

            // Search by customer name or phone
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $discounts = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $discounts,
                'message' => 'تم جلب خصومات العملاء بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching customer discounts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب خصومات العملاء'
            ], 500);
        }
    }

    /**
     * Get all customers for selection (with their discount status).
     */
    public function customers(Request $request)
    {
        try {
            $query = Customer::with('activeDiscount')
                ->select('id', 'name', 'phone', 'email', 'total_orders', 'total_spent');

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter customers without discount
            if ($request->has('without_discount') && $request->without_discount === 'true') {
                $query->whereDoesntHave('activeDiscount');
            }

            $customers = $query->orderBy('total_orders', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'تم جلب العملاء بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العملاء'
            ], 500);
        }
    }

    /**
     * Create a new customer discount.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id|unique:customer_discounts,customer_id',
            'type' => 'required|in:percentage,fixed_amount,free_shipping',
            'value' => 'required_unless:type,free_shipping|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'customer_id.unique' => 'هذا العميل لديه خصم بالفعل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['created_by'] = Auth::id();
            
            // For free_shipping, value is 0
            if ($data['type'] === 'free_shipping') {
                $data['value'] = 0;
            }

            $discount = CustomerDiscount::create($data);
            $discount->load(['customer', 'createdBy']);

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'تم إنشاء خصم العميل بنجاح'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating customer discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء خصم العميل'
            ], 500);
        }
    }

    /**
     * Update a customer discount.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:percentage,fixed_amount,free_shipping',
            'value' => 'required_unless:type,free_shipping|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $discount = CustomerDiscount::findOrFail($id);
            
            $data = $request->all();
            
            // For free_shipping, value is 0
            if (isset($data['type']) && $data['type'] === 'free_shipping') {
                $data['value'] = 0;
            }

            $discount->update($data);
            $discount->load(['customer', 'createdBy']);

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => 'تم تحديث خصم العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating customer discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث خصم العميل'
            ], 500);
        }
    }

    /**
     * Delete a customer discount.
     */
    public function destroy($id)
    {
        try {
            $discount = CustomerDiscount::findOrFail($id);
            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف خصم العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting customer discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف خصم العميل'
            ], 500);
        }
    }

    /**
     * Toggle discount active status.
     */
    public function toggle($id)
    {
        try {
            $discount = CustomerDiscount::findOrFail($id);
            $discount->update(['is_active' => !$discount->is_active]);
            $discount->load(['customer', 'createdBy']);

            return response()->json([
                'success' => true,
                'data' => $discount,
                'message' => $discount->is_active 
                    ? 'تم تفعيل خصم العميل' 
                    : 'تم إلغاء تفعيل خصم العميل'
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling customer discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة خصم العميل'
            ], 500);
        }
    }

    /**
     * Get statistics for customer discounts.
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => CustomerDiscount::count(),
                'active' => CustomerDiscount::where('is_active', true)->count(),
                'inactive' => CustomerDiscount::where('is_active', false)->count(),
                'by_type' => [
                    'percentage' => CustomerDiscount::where('type', 'percentage')->count(),
                    'fixed_amount' => CustomerDiscount::where('type', 'fixed_amount')->count(),
                    'free_shipping' => CustomerDiscount::where('type', 'free_shipping')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب إحصائيات خصومات العملاء بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching customer discount statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }
}
