<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public APIs (Customer)
Route::prefix('v1')->group(function () {
    // Products
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/featured', [\App\Http\Controllers\Api\ProductController::class, 'featured']);
    Route::get('/products/feed', [\App\Http\Controllers\Api\ProductFeedController::class, 'rss']);
    Route::get('/products/{identifier}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::get('/categories/{categorySlug}/products', [\App\Http\Controllers\Api\ProductController::class, 'byCategory']);
    
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/tree', [\App\Http\Controllers\Api\CategoryController::class, 'tree']);
    Route::get('/categories/{slug}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    


    // Orders & Checkout
    Route::post('/checkout/create-order', [\App\Http\Controllers\Api\OrderController::class, 'createOrder']);
    Route::post('/checkout/calculate-total', [\App\Http\Controllers\Api\OrderController::class, 'calculateTotal']);
    Route::post('/checkout/check-customer-discount', [\App\Http\Controllers\Api\OrderController::class, 'checkCustomerDiscount']);
    Route::post('/checkout/validate-discount', [\App\Http\Controllers\Api\OrderController::class, 'validateDiscount']);
    Route::get('/orders/{orderNumber}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::get('/orders/{orderNumber}/track', [\App\Http\Controllers\Api\OrderController::class, 'trackOrder']);
    Route::get('/orders/{orderNumber}/details', [\App\Http\Controllers\Api\OrderController::class, 'getOrderDetails']);
    Route::post('/orders/{orderNumber}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    Route::post('/orders/{orderNumber}/apply-discount', [\App\Http\Controllers\Api\OrderController::class, 'applyDiscountCode']);
    
    // Temporary Orders Management (No Authentication Required)
    Route::prefix('temp-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\TempOrdersController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\TempOrdersController::class, 'statistics']);
        Route::get('/{id}', [\App\Http\Controllers\TempOrdersController::class, 'show']);
        Route::put('/{id}/update-status', [\App\Http\Controllers\TempOrdersController::class, 'updateStatus']);
        Route::delete('/{id}', [\App\Http\Controllers\TempOrdersController::class, 'destroy']);
    });
    
    // Debug routes
    Route::get('/debug/create-test-order', [\App\Http\Controllers\Api\OrderController::class, 'createTestOrder']);
    Route::get('/debug/orders', [\App\Http\Controllers\Api\OrderController::class, 'listOrders']);
    
    // Visit Tracking (Public)
    Route::post('/visits/track', [\App\Http\Controllers\VisitController::class, 'track']);
    Route::get('/visits/pixel.gif', [\App\Http\Controllers\VisitController::class, 'pixel']);
    Route::post('/visits/pixel', [\App\Http\Controllers\VisitController::class, 'pixel']);
    
    // Sitemap (Public)
    Route::get('/sitemap.xml', [\App\Http\Controllers\Api\SitemapController::class, 'index']);
    Route::get('/sitemap/index.xml', [\App\Http\Controllers\Api\SitemapController::class, 'sitemapIndex']);
    Route::get('/sitemap/products.xml', [\App\Http\Controllers\Api\SitemapController::class, 'productsSitemap']);
    
    // Chatbot APIs (Public)
    Route::prefix('chat')->group(function () {
        Route::post('/start', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'startChat']);
        Route::post('/message', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'sendMessage']);
        Route::get('/history', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'getHistory']);
        Route::post('/end', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'endChat']);
        Route::get('/settings', [\App\Http\Controllers\Api\V1\ChatbotController::class, 'getSettings']);
    });

        // Product Comments (Public)
    Route::get('/products/{productId}/comments', [\App\Http\Controllers\Api\ProductCommentController::class, 'index']);
    Route::post('/products/{productId}/comments', [\App\Http\Controllers\Api\ProductCommentController::class, 'store']);
    
    
    // Payments
    Route::get('/payments/methods', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'getPaymentMethods']);
    Route::post('/payments/initiate', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'initiatePayment']);
    Route::get('/payments/status', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'checkPaymentStatus']);
    Route::post('/payments/callback', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'handleCallback']);
    Route::post('/payments/error', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'handleError']);
    Route::post('/payments/webhook/myfatoorah', [\App\Http\Controllers\Api\PaymentController::class, 'webhook']);
    
    // Payment Callbacks for Frontend
    Route::get('/payments/success', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'handleSuccessCallback']);
    Route::get('/payments/failure', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'handleFailureCallback']);
    
    // Discount Codes
    Route::get('/discount-codes', [\App\Http\Controllers\Api\Customer\DiscountController::class, 'getAvailableCodes']);
    Route::get('/discount-codes/{code}', [\App\Http\Controllers\Api\Customer\DiscountController::class, 'getCodeDetails']);
    Route::post('/discount-codes/validate', [\App\Http\Controllers\Api\Customer\DiscountController::class, 'validateCode']);
    
    // Spin Wheel (Public)
    Route::get('/spin-wheel/items', [\App\Http\Controllers\Api\Customer\SpinWheelController::class, 'getItems']);
    Route::post('/spin-wheel/check-previous', [\App\Http\Controllers\Api\Customer\SpinWheelController::class, 'checkPreviousSpin']);
    Route::post('/spin-wheel/spin', [\App\Http\Controllers\Api\Customer\SpinWheelController::class, 'spin']);
    
    // Home Banner (Public)
    Route::get('/home-banner', [\App\Http\Controllers\Api\HomeBannerController::class, 'show']);
    
    // Abandoned Carts (Public - for frontend sync)
    Route::prefix('abandoned-carts')->group(function () {
        Route::post('/sync', [\App\Http\Controllers\Api\AbandonedCartController::class, 'sync']);
        Route::delete('/{sessionId}', [\App\Http\Controllers\Api\AbandonedCartController::class, 'delete']);
        Route::post('/mark-converted', [\App\Http\Controllers\Api\AbandonedCartController::class, 'markConverted']);
    });
    
    // Home Media (Public)
    Route::get('/home-media', [\App\Http\Controllers\Api\HomeMediaController::class, 'index']);

    // Shipping Cost (Public)
    Route::get('/shipping/cost', [\App\Http\Controllers\ShippingController::class, 'getCost']);
    Route::post('/shipping/calculate', [\App\Http\Controllers\Api\ShippingCalculationController::class, 'calculate']);
    Route::get('/shipping/rates', [\App\Http\Controllers\Api\ShippingCalculationController::class, 'getRates']);
    
    // Site Settings (Public)
    Route::get('/site/orders-status', [\App\Http\Controllers\Api\SiteController::class, 'getOrdersStatus']);
    
    // Analytics (Public)
    Route::get('/analytics/statistics', [\App\Http\Controllers\AnalyticsController::class, 'statistics']);
    Route::get('/analytics/general', [\App\Http\Controllers\AnalyticsController::class, 'statistics']); // Alias for general stats
    Route::get('/analytics/pages', [\App\Http\Controllers\AnalyticsController::class, 'popularPages']);
    Route::get('/analytics/popular-pages', [\App\Http\Controllers\AnalyticsController::class, 'popularPages']);
    Route::get('/analytics/realtime', [\App\Http\Controllers\AnalyticsController::class, 'realTime']);
    Route::get('/analytics/real-time', [\App\Http\Controllers\AnalyticsController::class, 'realTime']);
    Route::get('/analytics/referer-types', [\App\Http\Controllers\AnalyticsController::class, 'visitsByRefererType']);
    Route::get('/analytics/referers', [\App\Http\Controllers\AnalyticsController::class, 'topRefererDomains']); // Alias for referers
    Route::get('/analytics/top-referer-domains', [\App\Http\Controllers\AnalyticsController::class, 'topRefererDomains']);
    Route::get('/analytics/daily-visits', [\App\Http\Controllers\AnalyticsController::class, 'dailyVisits']);
    Route::get('/analytics/daily', [\App\Http\Controllers\AnalyticsController::class, 'dailyVisits']); // Alias for daily
    Route::get('/analytics/device-stats', [\App\Http\Controllers\AnalyticsController::class, 'deviceStats']);
    Route::get('/analytics/devices', [\App\Http\Controllers\AnalyticsController::class, 'deviceStats']); // Alias for devices
    Route::get('/analytics/social-visits', [\App\Http\Controllers\AnalyticsController::class, 'socialVisits']);
    Route::get('/analytics/social', [\App\Http\Controllers\AnalyticsController::class, 'socialVisits']); // Alias for social
    
    // Database Management (Temporary - for development only)
    Route::prefix('temp-db')->group(function () {
        Route::get('/tables', [\App\Http\Controllers\DatabaseManagementController::class, 'getTables']);
        Route::get('/tables/{tableName}', [\App\Http\Controllers\DatabaseManagementController::class, 'getTableData']);
        Route::post('/tables/{tableName}/truncate', [\App\Http\Controllers\DatabaseManagementController::class, 'truncateTable']);
        Route::delete('/tables/{tableName}/records', [\App\Http\Controllers\DatabaseManagementController::class, 'deleteRecords']);
    });
    
    // Data Export System (Public)
    Route::prefix('exports')->group(function () {
        Route::post('/products', [\App\Http\Controllers\Api\ExportController::class, 'exportProducts']);
        Route::post('/customers', [\App\Http\Controllers\Api\ExportController::class, 'exportCustomers']);
        Route::post('/orders', [\App\Http\Controllers\Api\ExportController::class, 'exportOrders']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ExportController::class, 'show']);
        Route::get('/{id}/download', [\App\Http\Controllers\Api\ExportController::class, 'download']);
    });
    
    // Webhooks
    Route::post('/webhooks/myfatoorah', [\App\Http\Controllers\Api\WebhookController::class, 'handleMyFatoorahWebhook'])->middleware('webhook');
    Route::post('/webhooks/test', [\App\Http\Controllers\Api\WebhookController::class, 'testWebhook'])->middleware('webhook');
    
    // Public Reports System (for external access)
    Route::prefix('reports')->group(function () {
        // Dashboard reports
        Route::get('/dashboard/overview', [\App\Http\Controllers\Api\ReportController::class, 'getDashboardOverview']);
        Route::get('/dashboard/business-intelligence', [\App\Http\Controllers\Api\ReportController::class, 'getBusinessIntelligence']);
        
        // Analytics reports
        Route::get('/analytics/sales', [\App\Http\Controllers\Api\ReportController::class, 'getSalesAnalytics']);
        Route::get('/analytics/customers', [\App\Http\Controllers\Api\ReportController::class, 'getCustomerAnalytics']);
        Route::get('/analytics/products', [\App\Http\Controllers\Api\ReportController::class, 'getProductAnalytics']);
        Route::get('/analytics/orders', [\App\Http\Controllers\Api\ReportController::class, 'getOrderAnalytics']);
        Route::get('/analytics/seasonal-trends', [\App\Http\Controllers\Api\ReportController::class, 'getSeasonalTrends']);
        
        // Financial reports
        Route::get('/financial/overview', [\App\Http\Controllers\Api\ReportController::class, 'getFinancialReports']);
        
        // OPTIONS routes for CORS
        Route::options('/dashboard/overview', function () { return response('', 204); });
        Route::options('/dashboard/business-intelligence', function () { return response('', 204); });
        Route::options('/analytics/sales', function () { return response('', 204); });
        Route::options('/analytics/customers', function () { return response('', 204); });
        Route::options('/analytics/products', function () { return response('', 204); });
        Route::options('/analytics/orders', function () { return response('', 204); });
        Route::options('/analytics/seasonal-trends', function () { return response('', 204); });
        Route::options('/financial/overview', function () { return response('', 204); });
    });
});

