<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Search
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,customer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => $request->get('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:6',
                'role' => 'sometimes|in:admin,customer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('role')) {
                $updateData['role'] = $request->role;
            }
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->is_active;
            }

            if ($request->has('password') && $request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting the last admin
            if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last admin user'
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(string $id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deactivating the last admin
            if ($user->role === 'admin' && $user->is_active && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the last active admin user'
                ], 422);
            }

            $user->update(['is_active' => !$user->is_active]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'admin_users' => User::where('role', 'admin')->count(),
                'customer_users' => User::where('role', 'customer')->count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'users_this_month' => User::whereMonth('created_at', now()->month)->count(),
                'users_this_year' => User::whereYear('created_at', now()->year)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'User statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'action' => 'required|in:activate,deactivate,delete,change_role',
                'role' => 'required_if:action,change_role|in:admin,customer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userIds = $request->user_ids;
            $action = $request->action;
            $updatedCount = 0;

            DB::transaction(function () use ($userIds, $action, $request, &$updatedCount) {
                switch ($action) {
                    case 'activate':
                        $updatedCount = User::whereIn('id', $userIds)
                            ->update(['is_active' => true]);
                        break;
                    
                    case 'deactivate':
                        // Check if we're trying to deactivate all admins
                        $adminCount = User::whereIn('id', $userIds)
                            ->where('role', 'admin')
                            ->where('is_active', true)
                            ->count();
                        
                        $totalActiveAdmins = User::where('role', 'admin')
                            ->where('is_active', true)
                            ->count();
                        
                        if ($adminCount >= $totalActiveAdmins) {
                            throw new \Exception('Cannot deactivate all admin users');
                        }
                        
                        $updatedCount = User::whereIn('id', $userIds)
                            ->update(['is_active' => false]);
                        break;
                    
                    case 'delete':
                        // Check if we're trying to delete all admins
                        $adminCount = User::whereIn('id', $userIds)
                            ->where('role', 'admin')
                            ->count();
                        
                        $totalAdmins = User::where('role', 'admin')->count();
                        
                        if ($adminCount >= $totalAdmins) {
                            throw new \Exception('Cannot delete all admin users');
                        }
                        
                        $updatedCount = User::whereIn('id', $userIds)->delete();
                        break;
                    
                    case 'change_role':
                        $updatedCount = User::whereIn('id', $userIds)
                            ->update(['role' => $request->role]);
                        break;
                }
            });

            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $updatedCount],
                'message' => "Bulk {$action} completed successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk update: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export users
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'json');
            $role = $request->get('role');
            $isActive = $request->get('is_active');

            $query = User::query();

            if ($role) {
                $query->where('role', $role);
            }

            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            $users = $query->get();

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($users);
                case 'xlsx':
                    return $this->exportToXlsx($users);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $users,
                        'message' => 'Users exported successfully'
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToCsv($users)
    {
        $filename = 'users_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Phone', 'Role', 'Is Active', 'Created At'
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->is_active ? 'Yes' : 'No',
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToXlsx($users)
    {
        // This would require a package like PhpSpreadsheet
        // For now, return JSON
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'XLSX export not implemented yet. Use CSV or JSON format.'
        ]);
    }
}
