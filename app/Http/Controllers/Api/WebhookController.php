<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Models\AdminNotification;
use App\Services\PaymentService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    protected $paymentService;
    protected $webhookService;

    public function __construct(PaymentService $paymentService, WebhookService $webhookService)
    {
        $this->paymentService = $paymentService;
        $this->webhookService = $webhookService;
    }

    /**
     * Handle MyFatoorah webhook notifications
     */
    public function handleMyFatoorahWebhook(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('MyFatoorah Webhook Received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Validate webhook signature (if provided)
            if (!$this->webhookService->verifyWebhookSignature($request)) {
                Log::warning('MyFatoorah Webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                ], 401);
            }

            // Store webhook log
            $webhookLog = WebhookLog::create([
                'provider' => 'myfatoorah',
                'payload' => $request->all(),
                'received_at' => now(),
                'processed' => false,
                'processing_notes' => 'Webhook received, processing...'
            ]);

            DB::beginTransaction();

            // Process the webhook
            $processingResult = $this->webhookService->processMyFatoorahWebhook($request->all(), $webhookLog);

            if ($processingResult['success']) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => $processingResult['message']
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                    'webhook_id' => $webhookLog->id
                ]);
            } else {
                $webhookLog->update([
                    'processed' => false,
                    'processing_notes' => 'Processing failed: ' . $processingResult['error']
                ]);

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Webhook processing failed',
                    'error' => $processingResult['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('MyFatoorah Webhook Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get webhook logs for admin
     */
    public function getWebhookLogs(Request $request)
    {
        try {
            $query = WebhookLog::query();

            // Filter by provider
            if ($request->has('provider') && $request->provider) {
                $query->where('provider', $request->provider);
            }

            // Filter by processed status
            if ($request->has('processed') && $request->processed !== null) {
                $query->where('processed', $request->processed);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('received_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('received_at', '<=', $request->date_to);
            }

            // Sort by received date
            $query->orderBy('received_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $webhookLogs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $webhookLogs,
                'message' => 'Webhook logs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Get webhook logs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving webhook logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry processing a failed webhook
     */
    public function retryWebhook(Request $request, $webhookId)
    {
        try {
            $webhookLog = WebhookLog::find($webhookId);
            if (!$webhookLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook log not found'
                ], 404);
            }

            DB::beginTransaction();

            // Reprocess the webhook
            $processingResult = $this->webhookService->processMyFatoorahWebhook($webhookLog->payload, $webhookLog);

            if ($processingResult['success']) {
                $webhookLog->update([
                    'processed' => true,
                    'processing_notes' => 'Retry successful: ' . $processingResult['message']
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Webhook reprocessed successfully',
                    'result' => $processingResult
                ]);
            } else {
                $webhookLog->update([
                    'processed' => false,
                    'processing_notes' => 'Retry failed: ' . $processingResult['error']
                ]);

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Webhook reprocessing failed',
                    'error' => $processingResult['error']
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Retry webhook error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrying webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook statistics
     */
    public function getWebhookStatistics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $statistics = $this->webhookService->getWebhookStatistics($period);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Webhook statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Get webhook statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving webhook statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request)
    {
        try {
            $testData = [
                'PaymentId' => 'test_' . time(),
                'InvoiceId' => 'test_invoice_' . time(),
                'PaymentStatus' => 'Paid',
                'TransactionId' => 'test_txn_' . time(),
                'Amount' => 35.75,
                'Currency' => 'KWD',
                'CustomerName' => 'Test Customer',
                'CustomerEmail' => 'test@example.com',
                'CustomerMobile' => '96555555555',
                'PaymentMethod' => 'vm',
                'PaymentDate' => now()->toISOString(),
                'ReferenceId' => 'test_ref_' . time()
            ];

            // Log the test webhook
            Log::info('Test Webhook Received', $testData);

            return response()->json([
                'success' => true,
                'message' => 'Test webhook received successfully',
                'data' => $testData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Test webhook error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test webhook failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
