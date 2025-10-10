# نظام الإشعارات - Notification System

## نظرة عامة - Overview

نظام الإشعارات في متجر Soapy Shop يوفر إشعارات فورية للمديرين حول الأحداث المهمة في المتجر، مثل الطلبات المدفوعة والمشحونة والمسلمة. يستخدم النظام Laravel Broadcasting مع Pusher لإرسال الإشعارات في الوقت الفعلي.

The notification system in Soapy Shop provides real-time notifications to administrators about important store events, such as paid, shipped, and delivered orders. The system uses Laravel Broadcasting with Pusher for real-time notifications.

## الميزات الرئيسية - Key Features

- ✅ إشعارات فورية عبر WebSocket
- ✅ إشعارات مخصصة للطلبات المدفوعة فقط
- ✅ أولويات مختلفة للإشعارات (عالية، متوسطة، منخفضة)
- ✅ تصفية الإشعارات حسب النوع والحالة والتاريخ
- ✅ إحصائيات شاملة للإشعارات
- ✅ واجهة برمجة تطبيقات RESTful كاملة

## هيكل البيانات - Data Structure

### نموذج الإشعار - Notification Model

```php
// app/Models/AdminNotification.php
class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title', 
        'message',
        'priority',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];
}
```

### أنواع الإشعارات - Notification Types

| النوع - Type | الأولوية - Priority | الوصف - Description |
|-------------|-------------------|---------------------|
| `order_paid` | `high` | طلب تم دفعه - Order has been paid |
| `order_shipped` | `medium` | طلب تم شحنه - Order has been shipped |
| `order_delivered` | `medium` | طلب تم تسليمه - Order has been delivered |
| `new_payment` | `high` | دفعة جديدة - New payment received |
| `payment_failed` | `high` | فشل في الدفع - Payment failed |
| `payment_refunded` | `medium` | استرداد دفعة - Payment refunded |

## واجهة برمجة التطبيقات - API Endpoints

### 1. جلب الإشعارات - Get Notifications

**GET** `/api/admin/notifications`

#### المعاملات - Parameters

| المعامل - Parameter | النوع - Type | مطلوب - Required | الوصف - Description |
|-------------------|-------------|-----------------|---------------------|
| `page` | integer | لا - No | رقم الصفحة (افتراضي: 1) |
| `per_page` | integer | لا - No | عدد العناصر لكل صفحة (افتراضي: 15) |
| `type` | string | لا - No | تصفية حسب النوع |
| `read` | boolean | لا - No | تصفية حسب حالة القراءة |
| `date_from` | date | لا - No | من تاريخ (Y-m-d) |
| `date_to` | date | لا - No | إلى تاريخ (Y-m-d) |

#### مثال على الطلب - Request Example

```bash
GET /api/admin/notifications?page=1&per_page=10&type=order_paid&read=false
Authorization: Bearer {token}
Content-Type: application/json
```

#### مثال على الاستجابة - Response Example

```json
{
    "success": true,
    "data": {
        "notifications": {
            "current_page": 1,
            "data": [
                {
                    "id": 15,
                    "type": "order_paid",
                    "title": "طلب مدفوع",
                    "message": "تم دفع الطلب رقم ORD-2024-001 بقيمة 75.500 KWD من العميل أحمد محمد",
                    "priority": "high",
                    "data": {
                        "order_id": 123,
                        "order_number": "ORD-2024-001",
                        "customer_name": "أحمد محمد",
                        "customer_phone": "+965 1234 5678",
                        "total_amount": "75.500",
                        "currency": "KWD",
                        "status": "paid",
                        "created_at": "2024-01-15T10:30:00Z"
                    },
                    "read_at": null,
                    "created_at": "2024-01-15T10:30:00Z",
                    "updated_at": "2024-01-15T10:30:00Z"
                }
            ],
            "first_page_url": "http://localhost:8000/api/admin/notifications?page=1",
            "from": 1,
            "last_page": 3,
            "last_page_url": "http://localhost:8000/api/admin/notifications?page=3",
            "links": [
                {
                    "url": null,
                    "label": "&laquo; Previous",
                    "active": false
                },
                {
                    "url": "http://localhost:8000/api/admin/notifications?page=1",
                    "label": "1",
                    "active": true
                }
            ],
            "next_page_url": "http://localhost:8000/api/admin/notifications?page=2",
            "path": "http://localhost:8000/api/admin/notifications",
            "per_page": 10,
            "prev_page_url": null,
            "to": 10,
            "total": 25
        },
        "summary": {
            "total": 25,
            "unread": 8,
            "read": 17,
            "by_type": {
                "order_paid": 12,
                "order_shipped": 8,
                "order_delivered": 5
            }
        }
    }
}
```

### 2. تحديد إشعار كمقروء - Mark as Read

**PUT** `/api/admin/notifications/{id}/read`

#### مثال على الطلب - Request Example

```bash
PUT /api/admin/notifications/15/read
Authorization: Bearer {token}
Content-Type: application/json
```

