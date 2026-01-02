<?php

namespace App\Services;

use App\Models\Export;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;

class ExportService
{
    /**
     * Export data to specified format
     */
    public function exportData(string $type, string $format, array $filters = [], ?int $userId = null): array
    {
        // Create export record
        $export = Export::create([
            'type' => $type,
            'format' => $format,
            'status' => 'processing',
            'user_id' => $userId,
            'filters' => $filters,
            'file_name' => $this->generateFileName($type, $format),
        ]);

        try {
            // Get data based on type
            $data = $this->getData($type, $filters);
            
            // Generate file based on format
            $filePath = $this->generateFile($data, $type, $format, $export->file_name);
            
            // Update export record
            $export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $this->getFileSize($filePath),
                'records_count' => count($data),
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'export_id' => $export->id,
                'download_url' => url("/api/v1/exports/{$export->id}/download"),
                'file_name' => $export->file_name,
                'file_path' => $filePath,
                'records_count' => count($data),
            ];

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get data based on type and filters
     */
    private function getData(string $type, array $filters): array
    {
        switch ($type) {
            case 'products':
                return $this->getProductsData($filters);
            case 'customers':
                return $this->getCustomersData($filters);
            case 'orders':
                return $this->getOrdersData($filters);
            default:
                throw new \InvalidArgumentException("Unsupported export type: {$type}");
        }
    }

    /**
     * Get products data with filters
     */
    private function getProductsData(array $filters = []): array
    {
        $query = Product::with(['category']);
        
        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }
        
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        // Apply limit
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }
        
