# 📦 إدارة الطلبات المتقدمة - دليل المطور

## نظرة عامة

نظام إدارة الطلبات المتقدم يوفر أدوات شاملة لإدارة وتحليل الطلبات في المتجر الإلكتروني. يتضمن فلترة متقدمة، تحديثات مجمعة، تتبع الطلبات، وتحليلات مفصلة.

## 🚀 الميزات الرئيسية

### 1. إدارة الطلبات المتقدمة
- **فلترة متقدمة**: حسب الحالة، التاريخ، العميل، المبلغ
- **بحث ذكي**: البحث في رقم الطلب، اسم العميل، الهاتف، البريد
- **تحديث مجمع**: تحديث حالة عدة طلبات في مرة واحدة
- **تتبع الطلبات**: رقم التتبع، تواريخ الشحن والتسليم
- **ملاحظات إدارية**: ملاحظات خاصة بالمدير

### 2. تحليلات الطلبات
- **إحصائيات شاملة**: نظرة عامة على جميع الطلبات
- **توزيع الحالات**: إحصائيات حالات الطلبات
- **اتجاهات يومية**: تحليل المبيعات اليومية
- **أفضل العملاء**: العملاء الأكثر إنفاقاً
- **معدل الإنجاز**: نسبة الطلبات المكتملة

### 3. تصدير البيانات
- **تصدير متعدد التنسيقات**: CSV, JSON, XLSX
- **فلترة التصدير**: تصدير حسب المعايير المحددة
- **بيانات شاملة**: جميع تفاصيل الطلب والدفع

### 4. إدارة الحالات
- **انتقالات صحيحة**: التحقق من صحة تغيير الحالة
- **إشعارات تلقائية**: إشعارات عند تغيير الحالة
- **سجل التغييرات**: تتبع جميع التغييرات

## 📡 APIs المتاحة

### قائمة الطلبات مع الفلترة
```http
GET /api/v1/admin/order-management?per_page=15&status=paid&date_from=2025-10-01&date_to=2025-10-31&customer_name=أحمد&min_amount=50&max_amount=200&sort_by=created_at&sort_order=desc
```

**المعاملات:**
- `per_page` (اختياري): عدد الطلبات في الصفحة (افتراضي: 15)
- `status` (اختياري): حالة الطلب (pending, awaiting_payment, paid, shipped, delivered, cancelled, refunded)
- `date_from` (اختياري): تاريخ البداية (YYYY-MM-DD)
- `date_to` (اختياري): تاريخ النهاية (YYYY-MM-DD)
- `customer_name` (اختياري): اسم العميل
- `customer_phone` (اختياري): رقم هاتف العميل
- `order_number` (اختياري): رقم الطلب
- `min_amount` (اختياري): الحد الأدنى للمبلغ
- `max_amount` (اختياري): الحد الأقصى للمبلغ
- `payment_status` (اختياري): حالة الدفع
- `sort_by` (اختياري): ترتيب حسب (created_at, total_amount, status)
- `sort_order` (اختياري): اتجاه الترتيب (asc, desc)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "orders": {
      "current_page": 1,
      "data": [
        {
          "id": 3,
          "order_number": "ORD-20251002-33685F",
          "customer_name": "محمد علي",
          "customer_phone": "+96555555555",
          "customer_email": "mohammed@example.com",
          "shipping_address": {...},
          "total_amount": "35.750",
          "currency": "KWD",
          "status": "shipped",
          "tracking_number": "TRK123456789",
          "shipping_date": null,
          "delivery_date": null,
          "payment_id": 3,
          "notes": "تم شحن الطلب بنجاح",
          "admin_notes": null,
          "created_at": "2025-10-02T19:13:39.000000Z",
          "updated_at": "2025-10-02T19:44:34.000000Z",
          "order_items": [...],
          "payment": {...}
        }
      ],
      "pagination": {...}
    },
    "summary": {
      "total_orders": 3,
      "total_revenue": "122.500",
      "pending_orders": 0,
      "paid_orders": 1,
      "shipped_orders": 1,
      "delivered_orders": 0,
      "cancelled_orders": 0
    }
  }
}
```

### تفاصيل الطلب
```http
GET /api/v1/admin/order-management/{id}
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 3,
      "order_number": "ORD-20251002-33685F",
      "customer_name": "محمد علي",
      "customer_phone": "+96555555555",
      "customer_email": "mohammed@example.com",
      "shipping_address": {...},
      "total_amount": "35.750",
      "currency": "KWD",
      "status": "shipped",
      "tracking_number": "TRK123456789",
      "shipping_date": null,
      "delivery_date": null,
      "payment_id": 3,
      "notes": "تم شحن الطلب بنجاح",
      "admin_notes": null,
      "created_at": "2025-10-02T19:13:39.000000Z",
      "updated_at": "2025-10-02T19:44:34.000000Z",
      "order_items": [...],
      "payment": {...}
    },
    "timeline": [
      {
        "event": "order_created",
        "title": "تم إنشاء الطلب",
        "description": "تم إنشاء الطلب بنجاح",
        "timestamp": "2025-10-02T19:13:39.000000Z",
        "status": "completed"
      },
      {
        "event": "payment_completed",
        "title": "تم الدفع بنجاح",
        "description": "تم إتمام عملية الدفع",
        "timestamp": "2025-10-02T19:22:53.000000Z",
        "status": "completed"
      },
      {
        "event": "order_shipped",
        "title": "تم شحن الطلب",
        "description": "تم شحن الطلب بنجاح",
        "timestamp": "2025-10-02T19:44:34.000000Z",
        "status": "completed"
      }
    ],
    "related_orders": [...]
  }
}
```

### تحديث حالة الطلب
```http
PUT /api/v1/admin/order-management/{id}/update-status
```

**المعاملات:**
```json
{
  "status": "shipped",
  "notes": "تم شحن الطلب بنجاح",
  "tracking_number": "TRK123456789",
  "shipping_date": "2025-10-02",
  "delivery_date": "2025-10-05"
}
```

**المعاملات:**
- `status` (مطلوب): الحالة الجديدة
- `notes` (اختياري): ملاحظات إضافية
- `tracking_number` (اختياري): رقم التتبع
- `shipping_date` (اختياري): تاريخ الشحن
- `delivery_date` (اختياري): تاريخ التسليم

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "order_number": "ORD-20251002-33685F",
    "status": "shipped",
    "tracking_number": "TRK123456789",
    "notes": "تم شحن الطلب بنجاح",
    "updated_at": "2025-10-02T19:44:34.000000Z"
  },
  "message": "Order status updated successfully"
}
```

