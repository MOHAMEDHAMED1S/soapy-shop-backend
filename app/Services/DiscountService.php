<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DiscountService
{
    /**
     * Validate and apply a discount code to an order.
     */
    public function validateAndApplyDiscountCode(
        string $code,
        array $orderData,
        ?Customer $customer = null
    ): array {
        try {
            // Find the discount code
            $discountCode = DiscountCode::where('code', $code)->first();

            if (!$discountCode) {
                return [
                    'success' => false,
                    'message' => 'كود الخصم غير صحيح',
                    'error_code' => 'INVALID_CODE'
                ];
            }

            // Check if code is valid
            if (!$discountCode->isValid()) {
                if ($discountCode->isExpired()) {
                    return [
                        'success' => false,
                        'message' => 'كود الخصم منتهي الصلاحية',
                        'error_code' => 'EXPIRED'
                    ];
                }

                if ($discountCode->hasReachedUsageLimit()) {
                    return [
                        'success' => false,
                        'message' => 'تم استخدام كود الخصم بالكامل',
                        'error_code' => 'USAGE_LIMIT_REACHED'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'كود الخصم غير متاح حالياً',
                    'error_code' => 'INACTIVE'
                ];
            }

            // Check if customer can use this code
            if (!$discountCode->canBeUsedByCustomer($customer, $orderData['customer_phone'] ?? null)) {
                if ($discountCode->first_time_customer_only) {
                    return [
                        'success' => false,
                        'message' => 'هذا الكود متاح للعملاء الجدد فقط',
                        'error_code' => 'FIRST_TIME_ONLY'
                    ];
                }

                if ($discountCode->new_customer_only) {
                    return [
                        'success' => false,
                        'message' => 'هذا الكود متاح للعملاء الجدد فقط',
                        'error_code' => 'NEW_CUSTOMER_ONLY'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'لا يمكنك استخدام هذا الكود',
                    'error_code' => 'CUSTOMER_NOT_ELIGIBLE'
                ];
            }

            // Calculate order amount
            $orderAmount = $this->calculateOrderAmount($orderData['items']);

            // Check minimum order amount
            if ($discountCode->minimum_order_amount && $orderAmount < $discountCode->minimum_order_amount) {
                return [
                    'success' => false,
                    'message' => "الحد الأدنى للطلب هو {$discountCode->minimum_order_amount} د.ك",
                    'error_code' => 'MINIMUM_ORDER_NOT_MET'
                ];
            }

            // Check if code applies to products
            $productIds = collect($orderData['items'])->pluck('product_id')->toArray();
            $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->toArray();

            if (!$discountCode->appliesToProducts($productIds, $categoryIds)) {
                return [
                    'success' => false,
                    'message' => 'هذا الكود لا ينطبق على المنتجات المختارة',
                    'error_code' => 'PRODUCTS_NOT_APPLICABLE'
                ];
            }

            // Calculate discount amount
            $discountAmount = $discountCode->calculateDiscountAmount($orderAmount);

            if ($discountAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن تطبيق هذا الكود على هذا الطلب',
                    'error_code' => 'NO_DISCOUNT_APPLICABLE'
                ];
            }

            return [
                'success' => true,
                'discount_code' => $discountCode,
                'discount_amount' => $discountAmount,
                'order_amount_before_discount' => $orderAmount,
                'order_amount_after_discount' => $orderAmount - $discountAmount,
                'message' => 'تم تطبيق كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error validating discount code: ' . $e->getMessage(), [
                'code' => $code,
                'order_data' => $orderData,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من كود الخصم',
                'error_code' => 'VALIDATION_ERROR'
            ];
        }
    }

    /**
     * Apply discount code to an order and record usage.
     */
    public function applyDiscountCodeToOrder(
        string $code,
        Order $order,
        ?Customer $customer = null
    ): array {
        try {
            DB::beginTransaction();

            // Validate the discount code
            $validation = $this->validateAndApplyDiscountCode($code, [
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity
                    ];
                })->toArray(),
                'customer_phone' => $order->customer_phone
            ], $customer);

            if (!$validation['success']) {
                DB::rollBack();
                return $validation;
            }

            $discountCode = $validation['discount_code'];
            $discountAmount = $validation['discount_amount'];

            // Record the usage
            DiscountCodeUsage::create([
                'discount_code_id' => $discountCode->id,
                'order_id' => $order->id,
                'customer_id' => $customer?->id,
                'discount_amount' => $discountAmount,
                'order_amount_before_discount' => $validation['order_amount_before_discount'],
                'order_amount_after_discount' => $validation['order_amount_after_discount'],
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'used_at' => now(),
            ]);

            // Increment usage count
            $discountCode->incrementUsage();

            // Update order total
            $order->update([
                'total_amount' => $validation['order_amount_after_discount']
            ]);

            DB::commit();

            return [
                'success' => true,
                'discount_code' => $discountCode,
                'discount_amount' => $discountAmount,
                'order_amount_before_discount' => $validation['order_amount_before_discount'],
                'order_amount_after_discount' => $validation['order_amount_after_discount'],
                'message' => 'تم تطبيق كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying discount code to order: ' . $e->getMessage(), [
                'code' => $code,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تطبيق كود الخصم',
                'error_code' => 'APPLICATION_ERROR'
            ];
        }
    }

    /**
     * Record discount code usage for an order.
     */
    public function recordDiscountCodeUsage(
        string $code,
        Order $order,
        ?Customer $customer = null
    ): array {
        try {
            $discountCode = DiscountCode::where('code', $code)->first();

            if (!$discountCode) {
                return [
                    'success' => false,
                    'message' => 'كود الخصم غير موجود',
                    'error_code' => 'CODE_NOT_FOUND'
                ];
            }

            // Record the usage
            DiscountCodeUsage::create([
                'discount_code_id' => $discountCode->id,
                'order_id' => $order->id,
                'customer_id' => $customer?->id,
                'discount_amount' => $order->discount_amount,
                'order_amount_before_discount' => $order->subtotal_amount,
                'order_amount_after_discount' => $order->total_amount,
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'used_at' => now(),
            ]);

            // Increment usage count
            $discountCode->incrementUsage();

            return [
                'success' => true,
                'message' => 'تم تسجيل استخدام كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error recording discount code usage: ' . $e->getMessage(), [
                'code' => $code,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل استخدام كود الخصم',
                'error_code' => 'RECORDING_ERROR'
            ];
        }
    }

    /**
     * Calculate order amount from items.
     */
    private function calculateOrderAmount(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $total += $product->price * $item['quantity'];
            }
        }

        return $total;
    }

    /**
     * Get discount code statistics.
     */
    public function getDiscountCodeStatistics(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);

        $totalCodes = DiscountCode::count();
        $activeCodes = DiscountCode::active()->count();
        $expiredCodes = DiscountCode::expired()->count();
        $usedCodes = DiscountCode::where('usage_count', '>', 0)->count();

        $totalUsage = DiscountCodeUsage::where('used_at', '>=', $startDate)->count();
        $totalDiscountAmount = DiscountCodeUsage::where('used_at', '>=', $startDate)->sum('discount_amount');

        $mostUsedCodes = DiscountCode::withCount('usage')
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();

        $usageByType = DiscountCode::select('type', DB::raw('SUM(usage_count) as total_usage'))
            ->groupBy('type')
            ->get();

        $recentUsage = DiscountCodeUsage::with(['discountCode', 'order'])
            ->where('used_at', '>=', $startDate)
            ->orderBy('used_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'overview' => [
                'total_codes' => $totalCodes,
                'active_codes' => $activeCodes,
                'expired_codes' => $expiredCodes,
                'used_codes' => $usedCodes,
            ],
            'period_stats' => [
                'total_usage' => $totalUsage,
                'total_discount_amount' => number_format($totalDiscountAmount, 3),
                'average_discount_per_usage' => $totalUsage > 0 ? number_format($totalDiscountAmount / $totalUsage, 3) : 0,
            ],
            'most_used_codes' => $mostUsedCodes,
            'usage_by_type' => $usageByType,
            'recent_usage' => $recentUsage,
        ];
    }

    /**
     * Create a new discount code.
     */
    public function createDiscountCode(array $data): array
    {
        try {
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = DiscountCode::generateUniqueCode();
            }

            // Check if code already exists
            if (DiscountCode::where('code', $data['code'])->exists()) {
                return [
                    'success' => false,
                    'message' => 'كود الخصم موجود بالفعل',
                    'error_code' => 'CODE_EXISTS'
                ];
            }

            $discountCode = DiscountCode::create($data);

            return [
                'success' => true,
                'data' => $discountCode,
                'message' => 'تم إنشاء كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error creating discount code: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء كود الخصم',
                'error_code' => 'CREATION_ERROR'
            ];
        }
    }

    /**
     * Update a discount code.
     */
    public function updateDiscountCode(int $id, array $data): array
    {
        try {
            $discountCode = DiscountCode::findOrFail($id);

            // Check if code already exists (excluding current)
            if (isset($data['code']) && DiscountCode::where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                return [
                    'success' => false,
                    'message' => 'كود الخصم موجود بالفعل',
                    'error_code' => 'CODE_EXISTS'
                ];
            }

            $discountCode->update($data);

            return [
                'success' => true,
                'data' => $discountCode,
                'message' => 'تم تحديث كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error updating discount code: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث كود الخصم',
                'error_code' => 'UPDATE_ERROR'
            ];
        }
    }

    /**
     * Delete a discount code.
     */
    public function deleteDiscountCode(int $id): array
    {
        try {
            $discountCode = DiscountCode::findOrFail($id);

            // Check if code has been used
            if ($discountCode->usage_count > 0) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن حذف كود الخصم المستخدم',
                    'error_code' => 'CODE_IN_USE'
                ];
            }

            $discountCode->delete();

            return [
                'success' => true,
                'message' => 'تم حذف كود الخصم بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting discount code: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف كود الخصم',
                'error_code' => 'DELETE_ERROR'
            ];
        }
    }

    /**
     * Get discount codes for admin management.
     */
    public function getDiscountCodesForAdmin(array $filters = []): array
    {
        $query = DiscountCode::withCount('usage');

        // Apply filters
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'used':
                    $query->where('usage_count', '>', 0);
                    break;
                case 'unused':
                    $query->where('usage_count', 0);
                    break;
            }
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $discountCodes = $query->orderBy('created_at', 'desc')->paginate(15);

        return [
            'success' => true,
            'data' => $discountCodes,
            'message' => 'تم جلب أكواد الخصم بنجاح'
        ];
    }
}
