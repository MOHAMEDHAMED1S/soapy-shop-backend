# 📚 وثائق API - Soapy Shop

## 📋 نظرة عامة

هذه الوثائق تغطي جميع APIs المتاحة في نظام Soapy Shop للتجارة الإلكترونية. النظام يدعم إدارة المنتجات، الطلبات، المدفوعات، العملاء، وأكواد الخصم.

**Base URL:** `http://localhost:8000/api/v1`

---

## 🔐 المصادقة والتفويض

### JWT Authentication
جميع APIs المدير تتطلب مصادقة JWT. احصل على token من خلال:

```http
POST /api/v1/admin/login
```

**Headers المطلوبة:**
```http
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## 🛍️ APIs العملاء (Public APIs)

### 📦 المنتجات

#### 1. جلب جميع المنتجات
```http
GET /api/v1/products
```

**Query Parameters:**
- `page` (optional): رقم الصفحة (افتراضي: 1)
- `per_page` (optional): عدد العناصر في الصفحة (افتراضي: 15)
- `category` (optional): فلترة حسب الفئة (slug) - يدعم فئات متعددة مفصولة بفاصلة
- `category_id` (optional): فلترة حسب ID الفئة
- `search` (optional): البحث في المنتجات (العنوان، الوصف، الوصف المختصر)
- `min_price` (optional): الحد الأدنى للسعر
- `max_price` (optional): الحد الأقصى للسعر
- `is_available` (optional): فلترة حسب التوفر (افتراضي: true)
- `sort_by` (optional): ترتيب حسب (created_at, updated_at, price, title, stock_quantity)
- `sort_direction` (optional): اتجاه الترتيب (asc, desc) - افتراضي: desc
- `sort_by_price` (optional): ترتيب حسب السعر (asc, desc) - للتوافق مع الإصدارات القديمة

**أمثلة الطلبات:**

1. **فلترة حسب فئة واحدة:**
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=lipstick&page=1&per_page=12" \
  -H "Accept: application/json"
```

2. **فلترة حسب فئات متعددة:**
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=lipstick,skincare&page=1&per_page=12" \
  -H "Accept: application/json"
```

3. **فلترة حسب نطاق السعر:**
```bash
curl -X GET "http://localhost:8000/api/v1/products?min_price=10&max_price=20&page=1&per_page=12" \
  -H "Accept: application/json"
```

4. **البحث والترتيب:**
```bash
curl -X GET "http://localhost:8000/api/v1/products?search=كريم&sort_by=price&sort_direction=asc&page=1&per_page=12" \
  -H "Accept: application/json"
```

5. **جميع المنتجات (افتراضي):**
```bash
curl -X GET "http://localhost:8000/api/v1/products?page=1&per_page=10" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "كريم مرطب للوجه بفيتامين C",
        "slug": "vitamin-c-face-moisturizer",
        "description": "كريم مرطب غني بفيتامين C يساعد على تجديد خلايا البشرة وإشراقها...",
        "short_description": "كريم مرطب بفيتامين C للإشراق والترطيب العميق",
        "price": "25.500",
        "currency": "KWD",
        "is_available": true,
        "images": [
          "https://picsum.photos/400/400?random=15"
        ],
        "category": {
          "id": 2,
          "name": "كريمات الوجه",
          "slug": "face-creams"
        },
        "created_at": "2025-10-02T18:50:54.000000Z"
      }
    ],
    "total": 50,
    "per_page": 10,
    "last_page": 5
  },
  "message": "Products retrieved successfully"
}
```

#### 2. جلب المنتجات المميزة
```http
GET /api/v1/products/featured
```

#### 3. جلب منتج محدد
```http
GET /api/v1/products/{slug}
```

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/products/vitamin-c-face-moisturizer" \
  -H "Accept: application/json"
```

#### 4. جلب منتجات فئة معينة
```http
GET /api/v1/categories/{categorySlug}/products
```

### 🏷️ الفئات

#### 1. جلب جميع الفئات
```http
GET /api/v1/categories
```