### تحديث مجمع للحالات
```http
POST /api/v1/admin/order-management/bulk-update-status
```

**المعاملات:**
```json
{
  "order_ids": [1, 2, 3],
  "status": "shipped",
  "notes": "تم شحن جميع الطلبات"
}
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "updated_orders": [...],
    "failed_orders": [...],
    "summary": {
      "total_requested": 3,
      "successfully_updated": 2,
      "failed": 1
    }
  },
  "message": "Bulk status update completed"
}
```

### إحصائيات الطلبات
```http
GET /api/v1/admin/order-management/statistics?period=30
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_orders": 3,
      "pending_orders": 0,
      "paid_orders": 1,
      "shipped_orders": 1,
      "delivered_orders": 0,
      "cancelled_orders": 0
    },
    "period_stats": {
      "orders_count": 3,
      "total_revenue": "86.750",
      "average_order_value": "86.7500000",
      "completion_rate": 0
    },
    "status_distribution": {
      "awaiting_payment": 1,
      "paid": 1,
      "shipped": 1
    },
    "daily_trends": [
      {
        "date": "2025-10-02",
        "orders_count": 3,
        "revenue": "148.000"
      }
    ],
    "top_customers": [
      {
        "customer_name": "أحمد محمد",
        "customer_phone": "+96512345678",
        "orders_count": 1,
        "total_spent": "86.750"
      }
    ]
  }
}
```

### البحث في الطلبات
```http
GET /api/v1/admin/order-management/search?q=ORD-20251002&limit=10
```

**المعاملات:**
- `q` (مطلوب): نص البحث
- `limit` (اختياري): عدد النتائج (افتراضي: 10)

### خط زمني الطلب
```http
GET /api/v1/admin/order-management/{id}/timeline
```

### تصدير الطلبات
```http
GET /api/v1/admin/order-management/export?format=csv&status=paid&date_from=2025-10-01&date_to=2025-10-31
```

**المعاملات:**
- `format` (اختياري): تنسيق التصدير (csv, json, xlsx)
- `status` (اختياري): حالة الطلبات
- `date_from` (اختياري): تاريخ البداية
- `date_to` (اختياري): تاريخ النهاية
- `customer_name` (اختياري): اسم العميل
- `min_amount` (اختياري): الحد الأدنى للمبلغ
- `max_amount` (اختياري): الحد الأقصى للمبلغ

## 🛠️ أوامر CLI

### إدارة الطلبات
```bash
php artisan order:manage {action} --days=7 --status= --dry-run
```

