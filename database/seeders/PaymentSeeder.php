<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Order;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing orders
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->command->info('No orders found. Please run OrderSeeder first.');
            return;
        }

        foreach ($orders as $order) {
            // Check if payment already exists for this order
            $existingPayment = Payment::where('order_id', $order->id)->first();
            
            if (!$existingPayment) {
                // Create a payment for each order only if it doesn't exist
                Payment::create([
                    'order_id' => $order->id,
                    'provider' => 'myfatoorah',
                    'payment_method' => $this->getRandomPaymentMethod(),
                    'invoice_reference' => 'INV-' . strtoupper(uniqid()),
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => $this->getRandomPaymentStatus(),
                    'response_raw' => [
                        'InvoiceId' => 'INV-' . strtoupper(uniqid()),
                        'InvoiceURL' => 'https://demo.myfatoorah.com/payment/' . strtoupper(uniqid()),
                        'PaymentMethod' => $this->getRandomPaymentMethod(),
                        'InvoiceStatus' => $this->getRandomPaymentStatus(),
                        'CreatedDate' => now()->toISOString(),
                    ],
                ]);
            }
        }

        $this->command->info('Payments seeded successfully!');
    }

    /**
     * Get random payment method
     */
    private function getRandomPaymentMethod(): string
    {
        $methods = ['card', 'knet', 'visa', 'mastercard', 'amex'];
        return $methods[array_rand($methods)];
    }

    /**
     * Get random payment status
     */
    private function getRandomPaymentStatus(): string
    {
        $statuses = ['initiated', 'pending', 'paid', 'failed'];
        return $statuses[array_rand($statuses)];
    }
}
