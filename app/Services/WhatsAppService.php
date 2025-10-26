<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $adminPhone;

    protected $deliveryPhone;

    public function __construct()
    {
        // يمكن تخزين هذه في .env لاحقاً
        // Get from .env, if not found use default value
        $this->baseUrl = env('WHATSAPP_API_URL', 'https://wapi.soapy-bubbles.com');
        $this->adminPhone = env('ADMIN_WHATSAPP_PHONE', '201062532581');
        $this->deliveryPhone = env('DELIVERY_WHATSAPP_PHONE', '201062532581');
    }

    /**
     * إرسال رسالة واتساب مع صورة للأدمن عند دفع طلب جديد
     */
    public function notifyAdminNewPaidOrder($order)
    {
        try {
            // Ensure order items are loaded
            if (!$order->relationLoaded('orderItems')) {
                $order->load('orderItems');
            }

            Log::info('Attempting to send WhatsApp notification for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

            // تحضير الرسالة
            $message = $this->formatOrderMessage($order);
            $imageUrl = 'https://soapy-bubbles.com/logo.png';

            // إرسال الواتساب
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/send/image-url", [
                'to' => $this->adminPhone,
                'imageUrl' => $imageUrl,
                'caption' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent successfully', [
                    'order_id' => $order->id,
                    'response' => $response->json()
                ]);
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::warning('WhatsApp API returned error', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp API error: ' . $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تنسيق رسالة الطلب
     */
    private function formatOrderMessage($order)
    {
        try {
            $itemsList = '';
            $totalItems = 0;
            $subtotal = 0;
            
            if ($order->orderItems && $order->orderItems->count() > 0) {
                foreach ($order->orderItems as $item) {
                    try {
                        // Handle product_snapshot (could be array or JSON string)
                        $snapshot = $item->product_snapshot;
                        if (is_string($snapshot)) {
                            $snapshot = json_decode($snapshot, true);
                        }
                        
                        $productName = $snapshot['title'] ?? $snapshot['name'] ?? 'Unknown Product';
                        $quantity = $item->quantity ?? 1;
                        $price = number_format($item->product_price, 3);
                        $itemTotal = $item->product_price * $quantity;
                        
                        $totalItems += $quantity;
                        $subtotal += $itemTotal;
                        
                        $itemsList .= "\n  - {$productName}";
                        $itemsList .= "\n    الكمية: {$quantity}";
                        $itemsList .= "\n    السعر: {$price} {$order->currency}";
                        $itemsList .= "\n    المجموع: " . number_format($itemTotal, 3) . " {$order->currency}\n";
                    } catch (\Exception $e) {
                        Log::warning('Error formatting order item for WhatsApp', [
                            'item_id' => $item->id ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        $itemsList .= "\n  - منتج (تفاصيل غير متوفرة)\n";
                    }
                }
            } else {
                $itemsList = "\n  (لا توجد تفاصيل المنتجات)";
            }

            // Build message
            $message = "*====== طلب جديد مدفوع ======*\n\n";
            
            // Order Information
            $message .= "*معلومات الطلب:*\n";
            $message .= "رقم الطلب: {$order->order_number}\n";
            $message .= "حالة الطلب: مدفوع\n";
            $message .= "تاريخ الطلب: " . $order->created_at->format('Y-m-d') . "\n";
            $message .= "وقت الطلب: " . $order->created_at->format('H:i:s') . "\n\n";
            
            // Customer Information
            $message .= "*معلومات العميل:*\n";
            $message .= "الاسم: {$order->customer_name}\n";
            $message .= "رقم الهاتف: {$order->customer_phone}\n";
            
            if ($order->customer_email) {
                $message .= "البريد الإلكتروني: {$order->customer_email}\n";
            }
            $message .= "\n";
            
            // Shipping Address
            if ($order->shipping_address) {
                $message .= "*عنوان الشحن:*\n";
                $address = $this->formatAddress($order->shipping_address);
                $message .= "{$address}\n\n";
            }
            
            // Products
            $message .= "*المنتجات المطلوبة:*";
            $message .= $itemsList;
            
            if ($totalItems > 0) {
                $message .= "\nإجمالي عدد القطع: {$totalItems}\n";
            }
            $message .= "\n";
            
            // Financial Details
            $message .= "*التفاصيل المالية:*\n";
            
            if ($subtotal > 0) {
                $message .= "المجموع الفرعي: " . number_format($subtotal, 3) . " {$order->currency}\n";
            }
            
            if (isset($order->shipping_cost) && $order->shipping_cost > 0) {
                $message .= "تكلفة الشحن: " . number_format($order->shipping_cost, 3) . " {$order->currency}\n";
            }
            
            if (isset($order->discount_amount) && $order->discount_amount > 0) {
                $message .= "الخصم: -" . number_format($order->discount_amount, 3) . " {$order->currency}\n";
                
                if (isset($order->discount_code) && $order->discount_code) {
                    $message .= "كود الخصم المستخدم: {$order->discount_code}\n";
                }
            }
            
            $message .= "*المبلغ الإجمالي النهائي: " . number_format($order->total_amount, 3) . " {$order->currency}*\n\n";
            
            // Payment Information
            if ($order->payment) {
                $message .= "*معلومات الدفع:*\n";
                $message .= "حالة الدفع: " . ($order->payment->status ?? 'غير محدد') . "\n";
                
                if ($order->payment->invoice_reference) {
                    $message .= "رقم الفاتورة: {$order->payment->invoice_reference}\n";
                }
                
                if ($order->payment->payment_method) {
                    $message .= "طريقة الدفع: {$order->payment->payment_method}\n";
                }
            }
            
            $message .= "\n" . str_repeat("=", 28);


            return $message;
            
        } catch (\Exception $e) {
            Log::error('Error formatting WhatsApp message', [
                'order_id' => $order->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Fallback simple message
            return "*طلب جديد مدفوع*\n\n" .
                   "رقم الطلب: {$order->order_number}\n" .
                   "المبلغ: " . number_format($order->total_amount, 3) . " {$order->currency}\n" .
                   "العميل: {$order->customer_name}\n" .
                   "الهاتف: {$order->customer_phone}";
        }
    }

    /**
     * إرسال رسالة واتساب مع صورة لمندوب التوصيل عند دفع طلب جديد
     */
    public function notifyDeliveryNewPaidOrder($order)
    {
        try {
            // Ensure order items are loaded
            if (!$order->relationLoaded('orderItems')) {
                $order->load('orderItems');
            }

            Log::info('Attempting to send WhatsApp notification to delivery', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

            // تحضير رسالة مندوب التوصيل
            $message = $this->formatDeliveryMessage($order);
            $imageUrl = 'https://soapy-bubbles.com/logo.png';

            // إرسال الواتساب
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/send/image-url", [
                'to' => $this->deliveryPhone,
                'imageUrl' => $imageUrl,
                'caption' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent successfully to delivery', [
                    'order_id' => $order->id,
                    'response' => $response->json()
                ]);
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::warning('WhatsApp API returned error for delivery', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp API error: ' . $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification to delivery', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تنسيق رسالة مندوب التوصيل (أبسط وأكثر تركيزاً على التوصيل)
     */
    private function formatDeliveryMessage($order)
    {
        try {
            $message = "*===== طلب جديد للتوصيل =====*\n\n";
            
            // معلومات الطلب الأساسية
            $message .= "*رقم الطلب:* {$order->order_number}\n";
            $message .= "*التاريخ:* " . $order->created_at->format('Y-m-d') . "\n";
            $message .= "*الوقت:* " . $order->created_at->format('H:i:s') . "\n\n";
            
            // معلومات العميل
            $message .= "*معلومات العميل:*\n";
            $message .= "الاسم: {$order->customer_name}\n";
            $message .= "الهاتف: {$order->customer_phone}\n\n";
            
            // عنوان التوصيل (مهم جداً للمندوب)
            $message .= "*عنوان التوصيل:*\n";
            if ($order->shipping_address) {
                $address = $this->formatAddress($order->shipping_address);
                $message .= "{$address}\n\n";
            } else {
                $message .= "(لم يتم تحديد العنوان)\n\n";
            }
            
            // عدد القطع
            $totalItems = 0;
            if ($order->orderItems && $order->orderItems->count() > 0) {
                foreach ($order->orderItems as $item) {
                    $totalItems += $item->quantity ?? 1;
                }
                $message .= "*عدد القطع:* {$totalItems} قطعة\n\n";
            }
            
            // المبلغ
            $message .= "*المبلغ الإجمالي:* " . number_format($order->total_amount, 3) . " {$order->currency}\n";
            $message .= "*حالة الدفع:* مدفوع مسبقاً\n\n";
            
            // ملاحظات إضافية (إذا وُجدت)
            if (isset($order->notes) && $order->notes) {
                $message .= "*ملاحظات:*\n{$order->notes}\n\n";
            }
            
            $message .= str_repeat("=", 28);
            $message .= "\n*يرجى التواصل مع العميل لتحديد موعد التوصيل*";

            return $message;
            
        } catch (\Exception $e) {
            Log::error('Error formatting delivery WhatsApp message', [
                'order_id' => $order->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Fallback simple message
            $address = 'غير محدد';
            try {
                if ($order->shipping_address) {
                    $address = $this->formatAddress($order->shipping_address);
                }
            } catch (\Exception $ex) {
                // If formatting fails, keep it as 'غير محدد'
            }
            
            return "*طلب جديد للتوصيل*\n\n" .
                   "رقم الطلب: {$order->order_number}\n" .
                   "العميل: {$order->customer_name}\n" .
                   "الهاتف: {$order->customer_phone}\n" .
                   "العنوان: {$address}";
        }
    }

    /**
     * تنسيق العنوان ليكون مقروءاً
     */
    private function formatAddress($address)
    {
        // If it's already a string, return it
        if (is_string($address) && !$this->isJson($address)) {
            return $address;
        }

        // If it's JSON string, decode it
        if (is_string($address)) {
            $address = json_decode($address, true);
        }

        // If it's an array, format it nicely
        if (is_array($address)) {
            $formatted = [];
            
            if (!empty($address['street'])) {
                $formatted[] = "الشارع: {$address['street']}";
            }
            
            if (!empty($address['city'])) {
                $formatted[] = "المدينة: {$address['city']}";
            }
            
            if (!empty($address['governorate'])) {
                $formatted[] = "المحافظة: {$address['governorate']}";
            }
            
            if (!empty($address['postal_code'])) {
                $formatted[] = "الرمز البريدي: {$address['postal_code']}";
            }
            
            if (!empty($address['block'])) {
                $formatted[] = "القطعة: {$address['block']}";
            }
            
            if (!empty($address['building'])) {
                $formatted[] = "البناية: {$address['building']}";
            }
            
            if (!empty($address['floor'])) {
                $formatted[] = "الطابق: {$address['floor']}";
            }
            
            if (!empty($address['apartment'])) {
                $formatted[] = "الشقة: {$address['apartment']}";
            }
            
            if (!empty($address['notes'])) {
                $formatted[] = "ملاحظات: {$address['notes']}";
            }
            
            return !empty($formatted) ? implode("\n", $formatted) : 'غير محدد';
        }

        return 'غير محدد';
    }

    /**
     * التحقق من أن النص هو JSON
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * إرسال رسالة واتساب عادية (للاستخدام المستقبلي)
     */
    public function sendMessage($to, $message)
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/send/text", [
                'to' => $to,
                'message' => $message,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * إرسال صورة مع رسالة
     */
    public function sendImageWithCaption($to, $imageUrl, $caption, $additionalMessage = null)
    {
        try {
            $payload = [
                'to' => $to,
                'imageUrl' => $imageUrl,
                'caption' => $caption,
            ];

            if ($additionalMessage) {
                $payload['message'] = $additionalMessage;
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/api/send/image-url", $payload);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp image', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

