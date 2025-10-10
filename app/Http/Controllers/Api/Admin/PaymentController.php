<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['order']);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by provider
            if ($request->has('provider') && $request->provider) {
                $query->where('provider', $request->provider);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search by invoice reference or order number
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('invoice_reference', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('order', function ($orderQuery) use ($searchTerm) {
                          $orderQuery->where('order_number', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            // Add summary statistics
            $summary = [
                'total_payments' => Payment::count(),
                'initiated_payments' => Payment::where('status', 'initiated')->count(),
                'paid_payments' => Payment::where('status', 'paid')->count(),
                'failed_payments' => Payment::where('status', 'failed')->count(),
                'total_amount' => Payment::where('status', 'paid')->sum('amount'),
                'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'summary' => $summary
                ],
                'message' => 'Payments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment
     */
    public function show(string $id)
    {
        try {
            $payment = Payment::with(['order.orderItems.product'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = now()->subDays($period);

            $stats = [
                'total_payments' => Payment::where('created_at', '>=', $startDate)->count(),
                'total_revenue' => Payment::where('created_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->sum('amount'),
                'initiated_payments' => Payment::where('status', 'initiated')->count(),
                'paid_payments' => Payment::where('status', 'paid')->count(),
                'failed_payments' => Payment::where('status', 'failed')->count(),
                'average_payment_value' => Payment::where('created_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->avg('amount'),
                'payments_by_status' => Payment::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status'),
                'payments_by_provider' => Payment::selectRaw('provider, COUNT(*) as count')
                    ->groupBy('provider')
                    ->get()
                    ->pluck('count', 'provider'),
                'recent_payments' => Payment::with(['order'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Payment statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed payment
     */
    public function retryPayment(Request $request, string $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|string|in:card,knet,visa,mastercard,amex',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payment = Payment::with(['order'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if ($payment->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed payments can be retried'
                ], 400);
            }

            // Initiate new payment
            $paymentResponse = $this->paymentService->initiatePayment($payment->order, [
                'payment_method' => $request->payment_method,
                'customer_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if (!$paymentResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment retry failed',
                    'error' => $paymentResponse['error']
                ], 500);
            }

            // Update payment record
            $payment->update([
                'payment_method' => $request->payment_method,
                'invoice_reference' => $paymentResponse['data']['InvoiceId'],
                'status' => 'initiated',
                'response_raw' => $paymentResponse['data'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'invoice_id' => $paymentResponse['data']['InvoiceId'],
                    'payment_url' => $paymentResponse['data']['InvoiceURL'],
                    'order_id' => $payment->order->id,
                    'order_number' => $payment->order->order_number,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                'message' => 'Payment retry initiated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrying payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook logs
     */
    public function webhookLogs(Request $request)
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

            // Sort
            $query->orderBy('received_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Webhook logs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving webhook logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook log details
     */
    public function webhookLog(string $id)
    {
        try {
            $log = WebhookLog::find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook log not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
                'message' => 'Webhook log retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving webhook log',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