#### 2. جلب شجرة الفئات
```http
GET /api/v1/categories/tree
```

#### 3. جلب فئة محددة
```http
GET /api/v1/categories/{slug}
```

### 🛒 الطلبات والدفع

#### 1. إنشاء طلب جديد
```http
POST /api/v1/checkout/create-order
```

**مثال الطلب:**
```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "+96512345678",
  "customer_email": "ahmed@example.com",
  "shipping_address": {
    "street": "شارع الخليج العربي",
    "city": "الكويت",
    "governorate": "الكويت",
    "postal_code": "12345",
    "notes": "بجانب المدرسة"
  },
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "discount_code": "SAVE20",
  "shipping_amount": 5,
  "notes": "طلب عاجل"
}
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 4,
      "order_number": "ORD-20251003-8EE77A",
      "customer_id": 1,
      "customer_name": "أحمد محمد",
      "customer_phone": "+96512345678",
      "customer_email": "ahmed@example.com",
      "shipping_address": {
        "street": "شارع الخليج العربي",
        "city": "الكويت",
        "governorate": "الكويت",
        "postal_code": "12345",
        "notes": "بجانب المدرسة"
      },
      "total_amount": "107.000",
      "currency": "KWD",
      "status": "pending",
      "discount_code": "SAVE20",
      "discount_amount": "25.500",
      "subtotal_amount": "127.500",
      "shipping_amount": "5.000",
      "free_shipping": false,
      "created_at": "2025-10-03T06:30:01.000000Z"
    },
    "subtotal_amount": 127.5,
    "discount_amount": 25.5,
    "shipping_amount": 5,
    "total_amount": 107,
    "currency": "KWD",
    "discount_code": "SAVE20",
    "free_shipping": false,
    "next_step": "payment_required"
  },
  "message": "Order created successfully. Proceed to payment."
}
```

#### 2. حساب مجموع الطلب
```http
POST /api/v1/checkout/calculate-total
```

**مثال الطلب:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "discount_code": "SAVE20",
  "shipping_amount": 5
}
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "product": {
          "id": 1,
          "title": "كريم مرطب للوجه بفيتامين C",
          "price": "25.500",
          "currency": "KWD"
        },
        "quantity": 2,
        "item_total": 51
      }
    ],
    "subtotal_amount": 51,
    "discount_amount": 10.2,
    "shipping_amount": 5,
    "total_amount": 45.8,
    "currency": "KWD",
    "discount_code": "SAVE20",
    "free_shipping": false
  },
  "message": "Order total calculated successfully"
}
```

#### 3. التحقق من صحة كود الخصم
```http
POST /api/v1/checkout/validate-discount
```

**مثال الطلب:**
```json
{
  "discount_code": "SAVE20",
  "items": [
    {
      "product_id": 1,
      "quantity": 3
    }
  ],
  "customer_phone": "+96512345678",
  "shipping_amount": 5
}
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "discount_code": {
      "code": "SAVE20",
      "name": "وفر 20%",
      "description": "خصم 20% على جميع المنتجات",
      "type": "percentage",
      "value": "20.000",
      "minimum_order_amount": "100.000",
      "maximum_discount_amount": "50.000",
      "expires_at": "2025-10-18T06:22:35.000000Z",
      "usage_count": 1,
      "usage_limit": 50,
      "remaining_usage": 49
    },
    "order_summary": {
      "subtotal_amount": 127.5,
      "discount_amount": 25.5,
      "shipping_amount": 5,
      "total_amount": 107,
      "currency": "KWD",
      "free_shipping": false
    },
    "items": [
      {
        "product": {
          "id": 1,
          "title": "كريم مرطب للوجه بفيتامين C",
          "price": "25.500",
          "currency": "KWD"
        },
        "quantity": 3,
        "item_total": 76.5
      }
    ]
  },
  "message": "كود الخصم صالح ويمكن استخدامه"
}
```

#### 3. جلب تفاصيل الطلب
```http
GET /api/v1/orders/{orderNumber}
```

**Query Parameters:**
- `phone` (required): رقم هاتف العميل للتحقق

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/orders/ORD-20251003-8EE77A?phone=+96512345678" \
  -H "Accept: application/json"
```

