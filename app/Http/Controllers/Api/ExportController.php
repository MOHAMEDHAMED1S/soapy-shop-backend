<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
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
        try {
            $format = $request->get('format', 'csv');
            $result = $this->exportService->exportData('products', $format, [], null);

            return response()->json([
                'success' => true,
                'message' => 'Products exported successfully',
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
        try {
            $format = $request->get('format', 'csv');
            $result = $this->exportService->exportData('customers', $format, [], null);

            return response()->json([
                'success' => true,
                'message' => 'Customers exported successfully',
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
        try {
            $format = $request->get('format', 'csv');
            $result = $this->exportService->exportData('orders', $format, [], null);

            return response()->json([
                'success' => true,
                'message' => 'Orders exported successfully',
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

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $export->id,
                    'type' => $export->type,
                    'format' => $export->format,
                    'status' => $export->status,
                    'file_name' => $export->file_name,
                    'records_count' => $export->records_count,
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

            if (!$export->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export is not completed yet'
                ], 400);
            }

            if (!$export->file_path || !Storage::disk('public')->exists('exports/' . basename($export->file_path))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export file not found'
                ], 404);
            }

            return Storage::disk('public')->download('exports/' . basename($export->file_path), $export->file_name);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List exports
     */
    public function listExports(Request $request): JsonResponse
    {
        try {
            $query = Export::query();

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

            // Delete file if exists
            if ($export->file_path && Storage::disk('public')->exists('exports/' . basename($export->file_path))) {
                Storage::disk('public')->delete('exports/' . basename($export->file_path));
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

    /**
     * List exports (index)
     */
    public function index(Request $request): JsonResponse
    {
        return $this->listExports($request);
    }

    /**
     * Show export details
     */
    public function show(int $exportId): JsonResponse
    {
        return $this->getExportStatus($exportId);
    }

    /**
     * Get export status
     */
    public function getStatus(int $exportId): JsonResponse
    {
        return $this->getExportStatus($exportId);
    }

    /**
     * Download export
     */
    public function download(int $exportId): StreamedResponse|JsonResponse
    {
        return $this->downloadExport($exportId);
    }

    /**
     * Destroy export
     */
    public function destroy(int $exportId): JsonResponse
    {
        return $this->deleteExport($exportId);
    }

    /**
     * Get statistics overview
     */
    public function getStatistics(): JsonResponse
    {
        return $this->getExportStats();
    }
}
