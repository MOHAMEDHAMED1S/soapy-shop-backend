<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\WebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:test {--payment-id=} {--status=Paid} {--url=http://localhost:8000/api/v1/webhooks/test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook functionality by sending a test webhook';

    protected WebhookService $webhookService;

    /**
     * Create a new command instance.
     */
    public function __construct(WebhookService $webhookService)
    {
        parent::__construct();
        $this->webhookService = $webhookService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentId = $this->option('payment-id');
        $status = $this->option('status');
        $url = $this->option('url');

        $this->info('Testing webhook functionality...');

        // If no payment ID provided, find the latest payment
        if (!$paymentId) {
            $payment = Payment::latest()->first();
            if (!$payment) {
                $this->error('No payments found. Please create a payment first or specify --payment-id');
                return 1;
            }
            $paymentId = $payment->invoice_reference;
            $this->info("Using latest payment ID: {$paymentId}");
        }

        // Create test webhook data
        $testData = [
            'PaymentId' => $paymentId,
            'InvoiceId' => $paymentId,
            'PaymentStatus' => $status,
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

        $this->info('Sending test webhook...');
        $this->table(['Field', 'Value'], collect($testData)->map(fn($value, $key) => [$key, $value]));

        try {
            // Send HTTP request to webhook endpoint
            $response = Http::post($url, $testData);

            if ($response->successful()) {
                $this->info('✅ Webhook sent successfully!');
                $this->line('Response: ' . $response->body());
                
                // Check if webhook was logged
                $webhookLog = WebhookLog::latest()->first();
                if ($webhookLog) {
                    $this->info("📝 Webhook logged with ID: {$webhookLog->id}");
                    $this->info("Status: " . ($webhookLog->processed ? '✅ Processed' : '❌ Failed'));
                    if ($webhookLog->processing_notes) {
                        $this->info("Notes: {$webhookLog->processing_notes}");
                    }
                }
            } else {
                $this->error('❌ Webhook failed!');
                $this->error('Status: ' . $response->status());
                $this->error('Response: ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Error sending webhook: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