#### 4. إلغاء الطلب
```http
POST /api/v1/orders/{orderNumber}/cancel
```

**مثال الطلب:**
```json
{
  "phone": "+96512345678"
}
```

#### 5. تطبيق كود خصم على طلب موجود
```http
POST /api/v1/orders/{orderNumber}/apply-discount
```

**مثال الطلب:**
```json
{
  "phone": "+96512345678",
  "discount_code": "SAVE20"
}
```

### 💳 المدفوعات

#### 1. جلب طرق الدفع المتاحة
```http
GET /api/v1/payments/methods
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": [
    {
      "PaymentMethodId": 1,
      "PaymentMethodAr": "كي نت",
      "PaymentMethodEn": "KNET",
      "PaymentMethodCode": "kn",
      "IsDirectPayment": false,
      "ServiceCharge": 1.01,
      "TotalAmount": 1,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/kn.png",
      "IsEmbeddedSupported": false,
      "PaymentCurrencyIso": "KWD"
    },
    {
      "PaymentMethodId": 2,
      "PaymentMethodAr": "فيزا / ماستر",
      "PaymentMethodEn": "VISA/MASTER",
      "PaymentMethodCode": "vm",
      "IsDirectPayment": false,
      "ServiceCharge": 0.101,
      "TotalAmount": 1,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/vm.png",
      "IsEmbeddedSupported": true,
      "PaymentCurrencyIso": "KWD"
    }
  ],
  "message": "Payment methods retrieved successfully"
}
```

#### 2. بدء عملية الدفع
```http
POST /api/v1/payments/initiate
```

**مثال الطلب:**
```json
{
  "order_id": 11,
  "payment_method": "kn",
  "customer_ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
}
```

**المعاملات المطلوبة:**
- `order_id` (required): معرف الطلب في قاعدة البيانات
- `payment_method` (required): طريقة الدفع (kn, vm, ae, md, ap, stc, uaecc, gp, b)
- `customer_ip` (required): عنوان IP الخاص بالعميل
- `user_agent` (optional): معلومات المتصفح

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "payment_id": 9,
    "invoice_id": 6142718,
    "payment_url": "https://demo.MyFatoorah.com/KWT/ia/01072614271841-6c76090a",
    "order_id": 16,
    "order_number": "ORD-20251004-CE9E92",
    "amount": "51.000",
    "currency": "KWD",
    "redirect_url": "https://demo.MyFatoorah.com/KWT/ia/01072614271841-6c76090a"
  },
  "message": "Payment initiated successfully. Redirect to payment URL."
}
```

**ملاحظة مهمة:** 
- `payment_url` و `redirect_url` يحتويان على رابط الدفع الفعلي من MyFatoorah
- يجب توجيه العميل إلى هذا الرابط لإتمام عملية الدفع
- بعد إتمام الدفع، سيتم توجيه العميل إلى `CallBackUrl` أو `ErrorUrl`

#### 3. التحقق من حالة الدفع
```http
GET /api/v1/payments/status
```

**Query Parameters:**
- `order_id` (required): معرف الطلب في قاعدة البيانات

**مثال الطلب:**
```bash
GET /api/v1/payments/status?order_id=11
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "awaiting_payment",
    "payment_status": "initiated",
    "amount": "51.000",
    "currency": "KWD",
    "payment_method": "kn",
    "invoice_id": "1"
  },
  "message": "Payment status retrieved successfully"
}
```

#### 4. معالجة استجابة الدفع (Callback)
```http
POST /api/v1/payments/callback
```

**مثال الطلب:**
```json
{
  "paymentId": "123456789",
  "order_id": 11
}
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "paid",
    "payment_status": "Paid",
    "amount": "51.000",
    "currency": "KWD"
  },
  "message": "Payment processed successfully"
}

