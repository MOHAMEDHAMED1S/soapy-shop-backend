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

    /**
     * Comprehensive payment verification
     * Checks all orders and verifies payment status with MyFatoorah
     * Organized into two main sections: awaiting_payment and completed orders
     */
    public function verifyPendingPayments(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('Starting comprehensive payment verification');

            // Section 1: Awaiting Payment Orders
            $awaitingPaymentResults = $this->verifyAwaitingPaymentOrders();
            
            // Section 2: Completed Orders (paid, shipped, delivered)
            $completedOrdersResults = $this->verifyCompletedOrders();

            // Overall Summary
            $overallSummary = [
                'total_orders_checked' => $awaitingPaymentResults['summary']['total_checked'] + $completedOrdersResults['summary']['total_checked'],
                'critical_issues_found' => 
                    $awaitingPaymentResults['summary']['paid_but_not_updated'] + 
                    $completedOrdersResults['summary']['not_paid_but_marked'] +
                    $completedOrdersResults['summary']['no_payment_record'],
                'verification_timestamp' => now()->toDateTimeString(),
                
                // Detailed breakdown
                'issues_breakdown' => [
                    'awaiting_payment_section' => [
                        'paid_but_not_updated' => $awaitingPaymentResults['summary']['paid_but_not_updated'],
                    ],
                    'completed_orders_section' => [
                        'not_paid_but_marked' => $completedOrdersResults['summary']['not_paid_but_marked'],
                        'no_payment_record' => $completedOrdersResults['summary']['no_payment_record'],
                    ],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'overall_summary' => $overallSummary,
                    'awaiting_payment_section' => $awaitingPaymentResults,
                    'completed_orders_section' => $completedOrdersResults,
                ],
                'message' => 'Comprehensive payment verification completed successfully'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in comprehensive payment verification', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error verifying payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify orders with status 'awaiting_payment'
     * Check if they were actually paid but not updated
     */
    private function verifyAwaitingPaymentOrders()
    {
        $pendingOrders = \App\Models\Order::where('status', 'awaiting_payment')
            ->with(['payment', 'orderItems.product'])
            ->whereHas('payment', function($query) {
                $query->whereNotNull('invoice_reference');
            })
            ->get();

        \Illuminate\Support\Facades\Log::info('Found ' . $pendingOrders->count() . ' orders with awaiting_payment status');

        $actuallyPaid = [];
        $correctlyPending = [];
        $errors = [];
        $processed = 0;

        foreach ($pendingOrders as $order) {
            $processed++;
            
            try {
                $invoiceReference = $order->payment->invoice_reference;
                
                // Add delay to avoid rate limiting (500ms between requests)
                if ($processed > 1) {
                    usleep(500000); // 0.5 seconds
                }
                
                \Illuminate\Support\Facades\Log::info("Verifying order {$processed}/{$pendingOrders->count()}: #{$order->id}");
                
                $paymentStatus = $this->verifyPaymentWithRetry($invoiceReference);

                if ($paymentStatus['success']) {
                    $invoiceData = $paymentStatus['data'];
                    $status = $invoiceData['InvoiceStatus'] ?? 'unknown';

                    if ($status === 'Paid') {
                        // ⚠️ Critical: Order is PAID but still marked as awaiting_payment
                        $actuallyPaid[] = [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'customer_phone' => $order->customer_phone,
                            'customer_email' => $order->customer_email,
                            'total_amount' => $order->total_amount,
                            'currency' => $order->currency,
                            'invoice_reference' => $invoiceReference,
                            'myfatoorah_status' => $status,
                            'database_status' => $order->status,
                            'order_created_at' => $order->created_at->toDateTimeString(),
                            'payment_date' => $invoiceData['CreatedDate'] ?? null,
                            'items_count' => $order->orderItems->count(),
                            'issue' => 'PAID_BUT_NOT_UPDATED',
                            'severity' => 'CRITICAL',
                        ];
                    } else {
                        // ✅ Correctly pending
                        $correctlyPending[] = [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'myfatoorah_status' => $status,
                            'invoice_reference' => $invoiceReference,
                        ];
                    }
                } else {
                    $errors[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'invoice_reference' => $invoiceReference,
                        'error' => $paymentStatus['error'] ?? 'Unknown error'
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'summary' => [
                'total_checked' => $pendingOrders->count(),
                'paid_but_not_updated' => count($actuallyPaid),
                'correctly_pending' => count($correctlyPending),
                'errors' => count($errors),
            ],
            'critical_issues' => $actuallyPaid,
            'correctly_pending' => $correctlyPending,
            'errors' => $errors,
        ];
    }

    /**
     * Verify orders with status 'paid', 'shipped', or 'delivered'
     * Check if they were actually paid in MyFatoorah
     * Also detects orders marked as completed but have NO payment record at all
     */
    private function verifyCompletedOrders()
    {
        // Get ALL completed orders (including those without payment records)
        $completedOrders = \App\Models\Order::whereIn('status', ['paid', 'shipped', 'delivered'])
            ->with(['payment', 'orderItems.product'])
            ->get();

        \Illuminate\Support\Facades\Log::info('Found ' . $completedOrders->count() . ' completed orders to verify');

        $correctlyPaid = [];
        $notActuallyPaid = [];
        $noPaymentRecord = [];
        $errors = [];

        $processed = 0;
        
        foreach ($completedOrders as $order) {
            $processed++;
            
            try {
                // Check if order has payment record with invoice_reference
                if (!$order->payment || !$order->payment->invoice_reference) {
                    // 🚨 CRITICAL: Order is marked as completed but has NO payment record!
                    $noPaymentRecord[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'customer_email' => $order->customer_email,
                        'total_amount' => $order->total_amount,
                        'currency' => $order->currency,
                        'database_status' => $order->status,
                        'has_payment_record' => $order->payment ? true : false,
                        'has_invoice_reference' => $order->payment && $order->payment->invoice_reference ? true : false,
                        'order_created_at' => $order->created_at->toDateTimeString(),
                        'items_count' => $order->orderItems->count(),
                        'issue' => 'NO_PAYMENT_RECORD',
                        'severity' => 'CRITICAL',
                        'description' => 'Order marked as completed but has no payment record or invoice reference'
                    ];
                    continue;
                }

                // Add delay to avoid rate limiting (500ms between requests)
                if ($processed > 1) {
                    usleep(500000); // 0.5 seconds
                }
                
                \Illuminate\Support\Facades\Log::info("Verifying completed order {$processed}/{$completedOrders->count()}: #{$order->id}");

                // Verify with MyFatoorah
                $invoiceReference = $order->payment->invoice_reference;
                $paymentStatus = $this->verifyPaymentWithRetry($invoiceReference);

                if ($paymentStatus['success']) {
                    $invoiceData = $paymentStatus['data'];
                    $status = $invoiceData['InvoiceStatus'] ?? 'unknown';

                    if ($status === 'Paid') {
                        // ✅ Correctly paid
                        $correctlyPaid[] = [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'database_status' => $order->status,
                            'myfatoorah_status' => $status,
                            'verified' => true,
                        ];
                    } else {
                        // ⚠️ Critical: Order is marked as paid/shipped/delivered but NOT paid in MyFatoorah
                        $notActuallyPaid[] = [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'customer_phone' => $order->customer_phone,
                            'customer_email' => $order->customer_email,
                            'total_amount' => $order->total_amount,
                            'currency' => $order->currency,
                            'invoice_reference' => $invoiceReference,
                            'database_status' => $order->status,
                            'myfatoorah_status' => $status,
                            'order_created_at' => $order->created_at->toDateTimeString(),
                            'items_count' => $order->orderItems->count(),
                            'issue' => 'MARKED_AS_PAID_BUT_NOT_PAID',
                            'severity' => 'CRITICAL',
                        ];
                    }
                } else {
                    $errors[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'database_status' => $order->status,
                        'invoice_reference' => $invoiceReference,
                        'error' => $paymentStatus['error'] ?? 'Unknown error'
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'database_status' => $order->status,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'summary' => [
                'total_checked' => $completedOrders->count(),
                'correctly_paid' => count($correctlyPaid),
                'not_paid_but_marked' => count($notActuallyPaid),
                'no_payment_record' => count($noPaymentRecord),
                'errors' => count($errors),
            ],
            'critical_issues' => array_merge($notActuallyPaid, $noPaymentRecord),
            'correctly_paid' => array_slice($correctlyPaid, 0, 10), // Show first 10 only to reduce response size
            'errors' => $errors,
        ];
    }

    /**
     * Verify payment with retry logic for rate limiting
     * Implements exponential backoff for 429 errors
     */
    private function verifyPaymentWithRetry($invoiceReference, $maxRetries = 3)
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $result = $this->paymentService->verifyPayment($invoiceReference);

                // Check if error is rate limiting (429)
                if (!$result['success']) {
                    $error = $result['error'] ?? '';
                    
                    // Check for rate limiting error
                    if (strpos($error, '429') !== false || strpos($error, '1015') !== false) {
                        $lastError = $result;
                        
                        // Exponential backoff: 2s, 4s, 8s
                        $waitTime = pow(2, $attempt) * 1000000; // in microseconds
                        
                        \Illuminate\Support\Facades\Log::warning("Rate limit hit for invoice {$invoiceReference}, retry {$attempt}/{$maxRetries}, waiting " . ($waitTime/1000000) . "s");
                        
                        if ($attempt < $maxRetries) {
                            usleep($waitTime);
                            continue;
                        }
                    }
                }

                return $result;

            } catch (\Exception $e) {
                $lastError = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt) * 1000000;
                    usleep($waitTime);
                    continue;
                }
            }
        }

        // If all retries failed, return last error
        return $lastError ?? [
            'success' => false,
            'error' => 'Max retries reached'
        ];
    }
}