// Admin APIs (Protected with JWT)
Route::prefix('v1/admin')->middleware(['auth:api', 'admin'])->group(function () {
    // OPTIONS routes for CORS preflight requests
    Route::options('/me', function () { return response('', 204); });
    Route::options('/orders', function () { return response('', 204); });
    Route::options('/orders/statistics', function () { return response('', 204); });
    Route::options('/orders/export', function () { return response('', 204); });
    Route::options('/orders/{id}', function () { return response('', 204); });
    Route::options('/orders/{id}/update-status', function () { return response('', 204); });
    Route::options('/order-management', function () { return response('', 204); });
    Route::options('/order-management/statistics', function () { return response('', 204); });
    Route::options('/order-management/export', function () { return response('', 204); });
    Route::options('/order-management/search', function () { return response('', 204); });
    Route::options('/order-management/{id}', function () { return response('', 204); });
    Route::options('/order-management/{id}/timeline', function () { return response('', 204); });
    Route::options('/order-management/{id}/update-status', function () { return response('', 204); });
    Route::options('/order-management/bulk-update-status', function () { return response('', 204); });
    Route::options('/payments', function () { return response('', 204); });
    Route::options('/payments/statistics', function () { return response('', 204); });
    Route::options('/payments/{id}', function () { return response('', 204); });
    Route::options('/payments/{id}/retry', function () { return response('', 204); });
    Route::options('/webhook-logs', function () { return response('', 204); });
    Route::options('/webhook-logs/{id}', function () { return response('', 204); });
    Route::options('/webhooks/logs', function () { return response('', 204); });
    Route::options('/webhooks/statistics', function () { return response('', 204); });
    Route::options('/webhooks/{id}/retry', function () { return response('', 204); });
    Route::options('/products', function () { return response('', 204); });
    Route::options('/products/statistics', function () { return response('', 204); });
    Route::options('/products/bulk-update', function () { return response('', 204); });
    Route::options('/products/export', function () { return response('', 204); });
    Route::options('/products/category/{categoryId}', function () { return response('', 204); });
    Route::options('/products/{id}', function () { return response('', 204); });
    Route::options('/products/{id}/toggle-availability', function () { return response('', 204); });
    Route::options('/products/{id}/duplicate', function () { return response('', 204); });
    Route::options('/products/{id}/images', function () { return response('', 204); });
    Route::options('/categories', function () { return response('', 204); });
    Route::options('/categories/tree', function () { return response('', 204); });
    Route::options('/categories/statistics', function () { return response('', 204); });
    Route::options('/categories/{id}', function () { return response('', 204); });
    Route::options('/categories/{id}/toggle-status', function () { return response('', 204); });
    Route::post('/categories/update-sort-order', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'updateSortOrder']);
    
    // OPTIONS routes for visit analytics
    Route::options('/analytics/visits/statistics', function () { return response('', 204); });
    Route::options('/analytics/visits/referer-types', function () { return response('', 204); });
    Route::options('/analytics/visits/top-referers', function () { return response('', 204); });
    Route::options('/analytics/visits/daily', function () { return response('', 204); });
    Route::options('/analytics/visits/popular-pages', function () { return response('', 204); });
    Route::options('/analytics/visits/real-time', function () { return response('', 204); });
    Route::options('/analytics/visits/devices', function () { return response('', 204); });
    Route::options('/analytics/visits/social', function () { return response('', 204); });
    
    // Auth
    Route::get('/me', [\App\Http\Controllers\Api\Admin\AuthController::class, 'me']);
    
    // Visit Analytics (Admin)
        Route::get('analytics/visits/statistics', [\App\Http\Controllers\AnalyticsController::class, 'statistics']);
        Route::get('analytics/visits/referer-types', [\App\Http\Controllers\AnalyticsController::class, 'visitsByRefererType']);
        Route::get('analytics/visits/top-referers', [\App\Http\Controllers\AnalyticsController::class, 'topRefererDomains']);
        Route::get('analytics/visits/daily', [\App\Http\Controllers\AnalyticsController::class, 'dailyVisits']);
        Route::get('analytics/visits/popular-pages', [\App\Http\Controllers\AnalyticsController::class, 'popularPages']);
        Route::get('analytics/visits/real-time', [\App\Http\Controllers\AnalyticsController::class, 'realTime']);
        Route::get('analytics/visits/devices', [\App\Http\Controllers\AnalyticsController::class, 'deviceStats']);
        Route::get('analytics/visits/social', [\App\Http\Controllers\AnalyticsController::class, 'socialVisits']);
    
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
    Route::get('/orders/statistics', [\App\Http\Controllers\Api\Admin\OrderController::class, 'statistics']);
    Route::get('/orders/export', [\App\Http\Controllers\Api\Admin\OrderController::class, 'export']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
    Route::put('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'update']);
    Route::put('/orders/{id}/update-status', [\App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'destroy']);
    
    // Order Management (Advanced)
    Route::get('/order-management', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'index']);
    Route::get('/order-management/statistics', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'statistics']);
    Route::get('/order-management/export', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'export']);
    Route::get('/order-management/search', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'search']);
    Route::get('/order-management/{id}', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'show']);
    Route::get('/order-management/{id}/timeline', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'timeline']);
    Route::put('/order-management/{id}/update-status', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'updateStatus']);
    Route::post('/order-management/bulk-update-status', [\App\Http\Controllers\Api\Admin\OrderManagementController::class, 'bulkUpdateStatus']);
    
        // Product Comments Management
    Route::get('/product-comments', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'index']);
    Route::get('/product-comments/statistics', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'statistics']);
    Route::get('/product-comments/{id}', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'show']);
    Route::put('/product-comments/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'approve']);
    Route::put('/product-comments/{id}/reject', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'reject']);
    Route::delete('/product-comments/{id}', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'destroy']);
    Route::get('/products/{productId}/comments', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'productComments']);
    Route::post('/product-comments/bulk-approve', [\App\Http\Controllers\Api\Admin\AdminProductCommentController::class, 'bulkApprove']);


    // Payments
    Route::get('/payments', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
    Route::get('/payments/statistics', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'statistics']);
    Route::get('/payments/verify-pendingg', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'verifyPendingPayments']);
    Route::get('/payments/{id}', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'show']);
    Route::post('/payments/{id}/retry', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'retryPayment']);
    Route::get('/webhook-logs', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'webhookLogs']);
    Route::get('/webhook-logs/{id}', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'webhookLog']);
    
    // Webhook Management
    Route::get('/webhooks/logs', [\App\Http\Controllers\Api\WebhookController::class, 'getWebhookLogs']);
    Route::get('/webhooks/statistics', [\App\Http\Controllers\Api\WebhookController::class, 'getWebhookStatistics']);
    Route::post('/webhooks/{id}/retry', [\App\Http\Controllers\Api\WebhookController::class, 'retryWebhook']);
    
    // Products
    Route::get('/products', [\App\Http\Controllers\Api\Admin\ProductController::class, 'index']);
    Route::post('/products', [\App\Http\Controllers\Api\Admin\ProductController::class, 'store']);
    Route::get('/products/statistics', [\App\Http\Controllers\Api\Admin\ProductController::class, 'statistics']);
    Route::post('/products/bulk-update', [\App\Http\Controllers\Api\Admin\ProductController::class, 'bulkUpdate']);
    Route::get('/products/export', [\App\Http\Controllers\Api\Admin\ProductController::class, 'export']);
    Route::get('/products/category/{categoryId}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'byCategory']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'show']);
    Route::put('/products/{id}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'update']);
    Route::delete('/products/{id}', [\App\Http\Controllers\Api\Admin\ProductController::class, 'destroy']);
    Route::patch('/products/{id}/toggle-availability', [\App\Http\Controllers\Api\Admin\ProductController::class, 'toggleAvailability']);
    Route::post('/products/{id}/duplicate', [\App\Http\Controllers\Api\Admin\ProductController::class, 'duplicate']);
    Route::put('/products/{id}/images', [\App\Http\Controllers\Api\Admin\ProductController::class, 'updateImages']);
    
    // Inventory Management
    Route::get('/inventory/statistics', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'statistics']);
    Route::get('/inventory/products', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'products']);
    Route::get('/inventory/transactions', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'allTransactions']);
    Route::get('/inventory/products/{productId}/transactions', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'productTransactions']);
    Route::post('/inventory/products/{productId}/adjust', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'adjustInventory']);
    Route::post('/inventory/bulk-import', [\App\Http\Controllers\Api\Admin\InventoryController::class, 'bulkImport']);
    
    // OPTIONS routes for inventory
    Route::options('/inventory/statistics', function () { return response('', 204); });
    Route::options('/inventory/products', function () { return response('', 204); });
    Route::options('/inventory/transactions', function () { return response('', 204); });
    Route::options('/inventory/products/{productId}/transactions', function () { return response('', 204); });
    Route::options('/inventory/products/{productId}/adjust', function () { return response('', 204); });
    Route::options('/inventory/bulk-import', function () { return response('', 204); });
    
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index']);
    Route::post('/categories', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'store']);
    Route::get('/categories/tree', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'tree']);
    Route::get('/categories/statistics', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'statistics']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show']);
    Route::put('/categories/{id}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'destroy']);
    Route::put('/categories/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'toggleStatus']);
    Route::post('/categories/update-sort-order', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'updateSortOrder']);
    Route::options('/categories/update-sort-order', function () { return response('', 204); });
    
    // OPTIONS routes for Spin Wheel
    Route::options('/spin-wheel/items', function () { return response('', 204); });
    Route::options('/spin-wheel/items/{id}', function () { return response('', 204); });
    Route::options('/spin-wheel/results', function () { return response('', 204); });
    Route::options('/spin-wheel/statistics', function () { return response('', 204); });
    Route::options('/home-banner', function () { return response('', 204); });
    
    // OPTIONS routes for notifications
    Route::options('/notifications', function () { return response('', 204); });
    Route::options('/notifications/statistics', function () { return response('', 204); });
    Route::options('/notifications/test', function () { return response('', 204); });
    Route::options('/notifications/preferences', function () { return response('', 204); });
    Route::options('/notifications/mark-all-read', function () { return response('', 204); });
    Route::options('/notifications/delete-read', function () { return response('', 204); });
    Route::options('/notifications/{id}', function () { return response('', 204); });
    Route::options('/notifications/{id}/read', function () { return response('', 204); });
    Route::options('/notifications/{id}/unread', function () { return response('', 204); });
    
    // OPTIONS routes for images
    Route::options('/images', function () { return response('', 204); });
    Route::options('/images/folders', function () { return response('', 204); });
    Route::options('/images/statistics', function () { return response('', 204); });
    Route::options('/images/{path}', function () { return response('', 204); });
    Route::options('/images/{path}/serve', function () { return response('', 204); });
    Route::options('/images/{path}/resize', function () { return response('', 204); });
    Route::options('/images/folders/{folderName}', function () { return response('', 204); });
    
    // OPTIONS routes for dashboard
    Route::options('/dashboard/overview', function () { return response('', 204); });
    Route::options('/dashboard/sales-analytics', function () { return response('', 204); });
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'index']);
    Route::get('/notifications/statistics', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'statistics']);
    Route::post('/notifications/test', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'createTest']);
    Route::get('/notifications/preferences', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'getPreferences']);
    Route::put('/notifications/preferences', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'updatePreferences']);
    Route::put('/notifications/mark-all-read', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/delete-read', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'deleteRead']);
    Route::get('/notifications/{id}', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'show']);
    Route::put('/notifications/{id}/read', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'markAsRead']);
    Route::put('/notifications/{id}/unread', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'markAsUnread']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'destroy']);
    
    // Images
    Route::options('/images/upload', function () {
        return response('', 204);
    });
    Route::options('/images/upload-multiple', function () {
        return response('', 204);
    });
    Route::get('/images', [\App\Http\Controllers\Api\Admin\ImageController::class, 'index']);
    Route::get('/images/folders', [\App\Http\Controllers\Api\Admin\ImageController::class, 'folders']);
    Route::get('/images/statistics', [\App\Http\Controllers\Api\Admin\ImageController::class, 'statistics']);
    Route::post('/images/upload', [\App\Http\Controllers\Api\Admin\ImageController::class, 'upload']);
    Route::post('/images/upload-multiple', [\App\Http\Controllers\Api\Admin\ImageController::class, 'uploadMultiple']);
    Route::post('/images/folders', [\App\Http\Controllers\Api\Admin\ImageController::class, 'createFolder']);
    Route::get('/images/{path}', [\App\Http\Controllers\Api\Admin\ImageController::class, 'show']);
    Route::get('/images/{path}/serve', [\App\Http\Controllers\Api\Admin\ImageController::class, 'serve']);
    Route::post('/images/{path}/resize', [\App\Http\Controllers\Api\Admin\ImageController::class, 'resize']);
    Route::delete('/images/{path}', [\App\Http\Controllers\Api\Admin\ImageController::class, 'destroy']);
    Route::delete('/images/folders/{folderName}', [\App\Http\Controllers\Api\Admin\ImageController::class, 'deleteFolder']);
    
    // Dashboard
    Route::get('/dashboard/overview', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'overview']);
    Route::get('/dashboard/sales-analytics', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'salesAnalytics']);
    Route::get('/dashboard/product-analytics', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'productAnalytics']);
    Route::get('/dashboard/order-analytics', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'orderAnalytics']);
    Route::get('/dashboard/payment-analytics', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'paymentAnalytics']);
    Route::get('/dashboard/recent-activities', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'recentActivities']);
    Route::get('/dashboard/top-products', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'topProducts']);
    Route::get('/dashboard/category-performance', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'categoryPerformance']);
    Route::get('/dashboard/customer-analytics', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'customerAnalytics']);
    Route::get('/dashboard/widgets', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'widgets']);
    Route::post('/dashboard/export', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'export']);
    Route::get('/dashboard/real-time-stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'realTimeStats']);
    Route::get('/dashboard/system-health', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'systemHealth']);
    
    // Customers
    Route::get('/customers', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'index']);
    Route::get('/customers/analytics', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'analytics']);
    Route::get('/customers/search', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'search']);
    Route::post('/customers/migrate-orders', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'migrateOrders']);
    Route::get('/customers/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'show']);
    Route::put('/customers/{id}', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'update']);
    Route::put('/customers/{id}/deactivate', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'deactivate']);
    Route::get('/customers/{id}/orders', [\App\Http\Controllers\Api\Admin\CustomerController::class, 'orders']);
    
    // Discount Codes
    Route::get('/discount-codes', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'index']);
    Route::post('/discount-codes', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'store']);
    Route::get('/discount-codes/statistics', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'statistics']);
    Route::get('/discount-codes/{id}', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'show']);
    Route::put('/discount-codes/{id}', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'update']);
    Route::delete('/discount-codes/{id}', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'destroy']);
    Route::put('/discount-codes/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'toggleStatus']);
    Route::get('/discount-codes/{id}/usage-history', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'usageHistory']);
    Route::post('/discount-codes/{id}/duplicate', [\App\Http\Controllers\Api\Admin\DiscountCodeController::class, 'duplicate']);

    // Spin Wheel Management (Admin)
    Route::get('/spin-wheel/items', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'index']);
    Route::post('/spin-wheel/items', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'store']);
    Route::get('/spin-wheel/items/{id}', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'show']);
    Route::put('/spin-wheel/items/{id}', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'update']);
    Route::delete('/spin-wheel/items/{id}', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'destroy']);
    Route::get('/spin-wheel/results', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'getResults']);
    Route::get('/spin-wheel/statistics', [\App\Http\Controllers\Api\Admin\AdminSpinWheelController::class, 'getStatistics']);
    
    // Home Banner (Admin)
    Route::get('/home-banner', [\App\Http\Controllers\Api\Admin\HomeBannerController::class, 'show']);
    Route::put('/home-banner', [\App\Http\Controllers\Api\Admin\HomeBannerController::class, 'update']);
    
    // Home Media (Admin)
    Route::get('/home-media', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'index']);
    Route::post('/home-media', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'store']);
    Route::get('/home-media/{id}', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'show']);
    Route::post('/home-media/{id}', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'update']);
    Route::delete('/home-media/{id}', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'destroy']);
    Route::post('/home-media/reorder', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'reorder']);
    Route::put('/home-media/{id}/toggle-active', [\App\Http\Controllers\Api\Admin\HomeMediaController::class, 'toggleActive']);
    
    // Product Discounts Management
    Route::get('/product-discounts', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'index']);
    Route::post('/product-discounts', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'store']);
    Route::get('/product-discounts/statistics', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'statistics']);
    Route::get('/product-discounts/{id}', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'show']);
    Route::put('/product-discounts/{id}', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'update']);
    Route::delete('/product-discounts/{id}', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'destroy']);
    Route::put('/product-discounts/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'toggleStatus']);
    Route::get('/product-discounts/{id}/affected-products', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'affectedProducts']);
    Route::post('/product-discounts/{id}/duplicate', [\App\Http\Controllers\Api\Admin\ProductDiscountController::class, 'duplicate']);
    
    // OPTIONS routes for product discounts
    Route::options('/product-discounts', function () { return response('', 204); });
    Route::options('/product-discounts/statistics', function () { return response('', 204); });
    Route::options('/product-discounts/{id}', function () { return response('', 204); });
    Route::options('/product-discounts/{id}/toggle-status', function () { return response('', 204); });
    Route::options('/product-discounts/{id}/affected-products', function () { return response('', 204); });
    Route::options('/product-discounts/{id}/duplicate', function () { return response('', 204); });

    // Payment Methods Management
    Route::get('/payment-methods', [\App\Http\Controllers\Api\Admin\PaymentMethodController::class, 'index']);
    Route::put('/payment-methods/{code}/toggle', [\App\Http\Controllers\Api\Admin\PaymentMethodController::class, 'toggle']);
    Route::post('/payment-methods/sync', [\App\Http\Controllers\Api\Admin\PaymentMethodController::class, 'sync']);
    Route::put('/payment-methods/{code}', [\App\Http\Controllers\Api\Admin\PaymentMethodController::class, 'update']);
    
    // OPTIONS routes for payment methods
    Route::options('/payment-methods', function () { return response('', 204); });
    Route::options('/payment-methods/{code}/toggle', function () { return response('', 204); });
    Route::options('/payment-methods/sync', function () { return response('', 204); });
    Route::options('/payment-methods/{code}', function () { return response('', 204); });
    
    // Users Management
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
    Route::get('/users/statistics', [\App\Http\Controllers\Api\Admin\UserController::class, 'statistics']);
    Route::post('/users/bulk-update', [\App\Http\Controllers\Api\Admin\UserController::class, 'bulkUpdate']);
    Route::get('/users/export', [\App\Http\Controllers\Api\Admin\UserController::class, 'export']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
    Route::put('/users/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleStatus']);
    Route::put('/users/{id}/change-password', [\App\Http\Controllers\Api\Admin\UserController::class, 'changePassword']);
    
    // Customer Discounts (Permanent discounts assigned to specific customers)
    Route::get('/customer-discounts', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'index']);
    Route::get('/customer-discounts/customers', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'customers']);
    Route::get('/customer-discounts/statistics', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'statistics']);
    Route::post('/customer-discounts', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'store']);
    Route::put('/customer-discounts/{id}', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'update']);
    Route::delete('/customer-discounts/{id}', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'destroy']);
    Route::post('/customer-discounts/{id}/toggle', [\App\Http\Controllers\Api\Admin\CustomerDiscountController::class, 'toggle']);

    // Data Export System (Admin)
    Route::prefix('exports')->group(function () {
        Route::post('/products', [\App\Http\Controllers\Api\ExportController::class, 'exportProducts']);
        Route::post('/customers', [\App\Http\Controllers\Api\ExportController::class, 'exportCustomers']);
        Route::post('/orders', [\App\Http\Controllers\Api\ExportController::class, 'exportOrders']);
        Route::get('/', [\App\Http\Controllers\Api\ExportController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ExportController::class, 'show']);
        Route::get('/{id}/status', [\App\Http\Controllers\Api\ExportController::class, 'getStatus']);
        Route::get('/{id}/download', [\App\Http\Controllers\Api\ExportController::class, 'download']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ExportController::class, 'destroy']);
        Route::get('/statistics/overview', [\App\Http\Controllers\Api\ExportController::class, 'getStatistics']);
    });
    
    // Advanced Reporting System
    Route::prefix('reports')->group(function () {
        // Dashboard reports
        Route::get('/dashboard/overview', [\App\Http\Controllers\Api\ReportController::class, 'getDashboardOverview']);
        Route::get('/dashboard/business-intelligence', [\App\Http\Controllers\Api\ReportController::class, 'getBusinessIntelligence']);
        
        // Analytics reports
        Route::get('/analytics/sales', [\App\Http\Controllers\Api\ReportController::class, 'getSalesAnalytics']);
        Route::get('/analytics/customers', [\App\Http\Controllers\Api\ReportController::class, 'getCustomerAnalytics']);
        Route::get('/analytics/products', [\App\Http\Controllers\Api\ReportController::class, 'getProductAnalytics']);
        Route::get('/analytics/orders', [\App\Http\Controllers\Api\ReportController::class, 'getOrderAnalytics']);
        
        // Financial reports
        Route::get('/financial/overview', [\App\Http\Controllers\Api\ReportController::class, 'getFinancialReports']);
        
        // OPTIONS routes for CORS
        Route::options('/dashboard/overview', function () { return response('', 204); });
        Route::options('/dashboard/business-intelligence', function () { return response('', 204); });
        Route::options('/analytics/sales', function () { return response('', 204); });
        Route::options('/analytics/customers', function () { return response('', 204); });
        Route::options('/analytics/products', function () { return response('', 204); });
        Route::options('/analytics/orders', function () { return response('', 204); });
        Route::options('/financial/overview', function () { return response('', 204); });
    });
    
    // Shipping Cost Management (Admin)
    Route::prefix('shipping')->group(function () {
        Route::get('/', [\App\Http\Controllers\ShippingController::class, 'index']);
        Route::get('/active', [\App\Http\Controllers\ShippingController::class, 'getActive']);
        Route::put('/update', [\App\Http\Controllers\ShippingController::class, 'update']);
        
        // OPTIONS routes for CORS
        Route::options('/', function () { return response('', 204); });
        Route::options('/active', function () { return response('', 204); });
        Route::options('/update', function () { return response('', 204); });
    });
    
    // Country Shipping Rates Management (Admin)
    Route::apiResource('shipping-rates', \App\Http\Controllers\Api\Admin\CountryShippingRateController::class);
    Route::post('/shipping-rates/bulk-update', [\App\Http\Controllers\Api\Admin\CountryShippingRateController::class, 'bulkUpdate']);
    Route::options('/shipping-rates', function () { return response('', 204); });
    Route::options('/shipping-rates/bulk-update', function () { return response('', 204); });
    Route::options('/shipping-rates/{id}', function () { return response('', 204); });
    
    // Shipping Weight Tiers Management (Admin)
    Route::prefix('shipping-rates/{countryCode}/tiers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'store']);
        Route::get('/{tier}', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'show']);
        Route::put('/{tier}', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'update']);
        Route::delete('/{tier}', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'destroy']);
        Route::post('/bulk', [\App\Http\Controllers\Api\Admin\ShippingWeightTierController::class, 'bulkUpdate']);
        
        // OPTIONS routes for CORS
        Route::options('/', function () { return response('', 204); });
        Route::options('/{tier}', function () { return response('', 204); });
        Route::options('/bulk', function () { return response('', 204); });
    });

    
    // WhatsApp Settings Management (Admin)
    Route::prefix('whatsapp')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'index']);
        Route::get('/{key}', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'show']);
        Route::put('/{key}', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'update']);
        Route::post('/bulk-update', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'bulkUpdate']);
        Route::post('/toggle-global', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'toggleGlobal']);
        Route::post('/toggle-admin', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'toggleAdminNotifications']);
        Route::post('/toggle-delivery', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'toggleDeliveryNotifications']);
        Route::post('/test', [\App\Http\Controllers\Api\Admin\WhatsAppController::class, 'test']);
        
        // OPTIONS routes for CORS
        Route::options('/', function () { return response('', 204); });
        Route::options('/{key}', function () { return response('', 204); });
        Route::options('/bulk-update', function () { return response('', 204); });
        Route::options('/toggle-global', function () { return response('', 204); });
        Route::options('/toggle-admin', function () { return response('', 204); });
        Route::options('/toggle-delivery', function () { return response('', 204); });
        Route::options('/test', function () { return response('', 204); });
    });
    
    // Site Settings Management (Admin)
    Route::prefix('site')->group(function () {
        Route::get('/orders-status', [\App\Http\Controllers\Api\Admin\SiteSettingController::class, 'getOrdersStatus']);
        Route::post('/toggle-orders', [\App\Http\Controllers\Api\Admin\SiteSettingController::class, 'toggleOrders']);
        Route::post('/set-orders-status', [\App\Http\Controllers\Api\Admin\SiteSettingController::class, 'setOrdersStatus']);
        
        // OPTIONS routes for CORS
        Route::options('/orders-status', function () { return response('', 204); });
        Route::options('/toggle-orders', function () { return response('', 204); });
        Route::options('/set-orders-status', function () { return response('', 204); });
    });
});