### 🎫 أكواد الخصم

#### 1. جلب أكواد الخصم المتاحة
```http
GET /api/v1/discount-codes
```

**Query Parameters:**
- `type` (optional): نوع الخصم (percentage, fixed_amount, free_shipping)
- `limit` (optional): عدد النتائج (افتراضي: 10)

#### 2. جلب تفاصيل كود خصم محدد
```http
GET /api/v1/discount-codes/{code}
```

#### 3. التحقق من صحة كود الخصم
```http
POST /api/v1/discount-codes/validate
```

**مثال الطلب:**
```json
{
  "code": "SAVE20",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "customer_phone": "+96512345678"
}
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "discount_code": {
    "id": 2,
    "code": "SAVE20",
    "name": "وفر 20%",
    "type": "percentage",
    "value": "20.000"
  },
  "discount_amount": 10.2,
  "order_amount_before_discount": 51,
  "order_amount_after_discount": 40.8,
  "message": "تم تطبيق كود الخصم بنجاح"
}
```

---

## 👨‍💼 APIs المدير (Admin APIs)

### 🔐 المصادقة

#### تسجيل دخول المدير
```http
POST /api/v1/admin/login
```

**مثال الطلب:**
```json
{
  "email": "admin@soapyshop.com",
  "password": "admin123"
}
```