        return $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'currency' => $product->currency,
                'is_available' => $product->is_available,
                'stock_quantity' => $product->stock_quantity,
                'category' => $product->category ? $product->category->name : null,
                'images' => json_encode($product->images),
                'created_at' => $product->created_at->toISOString(),
                'updated_at' => $product->updated_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Get customers data with filters
     */
    private function getCustomersData(array $filters): array
    {
        $query = Customer::withCount(['orders']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        return $query->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'address' => is_array($customer->address) ? $this->formatAddress($customer->address) : $customer->address,
                'status' => $customer->status,
                'orders_count' => $customer->orders_count,
                'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $customer->updated_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Get orders data for export
     */
    private function getOrdersData(array $filters = []): array
    {
        $query = Order::with(['customer', 'orderItems.product', 'payment']);

        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['total_min'])) {
            $query->where('total_amount', '>=', $filters['total_min']);
        }

        if (isset($filters['total_max'])) {
            $query->where('total_amount', '<=', $filters['total_max']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Country name mapping
        $countryNames = [
            'KW' => 'Kuwait',
            'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates',
            'BH' => 'Bahrain',
            'QA' => 'Qatar',
            'OM' => 'Oman',
        ];

        return $query->orderBy('created_at', 'desc')->get()->map(function ($order) use ($countryNames) {
            // Extract shipping address details
            $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
            $shippingCity = $shippingAddress['city'] ?? '';
            $shippingGovernorate = $shippingAddress['governorate'] ?? '';
            $shippingStreet = $shippingAddress['street'] ?? '';
            
            // Get items details
            $itemsDetails = $order->orderItems->map(function ($item) {
                $productName = $item->product ? $item->product->title : ($item->product_title ?? 'Unknown');
                return "{$productName} x{$item->quantity}";
            })->implode(', ');

            // Get payment status
            $paymentStatus = $order->payment ? $order->payment->status : 'unpaid';

            // Get country name
            $countryCode = $order->country_code ?? '';
            $countryName = $countryNames[$countryCode] ?? $countryCode;

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->name ?? $order->customer_name,
                'customer_email' => $order->customer->email ?? $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'status' => $order->status,
                'payment_status' => $paymentStatus,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_code' => $order->discount_code ?? '',
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'items_count' => $order->orderItems->count(),
                'items_details' => $itemsDetails,
                'shipping_city' => $shippingCity,
                'shipping_governorate' => $shippingGovernorate,
                'shipping_street' => $shippingStreet,
                'shipping_address_full' => is_array($order->shipping_address) ? $this->formatAddress($order->shipping_address) : $order->shipping_address,
                'notes' => $order->notes ?? '',
                'tracking_number' => $order->tracking_number ?? '',
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * Generate file based on format
     */
    private function generateFile(array $data, string $type, string $format, string $fileName): string
    {
        $filePath = "exports/{$fileName}";

        switch (strtolower($format)) {
            case 'json':
                return $this->generateJsonFile($data, $filePath);
            case 'csv':
                return $this->generateCsvFile($data, $filePath);
            case 'excel':
            case 'xlsx':
                return $this->generateExcelFile($data, $filePath, $type);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Generate JSON file
     */
    private function generateJsonFile(array $data, string $filePath): string
    {
        $jsonContent = json_encode([
            'exported_at' => now()->toISOString(),
            'total_records' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Storage::disk('public')->put('exports/' . basename($filePath), $jsonContent);
        return $filePath;
    }

    /**
     * Generate CSV file
     */
    private function generateCsvFile(array $data, string $filePath): string
    {
        if (empty($data)) {
            Storage::disk('public')->put('exports/' . basename($filePath), '');
            return $filePath;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $headers = array_keys($data[0]);
        $sheet->fromArray([$headers], null, 'A1');

        // Add data
        $sheet->fromArray($data, null, 'A2');

        $writer = new CsvWriter($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export_csv_');
        $writer->save($tempFile);

        Storage::disk('public')->put('exports/' . basename($filePath), file_get_contents($tempFile));
        unlink($tempFile);

        return $filePath;
    }

    /**
     * Generate Excel file
     */
    private function generateExcelFile(array $data, string $filePath, string $type): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet title
        $sheet->setTitle(ucfirst($type));

        if (!empty($data)) {
            // Add headers with styling
            $headers = array_keys($data[0]);
            $sheet->fromArray([$headers], null, 'A1');
            
            // Style headers
            $headerRange = 'A1:' . chr(64 + count($headers)) . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E2E8F0');

            // Add data
            $sheet->fromArray($data, null, 'A2');

            // Auto-size columns
            foreach (range('A', chr(64 + count($headers))) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export_excel_');
        $writer->save($tempFile);

        Storage::disk('public')->put('exports/' . basename($filePath), file_get_contents($tempFile));
        unlink($tempFile);

        return $filePath;
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(string $type, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        // Use xlsx extension for excel format
        $extension = strtolower($format) === 'excel' ? 'xlsx' : strtolower($format);
        
        return "{$type}_export_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get export by ID
     */
    public function getExport(int $exportId): ?Export
    {
        return Export::find($exportId);
    }

    /**
     * Delete old exports
     */
    public function cleanupOldExports(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $exports = Export::where('created_at', '<', $cutoffDate)->get();
        
        $deletedCount = 0;
        foreach ($exports as $export) {
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }
            $export->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        return [
            'total_exports' => Export::count(),
            'completed_exports' => Export::where('status', 'completed')->count(),
            'failed_exports' => Export::where('status', 'failed')->count(),
            'processing_exports' => Export::where('status', 'processing')->count(),
            'exports_by_type' => Export::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'exports_by_format' => Export::selectRaw('format, COUNT(*) as count')
                ->groupBy('format')
                ->pluck('count', 'format')
                ->toArray(),
        ];
    }

    /**
     * Get file size safely
     */
    private function getFileSize(string $filePath): ?int
    {
        try {
            if (Storage::disk('public')->exists('exports/' . basename($filePath))) {
                return Storage::disk('public')->size('exports/' . basename($filePath));
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format address array to readable string
     */
    private function formatAddress(array $address): string
    {
        $parts = [];
        
        if (!empty($address['street'])) {
            $parts[] = $address['street'];
        }
        
        if (!empty($address['city'])) {
            $parts[] = $address['city'];
        }
        
        if (!empty($address['governorate'])) {
            $parts[] = $address['governorate'];
        }
        
        if (!empty($address['postal_code'])) {
            $parts[] = $address['postal_code'];
        }
        
        return implode(', ', $parts);
    }
}