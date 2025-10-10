<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Generate sales report
     */
    public function salesReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'in:json,csv,xlsx',
                'group_by' => 'in:day,week,month',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $format = $request->get('format', 'json');
            $groupBy = $request->get('group_by', 'day');

            $query = Order::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'paid');

            // Group by period
            switch ($groupBy) {
                case 'day':
                    $query->selectRaw('DATE(created_at) as period, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
                          ->groupBy('period')
                          ->orderBy('period');
                    break;
                case 'week':
                    $query->selectRaw('YEAR(created_at) as year, WEEK(created_at) as week, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
                          ->groupBy('year', 'week')
                          ->orderBy('year', 'week');
                    break;
                case 'month':
                    $query->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
                          ->groupBy('year', 'month')
                          ->orderBy('year', 'month');
                    break;
            }

            $salesData = $query->get();

            // Calculate summary
            $summary = [
                'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->count(),
                'total_revenue' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'average_order_value' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->avg('total_amount'),
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'group_by' => $groupBy
                ]
            ];

            $reportData = [
                'summary' => $summary,
                'data' => $salesData
            ];

            switch ($format) {
                case 'csv':
                    return $this->exportSalesToCsv($reportData, $startDate, $endDate);
                case 'xlsx':
                    return $this->exportSalesToXlsx($reportData, $startDate, $endDate);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $reportData,
                        'message' => 'Sales report generated successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating sales report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate products report
     */
    public function productsReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'nullable|exists:categories,id',
                'format' => 'in:json,csv,xlsx',
                'include_inactive' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Product::with('category');

            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            if (!$request->get('include_inactive', false)) {
                $query->where('is_available', true);
            }

            $products = $query->get();

            // Calculate statistics
            $stats = [
                'total_products' => $products->count(),
                'available_products' => $products->where('is_available', true)->count(),
                'unavailable_products' => $products->where('is_available', false)->count(),
                'low_stock_products' => $products->where('stock_quantity', '<=', 10)->count(),
                'out_of_stock_products' => $products->where('stock_quantity', 0)->count(),
                'average_price' => $products->avg('price'),
                'total_value' => $products->sum(function ($product) {
                    return $product->price * ($product->stock_quantity ?? 0);
                }),
            ];

            $reportData = [
                'statistics' => $stats,
                'products' => $products
            ];

            $format = $request->get('format', 'json');

            switch ($format) {
                case 'csv':
                    return $this->exportProductsToCsv($reportData);
                case 'xlsx':
                    return $this->exportProductsToXlsx($reportData);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $reportData,
                        'message' => 'Products report generated successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating products report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate customers report
     */
    public function customersReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'format' => 'in:json,csv,xlsx',
                'customer_type' => 'in:all,active,inactive,vip,new',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Customer::with(['orders']);

            if ($request->start_date && $request->end_date) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Filter by customer type
            switch ($request->get('customer_type', 'all')) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'vip':
                    $query->where('total_spent', '>', 1000); // Define VIP threshold
                    break;
                case 'new':
                    $query->where('created_at', '>=', Carbon::now()->subDays(30));
                    break;
            }

            $customers = $query->get();

            // Calculate statistics
            $stats = [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->where('is_active', true)->count(),
                'inactive_customers' => $customers->where('is_active', false)->count(),
                'vip_customers' => $customers->where('total_spent', '>', 1000)->count(),
                'new_customers' => $customers->where('created_at', '>=', Carbon::now()->subDays(30))->count(),
                'total_spent' => $customers->sum('total_spent'),
                'average_order_value' => $customers->avg('total_spent'),
                'customers_with_orders' => $customers->where('orders_count', '>', 0)->count(),
            ];

            $reportData = [
                'statistics' => $stats,
                'customers' => $customers
            ];

            $format = $request->get('format', 'json');

            switch ($format) {
                case 'csv':
                    return $this->exportCustomersToCsv($reportData);
                case 'xlsx':
                    return $this->exportCustomersToXlsx($reportData);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $reportData,
                        'message' => 'Customers report generated successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating customers report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate comprehensive dashboard report
     */
    public function dashboardReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'in:json,csv,xlsx',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $format = $request->get('format', 'json');

            // Orders data
            $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
            $paidOrders = $orders->where('status', 'paid');

            // Products data
            $products = Product::all();

            // Customers data
            $customers = Customer::whereBetween('created_at', [$startDate, $endDate])->get();

            // Payments data
            $payments = Payment::whereBetween('created_at', [$startDate, $endDate])->get();

            $reportData = [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'orders' => [
                    'total' => $orders->count(),
                    'paid' => $paidOrders->count(),
                    'pending' => $orders->where('status', 'pending')->count(),
                    'cancelled' => $orders->where('status', 'cancelled')->count(),
                    'total_revenue' => $paidOrders->sum('total_amount'),
                    'average_order_value' => $paidOrders->avg('total_amount'),
                ],
                'products' => [
                    'total' => $products->count(),
                    'available' => $products->where('is_available', true)->count(),
                    'unavailable' => $products->where('is_available', false)->count(),
                    'low_stock' => $products->where('stock_quantity', '<=', 10)->count(),
                ],
                'customers' => [
                    'total' => $customers->count(),
                    'active' => $customers->where('is_active', true)->count(),
                    'new' => $customers->where('created_at', '>=', Carbon::now()->subDays(30))->count(),
                ],
                'payments' => [
                    'total' => $payments->count(),
                    'successful' => $payments->where('status', 'paid')->count(),
                    'failed' => $payments->where('status', 'failed')->count(),
                    'total_amount' => $payments->where('status', 'paid')->sum('amount'),
                ]
            ];

            switch ($format) {
                case 'csv':
                    return $this->exportDashboardToCsv($reportData, $startDate, $endDate);
                case 'xlsx':
                    return $this->exportDashboardToXlsx($reportData, $startDate, $endDate);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $reportData,
                        'message' => 'Dashboard report generated successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating dashboard report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Export methods
    private function exportSalesToCsv($data, $startDate, $endDate)
    {
        $filename = 'sales_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Summary
            fputcsv($file, ['Summary']);
            fputcsv($file, ['Total Orders', $data['summary']['total_orders']]);
            fputcsv($file, ['Total Revenue', $data['summary']['total_revenue']]);
            fputcsv($file, ['Average Order Value', $data['summary']['average_order_value']]);
            fputcsv($file, []);
            
            // Data
            fputcsv($file, ['Period', 'Orders Count', 'Total Revenue']);
            foreach ($data['data'] as $row) {
                fputcsv($file, [
                    $row->period ?? $row->year . '-' . $row->month ?? $row->year . '-' . $row->week,
                    $row->orders_count,
                    $row->total_revenue
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportProductsToCsv($data)
    {
        $filename = 'products_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Statistics
            fputcsv($file, ['Statistics']);
            fputcsv($file, ['Total Products', $data['statistics']['total_products']]);
            fputcsv($file, ['Available Products', $data['statistics']['available_products']]);
            fputcsv($file, ['Average Price', $data['statistics']['average_price']]);
            fputcsv($file, []);
            
            // Products
            fputcsv($file, ['ID', 'Title', 'Price', 'Currency', 'Available', 'Stock', 'Category']);
            foreach ($data['products'] as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->title,
                    $product->price,
                    $product->currency,
                    $product->is_available ? 'Yes' : 'No',
                    $product->stock_quantity ?? 0,
                    $product->category->name ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCustomersToCsv($data)
    {
        $filename = 'customers_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Statistics
            fputcsv($file, ['Statistics']);
            fputcsv($file, ['Total Customers', $data['statistics']['total_customers']]);
            fputcsv($file, ['Active Customers', $data['statistics']['active_customers']]);
            fputcsv($file, ['Total Spent', $data['statistics']['total_spent']]);
            fputcsv($file, []);
            
            // Customers
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Total Spent', 'Orders Count', 'Active', 'Created At']);
            foreach ($data['customers'] as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->total_spent,
                    $customer->orders_count,
                    $customer->is_active ? 'Yes' : 'No',
                    $customer->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportDashboardToCsv($data, $startDate, $endDate)
    {
        $filename = 'dashboard_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Dashboard Report - ' . $data['period']['start'] . ' to ' . $data['period']['end']]);
            fputcsv($file, []);
            
            fputcsv($file, ['Orders']);
            fputcsv($file, ['Total Orders', $data['orders']['total']]);
            fputcsv($file, ['Paid Orders', $data['orders']['paid']]);
            fputcsv($file, ['Total Revenue', $data['orders']['total_revenue']]);
            fputcsv($file, ['Average Order Value', $data['orders']['average_order_value']]);
            fputcsv($file, []);
            
            fputcsv($file, ['Products']);
            fputcsv($file, ['Total Products', $data['products']['total']]);
            fputcsv($file, ['Available Products', $data['products']['available']]);
            fputcsv($file, ['Low Stock Products', $data['products']['low_stock']]);
            fputcsv($file, []);
            
            fputcsv($file, ['Customers']);
            fputcsv($file, ['Total Customers', $data['customers']['total']]);
            fputcsv($file, ['Active Customers', $data['customers']['active']]);
            fputcsv($file, ['New Customers', $data['customers']['new']]);
            fputcsv($file, []);
            
            fputcsv($file, ['Payments']);
            fputcsv($file, ['Total Payments', $data['payments']['total']]);
            fputcsv($file, ['Successful Payments', $data['payments']['successful']]);
            fputcsv($file, ['Total Amount', $data['payments']['total_amount']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportSalesToXlsx($data, $startDate, $endDate)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }

    private function exportProductsToXlsx($data)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }

    private function exportCustomersToXlsx($data)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }

    private function exportDashboardToXlsx($data, $startDate, $endDate)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }
}
