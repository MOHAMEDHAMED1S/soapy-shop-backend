<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerService
{
    /**
     * Find or create customer for order
     */
    public function findOrCreateCustomerForOrder(array $orderData): Customer
    {
        try {
            $phone = $orderData['customer_phone'];
            
            // Try to find existing customer by phone
            $customer = Customer::where('phone', $phone)->first();
            
            if ($customer) {
                // Update customer data if provided
                $updateData = [];
                
                if (isset($orderData['customer_name']) && $orderData['customer_name'] !== $customer->name) {
                    $updateData['name'] = $orderData['customer_name'];
                }
                
                // Handle email update carefully to avoid unique constraint violation
                if (isset($orderData['customer_email']) && !empty($orderData['customer_email'])) {
                    $newEmail = $orderData['customer_email'];
                    
                    // Only update if the email is different from current email
                    if ($newEmail !== $customer->email) {
                        // Check if this email is already used by another customer
                        $existingCustomerWithEmail = Customer::where('email', $newEmail)
                            ->where('id', '!=', $customer->id)
                            ->first();
                        
                        if (!$existingCustomerWithEmail) {
                            $updateData['email'] = $newEmail;
                        } else {
                            Log::warning('Email already exists for another customer', [
                                'email' => $newEmail,
                                'existing_customer_id' => $existingCustomerWithEmail->id,
                                'current_customer_id' => $customer->id,
                                'phone' => $phone
                            ]);
                        }
                    }
                }
                
                if (isset($orderData['shipping_address']) && $orderData['shipping_address'] !== $customer->address) {
                    $updateData['address'] = $orderData['shipping_address'];
                }
                
                if (!empty($updateData)) {
                    $customer->update($updateData);
                }
                
                Log::info('Found existing customer for order', [
                    'customer_id' => $customer->id,
                    'phone' => $phone,
                    'order_data' => $orderData
                ]);
                
                return $customer;
            }
            
            // Create new customer
            $customerData = [
                'name' => $orderData['customer_name'],
                'phone' => $phone,
                'address' => $orderData['shipping_address'] ?? null,
                'is_active' => true,
                'phone_verified' => false,
                'email_verified' => false,
            ];
            
            // Handle email for new customer - check for duplicates
            if (isset($orderData['customer_email']) && !empty($orderData['customer_email'])) {
                $existingCustomerWithEmail = Customer::where('email', $orderData['customer_email'])->first();
                
                if (!$existingCustomerWithEmail) {
                    $customerData['email'] = $orderData['customer_email'];
                } else {
                    Log::warning('Email already exists when creating new customer', [
                        'email' => $orderData['customer_email'],
                        'existing_customer_id' => $existingCustomerWithEmail->id,
                        'new_phone' => $phone
                    ]);
                    // Don't set email to avoid duplicate constraint violation
                }
            }
            
            $customer = Customer::create($customerData);
            
            Log::info('Created new customer for order', [
                'customer_id' => $customer->id,
                'phone' => $phone,
                'order_data' => $orderData
            ]);
            
            return $customer;
            
        } catch (\Exception $e) {
            Log::error('Error finding or creating customer for order', [
                'order_data' => $orderData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update customer statistics after order completion
     */
    public function updateCustomerStatistics(Order $order): void
    {
        try {
            if (!$order->customer_id) {
                Log::warning('Order has no customer_id, skipping statistics update', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
                return;
            }

            $customer = Customer::find($order->customer_id);
            if (!$customer) {
                Log::warning('Customer not found for order', [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id
                ]);
                return;
            }

            $customer->updateOrderStatistics($order);
            
            Log::info('Updated customer statistics', [
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'total_orders' => $customer->total_orders,
                'total_spent' => $customer->total_spent
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating customer statistics', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(int $period = 30): array
    {
        $startDate = Carbon::now()->subDays($period);
        
        $analytics = [
            'overview' => [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::active()->count(),
                'new_customers' => Customer::new()->count(),
                'vip_customers' => Customer::vip()->count(),
                'recent_customers' => Customer::recent($period)->count(),
            ],
            'period_stats' => [
                'new_customers_count' => Customer::where('created_at', '>=', $startDate)->count(),
                'total_revenue' => Customer::where('last_order_at', '>=', $startDate)->sum('total_spent'),
                'average_order_value' => Customer::where('last_order_at', '>=', $startDate)->avg('average_order_value'),
                'customer_retention_rate' => $this->calculateRetentionRate($period),
            ],
            'customer_segments' => [
                'new_customers' => Customer::new()->count(),
                'returning_customers' => Customer::where('total_orders', '>', 1)->count(),
                'vip_customers' => Customer::vip()->count(),
                'inactive_customers' => Customer::where('last_order_at', '<', Carbon::now()->subDays(90))->count(),
            ],
            'top_customers' => Customer::where('last_order_at', '>=', $startDate)
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get(),
            'customer_growth' => $this->getCustomerGrowthData($period),
            'lifetime_value_distribution' => $this->getLifetimeValueDistribution(),
        ];

        return $analytics;
    }

    /**
     * Get customer details with orders
     */
    public function getCustomerDetails(int $customerId): ?array
    {
        $paidStatuses = ['paid', 'shipped', 'delivered'];
        
        $customer = Customer::with(['orders.orderItems.product', 'latestOrder'])
            ->withCount(['orders as calculated_total_orders' => function($query) use ($paidStatuses) {
                $query->whereIn('status', $paidStatuses);
            }])
            ->withSum(['orders as calculated_total_spent' => function($query) use ($paidStatuses) {
                $query->whereIn('status', $paidStatuses);
            }], 'total_amount')
            ->withAvg(['orders as calculated_average_order_value' => function($query) use ($paidStatuses) {
                $query->whereIn('status', $paidStatuses);
            }], 'total_amount')
            ->find($customerId);

        if (!$customer) {
            return null;
        }

        // Use calculated values (dynamic from database) with fallback to stored values
        $totalOrders = $customer->calculated_total_orders ?? $customer->total_orders ?? 0;
        $totalSpent = $customer->calculated_total_spent ?? $customer->total_spent ?? 0;
        $averageOrderValue = $customer->calculated_average_order_value ?? $customer->average_order_value ?? 0;

        return [
            'customer' => $customer,
            'order_history' => $customer->orders()->orderBy('created_at', 'desc')->get(),
            'statistics' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'average_order_value' => $averageOrderValue,
                'last_order_date' => $customer->last_order_at,
                'customer_since' => $customer->created_at,
                'is_vip' => $customer->isVip(),
                'is_new' => $customer->isNew(),
                'is_active' => $customer->isActive(),
            ],
            'preferences' => $customer->preferences ?? [],
        ];
    }

    /**
     * Search customers
     */
    public function searchCustomers(string $query, int $limit = 20): array
    {
        $paidStatuses = ['paid', 'shipped', 'delivered'];
        
        $customers = Customer::where(function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('phone', 'like', '%' . $query . '%')
              ->orWhere('email', 'like', '%' . $query . '%');
        })
        ->with(['latestOrder'])
        ->withCount(['orders as total_orders' => function($query) use ($paidStatuses) {
            $query->whereIn('status', $paidStatuses);
        }])
        ->withSum(['orders as calculated_total_spent' => function($query) use ($paidStatuses) {
            $query->whereIn('status', $paidStatuses);
        }], 'total_amount')
        ->withAvg(['orders as calculated_average_order_value' => function($query) use ($paidStatuses) {
            $query->whereIn('status', $paidStatuses);
        }], 'total_amount')
        ->orderBy('total_spent', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($customer) {
            // Use calculated values with fallback
            $customer->total_orders = $customer->total_orders ?? 0;
            $customer->total_spent = $customer->calculated_total_spent ?? $customer->total_spent ?? 0;
            $customer->average_order_value = $customer->calculated_average_order_value ?? $customer->average_order_value ?? 0;
            
            // Clean up temporary calculated fields
            unset($customer->calculated_total_spent);
            unset($customer->calculated_average_order_value);
            
            return $customer;
        });

        return $customers->toArray();
    }

    /**
     * Update customer information
     */
    public function updateCustomer(int $customerId, array $data): ?Customer
    {
        try {
            $customer = Customer::findOrFail($customerId);
            
            $customer->update($data);
            
            Log::info('Customer updated', [
                'customer_id' => $customerId,
                'updated_fields' => array_keys($data)
            ]);
            
            return $customer;
            
        } catch (\Exception $e) {
            Log::error('Error updating customer', [
                'customer_id' => $customerId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Deactivate customer
     */
    public function deactivateCustomer(int $customerId): bool
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $customer->update(['is_active' => false]);
            
            Log::info('Customer deactivated', ['customer_id' => $customerId]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error deactivating customer', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calculate customer retention rate
     */
    private function calculateRetentionRate(int $period): float
    {
        $startDate = Carbon::now()->subDays($period);
        $previousPeriodStart = Carbon::now()->subDays($period * 2);
        
        $customersInPeriod = Customer::where('created_at', '>=', $startDate)->count();
        $returningCustomers = Customer::where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $startDate)
            ->where('last_order_at', '>=', $startDate)
            ->count();
        
        if ($customersInPeriod === 0) {
            return 0;
        }
        
        return round(($returningCustomers / $customersInPeriod) * 100, 2);
    }

    /**
     * Get customer growth data
     */
    private function getCustomerGrowthData(int $period): array
    {
        $growthData = [];
        
        for ($i = $period; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Customer::whereDate('created_at', $date)->count();
            
            $growthData[] = [
                'date' => $date->toDateString(),
                'customers' => $count
            ];
        }
        
        return $growthData;
    }

    /**
     * Get lifetime value distribution
     */
    private function getLifetimeValueDistribution(): array
    {
        return [
            '0-50' => Customer::where('total_spent', '<', 50)->count(),
            '50-100' => Customer::whereBetween('total_spent', [50, 100])->count(),
            '100-250' => Customer::whereBetween('total_spent', [100, 250])->count(),
            '250-500' => Customer::whereBetween('total_spent', [250, 500])->count(),
            '500+' => Customer::where('total_spent', '>=', 500)->count(),
        ];
    }

    /**
     * Migrate existing orders to customers
     */
    public function migrateExistingOrdersToCustomers(): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            DB::beginTransaction();

            // Get orders without customer_id
            $orders = Order::whereNull('customer_id')->get();

            foreach ($orders as $order) {
                try {
                    // Find or create customer
                    $customer = $this->findOrCreateCustomerForOrder([
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'customer_email' => $order->customer_email,
                        'shipping_address' => $order->shipping_address,
                    ]);

                    // Update order with customer_id
                    $order->update(['customer_id' => $customer->id]);

                    // Update customer statistics
                    $this->updateCustomerStatistics($order);

                    $results['processed']++;
                    $results['created']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Error migrating order to customer', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Migration completed', $results);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Migration failed', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }
}
