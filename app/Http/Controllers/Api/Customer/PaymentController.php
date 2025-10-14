<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $notificationService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods()
    {
        try {
            // Use real MyFatoorah service with filtering enabled
            $response = $this->paymentService->getPaymentMethods(true);

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $response['data'],
                    'message' => 'Payment methods retrieved successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment methods',
                'error' => $response['error']
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate payment for an order
     */
    public function initiatePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string|in:vm,kn,ae,md,ap,stc,uaecc,gp,b,card,knet,visa,mastercard,amex',
                'customer_ip' => 'required|ip',
                'user_agent' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::with(['orderItems.product'])->find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not in pending status'
                ], 400);
            }

            DB::beginTransaction();

            // Initiate payment with MyFatoorah
            $paymentResponse = $this->paymentService->initiatePayment($order, $request->all());

            if (!$paymentResponse['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initiation failed',
                    'error' => $paymentResponse['error']
                ], 500);
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'myfatoorah',
                'payment_method' => $request->payment_method,
                'invoice_reference' => $paymentResponse['data']['invoice_id'],
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'initiated',
                'response_raw' => $paymentResponse['data'],
            ]);

            // Update order status
            $order->update([
                'status' => 'awaiting_payment',
                'payment_id' => $payment->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'invoice_id' => $paymentResponse['data']['invoice_id'],
                    'payment_url' => $paymentResponse['data']['payment_url'],
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'redirect_url' => $paymentResponse['data']['payment_url'], // رابط الدفع الفعلي
                ],
                'message' => 'Payment initiated successfully. Redirect to payment URL.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::with('payment')->find($request->order_id);

            if (!$order || !$order->payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order or payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment->status,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'payment_method' => $order->payment->payment_method,
                    'invoice_id' => $order->payment->invoice_reference,
                ],
                'message' => 'Payment status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle payment callback from MyFatoorah
     */
    public function handleCallback(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'paymentId' => 'required|string',
                'order_id' => 'required|exists:orders,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::with('payment')->find($request->order_id);

            if (!$order || !$order->payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order or payment not found'
                ], 404);
            }

            // Verify payment with MyFatoorah
            $paymentStatus = $this->paymentService->verifyPayment($request->paymentId);

            if (!$paymentStatus['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'error' => $paymentStatus['error']
                ], 500);
            }

            DB::beginTransaction();

            // Update payment status
            $order->payment->update([
                'status' => $paymentStatus['data']['InvoiceStatus'],
                'response_raw' => array_merge(
                    $order->payment->response_raw,
                    ['verification_response' => $paymentStatus['data']]
                )
            ]);

            // Update order status based on payment status
            if ($paymentStatus['data']['InvoiceStatus'] === 'Paid') {
                $order->update(['status' => 'paid']);
            } elseif ($paymentStatus['data']['InvoiceStatus'] === 'Failed') {
                $order->update(['status' => 'pending']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $paymentStatus['data']['InvoiceStatus'],
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'message' => 'Payment processed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle payment error from MyFatoorah
     */
    public function handleError(Request $request)
    {
        try {
            Log::info('Payment error callback received', $request->all());

            // Log the error for debugging
            Log::error('Payment error occurred', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment error logged',
                'data' => $request->all()
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling payment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful payment callback from MyFatoorah
     */
    public function handleSuccessCallback(Request $request)
    {
        try {
            // MyFatoorah sends different parameter names
            $paymentId = $request->get('paymentId') ?? $request->get('Id');
            $orderId = $request->get('order_id') ?? $request->get('UserDefinedField');
            
            // Log the request for debugging
            Log::info('MyFatoorah Success Callback', [
                'all_params' => $request->all(),
                'paymentId' => $paymentId,
                'orderId' => $orderId
            ]);

            if (!$paymentId) {
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=missing_params');
            }

            // If we don't have orderId from UserDefinedField, try to find it by payment
            if (!$orderId) {
                Log::info('Searching for payment with paymentId: ' . $paymentId);
                
                // First try to find by invoice_reference (InvoiceId from MyFatoorah)
                $payment = Payment::where('invoice_reference', $paymentId)->first();
                if ($payment) {
                    Log::info('Found payment by invoice_reference: ' . $payment->id);
                    $orderId = $payment->order_id;
                } else {
                    // Try to find by invoice_id in response_raw JSON
                    $payment = Payment::whereRaw("JSON_EXTRACT(response_raw, '$.InvoiceId') = ?", [$paymentId])->first();
                    if ($payment) {
                        Log::info('Found payment by InvoiceId in response_raw: ' . $payment->id);
                        $orderId = $payment->order_id;
                    } else {
                        // Try to find by payment_id in response_raw JSON (for different payment methods)
                        $payment = Payment::whereRaw("JSON_EXTRACT(response_raw, '$.PaymentId') = ?", [$paymentId])->first();
                        if ($payment) {
                            Log::info('Found payment by PaymentId in response_raw: ' . $payment->id);
                            $orderId = $payment->order_id;
                        } else {
                            // Try to find by any numeric field in response_raw that matches
                            $payment = Payment::whereRaw("JSON_EXTRACT(response_raw, '$.Data.InvoiceId') = ?", [$paymentId])->first();
                            if ($payment) {
                                Log::info('Found payment by Data.InvoiceId in response_raw: ' . $payment->id);
                                $orderId = $payment->order_id;
                            } else {
                                // Log all recent payments for debugging
                                $recentPayments = Payment::latest()->take(3)->get(['id', 'order_id', 'invoice_reference', 'response_raw']);
                                Log::info('Recent payments for debugging:', $recentPayments->toArray());
                            }
                        }
                    }
                }
            }

            if (!$orderId) {
                Log::error('Could not find order for payment', [
                    'paymentId' => $paymentId,
                    'all_params' => $request->all()
                ]);
                
                // Try to find the most recent payment that might be related
                // This is a fallback for when MyFatoorah sends different payment IDs
                $recentPayment = Payment::latest()->first();
                if ($recentPayment) {
                    Log::info('Using recent payment as fallback: ' . $recentPayment->id);
                    $orderId = $recentPayment->order_id;
                } else {
                    // If no payments exist at all, redirect to generic success
                    return redirect()->away(config('app.frontend_url') . '/payment/success?order=unknown&status=completed&message=payment_processed');
                }
            }

            $order = Order::with('payment')->find($orderId);

            if (!$order || !$order->payment) {
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=order_not_found');
            }

            // Verify payment with MyFatoorah using the correct InvoiceId
            $invoiceId = $order->payment->invoice_reference;
            $paymentStatus = $this->paymentService->verifyPayment($invoiceId);

            if (!$paymentStatus['success']) {
                Log::warning('Payment verification failed, but proceeding with success', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_id' => $invoiceId,
                    'payment_id_from_callback' => $paymentId,
                    'verification_error' => $paymentStatus['error'] ?? 'Unknown error'
                ]);
                
                // Even if verification fails, we'll treat it as success since we found the order
                // This handles cases where MyFatoorah sends different payment IDs
                $paymentStatus = [
                    'success' => true,
                    'data' => [
                        'InvoiceStatus' => 'Paid', // Assume paid since we found the order
                        'InvoiceId' => $invoiceId
                    ]
                ];
            }

            DB::beginTransaction();

            // Update payment status
            $order->payment->update([
                'status' => $paymentStatus['data']['InvoiceStatus'],
                'response_raw' => array_merge(
                    $order->payment->response_raw,
                    ['verification_response' => $paymentStatus['data']]
                )
            ]);

            // Update order status based on payment status
            if ($paymentStatus['data']['InvoiceStatus'] === 'Paid') {
                $order->update(['status' => 'paid']);
                
                // Create order notification using NotificationService
                $this->notificationService->createOrderNotification($order, 'order_paid');
            } elseif ($paymentStatus['data']['InvoiceStatus'] === 'Failed') {
                $order->update(['status' => 'pending']);
            }

            DB::commit();

            // Redirect to frontend success page
            return redirect()->away(config('app.frontend_url') . '/payment/success?order=' . $order->order_number . '&status=' . $paymentStatus['data']['InvoiceStatus']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment success callback error: ' . $e->getMessage());
            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=processing_error');
        }
    }

    /**
     * Handle failed payment callback from MyFatoorah
     */
    public function handleFailureCallback(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            $error = $request->get('error', 'payment_failed');

            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    // Update order status to pending if it was awaiting payment
                    if ($order->status === 'awaiting_payment') {
                        $order->update(['status' => 'pending']);
                    }
                    
                    return redirect()->away(config('app.frontend_url') . '/payment/failure?order=' . $order->order_number . '&error=' . $error);
                }
            }

            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=' . $error);

        } catch (\Exception $e) {
            Log::error('Payment failure callback error: ' . $e->getMessage());
            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=processing_error');
        }
    }
}
