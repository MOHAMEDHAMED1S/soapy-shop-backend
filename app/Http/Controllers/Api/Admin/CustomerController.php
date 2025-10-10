<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        try {
            $query = Customer::with(['latestOrder']);

            // Apply filters
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $searchTerm . '%');
                });
            }

            if ($request->has('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->active();
                        break;
                    case 'vip':
                        $query->vip();
                        break;
                    case 'new':
                        $query->new();
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                }
            }

            if ($request->has('min_spent')) {
                $query->where('total_spent', '>=', $request->min_spent);
            }

            if ($request->has('max_spent')) {
                $query->where('total_spent', '<=', $request->max_spent);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $customers = $query->paginate($perPage);

            // Add summary statistics
            $summary = [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::active()->count(),
                'vip_customers' => Customer::vip()->count(),
                'new_customers' => Customer::new()->count(),
                'total_revenue' => Customer::sum('total_spent'),
                'average_order_value' => Customer::avg('average_order_value'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers,
                    'summary' => $summary
                ],
                'message' => 'Customers retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified customer
     */
    public function show(string $id)
    {
        try {
            $customerDetails = $this->customerService->getCustomerDetails($id);

            if (!$customerDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $customerDetails,
                'message' => 'Customer details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|array',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'nationality' => 'sometimes|string|max:100',
            'preferred_language' => 'sometimes|string|max:5',
            'is_active' => 'sometimes|boolean',
            'notes' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = $this->customerService->updateCustomer($id, $request->all());

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Customer updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate the specified customer
     */
    public function deactivate(string $id)
    {
        try {
            $success = $this->customerService->deactivateCustomer($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer deactivated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deactivating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer analytics
     */
    public function analytics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $analytics = $this->customerService->getCustomerAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Customer analytics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search customers
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            $limit = $request->get('limit', 20);

            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No search query provided'
                ]);
            }

            $customers = $this->customerService->searchCustomers($query, $limit);

            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Search results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer orders
     */
    public function orders(Request $request, string $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            $query = $customer->orders()->with(['orderItems.product', 'payment']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'orders' => $orders
                ],
                'message' => 'Customer orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Migrate existing orders to customers
     */
    public function migrateOrders()
    {
        try {
            $results = $this->customerService->migrateExistingOrdersToCustomers();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Migration completed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during migration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}