#### مثال على الاستجابة - Response Example

```json
{
    "success": true,
    "message": "تم تحديد الإشعار كمقروء",
    "data": {
        "id": 15,
        "type": "order_paid",
        "title": "طلب مدفوع",
        "message": "تم دفع الطلب رقم ORD-2024-001 بقيمة 75.500 KWD من العميل أحمد محمد",
        "priority": "high",
        "data": {
            "order_id": 123,
            "order_number": "ORD-2024-001",
            "customer_name": "أحمد محمد",
            "total_amount": "75.500",
            "currency": "KWD"
        },
        "read_at": "2024-01-15T11:00:00Z",
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T11:00:00Z"
    }
}
```

### 3. تحديد جميع الإشعارات كمقروءة - Mark All as Read

**PUT** `/api/admin/notifications/mark-all-read`

#### مثال على الطلب - Request Example

```bash
PUT /api/admin/notifications/mark-all-read
Authorization: Bearer {token}
Content-Type: application/json
```

#### مثال على الاستجابة - Response Example

```json
{
    "success": true,
    "message": "تم تحديد جميع الإشعارات كمقروءة",
    "data": {
        "updated_count": 8
    }
}
```

### 4. حذف إشعار - Delete Notification

**DELETE** `/api/admin/notifications/{id}`

#### مثال على الطلب - Request Example

```bash
DELETE /api/admin/notifications/15
Authorization: Bearer {token}
Content-Type: application/json
```

#### مثال على الاستجابة - Response Example

```json
{
    "success": true,
    "message": "تم حذف الإشعار بنجاح"
}
```

### 5. حذف الإشعارات المقروءة - Delete Read Notifications

**DELETE** `/api/admin/notifications/read`

#### مثال على الطلب - Request Example

```bash
DELETE /api/admin/notifications/read
Authorization: Bearer {token}
Content-Type: application/json
```

#### مثال على الاستجابة - Response Example

```json
{
    "success": true,
    "message": "تم حذف الإشعارات المقروءة بنجاح",
    "data": {
        "deleted_count": 17
    }
}
```

## الإشعارات الفورية - Real-time Notifications

### إعداد Laravel Echo

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    },
});
```

### الاستماع للإشعارات - Listening to Notifications

```javascript
// الاستماع لإشعارات المدير
Echo.private('admin-notifications')
    .listen('NotificationCreated', (e) => {
        console.log('إشعار جديد:', e);
        
        // عرض الإشعار
        showNotification(e);
        
        // تحديث عداد الإشعارات
        updateNotificationCounter();
    });

// دالة عرض الإشعار
function showNotification(notification) {
    const toast = document.createElement('div');
    toast.className = `notification-toast priority-${notification.priority}`;
    toast.innerHTML = `
        <div class="notification-header">
            <span class="notification-title">${notification.title}</span>
            <span class="notification-time">${formatTime(notification.created_at)}</span>
        </div>
        <div class="notification-message">${notification.message}</div>
    `;
    
    document.body.appendChild(toast);
    
    // إزالة الإشعار بعد فترة
    setTimeout(() => {
        toast.remove();
    }, getPriorityDuration(notification.priority));
}

// مدة عرض الإشعار حسب الأولوية
function getPriorityDuration(priority) {
    const durations = {
        'high': 8000,    // 8 ثواني
        'medium': 5000,  // 5 ثواني
        'low': 3000      // 3 ثواني
    };
    return durations[priority] || 5000;
}
```

### بيانات الإشعار الفوري - Real-time Notification Data

```json
{
    "id": 16,
    "type": "order_paid",
    "title": "طلب مدفوع",
    "message": "تم دفع الطلب رقم ORD-2024-002 بقيمة 125.750 KWD من العميل فاطمة أحمد",
    "priority": "high",
    "data": {
        "order_id": 124,
        "order_number": "ORD-2024-002",
        "customer_name": "فاطمة أحمد",
        "customer_phone": "+965 9876 5432",
        "total_amount": "125.750",
        "currency": "KWD",
        "status": "paid",
        "created_at": "2024-01-15T14:20:00Z"
    },
    "created_at": "2024-01-15T14:20:00Z",
    "read_at": null
}
```

## أوامر Artisan - Artisan Commands

### 1. إحصائيات الإشعارات - Notification Statistics

```bash
php artisan notification:stats
```

**الناتج - Output:**
```
📊 إحصائيات الإشعارات - Notification Statistics
+------------------+-------+
| النوع - Type     | العدد |
+------------------+-------+
| order_paid       | 45    |
| order_shipped    | 32    |
| order_delivered  | 28    |
| new_payment      | 15    |
| payment_failed   | 3     |
+------------------+-------+
| المجموع - Total  | 123   |
+------------------+-------+