**مثال الاستجابة:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@soapyshop.com",
    "role": "admin"
  }
}
```

#### جلب معلومات المدير الحالي
```http
GET /api/v1/admin/me
```

**Headers المطلوبة:**
```http
Authorization: Bearer {your_jwt_token}
```

### 📊 لوحة التحكم

#### 1. نظرة عامة
```http
GET /api/v1/admin/dashboard/overview
```

#### 2. تحليلات المبيعات
```http
GET /api/v1/admin/dashboard/sales-analytics
```

**Query Parameters:**
- `period` (optional): الفترة بالأيام (افتراضي: 30)
- `start_date` (optional): تاريخ البداية
- `end_date` (optional): تاريخ النهاية

#### 3. تحليلات المنتجات
```http
GET /api/v1/admin/dashboard/product-analytics
```

#### 4. تحليلات الطلبات
```http
GET /api/v1/admin/dashboard/order-analytics
```

#### 5. تحليلات المدفوعات
```http
GET /api/v1/admin/dashboard/payment-analytics
```

#### 6. الأنشطة الأخيرة
```http
GET /api/v1/admin/dashboard/recent-activities
```

#### 7. أفضل المنتجات
```http
GET /api/v1/admin/dashboard/top-products
```

#### 8. أداء الفئات
```http
GET /api/v1/admin/dashboard/category-performance
```

#### 9. تحليلات العملاء
```http
GET /api/v1/admin/dashboard/customer-analytics
```

#### 10. الويدجت
```http
GET /api/v1/admin/dashboard/widgets
```

#### 11. تصدير البيانات
```http
POST /api/v1/admin/dashboard/export
```

**مثال الطلب:**
```json
{
  "type": "sales",
  "period": 30,
  "format": "excel"
}
```

### 📦 إدارة المنتجات

#### 1. جلب جميع المنتجات
```http
GET /api/v1/admin/products
```

**Query Parameters:**
- `page` (optional): رقم الصفحة
- `per_page` (optional): عدد العناصر
- `search` (optional): البحث
- `category` (optional): فلترة حسب الفئة
- `status` (optional): حالة التوفر

#### 2. إنشاء منتج جديد
```http
POST /api/v1/admin/products
```

**مثال الطلب:**
```json
{
  "title": "كريم مرطب جديد",
  "slug": "new-moisturizer",
  "description": "وصف المنتج",
  "short_description": "وصف مختصر",
  "price": 25.500,
  "currency": "KWD",
  "is_available": true,
  "category_id": 1,
  "images": [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg"
  ],
  "meta": {
    "brand": "Soapy Shop",
    "size": "50ml",
    "ingredients": ["مكون 1", "مكون 2"]
  }
}
```

#### 3. جلب منتج محدد
```http
GET /api/v1/admin/products/{id}
```

#### 4. تحديث منتج
```http
PUT /api/v1/admin/products/{id}
```

#### 5. حذف منتج
```http
DELETE /api/v1/admin/products/{id}
```

#### 6. تبديل حالة التوفر
```http
PATCH /api/v1/admin/products/{id}/toggle-availability
```

### 🏷️ إدارة الفئات

#### 1. جلب جميع الفئات
```http
GET /api/v1/admin/categories
```

#### 2. إنشاء فئة جديدة
```http
POST /api/v1/admin/categories
```

**مثال الطلب:**
```json
{
  "name": "كريمات الوجه",
  "slug": "face-creams",
  "image": "https://example.com/category.jpg",
  "parent_id": null
}
```

#### 3. تحديث فئة
```http
PUT /api/v1/admin/categories/{id}
```

#### 4. حذف فئة
```http
DELETE /api/v1/admin/categories/{id}
```

### 🛒 إدارة الطلبات

#### 1. جلب جميع الطلبات
```http
GET /api/v1/admin/orders
```

**Query Parameters:**
- `page` (optional): رقم الصفحة
- `status` (optional): حالة الطلب
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية
- `search` (optional): البحث

#### 2. إحصائيات الطلبات
```http
GET /api/v1/admin/orders/statistics
```

#### 3. تصدير الطلبات
```http
GET /api/v1/admin/orders/export
```

#### 4. جلب طلب محدد
```http
GET /api/v1/admin/orders/{id}
```

#### 5. تحديث حالة الطلب
```http
PUT /api/v1/admin/orders/{id}/update-status
```

**مثال الطلب:**
```json
{
  "status": "shipped",
  "tracking_number": "TRK123456789",
  "admin_notes": "تم الشحن"
}
```

### 🛒 إدارة الطلبات المتقدمة

#### 1. جلب الطلبات مع فلاتر متقدمة
```http
GET /api/v1/admin/order-management
```

#### 2. إحصائيات متقدمة
```http
GET /api/v1/admin/order-management/statistics
```

#### 3. البحث في الطلبات
```http
GET /api/v1/admin/order-management/search
```

#### 4. جلب طلب مع التفاصيل الكاملة
```http
GET /api/v1/admin/order-management/{id}
```

#### 5. جلب خط زمني للطلب
```http
GET /api/v1/admin/order-management/{id}/timeline
```

#### 6. تحديث حالة الطلب
```http
PUT /api/v1/admin/order-management/{id}/update-status
```

#### 7. تحديث حالة متعدد
```http
POST /api/v1/admin/order-management/bulk-update-status
```

**مثال الطلب:**
```json
{
  "order_ids": [1, 2, 3],
  "status": "shipped",
  "admin_notes": "تم شحن الطلبات"
}
```

### 💳 إدارة المدفوعات

#### 1. جلب جميع المدفوعات
```http
GET /api/v1/admin/payments
```

#### 2. إحصائيات المدفوعات
```http
GET /api/v1/admin/payments/statistics
```

#### 3. جلب تفاصيل دفع
```http
GET /api/v1/admin/payments/{id}
```

#### 4. إعادة محاولة الدفع
```http
POST /api/v1/admin/payments/{id}/retry
```

#### 5. سجلات Webhook
```http
GET /api/v1/admin/webhook-logs
```

#### 6. سجل Webhook محدد
```http
GET /api/v1/admin/webhook-logs/{id}
```

### 👥 إدارة العملاء

#### 1. جلب جميع العملاء
```http
GET /api/v1/admin/customers
```

**Query Parameters:**
- `page` (optional): رقم الصفحة
- `status` (optional): حالة العميل (active, inactive, vip, new)
- `search` (optional): البحث

#### 2. تحليلات العملاء
```http
GET /api/v1/admin/customers/analytics
```

#### 3. البحث في العملاء
```http
GET /api/v1/admin/customers/search
```

#### 4. هجرة الطلبات للعملاء
```http
POST /api/v1/admin/customers/migrate-orders
```

**مثال الطلب:**
```json
{
  "dry_run": true
}
```

#### 5. جلب عميل محدد
```http
GET /api/v1/admin/customers/{id}
```

#### 6. تحديث بيانات العميل
```http
PUT /api/v1/admin/customers/{id}
```

**مثال الطلب:**
```json
{
  "name": "أحمد محمد",
  "phone": "+96512345678",
  "email": "ahmed@example.com",
  "is_active": true,
  "notes": "عميل مميز"
}
```

#### 7. إلغاء تفعيل العميل
```http
PUT /api/v1/admin/customers/{id}/deactivate
```

#### 8. جلب طلبات العميل
```http
GET /api/v1/admin/customers/{id}/orders
```

### 🎫 إدارة أكواد الخصم

#### 1. جلب جميع أكواد الخصم
```http
GET /api/v1/admin/discount-codes
```

**Query Parameters:**
- `page` (optional): رقم الصفحة
- `status` (optional): حالة الكود (active, expired, used, unused)
- `type` (optional): نوع الخصم
- `search` (optional): البحث

#### 2. إنشاء كود خصم جديد
```http
POST /api/v1/admin/discount-codes
```

**مثال الطلب:**
```json
{
  "code": "NEW20",
  "name": "خصم جديد 20%",
  "description": "خصم خاص للعملاء الجدد",
  "type": "percentage",
  "value": 20,
  "minimum_order_amount": 50,
  "maximum_discount_amount": 25,
  "usage_limit": 100,
  "usage_limit_per_customer": 1,
  "is_active": true,
  "starts_at": "2025-10-03 00:00:00",
  "expires_at": "2025-12-31 23:59:59",
  "first_time_customer_only": true,
  "admin_notes": "كود ترحيبي"
}
```

#### 3. إحصائيات أكواد الخصم
```http
GET /api/v1/admin/discount-codes/statistics
```

#### 4. جلب كود خصم محدد
```http
GET /api/v1/admin/discount-codes/{id}
```

#### 5. تحديث كود خصم
```http
PUT /api/v1/admin/discount-codes/{id}
```

#### 6. حذف كود خصم
```http
DELETE /api/v1/admin/discount-codes/{id}
```

#### 7. تبديل حالة الكود
```http
PUT /api/v1/admin/discount-codes/{id}/toggle-status
```

#### 8. تاريخ استخدام الكود
```http
GET /api/v1/admin/discount-codes/{id}/usage-history
```

#### 9. نسخ كود خصم
```http
POST /api/v1/admin/discount-codes/{id}/duplicate
```

### 🔔 إدارة الإشعارات

#### 1. جلب جميع الإشعارات
```http
GET /api/v1/admin/notifications
```

#### 2. إحصائيات الإشعارات
```http
GET /api/v1/admin/notifications/statistics
```

#### 3. إنشاء إشعار تجريبي
```http
POST /api/v1/admin/notifications/test
```

#### 4. جلب تفضيلات الإشعارات
```http
GET /api/v1/admin/notifications/preferences
```

#### 5. تحديث تفضيلات الإشعارات
```http
PUT /api/v1/admin/notifications/preferences
```

#### 6. تحديد جميع الإشعارات كمقروءة
```http
PUT /api/v1/admin/notifications/mark-all-read
```

#### 7. حذف الإشعارات المقروءة
```http
DELETE /api/v1/admin/notifications/delete-read
```

#### 8. جلب إشعار محدد
```http
GET /api/v1/admin/notifications/{id}
```

#### 9. تحديد إشعار كمقروء
```http
PUT /api/v1/admin/notifications/{id}/read
```

#### 10. تحديد إشعار كغير مقروء
```http
PUT /api/v1/admin/notifications/{id}/unread
```

#### 11. حذف إشعار
```http
DELETE /api/v1/admin/notifications/{id}
```

### 🖼️ إدارة الصور

#### 1. جلب جميع الصور
```http
GET /api/v1/admin/images
```

#### 2. جلب المجلدات
```http
GET /api/v1/admin/images/folders
```

#### 3. إحصائيات الصور
```http
GET /api/v1/admin/images/statistics
```

#### 4. رفع صورة واحدة
```http
POST /api/v1/admin/images/upload
```

**Content-Type:** `multipart/form-data`

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/images/upload" \
  -H "Authorization: Bearer {token}" \
  -F "image=@/path/to/image.jpg" \
  -F "folder=products"
```

