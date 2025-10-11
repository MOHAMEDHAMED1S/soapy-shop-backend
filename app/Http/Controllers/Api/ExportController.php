<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export products data
     */
    public function exportProducts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel,json',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:active,inactive',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['category_id', 'status', 'price_min', 'price_max', 'date_from', 'date_to']);
            $result = $this->exportService->exportData('products', $request->format, $filters, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Products export initiated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers data
     */
    public function exportCustomers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel,json',
            'status' => 'nullable|in:active,inactive',
            'city' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['status', 'city', 'date_from', 'date_to']);
            $result = $this->exportService->exportData('customers', $request->format, $filters, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Customers export initiated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export orders data
     */
    public function exportOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,excel,json',
            'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'total_min' => 'nullable|numeric|min:0',
            'total_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only(['status', 'payment_status', 'total_min', 'total_max', 'date_from', 'date_to']);
            $result = $this->exportService->exportData('orders', $request->format, $filters, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Orders export initiated successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export status
     */
    public function getExportStatus(int $exportId): JsonResponse
    {
        try {
            $export = $this->exportService->getExport($exportId);

            if (!$export) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export not found'
                ], 404);
            }

            // Check if user owns this export (if authenticated)
            if (auth()->check() && $export->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to export'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $export->id,
                    'type' => $export->type,
                    'format' => $export->format,
                    'status' => $export->status,
                    'file_name' => $export->file_name,
                    'file_size' => $export->formatted_file_size,
                    'records_count' => $export->records_count,
                    'error_message' => $export->error_message,
                    'created_at' => $export->created_at,
                    'completed_at' => $export->completed_at,
                    'download_url' => $export->isCompleted() ? route('api.exports.download', $export->id) : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get export status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download export file
     */
    public function downloadExport(int $exportId): StreamedResponse|JsonResponse
    {
        try {
            $export = $this->exportService->getExport($exportId);

            if (!$export) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export not found'
                ], 404);
            }

            // Check if user owns this export (if authenticated)
            if (auth()->check() && $export->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to export'
                ], 403);
            }

            if (!$export->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export is not completed yet'
                ], 400);
            }

            if (!$export->file_path || !Storage::exists($export->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export file not found'
                ], 404);
            }

            return Storage::download($export->file_path, $export->file_name);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List user exports
     */
    public function listExports(Request $request): JsonResponse
    {
        try {
            $query = Export::query();

            // Filter by authenticated user if available
            if (auth()->check()) {
                $query->where('user_id', auth()->id());
            }

            // Apply filters
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            if ($request->has('format')) {
                $query->byFormat($request->format);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $exports = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $exports->items(),
                'pagination' => [
                    'current_page' => $exports->currentPage(),
                    'last_page' => $exports->lastPage(),
                    'per_page' => $exports->perPage(),
                    'total' => $exports->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list exports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete export
     */
    public function deleteExport(int $exportId): JsonResponse
    {
        try {
            $export = $this->exportService->getExport($exportId);

            if (!$export) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export not found'
                ], 404);
            }

            // Check if user owns this export (if authenticated)
            if (auth()->check() && $export->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to export'
                ], 403);
            }

            // Delete file if exists
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }

            // Delete export record
            $export->delete();

            return response()->json([
                'success' => true,
                'message' => 'Export deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): JsonResponse
    {
        try {
            $stats = $this->exportService->getExportStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get export statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
