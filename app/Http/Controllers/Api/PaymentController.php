<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initiate payment with MyFatoorah
     */
    public function initiate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string|in:card,knet,visa,mastercard,amex',
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

            // Prepare MyFatoorah payment data
            $paymentData = $this->prepareMyFatoorahData($order, $request);

            // Call MyFatoorah API
            $response = $this->callMyFatoorahAPI($paymentData);

            if (!$response['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment initiation failed',
                    'error' => $response['error']
                ], 500);
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'myfatoorah',
                'payment_method' => $request->payment_method,
                'invoice_reference' => $response['data']['InvoiceId'],
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => 'initiated',
                'response_raw' => $response['data'],
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
                    'invoice_id' => $response['data']['InvoiceId'],
                    'payment_url' => $response['data']['InvoiceURL'],
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'message' => 'Payment initiated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute payment (callback from MyFatoorah)
     */
    public function execute(Request $request)
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
            $paymentStatus = $this->verifyPaymentWithMyFatoorah($request->paymentId);

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

                // Create admin notification
                AdminNotification::create([
                    'type' => 'payment_received',
                    'payload' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'amount' => $order->total_amount,
                        'payment_method' => $order->payment->payment_method,
                        'invoice_id' => $order->payment->invoice_reference,
                    ],
                    'created_at' => now()
                ]);
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
            Log::error('Payment execution error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MyFatoorah webhook handler
     */
    public function webhook(Request $request)
    {
        try {
            // Log the webhook
            $webhookLog = WebhookLog::create([
                'provider' => 'myfatoorah',
                'payload' => $request->all(),
                'received_at' => now(),
                'processed' => false,
            ]);

            // Verify webhook signature (if MyFatoorah provides one)
            if (!$this->verifyWebhookSignature($request)) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => 'Invalid webhook signature'
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = $request->all();

            // Find the payment by invoice reference
            $payment = Payment::where('invoice_reference', $data['InvoiceId'] ?? '')
                ->with('order')
                ->first();

            if (!$payment) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => 'Payment not found for invoice: ' . ($data['InvoiceId'] ?? 'unknown')
                ]);
                
                return response()->json(['error' => 'Payment not found'], 404);
            }

            DB::beginTransaction();

            // Update payment status
            $payment->update([
                'status' => $data['InvoiceStatus'] ?? 'unknown',
                'response_raw' => array_merge(
                    $payment->response_raw,
                    ['webhook_data' => $data]
                )
            ]);

            // Update order status
            $order = $payment->order;
            if ($data['InvoiceStatus'] === 'Paid') {
                $order->update(['status' => 'paid']);

                // Create admin notification
                AdminNotification::create([
                    'type' => 'payment_received',
                    'payload' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'amount' => $order->total_amount,
                        'payment_method' => $payment->payment_method,
                        'invoice_id' => $payment->invoice_reference,
                        'webhook_source' => true,
                    ],
                    'created_at' => now()
                ]);
            } elseif ($data['InvoiceStatus'] === 'Failed') {
                $order->update(['status' => 'pending']);
            }

            $webhookLog->update([
                'processed' => true,
                'processing_notes' => 'Successfully processed webhook for order: ' . $order->order_number
            ]);

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook processing error: ' . $e->getMessage());
            
            if (isset($webhookLog)) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => 'Error: ' . $e->getMessage()
                ]);
            }
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Prepare MyFatoorah payment data
     */
    private function prepareMyFatoorahData($order, $request)
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'ItemName' => $item->product_snapshot['title'],
                'Quantity' => $item->quantity,
                'UnitPrice' => $item->product_price,
                'Weight' => 0,
                'Width' => 0,
                'Height' => 0,
                'Depth' => 0,
            ];
        }

        return [
            'InvoiceAmount' => $order->total_amount,
            'CurrencyIso' => $order->currency,
            'CustomerName' => $order->customer_name,
            'CustomerEmail' => $order->customer_email,
            'CustomerMobile' => $order->customer_phone,
            'CustomerReference' => $order->order_number,
            'UserDefinedField' => $order->id,
            'CallBackUrl' => config('app.url') . '/api/v1/payments/callback',
            'ErrorUrl' => config('app.url') . '/api/v1/payments/error',
            'Language' => 'ar',
            'DisplayCurrencyIso' => $order->currency,
            'MobileCountryCode' => '+965',
            'CustomerAddress' => [
                'Block' => '',
                'Street' => $order->shipping_address['street'] ?? '',
                'HouseBuildingNo' => '',
                'Address' => $order->shipping_address['street'] ?? '',
                'AddressInstructions' => $order->shipping_address['notes'] ?? '',
            ],
            'InvoiceItems' => $items,
        ];
    }

    /**
     * Call MyFatoorah API
     */
    private function callMyFatoorahAPI($data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.myfatoorah.base_url') . '/v2/SendPayment', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['IsSuccess']) {
                    return [
                        'success' => true,
                        'data' => $responseData['Data']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['Message'] ?? 'Unknown error'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment with MyFatoorah
     */
    private function verifyPaymentWithMyFatoorah($paymentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.myfatoorah.base_url') . '/v2/GetPaymentStatus', [
                'Key' => $paymentId,
                'KeyType' => 'PaymentId'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData['IsSuccess']) {
                    return [
                        'success' => true,
                        'data' => $responseData['Data']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['Message'] ?? 'Unknown error'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify webhook signature (implement based on MyFatoorah documentation)
     */
    private function verifyWebhookSignature($request)
    {
        // MyFatoorah may provide signature verification
        // For now, we'll return true, but implement proper verification
        return true;
    }
}