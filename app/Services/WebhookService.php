<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Models\AdminNotification;
use App\Events\OrderPaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Process MyFatoorah webhook
     */
    public function processMyFatoorahWebhook(array $webhookData, WebhookLog $webhookLog)
    {
        try {
            // Extract payment information
            $paymentInfo = $this->extractPaymentInfo($webhookData);
            
            if (!$paymentInfo['payment_id'] && !$paymentInfo['invoice_id']) {
                return [
                    'success' => false,
                    'error' => 'No payment or invoice ID found in webhook data'
                ];
            }

            // Find the payment record
            $payment = $this->findPaymentRecord($paymentInfo);
            if (!$payment) {
                return [
                    'success' => false,
                    'error' => 'Payment record not found for ID: ' . ($paymentInfo['payment_id'] ?: $paymentInfo['invoice_id'])
                ];
            }

            // Process the webhook based on payment status
            $result = $this->processPaymentStatusUpdate($payment, $paymentInfo, $webhookLog);

            return $result;

        } catch (\Exception $e) {
            Log::error('WebhookService processing error', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
                'webhook_log_id' => $webhookLog->id
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract payment information from webhook data
     */
    private function extractPaymentInfo(array $webhookData)
    {
        return [
            'payment_id' => $webhookData['PaymentId'] ?? null,
            'invoice_id' => $webhookData['InvoiceId'] ?? null,
            'payment_status' => $webhookData['PaymentStatus'] ?? null,
            'transaction_id' => $webhookData['TransactionId'] ?? null,
            'amount' => $webhookData['Amount'] ?? null,
            'currency' => $webhookData['Currency'] ?? null,
            'customer_name' => $webhookData['CustomerName'] ?? null,
            'customer_email' => $webhookData['CustomerEmail'] ?? null,
            'customer_mobile' => $webhookData['CustomerMobile'] ?? null,
            'payment_method' => $webhookData['PaymentMethod'] ?? null,
            'payment_date' => $webhookData['PaymentDate'] ?? null,
            'reference_id' => $webhookData['ReferenceId'] ?? null,
            'error_message' => $webhookData['ErrorMessage'] ?? null,
            'gateway_reference' => $webhookData['GatewayReference'] ?? null,
        ];
    }

    /**
     * Find payment record by payment ID or invoice ID
     */
    private function findPaymentRecord(array $paymentInfo)
    {
        $payment = null;

        // Try to find by payment ID first
        if ($paymentInfo['payment_id']) {
            $payment = Payment::where('invoice_reference', $paymentInfo['payment_id'])->first();
        }

        // If not found, try by invoice ID
        if (!$payment && $paymentInfo['invoice_id']) {
            $payment = Payment::where('invoice_reference', $paymentInfo['invoice_id'])->first();
        }

        // If still not found, try by order number in reference
        if (!$payment && $paymentInfo['reference_id']) {
            $order = Order::where('order_number', $paymentInfo['reference_id'])->first();
            if ($order) {
                $payment = $order->payment;
            }
        }

        return $payment;
    }

    /**
     * Process payment status update
     */
    private function processPaymentStatusUpdate(Payment $payment, array $paymentInfo, WebhookLog $webhookLog)
    {
        $oldStatus = $payment->status;
        $newStatus = $this->mapMyFatoorahStatus($paymentInfo['payment_status']);

        if (!$newStatus) {
            return [
                'success' => true,
                'message' => 'Unknown payment status received: ' . $paymentInfo['payment_status']
            ];
        }

        if ($newStatus === $oldStatus) {
            // Update response data even if status hasn't changed
            $payment->update([
                'response_raw' => array_merge($payment->response_raw ?? [], $paymentInfo)
            ]);

            return [
                'success' => true,
                'message' => 'Payment status unchanged, updated response data'
            ];
        }

        DB::beginTransaction();

        try {
            // Update payment status
            $payment->update([
                'status' => $newStatus,
                'response_raw' => array_merge($payment->response_raw ?? [], $paymentInfo)
            ]);

            // Update order status based on payment status
            $this->updateOrderStatusBasedOnPayment($payment, $newStatus);

            // Create admin notification for important status changes
            $this->createAdminNotification($payment, $newStatus, $oldStatus, $paymentInfo, $webhookLog);

            // Log the status change
            Log::info('Payment status updated via webhook', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order->order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'transaction_id' => $paymentInfo['transaction_id'],
                'webhook_log_id' => $webhookLog->id
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Payment status updated from {$oldStatus} to {$newStatus}",
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'transaction_id' => $paymentInfo['transaction_id']
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Map MyFatoorah payment status to our internal status
     */
    private function mapMyFatoorahStatus($myFatoorahStatus)
    {
        $statusMap = [
            'Paid' => 'paid',
            'Failed' => 'failed',
            'Cancelled' => 'failed',
            'Expired' => 'failed',
            'Refunded' => 'refunded',
            'PartiallyRefunded' => 'refunded',
            'Pending' => 'pending',
            'InProgress' => 'pending',
            'Authorized' => 'pending',
            'Captured' => 'paid',
            'Voided' => 'failed',
        ];

        return $statusMap[$myFatoorahStatus] ?? null;
    }

    /**
     * Update order status based on payment status
     */
    private function updateOrderStatusBasedOnPayment(Payment $payment, string $paymentStatus)
    {
        $order = $payment->order;
        if (!$order) {
            return;
        }

        $orderStatusMap = [
            'paid' => 'paid',
            'failed' => 'awaiting_payment',
            'refunded' => 'refunded',
            'pending' => 'awaiting_payment',
        ];

        $newOrderStatus = $orderStatusMap[$paymentStatus] ?? $order->status;

        if ($newOrderStatus !== $order->status) {
            $order->update(['status' => $newOrderStatus]);

            Log::info('Order status updated via webhook', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $order->status,
                'new_status' => $newOrderStatus,
                'payment_status' => $paymentStatus
            ]);
        }
    }

    /**
     * Create admin notification for important status changes
     */
    private function createAdminNotification(Payment $payment, string $newStatus, string $oldStatus, array $paymentInfo, WebhookLog $webhookLog)
    {
        $importantStatuses = ['paid', 'failed', 'refunded'];
        
        if (!in_array($newStatus, $importantStatuses)) {
            return;
        }

        AdminNotification::create([
            'type' => "payment_{$newStatus}",
            'payload' => [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'order_number' => $payment->order->order_number,
                'customer_name' => $payment->order->customer_name,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $newStatus,
                'previous_status' => $oldStatus,
                'transaction_id' => $paymentInfo['transaction_id'],
                'payment_method' => $paymentInfo['payment_method'],
                'webhook_log_id' => $webhookLog->id,
                'processed_at' => now()->toISOString()
            ],
            'created_at' => now()
        ]);

        // إرسال الإيميلات للإدارة عند الدفع الناجح
        if ($newStatus === 'paid') {
            event(new OrderPaid($payment->order));
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($request)
    {
        // For now, we'll accept all webhooks
        // In production, implement proper signature verification
        // based on MyFatoorah's webhook signature mechanism
        
        $signature = $request->header('X-MyFatoorah-Signature');
        $webhookSecret = config('services.myfatoorah.webhook_secret');
        
        if (!$webhookSecret) {
            // If no secret is configured, accept all webhooks (for testing)
            return true;
        }

        // Implement signature verification logic here
        // This is a placeholder - implement according to MyFatoorah docs
        return true;
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStatistics($period = 30)
    {
        $startDate = now()->subDays($period);

        return [
            'total_webhooks' => WebhookLog::where('received_at', '>=', $startDate)->count(),
            'processed_webhooks' => WebhookLog::where('received_at', '>=', $startDate)
                ->where('processed', true)->count(),
            'failed_webhooks' => WebhookLog::where('received_at', '>=', $startDate)
                ->where('processed', false)->count(),
            'webhooks_by_provider' => WebhookLog::where('received_at', '>=', $startDate)
                ->selectRaw('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->get()
                ->pluck('count', 'provider'),
            'recent_webhooks' => WebhookLog::with([])
                ->where('received_at', '>=', $startDate)
                ->orderBy('received_at', 'desc')
                ->limit(10)
                ->get()
        ];
    }

    /**
     * Retry processing a failed webhook
     */
    public function retryWebhook(WebhookLog $webhookLog)
    {
        try {
            DB::beginTransaction();

            // Reprocess the webhook
            $result = $this->processMyFatoorahWebhook($webhookLog->payload, $webhookLog);

            if ($result['success']) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => 'Retry successful: ' . $result['message']
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Webhook reprocessed successfully',
                    'result' => $result
                ];
            } else {
                $webhookLog->update([
                    'processed' => false,
                    'processing_notes' => 'Retry failed: ' . $result['error']
                ]);

                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Webhook reprocessing failed',
                    'error' => $result['error']
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Retry webhook error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error retrying webhook',
                'error' => $e->getMessage()
            ];
        }
    }
}
