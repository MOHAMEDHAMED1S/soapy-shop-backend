# 📊 لوحة تحكم المدير - دليل المطور

## نظرة عامة

لوحة تحكم المدير هي نظام شامل لإدارة وتحليل بيانات المتجر الإلكتروني. توفر إحصائيات مفصلة وتحليلات متقدمة لمساعدة المديرين على اتخاذ قرارات مدروسة.

## 🚀 الميزات الرئيسية

### 1. نظرة عامة شاملة
- إحصائيات الطلبات والمنتجات والفئات
- إجمالي الإيرادات والمبيعات
- الطلبات المعلقة والإشعارات غير المقروءة
- مقارنات النمو مع الفترات السابقة

### 2. تحليلات المبيعات
- بيانات المبيعات اليومية/الأسبوعية/الشهرية
- متوسط قيمة الطلب
- اتجاهات المبيعات
- مقارنات الفترات

### 3. تحليلات المنتجات
- أفضل المنتجات مبيعاً
- أداء الفئات
- إحصائيات التوفر
- تحليل الأداء

### 4. تحليلات الطلبات
- توزيع حالات الطلبات
- اتجاهات الطلبات اليومية
- متوسط وقت المعالجة
- توزيع قيم الطلبات

### 5. تحليلات المدفوعات
- توزيع طرق الدفع
- معدل نجاح المدفوعات
- اتجاهات المدفوعات اليومية
- إحصائيات الحالة

### 6. تحليلات العملاء
- أفضل العملاء
- توزيع العملاء
- إحصائيات الطلبات
- تحليل السلوك

## 📡 APIs المتاحة

### نظرة عامة
```http
GET /api/v1/admin/dashboard/overview?period=30
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
      "total_products": 10,
      "total_categories": 14,
      "total_revenue": "122.500",
      "pending_orders": 0,
      "low_stock_products": 10,
      "unread_notifications": 4
    },
    "period_stats": {
      "orders_count": 3,
      "revenue": "122.500",
      "new_products": 10,
      "completed_orders": 0
    },
    "growth": {
      "orders_growth": 100,
      "revenue_growth": 100
    },
    "period": 30,
    "date_range": {
      "start": "2025-09-02",
      "end": "2025-10-02"
    }
  }
}
```

### تحليلات المبيعات
```http
GET /api/v1/admin/dashboard/sales-analytics?period=30&group_by=day
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)
- `group_by` (اختياري): التجميع (day, week, month)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "sales_data": [
      {
        "date": "2025-10-02",
        "orders_count": 2,
        "revenue": "122.500"
      }
    ],
    "summary": {
      "total_revenue": 122.5,
      "total_orders": 2,
      "average_order_value": 61.25,
      "period": 30,
      "group_by": "day"
    }
  }
}
```

### تحليلات المنتجات
```http
GET /api/v1/admin/dashboard/product-analytics?period=30&limit=10
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)
- `limit` (اختياري): عدد المنتجات (افتراضي: 10)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "top_products": [
      {
        "id": 2,
        "title": "سيروم الريتينول المضاد للشيخوخة",
        "slug": "anti-aging-retinol-serum",
        "total_quantity": "2",
        "total_revenue": "71.500",
        "orders_count": 2
      }
    ],
    "category_performance": [
      {
        "id": 3,
        "name": "سيروم",
        "products_count": 1,
        "total_quantity": "2",
        "total_revenue": "71.500"
      }
    ],
    "availability_stats": {
      "total_products": 10,
      "available_products": 10,
      "unavailable_products": 0
    }
  }
}
```

### تحليلات الطلبات
```http
GET /api/v1/admin/dashboard/order-analytics?period=30
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "status_distribution": {
      "paid": 2,
      "pending": 1
    },
    "daily_trends": [
      {
        "date": "2025-10-02",
        "orders_count": 3,
        "revenue": "122.500"
      }
    ],
    "avg_processing_hours": 2.5,
    "value_distribution": [
      {
        "range": "25-50",
        "count": 2
      }
    ]
  }
}
```

### تحليلات المدفوعات
```http
GET /api/v1/admin/dashboard/payment-analytics?period=30
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "method_distribution": [
      {
        "payment_method": "vm",
        "count": 2,
        "total_amount": "122.500"
      }
    ],
    "status_distribution": {
      "paid": 2,
      "failed": 0
    },
    "daily_trends": [
      {
        "date": "2025-10-02",
        "payments_count": 2,
        "total_amount": "122.500"
      }
    ],
    "success_rate": 100.0,
    "total_payments": 2,
    "successful_payments": 2
  }
}
```

