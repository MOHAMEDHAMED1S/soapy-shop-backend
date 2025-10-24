<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Services\CustomerService;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $customerService;
    protected $discountService;

    public function __construct(CustomerService $customerService, DiscountService $discountService)
    {
        $this->customerService = $customerService;
        $this->discountService = $discountService;
    }
    /**
     * Create a new order (Checkout process)
     */
    public function createOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'shipping_address' => 'required|array',
                'shipping_address.street' => 'required|string|max:255',
                'shipping_address.city' => 'required|string|max:100',
                'shipping_address.governorate' => 'required|string|max:100',
                'shipping_address.postal_code' => 'nullable|string|max:20',
                'shipping_address.notes' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:1000',
                'discount_code' => 'nullable|string|max:50',
                'shipping_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Validate products availability and calculate subtotal
            $subtotalAmount = 0;
            $orderItems = [];
            $productSnapshots = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('is_available', true)
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Product ID {$item['product_id']} is not available"
                    ], 400);
                }

                // Use discounted_price if available, otherwise use regular price
                $finalPrice = $product->discounted_price ?? $product->price;
                $itemTotal = $finalPrice * $item['quantity'];
                $subtotalAmount += $itemTotal;

                // Create product snapshot for order history
                $productSnapshots[] = [
                    'product_id' => $product->id,
                    'product_price' => $finalPrice, // Use final price (after product discount)
                    'quantity' => $item['quantity'],
                    'product_snapshot' => [
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'description' => $product->description,
                        'short_description' => $product->short_description,
                        'price' => $product->price, // Original price for reference
                        'discounted_price' => $finalPrice, // Final price after discount
                        'has_discount' => $product->has_discount,
                        'discount_percentage' => $product->discount_percentage,
                        'currency' => $product->currency,
                        'images' => $product->images,
                        'meta' => $product->meta,
                        'category' => $product->category->name ?? null,
                    ]
                ];
            }

            // Handle discount code validation and calculation
            $discountAmount = 0;
            $discountCode = null;
            $freeShipping = false;

            if ($request->has('discount_code') && $request->discount_code) {
                $discountResult = $this->discountService->validateAndApplyDiscountCode(
                    $request->discount_code,
                    [
                        'items' => $request->items,
                        'customer_phone' => $request->customer_phone
                    ]
                );

                if (!$discountResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $discountResult['message'],
                        'error_code' => $discountResult['error_code'] ?? 'DISCOUNT_ERROR'
                    ], 400);
                }

                $discountCode = $discountResult['discount_code'];
                $discountAmount = $discountResult['discount_amount'];
                $freeShipping = $discountCode->type === 'free_shipping';
            }

            // Calculate shipping amount
            $shippingAmount = $request->shipping_amount ?? 0;
            if ($freeShipping) {
                $shippingAmount = 0;
            }

            // Calculate final total
            $totalAmount = $subtotalAmount - $discountAmount + $shippingAmount;

            // Find or create customer
            $customer = $this->customerService->findOrCreateCustomerForOrder([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'shipping_address' => $request->shipping_address,
            ]);

            // Generate unique order number
            $orderNumber = Order::generateOrderNumber();
            
            // Generate tracking number
            $trackingNumber = 'TRK-' . strtoupper(substr(md5($orderNumber . time()), 0, 8));

            // Create the order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'shipping_address' => $request->shipping_address,
                'total_amount' => $totalAmount,
                'currency' => 'KWD',
                'status' => 'pending',
                'notes' => $request->notes,
                'discount_code' => $discountCode?->code,
                'discount_amount' => $discountAmount,
                'subtotal_amount' => $subtotalAmount,
                'shipping_amount' => $shippingAmount,
                'free_shipping' => $freeShipping,
                'tracking_number' => $orderNumber,
            ]);

            // Create order items
            foreach ($productSnapshots as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'product_price' => $itemData['product_price'],
                    'quantity' => $itemData['quantity'],
                    'product_snapshot' => $itemData['product_snapshot'],
                ]);
            }

            // Record discount code usage if applicable
            if ($discountCode) {
                $this->discountService->recordDiscountCodeUsage(
                    $discountCode->code,
                    $order,
                    $customer
                );
            }

            $order->load(['orderItems.product', 'payment', 'discountCode']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'subtotal_amount' => $subtotalAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                    'currency' => 'KWD',
                    'discount_code' => $discountCode?->code,
                    'free_shipping' => $freeShipping,
                    'tracking_number' => $trackingNumber,
                    'next_step' => 'payment_required'
                ],
                'message' => 'Order created successfully. Proceed to payment.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details by order number and phone verification
     */
    public function show(Request $request, string $orderNumber)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is required for order verification'
                ], 422);
            }

            $order = Order::where('order_number', $orderNumber)
                ->where('customer_phone', $request->phone)
                ->with(['orderItems.product', 'payment'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or phone number does not match'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate discount code for checkout
     */
    public function validateDiscount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'discount_code' => 'required|string|max:50',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1|max:10',
                'customer_phone' => 'nullable|string|max:20',
                'shipping_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate order total first
            $subtotalAmount = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('is_available', true)
                    ->with('category')
                    ->first();

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product ID {$item['product_id']} is not available"
                    ], 400);
                }

                // Use discounted_price if available, otherwise use regular price
                $finalPrice = $product->discounted_price ?? $product->price;
                $itemTotal = $finalPrice * $item['quantity'];
                $subtotalAmount += $itemTotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'item_total' => $itemTotal,
                    'price_used' => $finalPrice,
                ];
            }

            // Validate discount code
            $discountResult = $this->discountService->validateAndApplyDiscountCode(
                $request->discount_code,
                [
                    'items' => $request->items,
                    'customer_phone' => $request->customer_phone ?? null
                ]
            );

            if (!$discountResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $discountResult['message'],
                    'error_code' => $discountResult['error_code'] ?? 'DISCOUNT_ERROR'
                ], 400);
            }

            $discountCode = $discountResult['discount_code'];
            $discountAmount = $discountResult['discount_amount'];
            $freeShipping = $discountCode->type === 'free_shipping';

            // Calculate shipping amount
            $shippingAmount = $request->shipping_amount ?? 0;
            if ($freeShipping) {
                $shippingAmount = 0;
            }

            // Calculate final total
            $totalAmount = $subtotalAmount - $discountAmount + $shippingAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'discount_code' => [
                        'code' => $discountCode->code,
                        'name' => $discountCode->name,
                        'description' => $discountCode->description,
                        'type' => $discountCode->type,
                        'value' => $discountCode->value,
                        'minimum_order_amount' => $discountCode->minimum_order_amount,
                        'maximum_discount_amount' => $discountCode->maximum_discount_amount,
                        'expires_at' => $discountCode->expires_at,
                        'usage_count' => $discountCode->usage_count,
                        'usage_limit' => $discountCode->usage_limit,
                        'remaining_usage' => $discountCode->remaining_usage,
                    ],
                    'order_summary' => [
                        'subtotal_amount' => $subtotalAmount,
                        'discount_amount' => $discountAmount,
                        'shipping_amount' => $shippingAmount,
                        'total_amount' => $totalAmount,
                        'currency' => 'KWD',
                        'free_shipping' => $freeShipping,
                    ],
                    'items' => $items,
                ],
                'message' => 'كود الخصم صالح ويمكن استخدامه'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating discount code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate order total without creating order (for preview)
     */
    public function calculateTotal(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1|max:10',
                'discount_code' => 'nullable|string|max:50',
                'shipping_amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subtotalAmount = 0;
            $items = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('is_available', true)
                    ->with('category')
                    ->first();

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product ID {$item['product_id']} is not available"
                    ], 400);
                }

                // Use discounted_price if available, otherwise use regular price
                $finalPrice = $product->discounted_price ?? $product->price;
                $itemTotal = $finalPrice * $item['quantity'];
                $subtotalAmount += $itemTotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'item_total' => $itemTotal,
                    'price_used' => $finalPrice,
                ];
            }

            // Handle discount code validation and calculation
            $discountAmount = 0;
            $discountCode = null;
            $freeShipping = false;

            if ($request->has('discount_code') && $request->discount_code) {
                $discountResult = $this->discountService->validateAndApplyDiscountCode(
                    $request->discount_code,
                    [
                        'items' => $request->items,
                        'customer_phone' => $request->customer_phone ?? null
                    ]
                );

                if (!$discountResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $discountResult['message'],
                        'error_code' => $discountResult['error_code'] ?? 'DISCOUNT_ERROR'
                    ], 400);
                }

                $discountCode = $discountResult['discount_code'];
                $discountAmount = $discountResult['discount_amount'];
                $freeShipping = $discountCode->type === 'free_shipping';
            }

            // Calculate shipping amount
            $shippingAmount = $request->shipping_amount ?? 0;
            if ($freeShipping) {
                $shippingAmount = 0;
            }

            // Calculate final total
            $totalAmount = $subtotalAmount - $discountAmount + $shippingAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'subtotal_amount' => $subtotalAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                    'currency' => 'KWD',
                    'discount_code' => $discountCode?->code,
                    'free_shipping' => $freeShipping,
                ],
                'message' => 'Order total calculated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating order total',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply discount code to an existing order
     */
    public function applyDiscountCode(Request $request, string $orderNumber)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string',
                'discount_code' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::where('order_number', $orderNumber)
                ->where('customer_phone', $request->phone)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or phone number does not match'
                ], 404);
            }

            // Check if order can be modified
            if (!in_array($order->status, ['pending', 'awaiting_payment'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot apply discount code to this order status'
                ], 400);
            }

            // Check if order already has a discount code
            if ($order->discount_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already has a discount code applied'
                ], 400);
            }

            DB::beginTransaction();

            // Get order items for validation
            $orderItems = $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity
                ];
            })->toArray();

            // Validate discount code
            $discountResult = $this->discountService->validateAndApplyDiscountCode(
                $request->discount_code,
                [
                    'items' => $orderItems,
                    'customer_phone' => $order->customer_phone
                ]
            );

            if (!$discountResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $discountResult['message'],
                    'error_code' => $discountResult['error_code'] ?? 'DISCOUNT_ERROR'
                ], 400);
            }

            $discountCode = $discountResult['discount_code'];
            $discountAmount = $discountResult['discount_amount'];
            $freeShipping = $discountCode->type === 'free_shipping';

            // Update order with discount
            $order->update([
                'discount_code' => $discountCode->code,
                'discount_amount' => $discountAmount,
                'subtotal_amount' => $order->total_amount, // Current total becomes subtotal
                'total_amount' => $order->total_amount - $discountAmount,
                'free_shipping' => $freeShipping,
                'shipping_amount' => $freeShipping ? 0 : $order->shipping_amount,
            ]);

            // Record discount code usage
            $this->discountService->recordDiscountCodeUsage(
                $discountCode->code,
                $order,
                $order->customer
            );

            $order->load(['orderItems.product', 'payment', 'discountCode']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'discount_code' => $discountCode->code,
                    'discount_amount' => $discountAmount,
                    'subtotal_amount' => $order->subtotal_amount,
                    'total_amount' => $order->total_amount,
                    'free_shipping' => $freeShipping,
                ],
                'message' => 'Discount code applied successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error applying discount code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track an order by order number only
     */
    public function trackOrder(Request $request, string $orderNumber)
    {
        try {
            // Debug logging
            \Illuminate\Support\Facades\Log::info('trackOrder called with orderNumber: ' . $orderNumber);
            
            $order = Order::with([
                'orderItems.product.category',
                'payment',
                'customer'
            ])->where('order_number', $orderNumber)
              ->orWhere('tracking_number', $orderNumber)
              ->first();
              
            \Illuminate\Support\Facades\Log::info('Order query result: ' . ($order ? 'Found' : 'Not found'));
            if ($order) {
                \Illuminate\Support\Facades\Log::info('Order details: ID=' . $order->id . ', order_number=' . $order->order_number . ', tracking_number=' . $order->tracking_number);
            }

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Get order timeline
            $timeline = $this->getOrderTimeline($order);

            // Get order status information
            $statusInfo = $this->getOrderStatusInfo($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'timeline' => $timeline,
                    'status_info' => $statusInfo
                ],
                'message' => 'Order tracking information retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error tracking order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details by order number only
     */
    public function getOrderDetails(Request $request, string $orderNumber)
    {
        try {
            $order = Order::with([
                'orderItems.product.category',
                'payment',
                'customer',
                'discountCode'
            ])->where('order_number', $orderNumber)
              ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order (only if status is pending)
     */
    public function cancel(Request $request, string $orderNumber)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is required for order verification'
                ], 422);
            }

            $order = Order::where('order_number', $orderNumber)
                ->where('customer_phone', $request->phone)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or phone number does not match'
                ], 404);
            }

            if (!in_array($order->status, ['pending', 'awaiting_payment'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled. Current status: ' . $order->status
                ], 400);
            }

            $order->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order timeline for tracking
     */
    private function getOrderTimeline($order)
    {
        $timeline = [];
        
        // Order created - always completed
        $timeline[] = [
            'status' => 'created',
            'title' => 'تم إنشاء الطلب',
            'description' => 'تم إنشاء الطلب بنجاح',
            'date' => $order->created_at,
            'completed' => true
        ];

        // Payment status based on order status
        if ($order->status === 'paid' || $order->status === 'shipped' || $order->status === 'delivered') {
            // Order is paid (if status is paid, shipped, or delivered)
            $timeline[] = [
                'status' => 'paid',
                'title' => 'تم الدفع',
                'description' => 'تم تأكيد الدفع بنجاح',
                'date' => $order->payment ? $order->payment->updated_at : $order->updated_at,
                'completed' => true
            ];
        } else if ($order->status === 'pending' || $order->status === 'awaiting_payment') {
            // Order is pending payment
            $timeline[] = [
                'status' => 'payment_pending',
                'title' => 'في انتظار الدفع',
                'description' => 'يرجى إتمام عملية الدفع',
                'date' => $order->created_at,
                'completed' => false
            ];
        }

        // Order processing status
        if ($order->status === 'paid') {
            $timeline[] = [
                'status' => 'processing',
                'title' => 'جاري المعالجة',
                'description' => 'جاري معالجة الطلب',
                'date' => $order->updated_at,
                'completed' => true
            ];
        } else if ($order->status === 'shipped' || $order->status === 'delivered') {
            $timeline[] = [
                'status' => 'processing',
                'title' => 'جاري المعالجة',
                'description' => 'تم معالجة الطلب',
                'date' => $order->updated_at,
                'completed' => true
            ];
        } else if ($order->status === 'pending' || $order->status === 'awaiting_payment') {
            $timeline[] = [
                'status' => 'processing',
                'title' => 'جاري المعالجة',
                'description' => 'جاري معالجة الطلب',
                'date' => $order->updated_at,
                'completed' => false
            ];
        }

        // Shipping status
        if ($order->status === 'shipped' || $order->status === 'delivered') {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'تم الشحن',
                'description' => $order->tracking_number ? "رقم التتبع: {$order->tracking_number}" : 'تم شحن الطلب',
                'date' => $order->shipping_date ?? $order->updated_at,
                'completed' => true
            ];
        } else if ($order->status === 'paid') {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'تم الشحن',
                'description' => 'سيتم شحن الطلب قريباً',
                'date' => $order->updated_at,
                'completed' => false
            ];
        }

        // Delivery status
        if ($order->status === 'delivered') {
            $timeline[] = [
                'status' => 'delivered',
                'title' => 'تم التسليم',
                'description' => 'تم تسليم الطلب بنجاح',
                'date' => $order->delivery_date ?? $order->updated_at,
                'completed' => true
            ];
        } else if ($order->status === 'shipped') {
            $timeline[] = [
                'status' => 'delivered',
                'title' => 'تم التسليم',
                'description' => 'جاري توصيل الطلب',
                'date' => $order->updated_at,
                'completed' => false
            ];
        }

        // Cancellation status
        if ($order->status === 'cancelled') {
            $timeline[] = [
                'status' => 'cancelled',
                'title' => 'تم الإلغاء',
                'description' => 'تم إلغاء الطلب',
                'date' => $order->updated_at,
                'completed' => true
            ];
        }

        return $timeline;
    }

    /**
     * Get order status information
     */
    private function getOrderStatusInfo($order)
    {
        $statusMap = [
            'pending' => [
                'title' => 'في الانتظار',
                'description' => 'جاري معالجة الطلب',
                'color' => 'yellow',
                'icon' => 'clock'
            ],
            'awaiting_payment' => [
                'title' => 'في انتظار الدفع',
                'description' => 'يرجى إتمام عملية الدفع',
                'color' => 'orange',
                'icon' => 'credit-card'
            ],
            'paid' => [
                'title' => 'تم الدفع',
                'description' => 'تم تأكيد الدفع بنجاح',
                'color' => 'green',
                'icon' => 'check-circle'
            ],
            'shipped' => [
                'title' => 'تم الشحن',
                'description' => 'تم شحن الطلب',
                'color' => 'blue',
                'icon' => 'truck'
            ],
            'delivered' => [
                'title' => 'تم التسليم',
                'description' => 'تم تسليم الطلب بنجاح',
                'color' => 'green',
                'icon' => 'package-check'
            ],
            'cancelled' => [
                'title' => 'ملغي',
                'description' => 'تم إلغاء الطلب',
                'color' => 'red',
                'icon' => 'x-circle'
            ]
        ];

        return $statusMap[$order->status] ?? [
            'title' => 'غير محدد',
            'description' => 'حالة غير معروفة',
            'color' => 'gray',
            'icon' => 'help-circle'
        ];
    }

    /**
     * Create a test order for debugging
     */
    public function createTestOrder()
    {
        try {
            // Create a test order
            $orderNumber = Order::generateOrderNumber();
            $trackingNumber = 'TRK-' . strtoupper(substr(md5($orderNumber . time()), 0, 8));
            
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => 1, // Assuming customer ID 1 exists
                'customer_name' => 'Test Customer',
                'customer_phone' => '+96512345678',
                'customer_email' => 'test@example.com',
                'shipping_address' => [
                    'street' => 'Test Street',
                    'city' => 'Test City',
                    'governorate' => 'Test Governorate'
                ],
                'total_amount' => 25.500,
                'currency' => 'KWD',
                'status' => 'pending',
                'notes' => 'Test order for debugging',
                'tracking_number' => $trackingNumber,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'order_number' => $orderNumber,
                    'tracking_number' => $trackingNumber
                ],
                'message' => 'Test order created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating test order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all orders for debugging
     */
    public function listOrders()
    {
        try {
            $orders = Order::select('id', 'order_number', 'tracking_number', 'status', 'created_at')
                          ->orderBy('created_at', 'desc')
                          ->limit(10)
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