**الأفعال المتاحة:**
- `cleanup`: تنظيف الطلبات القديمة
- `status-report`: تقرير حالات الطلبات
- `pending-reminder`: تذكير الطلبات المعلقة
- `overdue-check`: فحص الطلبات المتأخرة
- `statistics`: إحصائيات الطلبات

**المعاملات:**
- `--days`: عدد الأيام (افتراضي: 7)
- `--status`: حالة الطلبات
- `--dry-run`: وضع التجربة (لا يتم حذف البيانات)

### تصدير الطلبات
```bash
php artisan order:export --format=csv --status=paid --days=30 --output=orders_export
```

**المعاملات:**
- `--format`: تنسيق التصدير (csv, json, xlsx)
- `--status`: حالة الطلبات
- `--days`: عدد الأيام
- `--output`: اسم الملف

## 📊 أمثلة الاستخدام

### 1. الحصول على الطلبات المدفوعة
```bash
curl -X GET "http://localhost:8000/api/v1/admin/order-management?status=paid&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. البحث عن طلب معين
```bash
curl -X GET "http://localhost:8000/api/v1/admin/order-management/search?q=ORD-20251002" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. تحديث حالة الطلب
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/order-management/3/update-status" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"status": "shipped", "tracking_number": "TRK123456789"}'
```

### 4. تحديث مجمع للحالات
```bash
curl -X POST "http://localhost:8000/api/v1/admin/order-management/bulk-update-status" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"order_ids": [1, 2, 3], "status": "shipped"}'
```

### 5. تصدير الطلبات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/order-management/export?format=csv&status=paid" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🔧 التخصيص

### إضافة حقول جديدة
يمكن إضافة حقول جديدة في migration:

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('custom_field')->nullable();
});
```

### إضافة فلاتر جديدة
يمكن إضافة فلاتر جديدة في `OrderManagementController`:

```php
if ($request->has('custom_filter')) {
    $query->where('custom_field', $request->custom_filter);
}
```

### إضافة حالات جديدة
يمكن إضافة حالات جديدة في validation:

```php
'status' => 'required|in:pending,awaiting_payment,paid,shipped,delivered,cancelled,refunded,custom_status'
```

## 📈 الأداء والتحسين

### نصائح الأداء
1. **استخدام الفهرسة**: تأكد من وجود فهارس على الحقول المستخدمة في الفلترة
2. **التخزين المؤقت**: استخدم التخزين المؤقت للبيانات التي لا تتغير كثيراً
3. **الاستعلامات المحسنة**: استخدم `selectRaw` للاستعلامات المعقدة
4. **التجميع**: استخدم التجميع لتقليل عدد الاستعلامات

### مراقبة الأداء
```php
// مراقبة وقت الاستجابة
$startTime = microtime(true);
$orders = $query->paginate($perPage);
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
```

## 🔒 الأمان

### المصادقة
جميع APIs تتطلب مصادقة JWT:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

### الصلاحيات
تأكد من أن المستخدم لديه صلاحيات المدير:

```php
if (!$user->isAdmin()) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

### التحقق من صحة البيانات
```php
$validator = Validator::make($request->all(), [
    'status' => 'required|in:pending,awaiting_payment,paid,shipped,delivered,cancelled,refunded',
    'tracking_number' => 'nullable|string|max:255',
    'notes' => 'nullable|string|max:1000'
]);
```

## 🐛 استكشاف الأخطاء

### مشاكل شائعة

1. **خطأ في الاستعلامات**
   - تحقق من وجود البيانات
   - تأكد من صحة الفهارس
   - راجع استعلامات SQL

2. **مشاكل الأداء**
   - استخدم `EXPLAIN` لتحليل الاستعلامات
   - تحقق من استخدام الفهارس
   - فكر في التخزين المؤقت

3. **مشاكل التصدير**
   - تحقق من صلاحيات الكتابة
   - تأكد من وجود مساحة كافية
   - راجع تنسيق البيانات

### سجلات الأخطاء
```php
Log::error('Order management error', [
    'error' => $e->getMessage(),
    'order_id' => $orderId,
    'action' => $action
]);
```

## 📚 المراجع

- [Laravel Documentation](https://laravel.com/docs)
- [Carbon Documentation](https://carbon.nesbot.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [JWT Documentation](https://jwt.io/)

## 🤝 المساهمة

للمساهمة في تطوير نظام إدارة الطلبات:

1. Fork المشروع
2. إنشاء فرع جديد
3. إجراء التغييرات
4. إرسال Pull Request

## 📞 الدعم

للحصول على الدعم:
- إنشاء Issue في GitHub
- مراجعة الوثائق
- التواصل مع فريق التطوير
