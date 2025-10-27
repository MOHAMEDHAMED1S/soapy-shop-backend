<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class AsyncHelper
{
    /**
     * تنفيذ دالة بعد إرسال الاستجابة للعميل
     * 
     * هذه الدالة تسمح بإنهاء الطلب (request) وإرسال الاستجابة للعميل،
     * ثم تنفيذ الكود المطلوب في الخلفية دون أن ينتظر العميل.
     * 
     * @param callable $callback الدالة التي سيتم تنفيذها في الخلفية
     * @param string $name اسم العملية (للـ logging)
     * @return void
     */
    public static function runAfterResponse(callable $callback, string $name = 'async_task'): void
    {
        // تسجيل بداية العملية
        Log::info("Scheduling background task: {$name}");
        
        // استخدام register_shutdown_function لضمان التنفيذ بعد إرسال الاستجابة
        register_shutdown_function(function () use ($callback, $name) {
            try {
                // محاولة إنهاء الاتصال مع العميل إذا كان متاحاً
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                
                // تنفيذ الدالة في الخلفية
                Log::info("Executing background task: {$name}");
                $callback();
                Log::info("Background task completed successfully: {$name}");
                
            } catch (\Exception $e) {
                // تسجيل الخطأ دون التأثير على الاستجابة
                Log::error("Background task failed: {$name}", [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }
    
    /**
     * إرسال الاستجابة للعميل فوراً والاستمرار في تنفيذ الكود
     * 
     * @return bool نجح في فصل الاتصال أم لا
     */
    public static function finishRequest(): bool
    {
        // إغلاق الـ session إذا كان مفتوحاً
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // محاولة إنهاء الطلب مع FastCGI
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return true;
        }
        
        // إذا لم يكن FastCGI متاحاً، استخدم الطريقة البديلة
        // إرسال جميع البيانات المخزنة مؤقتاً
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
        
        return false;
    }
    
    /**
     * تنفيذ عدة مهام في الخلفية
     * 
     * @param array $tasks مصفوفة من المهام ['name' => callable]
     * @return void
     */
    public static function runMultipleTasks(array $tasks): void
    {
        foreach ($tasks as $name => $callback) {
            if (is_callable($callback)) {
                self::runAfterResponse($callback, is_string($name) ? $name : 'task_' . $name);
            }
        }
    }
}