// Admin Auth (Public)
Route::prefix('v1/admin')->group(function () {
    // CORS preflight for admin login
    Route::options('/login', function () { return response('', 204); });

    Route::post('/login', [\App\Http\Controllers\Api\Admin\AuthController::class, 'login']);
    
    // Chatbot Management APIs (Admin)
    Route::prefix('chatbot')->group(function () {
        // Settings management
        Route::get('/settings', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'getSettings']);
        Route::put('/settings', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'updateSettings']);
        
        // Statistics and analytics
        Route::get('/statistics', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'getStatistics']);
        
        // Conversation management
        Route::get('/conversations', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'getConversations']);
        Route::get('/conversations/{id}', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'getConversation']);
        Route::delete('/conversations/{id}', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'deleteConversation']);
        Route::delete('/conversations/clear-old', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'clearOldConversations']);
        
        // Product management for chatbot
        Route::get('/products', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'getAvailableProducts']);
        
        // Configuration testing
        Route::post('/test', [\App\Http\Controllers\Admin\ChatbotAdminController::class, 'testConfiguration']);
        
        // CORS preflight routes
        Route::options('/settings', function () { return response('', 204); });
        Route::options('/statistics', function () { return response('', 204); });
        Route::options('/conversations', function () { return response('', 204); });
        Route::options('/conversations/{id}', function () { return response('', 204); });
        Route::options('/products', function () { return response('', 204); });
        Route::options('/test', function () { return response('', 204); });
        Route::options('/conversations/clear-old', function () { return response('', 204); });
    });
});
// OPTIONS routes for all admin endpoints
Route::prefix('v1')->group(function () {
    Route::options('{any}', function () {
        return response('', 204);
    })->where('any', '.*');
});
