<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for admin
     */
    public function index(Request $request)
    {
        try {
            $query = AdminNotification::query();

            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Filter by read status
            if ($request->has('read') && $request->read !== null) {
                if ($request->read) {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sort by created_at desc by default
            $query->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $notifications = $query->paginate($perPage);

            // Add summary statistics
            $summary = [
                'total_notifications' => AdminNotification::count(),
                'unread_notifications' => AdminNotification::whereNull('read_at')->count(),
                'read_notifications' => AdminNotification::whereNotNull('read_at')->count(),
                'notifications_by_type' => AdminNotification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'summary' => $summary
                ],
                'message' => 'Notifications retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification by ID
     */
    public function show($id)
    {
        try {
            $notification = AdminNotification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notification,
                'message' => 'Notification retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $notification = AdminNotification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            if ($notification->read_at) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification already marked as read',
                    'data' => $notification
                ]);
            }

            $notification->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => $notification,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $updatedCount = AdminNotification::whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => ['updated_count' => $updatedCount],
                'message' => "Marked {$updatedCount} notifications as read"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        try {
            $notification = AdminNotification::find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function statistics(Request $request)
    {
        try {
            $period = $request->get('period', 30);
            $startDate = now()->subDays($period);

            $stats = [
                'total_notifications' => AdminNotification::where('created_at', '>=', $startDate)->count(),
                'unread_notifications' => AdminNotification::whereNull('read_at')->count(),
                'read_notifications' => AdminNotification::whereNotNull('read_at')->count(),
                'notifications_by_type' => AdminNotification::where('created_at', '>=', $startDate)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type'),
                'daily_notifications' => AdminNotification::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(7)
                    ->get(),
                'recent_notifications' => AdminNotification::orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Notification statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notification statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a test notification
     */
    public function createTest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:order_created,order_paid,order_shipped,order_delivered,payment_failed,payment_refunded,product_low_stock,new_customer',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notification = $this->notificationService->createNotification(
                $request->type,
                $request->title,
                $request->message,
                $request->data ?? [],
                $request->priority ?? 'medium'
            );

            return response()->json([
                'success' => true,
                'data' => $notification,
                'message' => 'Test notification created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all read notifications
     */
    public function deleteRead()
    {
        try {
            $deletedCount = AdminNotification::whereNotNull('read_at')->delete();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => $deletedCount],
                'message' => "Deleted {$deletedCount} read notifications"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting read notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}