#### 5. رفع عدة صور
```http
POST /api/v1/admin/images/upload-multiple
```

#### 6. إنشاء مجلد
```http
POST /api/v1/admin/images/folders
```

**مثال الطلب:**
```json
{
  "folder_name": "products",
  "description": "صور المنتجات"
}
```

#### 7. جلب تفاصيل صورة
```http
GET /api/v1/admin/images/{path}
```

#### 8. عرض صورة
```http
GET /api/v1/admin/images/{path}/serve
```

#### 9. تغيير حجم صورة
```http
POST /api/v1/admin/images/{path}/resize
```

**مثال الطلب:**
```json
{
  "width": 800,
  "height": 600,
  "quality": 90
}
```

#### 10. حذف صورة
```http
DELETE /api/v1/admin/images/{path}
```

#### 11. حذف مجلد
```http
DELETE /api/v1/admin/images/folders/{folderName}
```

### 🔗 إدارة Webhooks

#### 1. سجلات Webhook
```http
GET /api/v1/admin/webhooks/logs
```

#### 2. إحصائيات Webhook
```http
GET /api/v1/admin/webhooks/statistics
```

#### 3. إعادة محاولة Webhook
```http
POST /api/v1/admin/webhooks/{id}/retry
```

---

## 📝 أكواد الأخطاء والرسائل