📈 الحالة - Status:
- غير مقروءة - Unread: 18
- مقروءة - Read: 105
```

### 2. تنظيف الإشعارات القديمة - Cleanup Old Notifications

```bash
php artisan notification:cleanup --days=30
```

### 3. إنشاء إشعار تجريبي - Create Test Notification

```bash
php artisan notification:test
```

## التكامل مع النظام - System Integration

### خدمة الإشعارات - NotificationService

```php
// app/Services/NotificationService.php
class NotificationService
{
    public function createOrderNotification(Order $order, string $eventType): AdminNotification
    {
        $notificationData = $this->getNotificationData($eventType, $order);
        
        $notification = AdminNotification::create([
            'type' => $eventType,
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'priority' => $notificationData['priority'],
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $order->status,
                'created_at' => $order->created_at->toISOString(),
            ]
        ]);

        // إرسال الإشعار الفوري
        broadcast(new NotificationCreated($notification));

        return $notification;
    }
}
```

### متحكم الدفع - PaymentController Integration

```php
// app/Http/Controllers/Api/Customer/PaymentController.php
public function processPayment(Request $request)
{
    // معالجة الدفع...
    
    if ($paymentSuccessful) {
        $order->update(['status' => 'paid']);
        
        // إرسال إشعار الدفع
        $this->notificationService->createOrderNotification($order, 'order_paid');
        
        return response()->json([
            'success' => true,
            'message' => 'تم الدفع بنجاح'
        ]);
    }
}
```

## أمثلة على واجهة المستخدم - UI Examples

### عداد الإشعارات - Notification Counter

```html
<div class="notification-bell">
    <i class="fas fa-bell"></i>
    <span class="notification-count" id="notificationCount">3</span>
</div>
```

### قائمة الإشعارات المنسدلة - Notification Dropdown

```html
<div class="notification-dropdown">
    <div class="notification-header">
        <h3>الإشعارات</h3>
        <button onclick="markAllAsRead()">تحديد الكل كمقروء</button>
    </div>
    <div class="notification-list">
        <div class="notification-item unread priority-high">
            <div class="notification-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="notification-content">
                <h4>طلب مدفوع</h4>
                <p>تم دفع الطلب رقم ORD-2024-001 بقيمة 75.500 KWD</p>
                <span class="notification-time">منذ 5 دقائق</span>
            </div>
        </div>
    </div>
</div>
```

### أنماط CSS - CSS Styles

```css
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    max-width: 400px;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
}

.priority-high {
    background: #fee2e2;
    border-left: 4px solid #dc2626;
    color: #991b1b;
}

.priority-medium {
    background: #fef3c7;
    border-left: 4px solid #d97706;
    color: #92400e;
}

.priority-low {
    background: #dbeafe;
    border-left: 4px solid #2563eb;
    color: #1d4ed8;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
```

## معالجة الأخطاء - Error Handling

### أخطاء شائعة - Common Errors

```json
{
    "success": false,
    "message": "الإشعار غير موجود",
    "error": "Notification not found",
    "code": 404
}
```

```json
{
    "success": false,
    "message": "خطأ في التحقق من البيانات",
    "errors": {
        "type": ["نوع الإشعار مطلوب"],
        "priority": ["الأولوية يجب أن تكون: high, medium, أو low"]
    },
    "code": 422
}
```

## الأمان - Security

### التحقق من الصلاحيات - Authorization

```php
// في Middleware
public function handle($request, Closure $next)
{
    if (!$request->user() || !$request->user()->isAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بالوصول'
        ], 403);
    }
    
    return $next($request);
}
```

### تشفير البيانات الحساسة - Data Encryption

```php
// تشفير البيانات الحساسة في الإشعارات
protected $casts = [
    'data' => 'encrypted:array'
];
```

## الأداء والتحسين - Performance & Optimization

### فهرسة قاعدة البيانات - Database Indexing

```sql
-- إضافة فهارس لتحسين الأداء
CREATE INDEX idx_admin_notifications_type ON admin_notifications(type);
CREATE INDEX idx_admin_notifications_read_at ON admin_notifications(read_at);
CREATE INDEX idx_admin_notifications_created_at ON admin_notifications(created_at);
CREATE INDEX idx_admin_notifications_priority ON admin_notifications(priority);
```

### التخزين المؤقت - Caching

```php
// تخزين مؤقت لإحصائيات الإشعارات
$stats = Cache::remember('notification_stats', 300, function () {
    return [
        'total' => AdminNotification::count(),
        'unread' => AdminNotification::whereNull('read_at')->count(),
        'by_type' => AdminNotification::groupBy('type')->selectRaw('type, count(*) as count')->pluck('count', 'type')
    ];
});
```

## الخلاصة - Summary

نظام الإشعارات في Soapy Shop يوفر:

- ✅ إشعارات فورية للطلبات المدفوعة فقط
- ✅ واجهة برمجة تطبيقات شاملة ومرنة
- ✅ تكامل سلس مع Laravel Broadcasting
- ✅ أدوات إدارة وإحصائيات متقدمة
- ✅ أمان وأداء محسن

The notification system provides real-time notifications for paid orders only, with a comprehensive API, seamless Laravel Broadcasting integration, advanced management tools, and optimized security and performance.