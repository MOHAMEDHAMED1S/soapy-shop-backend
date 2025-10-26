<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $notificationService;
    protected $whatsappService;

    public function __construct(
        PaymentService $paymentService, 
        NotificationService $notificationService,
        WhatsAppService $whatsappService
    )
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
        $this->whatsappService = $whatsappService;
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
                
                // Deduct inventory for order items
                $order->load('orderItems.product');
                $order->deductInventory();
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
            // Get order_id from URL (sent in CallBackUrl)
            $orderId = $request->get('order_id');
            
            // Log the request for debugging
            Log::info('MyFatoorah Success Callback', [
                'all_params' => $request->all(),
                'order_id' => $orderId
            ]);

            if (!$orderId) {
                Log::error('Missing order_id in callback URL', [
                    'all_params' => $request->all()
                ]);
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=missing_order_id');
            }

            $order = Order::with('payment')->find($orderId);

            if (!$order || !$order->payment) {
                Log::error('Order or payment not found', [
                    'order_id' => $orderId
                ]);
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=order_not_found');
            }

            // Get invoice_reference from stored payment record
            $invoiceReference = $order->payment->invoice_reference;
            
            if (!$invoiceReference) {
                Log::error('Invoice reference not found in payment record', [
                    'order_id' => $orderId,
                    'payment_id' => $order->payment->id
                ]);
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=invoice_not_found');
            }

            // Verify payment with MyFatoorah using stored invoice_reference
            $paymentStatus = $this->paymentService->verifyPayment($invoiceReference);
            
            if (!$paymentStatus['success']) {
                Log::error('Failed to verify payment with MyFatoorah', [
                    'order_id' => $orderId,
                    'invoice_reference' => $invoiceReference,
                    'error' => $paymentStatus['error'] ?? 'Unknown error'
                ]);
                return redirect()->away(config('app.frontend_url') . '/payment/failure?error=verification_failed');
            }

            $invoiceData = $paymentStatus['data'];
            
            Log::info('Processing payment callback', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'invoice_reference' => $invoiceReference,
                'invoice_status' => $invoiceData['InvoiceStatus'] ?? 'unknown'
            ]);

            DB::beginTransaction();

            // Update payment status
            $order->payment->update([
                'status' => $invoiceData['InvoiceStatus'] ?? 'unknown',
                'response_raw' => array_merge(
                    $order->payment->response_raw ?? [],
                    ['callback_response' => $invoiceData]
                )
            ]);

            // Update order status based on payment status
            $invoiceStatus = $invoiceData['InvoiceStatus'] ?? 'unknown';
            
            if ($invoiceStatus === 'Paid') {
                $order->update(['status' => 'paid']);
                
                // Deduct inventory for order items
                $order->load('orderItems.product');
                $order->deductInventory();
            } elseif ($invoiceStatus === 'Failed') {
                $order->update(['status' => 'pending']);
            }

            DB::commit();
            
            // Send notifications after commit (don't let notification failures stop the process)
            if ($invoiceStatus === 'Paid') {
                // 1. Send Email Notification
                try {
                    $this->notificationService->createOrderNotification($order, 'order_paid');
                } catch (\Exception $e) {
                    Log::warning('Failed to send order notification email', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue execution - don't fail the callback because of email
                }

                // 2. Send WhatsApp Notification to Admin
                try {
                    $this->whatsappService->notifyAdminNewPaidOrder($order);
                } catch (\Exception $e) {
                    Log::warning('Failed to send WhatsApp notification to admin', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue execution - don't fail the callback because of WhatsApp
                }

                // 3. Send WhatsApp Notification to Delivery
                try {
                    $this->whatsappService->notifyDeliveryNewPaidOrder($order);
                } catch (\Exception $e) {
                    Log::warning('Failed to send WhatsApp notification to delivery', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue execution - don't fail the callback because of WhatsApp
                }
            }

            // Redirect to frontend success page
            return redirect()->away(config('app.frontend_url') . '/payment/success?order=' . $order->order_number . '&status=' . $invoiceStatus);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment success callback error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $request->get('order_id')
            ]);
            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=processing_error');
        }
    }

    /**
     * Handle failed payment callback from MyFatoorah
     */
    public function handleFailureCallback(Request $request)
    {
        try {
            // Get order_id from URL (sent in ErrorUrl)
            $orderId = $request->get('order_id');
            $error = $request->get('error', 'payment_failed');
            
            Log::info('MyFatoorah Failure Callback', [
                'all_params' => $request->all(),
                'order_id' => $orderId
            ]);

            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    // Update order status to pending if it was awaiting payment
                    if ($order->status === 'awaiting_payment') {
                        $order->update(['status' => 'pending']);
                    }
                    
                    Log::info('Payment failed for order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number
                    ]);
                    
                    return redirect()->away(config('app.frontend_url') . '/payment/failure?order=' . $order->order_number . '&error=' . $error);
                }
            }

            Log::warning('Could not identify order for failed payment', [
                'order_id' => $orderId,
                'all_params' => $request->all()
            ]);

            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=' . $error);

        } catch (\Exception $e) {
            Log::error('Payment failure callback error: ' . $e->getMessage());
            return redirect()->away(config('app.frontend_url') . '/payment/failure?error=processing_error');
        }
    }
}