### تحليلات العملاء
```http
GET /api/v1/admin/dashboard/customer-analytics?period=30
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "top_customers": [
      {
        "customer_name": "أحمد محمد",
        "customer_phone": "+96512345678",
        "orders_count": 1,
        "total_spent": "86.750",
        "avg_order_value": "86.750",
        "last_order_date": "2025-10-02T18:57:38.000000Z"
      }
    ],
    "customer_distribution": {
      "عملاء جدد": 3
    }
  }
}
```

### أفضل المنتجات
```http
GET /api/v1/admin/dashboard/top-products?period=30&limit=10&metric=revenue
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)
- `limit` (اختياري): عدد المنتجات (افتراضي: 10)
- `metric` (اختياري): المقياس (revenue, quantity, orders)

### أداء الفئات
```http
GET /api/v1/admin/dashboard/category-performance?period=30
```

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

### الأنشطة الأخيرة
```http
GET /api/v1/admin/dashboard/recent-activities?limit=20
```

**المعاملات:**
- `limit` (اختياري): عدد الأنشطة (افتراضي: 20)

### تكوين الويدجت
```http
GET /api/v1/admin/dashboard/widgets
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "title": "نظرة عامة",
      "type": "stats",
      "position": 1,
      "size": "large",
      "enabled": true
    },
    "sales_chart": {
      "title": "مخطط المبيعات",
      "type": "chart",
      "position": 2,
      "size": "medium",
      "enabled": true
    }
  }
}
```

### تصدير البيانات
```http
POST /api/v1/admin/dashboard/export
```

**المعاملات:**
```json
{
  "type": "sales",
  "period": 30,
  "format": "json"
}
```

**المعاملات:**
- `type`: نوع البيانات (overview, sales, products, orders, payments, customers)
- `period`: عدد الأيام
- `format`: تنسيق التصدير (json, csv, xlsx)

## 🛠️ أوامر CLI

### إحصائيات لوحة التحكم
```bash
php artisan dashboard:stats --period=30 --type=overview
```

**المعاملات:**
- `--period`: عدد الأيام (افتراضي: 30)
- `--type`: نوع الإحصائيات (overview, sales, products, orders, payments, customers)

### تصدير البيانات
```bash
php artisan dashboard:export --type=overview --period=30 --format=json --output=export_file
```

**المعاملات:**
- `--type`: نوع البيانات
- `--period`: عدد الأيام
- `--format`: تنسيق التصدير (json, csv, xlsx)
- `--output`: اسم الملف (اختياري)

## 📊 أمثلة الاستخدام

### 1. الحصول على نظرة عامة
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/overview" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. تحليلات المبيعات الشهرية
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/sales-analytics?period=30&group_by=day" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. أفضل المنتجات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/top-products?period=30&limit=5&metric=revenue" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. تصدير بيانات المبيعات
```bash
curl -X POST "http://localhost:8000/api/v1/admin/dashboard/export" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"type": "sales", "period": 30, "format": "json"}'
```

## 🔧 التخصيص

### إضافة مقاييس جديدة
يمكن إضافة مقاييس جديدة في `AnalyticsService`:

```php
public function getCustomAnalytics(int $period = 30): array
{
    // منطق التحليل المخصص
    return [
        'custom_metric' => $value,
        'period' => $period
    ];
}
```

### إضافة ويدجت جديد
يمكن إضافة ويدجت جديد في `DashboardController`:

```php
public function customWidget()
{
    $data = $this->analyticsService->getCustomAnalytics();
    
    return response()->json([
        'success' => true,
        'data' => $data,
        'message' => 'Custom widget data retrieved successfully'
    ]);
}
```

## 📈 الأداء والتحسين

### نصائح الأداء
1. **استخدام الفهرسة**: تأكد من وجود فهارس على الحقول المستخدمة في الاستعلامات
2. **التخزين المؤقت**: استخدم التخزين المؤقت للبيانات التي لا تتغير كثيراً
3. **الاستعلامات المحسنة**: استخدم `selectRaw` للاستعلامات المعقدة
4. **التجميع**: استخدم التجميع لتقليل عدد الاستعلامات

### مراقبة الأداء
```php
// مراقبة وقت الاستجابة
$startTime = microtime(true);
$data = $this->analyticsService->getSalesAnalytics(30);
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
Log::error('Dashboard analytics error', [
    'error' => $e->getMessage(),
    'period' => $period,
    'type' => $type
]);
```

## 📚 المراجع

- [Laravel Documentation](https://laravel.com/docs)
- [Carbon Documentation](https://carbon.nesbot.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [JWT Documentation](https://jwt.io/)

## 🤝 المساهمة

للمساهمة في تطوير لوحة التحكم:

1. Fork المشروع
2. إنشاء فرع جديد
3. إجراء التغييرات
4. إرسال Pull Request

## 📞 الدعم

للحصول على الدعم:
- إنشاء Issue في GitHub
- مراجعة الوثائق
- التواصل مع فريق التطوير