### أكواد الأخطاء الشائعة

| الكود | المعنى | الوصف |
|-------|--------|--------|
| `VALIDATION_ERROR` | خطأ في التحقق | البيانات المرسلة غير صحيحة |
| `UNAUTHORIZED` | غير مصرح | مطلوب مصادقة |
| `FORBIDDEN` | ممنوع | لا توجد صلاحية للوصول |
| `NOT_FOUND` | غير موجود | المورد المطلوب غير موجود |
| `INVALID_CODE` | كود غير صحيح | كود الخصم غير صحيح |
| `EXPIRED` | منتهي الصلاحية | كود الخصم منتهي |
| `USAGE_LIMIT_REACHED` | وصل للحد الأقصى | تم استخدام الكود بالكامل |
| `MINIMUM_ORDER_NOT_MET` | لم يصل للحد الأدنى | مبلغ الطلب أقل من المطلوب |
| `PRODUCTS_NOT_APPLICABLE` | المنتجات غير مناسبة | الكود لا ينطبق على المنتجات |
| `CUSTOMER_NOT_ELIGIBLE` | العميل غير مؤهل | العميل لا يمكنه استخدام الكود |
| `FIRST_TIME_ONLY` | للعملاء الجدد فقط | الكود للعملاء الجدد فقط |
| `NEW_CUSTOMER_ONLY` | للعملاء الجدد فقط | الكود للعملاء الجدد فقط |

### رسائل الأخطاء

#### أخطاء عامة
- `"Validation failed"` - فشل في التحقق من البيانات
- `"Unauthorized"` - غير مصرح بالوصول
- `"Forbidden"` - ممنوع الوصول
- `"Not found"` - المورد غير موجود
- `"Internal server error"` - خطأ في الخادم

#### أخطاء الطلبات
- `"Product not available"` - المنتج غير متوفر
- `"Order not found"` - الطلب غير موجود
- `"Cannot modify order"` - لا يمكن تعديل الطلب
- `"Order already has discount"` - الطلب لديه خصم بالفعل

