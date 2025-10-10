<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OrderExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:export {--format=csv} {--status=} {--days=30} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export orders data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $status = $this->option('status');
        $days = $this->option('days');
        $output = $this->option('output');

        $this->info("📊 Exporting orders data...");
        $this->info("Format: {$format}, Status: " . ($status ?: 'all'), ", Days: {$days}");

        try {
            $query = Order::with(['orderItems.product', 'payment']);

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($days) {
                $startDate = Carbon::now()->subDays($days);
                $query->where('created_at', '>=', $startDate);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            $this->info("Found {$orders->count()} orders to export");

            $exportData = $this->formatExportData($orders, $format);

            $filename = $output ?: "orders_export_{$format}_" . now()->format('Y-m-d_H-i-s') . '.' . $format;
            $filePath = storage_path('app/public/exports/' . $filename);

            file_put_contents($filePath, $exportData);

            $this->info("📁 File saved: storage/app/public/exports/{$filename}");
            $this->info("✅ Export completed successfully!");

        } catch (\Exception $e) {
            $this->error('❌ Error exporting orders: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Format export data
     */
    protected function formatExportData($orders, string $format): string
    {
        switch ($format) {
            case 'csv':
                return $this->formatAsCsv($orders);
            case 'json':
                return json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            case 'xlsx':
                return $this->formatAsXlsx($orders);
            default:
                return $this->formatAsCsv($orders);
        }
    }

    /**
     * Format data as CSV
     */
    protected function formatAsCsv($orders): string
    {
        $csv = "Order Number,Customer Name,Customer Phone,Customer Email,Status,Total Amount,Currency,Created At,Payment Status,Payment Method,Tracking Number,Shipping Date,Delivery Date,Admin Notes\n";
        
        foreach ($orders as $order) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $this->escapeCsvValue($order->order_number),
                $this->escapeCsvValue($order->customer_name),
                $this->escapeCsvValue($order->customer_phone),
                $this->escapeCsvValue($order->customer_email ?? ''),
                $this->escapeCsvValue($order->status),
                $this->escapeCsvValue($order->total_amount),
                $this->escapeCsvValue($order->currency),
                $this->escapeCsvValue($order->created_at->format('Y-m-d H:i:s')),
                $this->escapeCsvValue($order->payment ? $order->payment->status : 'N/A'),
                $this->escapeCsvValue($order->payment ? $order->payment->payment_method : 'N/A'),
                $this->escapeCsvValue($order->tracking_number ?? ''),
                $this->escapeCsvValue($order->shipping_date ? $order->shipping_date->format('Y-m-d H:i:s') : ''),
                $this->escapeCsvValue($order->delivery_date ? $order->delivery_date->format('Y-m-d H:i:s') : ''),
                $this->escapeCsvValue($order->admin_notes ?? '')
            );
        }

        return $csv;
    }

    /**
     * Format data as XLSX (simplified)
     */
    protected function formatAsXlsx($orders): string
    {
        // For simplicity, we'll create a CSV file with .xlsx extension
        // In a real application, you would use a library like PhpSpreadsheet
        return $this->formatAsCsv($orders);
    }

    /**
     * Escape CSV value
     */
    protected function escapeCsvValue($value): string
    {
        if (is_array($value)) {
            return '"' . str_replace('"', '""', json_encode($value)) . '"';
        }
        
        $value = (string) $value;
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        
        return $value;
    }
}
