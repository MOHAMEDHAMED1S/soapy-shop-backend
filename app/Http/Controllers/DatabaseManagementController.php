<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;

class DatabaseManagementController extends Controller
{
    /**
     * عرض صفحة إدارة قاعدة البيانات
     */
    public function index()
    {
        return view('database-management');
    }

    /**
     * الحصول على قائمة بجميع الجداول في قاعدة البيانات
     */
    public function getTables(): JsonResponse
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $tableNames = [];
            
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $count = DB::table($tableName)->count();
                $tableNames[] = [
                    'name' => $tableName,
                    'count' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'tables' => $tableNames
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب الجداول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض محتويات جدول معين
     */
    public function getTableData(Request $request, $tableName): JsonResponse
    {
        try {
            // التحقق من وجود الجدول
            if (!Schema::hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجدول غير موجود'
                ], 404);
            }

            $limit = $request->get('limit', 100);
            $offset = $request->get('offset', 0);

            $data = DB::table($tableName)
                ->limit($limit)
                ->offset($offset)
                ->get();

            $totalCount = DB::table($tableName)->count();

            // الحصول على أسماء الأعمدة
            $columns = Schema::getColumnListing($tableName);

            return response()->json([
                'success' => true,
                'table_name' => $tableName,
                'columns' => $columns,
                'data' => $data,
                'total_count' => $totalCount,
                'current_count' => count($data),
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب بيانات الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تفريغ جدول معين (حذف جميع البيانات وليس الجدول نفسه)
     */
    public function truncateTable(Request $request, $tableName): JsonResponse
    {
        try {
            // التحقق من وجود الجدول
            if (!Schema::hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجدول غير موجود'
                ], 404);
            }

            // قائمة الجداول المحمية التي لا يجب تفريغها
            $protectedTables = [
                'users',
                'migrations',
                'password_reset_tokens',
                'sessions',
                'cache',
                'cache_locks',
                'jobs',
                'job_batches',
                'failed_jobs'
            ];

            if (in_array($tableName, $protectedTables)) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الجدول محمي ولا يمكن تفريغه'
                ], 403);
            }

            $countBefore = DB::table($tableName)->count();
            
            // تفريغ الجدول
            DB::table($tableName)->truncate();
            
            $countAfter = DB::table($tableName)->count();

            return response()->json([
                'success' => true,
                'message' => "تم تفريغ الجدول {$tableName} بنجاح",
                'table_name' => $tableName,
                'records_deleted' => $countBefore,
                'records_remaining' => $countAfter
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تفريغ الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف سجلات معينة من جدول
     */
    public function deleteRecords(Request $request, $tableName): JsonResponse
    {
        try {
            // التحقق من وجود الجدول
            if (!Schema::hasTable($tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجدول غير موجود'
                ], 404);
            }

            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد معرفات السجلات المراد حذفها'
                ], 400);
            }

            $countBefore = DB::table($tableName)->count();
            
            // حذف السجلات المحددة
            $deletedCount = DB::table($tableName)->whereIn('id', $ids)->delete();
            
            $countAfter = DB::table($tableName)->count();

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} سجل من الجدول {$tableName}",
                'table_name' => $tableName,
                'records_deleted' => $deletedCount,
                'total_before' => $countBefore,
                'total_after' => $countAfter
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في حذف السجلات: ' . $e->getMessage()
            ], 500);
        }
    }
}