#### أخطاء أكواد الخصم
- `"كود الخصم غير صحيح"` - الكود غير موجود
- `"كود الخصم منتهي الصلاحية"` - الكود منتهي
- `"تم استخدام كود الخصم بالكامل"` - وصل للحد الأقصى
- `"الحد الأدنى للطلب هو X د.ك"` - لم يصل للحد الأدنى
- `"هذا الكود متاح للعملاء الجدد فقط"` - للعملاء الجدد فقط

---

## 🔧 أمثلة الاستخدام

### مثال كامل: إنشاء طلب مع خصم

```bash
# 1. حساب المجموع مع كود الخصم
curl -X POST "http://localhost:8000/api/v1/checkout/calculate-total" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 3
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }'

# 2. إنشاء الطلب
curl -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "+96512345678",
    "customer_email": "ahmed@example.com",
    "shipping_address": {
      "street": "شارع الخليج العربي",
      "city": "الكويت",
      "governorate": "الكويت",
      "postal_code": "12345"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 3
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }'

# 3. بدء عملية الدفع
curl -X POST "http://localhost:8000/api/v1/payments/initiate" \
  -H "Content-Type: application/json" \
  -d '{
    "order_number": "ORD-20251003-8EE77A",
    "payment_method": "credit_card",
    "amount": 107.000,
    "currency": "KWD",
    "customer_phone": "+96512345678"
  }'
```

### مثال: إدارة أكواد الخصم

```bash
# 1. تسجيل دخول المدير
curl -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "admin123"
  }'

# 2. إنشاء كود خصم جديد
curl -X POST "http://localhost:8000/api/v1/admin/discount-codes" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "WELCOME15",
    "name": "خصم ترحيبي 15%",
    "description": "خصم للعملاء الجدد",
    "type": "percentage",
    "value": 15,
    "minimum_order_amount": 50,
    "usage_limit": 100,
    "first_time_customer_only": true,
    "expires_at": "2025-12-31 23:59:59"
  }'

# 3. جلب إحصائيات أكواد الخصم
curl -X GET "http://localhost:8000/api/v1/admin/discount-codes/statistics" \
  -H "Authorization: Bearer {token}"
```

---

## 📊 معدلات الاستخدام والحدود

### حدود API
- **معدل الطلبات**: 1000 طلب/ساعة لكل IP
- **حجم الطلب**: 10MB كحد أقصى
- **مهلة الاستجابة**: 30 ثانية
- **حجم الصفحة**: 100 عنصر كحد أقصى

### حدود البيانات
- **أكواد الخصم**: 1000 كود نشط
- **الطلبات**: 10000 طلب/شهر
- **العملاء**: 50000 عميل
- **المنتجات**: 10000 منتج

---

## 🔄 التحديثات والإصدارات

### الإصدار الحالي: v1.0.0

#### الميزات المضافة:
- ✅ إدارة المنتجات والفئات
- ✅ نظام الطلبات والدفع
- ✅ إدارة العملاء
- ✅ نظام أكواد الخصم
- ✅ لوحة تحكم المدير
- ✅ إدارة الصور
- ✅ نظام الإشعارات
- ✅ Webhooks

#### التحديثات القادمة:
- 🔄 API للتقارير المتقدمة
- 🔄 نظام الكوبونات
- 🔄 إدارة المخزون
- 🔄 نظام التقييمات
- 🔄 API للهاتف المحمول

---

## 📞 الدعم والمساعدة

### معلومات الاتصال
- **البريد الإلكتروني**: support@soapyshop.com
- **الهاتف**: +965 1234 5678
- **ساعات العمل**: 9:00 ص - 6:00 م (بتوقيت الكويت)

### الموارد الإضافية
- **دليل المطور**: [Developer Guide](docs/developer-guide.md)
- **أمثلة الكود**: [Code Examples](docs/code-examples.md)
- **استكشاف الأخطاء**: [Troubleshooting](docs/troubleshooting.md)

---

**تم تطوير وثائق API بواسطة فريق Soapy Shop** 🧼✨

*آخر تحديث: 3 أكتوبر 2025*
