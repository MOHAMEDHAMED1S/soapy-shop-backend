<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Twilio\Rest\Client;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Exceptions\TwilioException;

class TwilioWhatsAppService
{
    private ?Client $client = null;
    private string $fromNumber;
    private string $storeName;
    private array $adminPhones;
    private array $deliveryPhones;
    private array $templates;
    private bool $isConfigured = false;
    private bool $customerNotificationsEnabled;
    private bool $adminNotificationsEnabled;

    public function __construct()
    {
        $accountSid = config('twilio.account_sid');
        $authToken = config('twilio.auth_token');
        $this->fromNumber = config('twilio.whatsapp_from', 'whatsapp:+14155238886');
        $this->storeName = config('twilio.store_name', 'المتجر');
        
        // Parse admin and delivery phones
        $adminPhonesString = config('twilio.admin_phones', '');
        $this->adminPhones = array_filter(array_map('trim', explode(',', $adminPhonesString)));
        
        $deliveryPhonesString = config('twilio.delivery_phones', '');
        $this->deliveryPhones = array_filter(array_map('trim', explode(',', $deliveryPhonesString)));
        
        // Template SIDs
        $this->templates = config('twilio.templates', []);
        
        // Notification toggles
        $this->customerNotificationsEnabled = config('twilio.customer_notifications_enabled', true);
        $this->adminNotificationsEnabled = config('twilio.admin_notifications_enabled', true);

        if ($accountSid && $authToken && $accountSid !== 'your_account_sid') {
            try {
                $this->client = new Client($accountSid, $authToken);
                $this->isConfigured = true;
            } catch (\Exception $e) {
                Log::error('Failed to initialize Twilio client: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Format phone number to WhatsApp format
     */
    private function formatWhatsAppNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        if (!str_starts_with($phone, 'whatsapp:')) {
            $phone = 'whatsapp:' . $phone;
        }

        return $phone;
    }

    /**
     * Send a template-based WhatsApp message
     * 
     * @param string $to Phone number
     * @param string $contentSid Template SID (e.g., HXxxxxxx)
     * @param array $contentVariables Variables: ['1' => 'value1', '2' => 'value2']
     */
    public function sendTemplateMessage(
        string $to,
        string $contentSid,
        array $contentVariables = []
    ): ?MessageInstance {
        if (!$this->isConfigured) {
            Log::warning('Twilio WhatsApp not configured. Skipping template message.');
            return null;
        }

        if (empty($contentSid)) {
            Log::warning('Template SID is empty. Skipping message.');
            return null;
        }

        try {
            $params = [
                'from' => $this->fromNumber,
                'contentSid' => $contentSid,
            ];

            if (!empty($contentVariables)) {
                $params['contentVariables'] = json_encode($contentVariables);
            }

            $message = $this->client->messages->create(
                $this->formatWhatsAppNumber($to),
                $params
            );

            Log::info('WhatsApp template message sent via Twilio', [
                'to' => $to,
                'contentSid' => $contentSid,
                'sid' => $message->sid,
                'status' => $message->status,
            ]);

            return $message;
        } catch (TwilioException $e) {
            Log::error('Twilio WhatsApp template error: ' . $e->getMessage(), [
                'to' => $to,
                'contentSid' => $contentSid,
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    // ==========================================
    // Customer Notification Methods
    // ==========================================

    /**
     * إشعار العميل بإنشاء الطلب
     * Template Variables:
     * {{1}} = اسم المتجر
     * {{2}} = اسم العميل
     * {{3}} = رقم الطلب
     * {{4}} = المبلغ الإجمالي
     * {{5}} = العملة
     * {{6}} = رقم الطلب للرابط (زر التتبع)
     */
    public function notifyCustomerOrderCreated(Order $order): ?MessageInstance
    {
        if (!$this->customerNotificationsEnabled) {
            Log::info('Customer WhatsApp notifications are disabled');
            return null;
        }

        $templateSid = $this->templates['order_created'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for order_created not configured');
            return null;
        }

        $cacheKey = 'twilio_customer_order_created_' . $order->id;
        if (Cache::has($cacheKey)) {
            Log::info('Customer order created notification already sent', ['order_id' => $order->id]);
            return null;
        }

        $variables = [
            '1' => $this->storeName,
            '2' => $order->customer_name,
            '3' => $order->order_number,
            '4' => number_format((float)$order->total_amount, 3),
            '5' => $order->currency ?? 'KWD',
            '6' => $order->order_number, // For tracking button URL
        ];

        $result = $this->sendTemplateMessage($order->customer_phone, $templateSid, $variables);
        
        if ($result) {
            Cache::put($cacheKey, true, now()->addHours(24));
        }
        
        return $result;
    }

    /**
     * إشعار العميل بتحديث حالة الطلب
     * Template Variables:
     * {{1}} = اسم المتجر
     * {{2}} = اسم العميل
     * {{3}} = رقم الطلب
     * {{4}} = الحالة الجديدة
     * {{5}} = رقم الطلب للرابط (زر التتبع)
     */
    public function notifyCustomerStatusUpdate(Order $order, string $newStatus): ?MessageInstance
    {
        if (!$this->customerNotificationsEnabled) {
            return null;
        }

        $templateSid = $this->templates['status_update'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for status_update not configured');
            return null;
        }

        $variables = [
            '1' => $this->storeName,
            '2' => $order->customer_name,
            '3' => $order->order_number,
            '4' => $this->translateStatus($newStatus),
            '5' => $order->order_number, // For tracking button URL
        ];

        return $this->sendTemplateMessage($order->customer_phone, $templateSid, $variables);
    }

    /**
     * إشعار العميل بالشحن
     * Template Variables:
     * {{1}} = اسم المتجر
     * {{2}} = اسم العميل
     * {{3}} = رقم الطلب
     * {{4}} = رقم التتبع
     * {{5}} = رقم الطلب للرابط (زر التتبع)
     */
    public function notifyCustomerOrderShipped(Order $order): ?MessageInstance
    {
        if (!$this->customerNotificationsEnabled) {
            return null;
        }

        $templateSid = $this->templates['order_shipped'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for order_shipped not configured');
            return null;
        }

        $variables = [
            '1' => $this->storeName,
            '2' => $order->customer_name,
            '3' => $order->order_number,
            '4' => $order->tracking_number ?? 'غير متوفر',
            '5' => $order->order_number, // For tracking button URL
        ];

        return $this->sendTemplateMessage($order->customer_phone, $templateSid, $variables);
    }

    /**
     * إشعار العميل بالتوصيل
     * Template Variables:
     * {{1}} = اسم المتجر
     * {{2}} = اسم العميل
     * {{3}} = رقم الطلب
     * {{4}} = رقم الطلب للرابط (زر التتبع)
     */
    public function notifyCustomerOrderDelivered(Order $order): ?MessageInstance
    {
        if (!$this->customerNotificationsEnabled) {
            return null;
        }

        $templateSid = $this->templates['order_delivered'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for order_delivered not configured');
            return null;
        }

        $variables = [
            '1' => $this->storeName,
            '2' => $order->customer_name,
            '3' => $order->order_number,
            '4' => $order->order_number, // For tracking button URL
        ];

        return $this->sendTemplateMessage($order->customer_phone, $templateSid, $variables);
    }

    // ==========================================
    // Admin & Delivery Notification Methods
    // ==========================================

    /**
     * إشعار الأدمن بطلب جديد مدفوع
     * Template Variables:
     * {{1}} = رقم الطلب
     * {{2}} = اسم العميل
     * {{3}} = رقم الهاتف
     * {{4}} = المبلغ
     * {{5}} = العملة
     */
    public function notifyAdminNewPaidOrder(Order $order): array
    {
        if (!$this->adminNotificationsEnabled) {
            return ['success' => false, 'reason' => 'disabled'];
        }

        $templateSid = $this->templates['admin_new_order'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for admin_new_order not configured');
            return ['success' => false, 'reason' => 'no_template'];
        }

        $cacheKey = 'twilio_admin_order_' . $order->id;
        if (Cache::has($cacheKey)) {
            return ['success' => true, 'reason' => 'already_sent'];
        }

        if (empty($this->adminPhones)) {
            return ['success' => false, 'reason' => 'no_phones'];
        }

        $variables = [
            '1' => $order->order_number,
            '2' => $order->customer_name,
            '3' => $order->customer_phone,
            '4' => number_format((float)$order->total_amount, 3),
            '5' => $order->currency ?? 'KWD',
        ];

        $results = [];
        foreach ($this->adminPhones as $phone) {
            try {
                $result = $this->sendTemplateMessage($phone, $templateSid, $variables);
                $results[] = [
                    'phone' => $phone,
                    'success' => $result !== null,
                    'sid' => $result?->sid
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'phone' => $phone,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $anySuccess = count(array_filter($results, fn($r) => $r['success'])) > 0;
        if ($anySuccess) {
            Cache::put($cacheKey, true, now()->addHours(24));
        }

        return ['success' => $anySuccess, 'results' => $results];
    }

    /**
     * إشعار مندوب التوصيل بطلب جديد
     * Template Variables:
     * {{1}} = رقم الطلب
     * {{2}} = اسم العميل
     * {{3}} = رقم الهاتف
     * {{4}} = العنوان
     */
    public function notifyDeliveryNewPaidOrder(Order $order): array
    {
        if (!$this->adminNotificationsEnabled) {
            return ['success' => false, 'reason' => 'disabled'];
        }

        $templateSid = $this->templates['delivery_new_order'] ?? null;
        if (!$templateSid) {
            Log::warning('Template SID for delivery_new_order not configured');
            return ['success' => false, 'reason' => 'no_template'];
        }

        $cacheKey = 'twilio_delivery_order_' . $order->id;
        if (Cache::has($cacheKey)) {
            return ['success' => true, 'reason' => 'already_sent'];
        }

        if (empty($this->deliveryPhones)) {
            return ['success' => false, 'reason' => 'no_phones'];
        }

        $variables = [
            '1' => $order->order_number,
            '2' => $order->customer_name,
            '3' => $order->customer_phone,
            '4' => $this->formatAddress($order->shipping_address),
        ];

        $results = [];
        foreach ($this->deliveryPhones as $phone) {
            try {
                $result = $this->sendTemplateMessage($phone, $templateSid, $variables);
                $results[] = [
                    'phone' => $phone,
                    'success' => $result !== null,
                    'sid' => $result?->sid
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'phone' => $phone,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $anySuccess = count(array_filter($results, fn($r) => $r['success'])) > 0;
        if ($anySuccess) {
            Cache::put($cacheKey, true, now()->addHours(24));
        }

        return ['success' => $anySuccess, 'results' => $results];
    }

    /**
     * Test the Twilio connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured) {
            return [
                'success' => false,
                'message' => 'Twilio is not configured.',
            ];
        }

        try {
            $account = $this->client->api->v2010->accounts(config('twilio.account_sid'))->fetch();
            
            return [
                'success' => true,
                'message' => 'Twilio connection successful',
                'account_name' => $account->friendlyName,
                'account_status' => $account->status,
                'templates_configured' => array_filter($this->templates),
            ];
        } catch (TwilioException $e) {
            return [
                'success' => false,
                'message' => 'Twilio connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ترجمة الحالة للعربية
     */
    private function translateStatus(string $status): string
    {
        return match($status) {
            'pending' => 'قيد الانتظار',
            'awaiting_payment' => 'بانتظار الدفع',
            'paid' => 'مدفوع',
            'processing' => 'قيد المعالجة',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغى',
            'refunded' => 'مسترد',
            default => $status
        };
    }

    /**
     * تنسيق العنوان
     */
    private function formatAddress($address): string
    {
        if (is_string($address)) {
            $decoded = json_decode($address, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $address = $decoded;
            } else {
                return $address;
            }
        }

        if (is_array($address)) {
            $parts = [];
            if (!empty($address['city'])) $parts[] = $address['city'];
            if (!empty($address['block'])) $parts[] = "ق{$address['block']}";
            if (!empty($address['street'])) $parts[] = "ش{$address['street']}";
            if (!empty($address['building'])) $parts[] = "بناية {$address['building']}";
            
            return !empty($parts) ? implode(' - ', $parts) : 'غير محدد';
        }

        return 'غير محدد';
    }
}
