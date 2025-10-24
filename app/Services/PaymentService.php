<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Initiate payment with MyFatoorah
     */
    public function initiatePayment(Order $order, array $paymentData)
    {
        try {
            // First, get payment methods
            $methodsResponse = $this->callMyFatoorahAPI('/v2/InitiatePayment', [
                'InvoiceAmount' => (float)$order->total_amount,
                'CurrencyIso' => $order->currency
            ]);

            if (!$methodsResponse['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment methods: ' . $methodsResponse['error']
                ];
            }

            // Find the selected payment method
            $selectedMethod = null;
            foreach ($methodsResponse['data']['PaymentMethods'] as $method) {
                if ($method['PaymentMethodCode'] === $paymentData['payment_method']) {
                    $selectedMethod = $method;
                    break;
                }
            }

            if (!$selectedMethod) {
                return [
                    'success' => false,
                    'error' => 'Payment method not found'
                ];
            }

            // Calculate total from items first
            $itemsTotal = 0;
            $invoiceItems = [];
            
            foreach ($order->orderItems as $item) {
                $itemTotal = (float)$item->product_price * $item->quantity;
                $itemsTotal += $itemTotal;
                
                $invoiceItems[] = [
                    'ItemName' => $item->product_snapshot['title'],
                    'Quantity' => $item->quantity,
                    'UnitPrice' => (float)$item->product_price,
                    'Weight' => 0,
                    'Width' => 0,
                    'Height' => 0,
                    'Depth' => 0
                ];
            }

            // Add shipping as a separate item if there's shipping cost
            if ($order->shipping_amount > 0) {
                $invoiceItems[] = [
                    'ItemName' => 'رسوم الشحن',
                    'Quantity' => 1,
                    'UnitPrice' => (float)$order->shipping_amount,
                    'Weight' => 0,
                    'Width' => 0,
                    'Height' => 0,
                    'Depth' => 0
                ];
                $itemsTotal += (float)$order->shipping_amount;
            }

            // Add discount as a negative item if there's a discount
            if ($order->discount_amount > 0) {
                $invoiceItems[] = [
                    'ItemName' => 'خصم - ' . ($order->discount_code ?? 'كود الخصم'),
                    'Quantity' => 1,
                    'UnitPrice' => -(float)$order->discount_amount, // Negative value for discount
                    'Weight' => 0,
                    'Width' => 0,
                    'Height' => 0,
                    'Depth' => 0
                ];
                $itemsTotal -= (float)$order->discount_amount;
            }

            // Round to 3 decimal places to match MyFatoorah requirements
            $itemsTotal = round($itemsTotal, 3);

            // Now create the actual payment using SendPayment API
            $paymentData = [
                'PaymentMethodId' => $selectedMethod['PaymentMethodId'],
                'CustomerName' => $order->customer_name,
                'CustomerEmail' => $order->customer_email ?? 'customer@soapyshop.com',
                'CustomerMobile' => substr(preg_replace('/[^0-9]/', '', $order->customer_phone), -11), // Clean phone number
                'CustomerReference' => $order->order_number,
                'UserDefinedField' => $order->id,
                'InvoiceValue' => $itemsTotal, // Use calculated itemsTotal to match InvoiceItems
                'DisplayCurrencyIso' => $order->currency,
                'CallBackUrl' => url('/api/v1/payments/success'),
                'ErrorUrl' => url('/api/v1/payments/failure'),
                'Language' => 'ar',
                'NotificationOption' => 'LNK', // Required field
                'CustomerAddress' => [
                    'AddressLine' => $order->shipping_address['street'] ?? '',
                    'City' => $order->shipping_address['city'] ?? '',
                    'State' => $order->shipping_address['governorate'] ?? '',
                    'PostalCode' => $order->shipping_address['postal_code'] ?? '',
                    'Country' => 'KW'
                ],
                'InvoiceItems' => $invoiceItems
            ];

            // Call SendPayment API
            $sendPaymentResponse = $this->callMyFatoorahAPI('/v2/SendPayment', $paymentData);

            if (!$sendPaymentResponse['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to create payment: ' . $sendPaymentResponse['error']
                ];
            }

            // Log the response for debugging
            Log::info('MyFatoorah SendPayment Response', $sendPaymentResponse);

            return [
                'success' => true,
                'data' => [
                    'payment_method' => $selectedMethod,
                    'invoice_id' => $sendPaymentResponse['data']['InvoiceId'] ?? 'temp_' . time(),
                    'payment_url' => $sendPaymentResponse['data']['InvoiceURL'] ?? null,
                    'payment_id' => $sendPaymentResponse['data']['InvoiceId'] ?? 'temp_' . time(),
                    'order_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'raw_response' => $sendPaymentResponse['data'] // For debugging
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment status with MyFatoorah
     */
    public function verifyPayment($paymentId)
    {
        try {
            $response = $this->callMyFatoorahAPI('/v2/GetPaymentStatus', [
                'Key' => $paymentId,
                'KeyType' => 'InvoiceId'
            ]);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            }

            return [
                'success' => false,
                'error' => $response['error']
            ];

        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment methods from MyFatoorah
     */
    public function getPaymentMethods($filterEnabled = false)
    {
        try {
            $response = $this->callMyFatoorahAPI('/v2/InitiatePayment', [
                'InvoiceAmount' => 1, // Minimum amount to get methods
                'CurrencyIso' => 'KWD'
            ]);

            if ($response['success']) {
                $paymentMethods = $response['data']['PaymentMethods'] ?? [];
                
                // Filter enabled payment methods if requested
                if ($filterEnabled) {
                    $paymentMethods = $this->filterEnabledPaymentMethods($paymentMethods);
                }
                
                return [
                    'success' => true,
                    'data' => $paymentMethods
                ];
            }

            return [
                'success' => false,
                'error' => $response['error']
            ];

        } catch (\Exception $e) {
            Log::error('Get payment methods error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Filter payment methods based on admin settings
     */
    private function filterEnabledPaymentMethods(array $paymentMethods): array
    {
        // Filter out methods that are explicitly disabled
        return array_filter($paymentMethods, function ($method) {
            return \App\Models\PaymentMethodSetting::isEnabled($method['PaymentMethodCode']);
        });
    }

    /**
     * Prepare MyFatoorah payment data
     */
    private function prepareMyFatoorahData(Order $order, array $paymentData)
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'ItemName' => $item->product_snapshot['title'],
                'Quantity' => (int)$item->quantity,
                'UnitPrice' => (float)$item->product_price,
                'Weight' => 0,
                'Width' => 0,
                'Height' => 0,
                'Depth' => 0,
            ];
        }

        // Clean phone number (remove + and spaces)
        $cleanPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
        if (strlen($cleanPhone) > 11) {
            $cleanPhone = substr($cleanPhone, -11); // Take last 11 digits
        }

        return [
            'InvoiceAmount' => (float)$order->total_amount,
            'CurrencyIso' => $order->currency,
            'CustomerName' => $order->customer_name,
            'CustomerEmail' => $order->customer_email,
            'CustomerMobile' => $cleanPhone,
            'CustomerReference' => $order->order_number,
            'UserDefinedField' => (string)$order->id,
            'CallBackUrl' => config('app.url') . '/api/v1/payments/callback',
            'ErrorUrl' => config('app.url') . '/api/v1/payments/error',
            'Language' => 'ar',
            'DisplayCurrencyIso' => $order->currency,
            'MobileCountryCode' => '+965',
            'NotificationOption' => 'EML', // Email notification
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
    private function callMyFatoorahAPI($endpoint, $data)
    {
        try {
            Log::info('MyFatoorah API Call', [
                'endpoint' => $endpoint,
                'data' => $data,
                'url' => config('services.myfatoorah.base_url') . $endpoint
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.myfatoorah.base_url') . $endpoint, $data);

            Log::info('MyFatoorah API Response', [
                'status' => $response->status(),
                'body' => $response->body()
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
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('MyFatoorah API Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($request)
    {
        // Implement signature verification based on MyFatoorah documentation
        // For now, return true
        return true;
    }

    /**
     * Process webhook data
     */
    public function processWebhook($data)
    {
        try {
            // Find payment by invoice reference
            $payment = Payment::where('invoice_reference', $data['InvoiceId'] ?? '')
                ->with('order')
                ->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment not found'
                ];
            }

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
                
                // Fire OrderPaid event to notify admins
                event(new \App\Events\OrderPaid($order));
            } elseif ($data['InvoiceStatus'] === 'Failed') {
                $order->update(['status' => 'pending']);
            }

            return [
                'success' => true,
                'order' => $order
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
