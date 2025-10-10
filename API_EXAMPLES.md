# 📚 أمثلة API - Soapy Shop

## 🚀 أمثلة سريعة للبدء

### 1. تسجيل دخول المدير
```bash
curl -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "admin123"
  }'
```

### 2. جلب جميع المنتجات
```bash
curl -X GET "http://localhost:8000/api/v1/products" \
  -H "Accept: application/json"
```

### 3. فلترة المنتجات حسب الفئة
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=lipstick&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 4. فلترة المنتجات حسب نطاق السعر
```bash
curl -X GET "http://localhost:8000/api/v1/products?min_price=10&max_price=20&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 5. إنشاء طلب مع خصم
```bash
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
        "quantity": 2
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }'
```

---

## 🔍 أمثلة الفلترة المتقدمة للمنتجات

### 1. فلترة حسب فئة واحدة
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=lipstick&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 2. فلترة حسب فئات متعددة
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=lipstick,skincare&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 3. فلترة حسب نطاق السعر
```bash
curl -X GET "http://localhost:8000/api/v1/products?min_price=10&max_price=20&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 4. البحث في المنتجات
```bash
curl -X GET "http://localhost:8000/api/v1/products?search=كريم&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 5. ترتيب حسب السعر (تصاعدي)
```bash
curl -X GET "http://localhost:8000/api/v1/products?sort_by=price&sort_direction=asc&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 6. ترتيب حسب السعر (تنازلي)
```bash
curl -X GET "http://localhost:8000/api/v1/products?sort_by=price&sort_direction=desc&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 7. فلترة متقدمة (فئة + سعر + بحث)
```bash
curl -X GET "http://localhost:8000/api/v1/products?category=skincare&min_price=15&max_price=30&search=مرطب&sort_by=price&sort_direction=asc&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 8. جلب المنتجات غير المتاحة (للمدير)
```bash
curl -X GET "http://localhost:8000/api/v1/products?is_available=false&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 9. ترتيب حسب تاريخ الإنشاء
```bash
curl -X GET "http://localhost:8000/api/v1/products?sort_by=created_at&sort_direction=desc&page=1&per_page=12" \
  -H "Accept: application/json"
```

### 10. ترتيب حسب العنوان
```bash
curl -X GET "http://localhost:8000/api/v1/products?sort_by=title&sort_direction=asc&page=1&per_page=12" \
  -H "Accept: application/json"
```

---

## 🛍️ أمثلة APIs العملاء

### 📦 المنتجات

#### جلب المنتجات مع فلترة
```bash
# جلب المنتجات المميزة
curl -X GET "http://localhost:8000/api/v1/products/featured" \
  -H "Accept: application/json"

# جلب منتجات فئة معينة
curl -X GET "http://localhost:8000/api/v1/categories/face-creams/products" \
  -H "Accept: application/json"

# البحث في المنتجات
curl -X GET "http://localhost:8000/api/v1/products?search=كريم" \
  -H "Accept: application/json"
```

#### جلب تفاصيل منتج
```bash
curl -X GET "http://localhost:8000/api/v1/products/vitamin-c-face-moisturizer" \
  -H "Accept: application/json"
```

### 🛒 الطلبات

#### حساب مجموع الطلب
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/calculate-total" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 3
      },
      {
        "product_id": 2,
        "quantity": 1
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }'
```

#### التحقق من صحة كود الخصم
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_code": "SAVE20",
    "items": [
      {
        "product_id": 1,
        "quantity": 3
      }
    ],
    "customer_phone": "+96512345678",
    "shipping_amount": 5
  }'
```

#### إنشاء طلب جديد
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "فاطمة أحمد",
    "customer_phone": "+96598765432",
    "customer_email": "fatima@example.com",
    "shipping_address": {
      "street": "شارع السالمية",
      "city": "الكويت",
      "governorate": "الكويت",
      "postal_code": "22000",
      "notes": "الطابق الثاني"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      },
      {
        "product_id": 3,
        "quantity": 1
      }
    ],
    "discount_code": "WELCOME10",
    "shipping_amount": 3,
    "notes": "طلب عاجل"
  }'
```

#### جلب تفاصيل الطلب
```bash
curl -X GET "http://localhost:8000/api/v1/orders/ORD-20251003-8EE77A?phone=+96512345678" \
  -H "Accept: application/json"
```

#### تطبيق كود خصم على طلب موجود
```bash
curl -X POST "http://localhost:8000/api/v1/orders/ORD-20251003-8EE77A/apply-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+96512345678",
    "discount_code": "VIP30"
  }'
```

#### إلغاء الطلب
```bash
curl -X POST "http://localhost:8000/api/v1/orders/ORD-20251003-8EE77A/cancel" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+96512345678"
  }'
```

### 💳 المدفوعات

#### جلب طرق الدفع المتاحة
```bash
curl -X GET "http://localhost:8000/api/v1/payments/methods" \
  -H "Accept: application/json"
```

#### بدء عملية الدفع
```bash
curl -X POST "http://localhost:8000/api/v1/payments/initiate" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 16,
    "payment_method": "kn",
    "customer_ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
  }'
```

**الاستجابة:**
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

**ملاحظة:** يجب توجيه العميل إلى `redirect_url` لإتمام الدفع!

#### التحقق من حالة الدفع
```bash
curl -X GET "http://localhost:8000/api/v1/payments/status?order_id=11" \
  -H "Accept: application/json"
```

#### معالجة استجابة الدفع (Callback)
```bash
curl -X POST "http://localhost:8000/api/v1/payments/callback" \
  -H "Content-Type: application/json" \
  -d '{
    "paymentId": "123456789",
    "order_id": 11
  }'
```

### 🎫 أكواد الخصم

#### جلب أكواد الخصم المتاحة
```bash
curl -X GET "http://localhost:8000/api/v1/discount-codes" \
  -H "Accept: application/json"
```

#### جلب تفاصيل كود خصم
```bash
curl -X GET "http://localhost:8000/api/v1/discount-codes/SAVE20" \
  -H "Accept: application/json"
```

#### التحقق من صحة كود الخصم
```bash
curl -X POST "http://localhost:8000/api/v1/discount-codes/validate" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "SAVE20",
    "order_total": 100,
    "product_ids": [1, 2],
    "category_ids": [1],
    "customer_phone": "+96512345678"
  }'
```

---

## 👨‍💼 أمثلة APIs المدير

### 🔐 المصادقة

#### تسجيل دخول المدير
```bash
curl -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "admin123"
  }'
```

#### جلب معلومات المدير
```bash
curl -X GET "http://localhost:8000/api/v1/admin/me" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 📊 لوحة التحكم

#### نظرة عامة
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/overview" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### تحليلات المبيعات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/sales-analytics?period=30" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### أفضل المنتجات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/top-products" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 📦 إدارة المنتجات

#### جلب جميع المنتجات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### إنشاء منتج جديد
```bash
curl -X POST "http://localhost:8000/api/v1/admin/products" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "صابون طبيعي بالعسل",
    "slug": "natural-honey-soap",
    "description": "صابون طبيعي مصنوع من العسل الخام وزيت الزيتون",
    "short_description": "صابون طبيعي بالعسل للبشرة الحساسة",
    "price": 15.500,
    "currency": "KWD",
    "is_available": true,
    "category_id": 1,
    "images": [
      "https://example.com/honey-soap-1.jpg",
      "https://example.com/honey-soap-2.jpg"
    ],
    "meta": {
      "brand": "Soapy Shop",
      "weight": "100g",
      "ingredients": ["عسل طبيعي", "زيت الزيتون", "جلسرين"],
      "skin_type": "جميع أنواع البشرة"
    }
  }'
```

#### تحديث منتج
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/products/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "كريم مرطب للوجه بفيتامين C - محدث",
    "price": 27.500,
    "is_available": true
  }'
```

#### تبديل حالة التوفر
```bash
curl -X PATCH "http://localhost:8000/api/v1/admin/products/1/toggle-availability" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 🏷️ إدارة الفئات

#### إنشاء فئة جديدة
```bash
curl -X POST "http://localhost:8000/api/v1/admin/categories" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "صابون طبيعي",
    "slug": "natural-soap",
    "image": "https://example.com/natural-soap-category.jpg",
    "parent_id": null
  }'
```

#### إنشاء فئة فرعية
```bash
curl -X POST "http://localhost:8000/api/v1/admin/categories" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "صابون بالعسل",
    "slug": "honey-soap",
    "image": "https://example.com/honey-soap-category.jpg",
    "parent_id": 3
  }'
```

### 🛒 إدارة الطلبات

#### جلب الطلبات مع فلاتر
```bash
# جلب الطلبات المعلقة
curl -X GET "http://localhost:8000/api/v1/admin/orders?status=pending" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"

# جلب الطلبات لشهر معين
curl -X GET "http://localhost:8000/api/v1/admin/orders?date_from=2025-10-01&date_to=2025-10-31" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"

# البحث في الطلبات
curl -X GET "http://localhost:8000/api/v1/admin/orders?search=أحمد" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### تحديث حالة الطلب
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/orders/1/update-status" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "shipped",
    "tracking_number": "TRK123456789",
    "admin_notes": "تم الشحن عبر شركة DHL"
  }'
```

#### تحديث حالة متعدد
```bash
curl -X POST "http://localhost:8000/api/v1/admin/order-management/bulk-update-status" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "order_ids": [1, 2, 3, 4],
    "status": "shipped",
    "admin_notes": "تم شحن جميع الطلبات"
  }'
```

### 👥 إدارة العملاء

#### جلب العملاء مع فلاتر
```bash
# جلب العملاء النشطين
curl -X GET "http://localhost:8000/api/v1/admin/customers?status=active" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"

# البحث في العملاء
curl -X GET "http://localhost:8000/api/v1/admin/customers/search?q=أحمد" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### تحديث بيانات العميل
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/customers/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد السالم",
    "phone": "+96512345678",
    "email": "ahmed.salem@example.com",
    "is_active": true,
    "notes": "عميل مميز - يطلب بانتظام"
  }'
```

#### جلب طلبات العميل
```bash
curl -X GET "http://localhost:8000/api/v1/admin/customers/1/orders" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 🎫 إدارة أكواد الخصم

#### جلب أكواد الخصم
```bash
# جلب الأكواد النشطة
curl -X GET "http://localhost:8000/api/v1/admin/discount-codes?status=active" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"

# جلب أكواد النسبة المئوية
curl -X GET "http://localhost:8000/api/v1/admin/discount-codes?type=percentage" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### إنشاء كود خصم جديد
```bash
curl -X POST "http://localhost:8000/api/v1/admin/discount-codes" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "SUMMER25",
    "name": "خصم الصيف 25%",
    "description": "خصم خاص لفصل الصيف على جميع المنتجات",
    "type": "percentage",
    "value": 25,
    "minimum_order_amount": 75,
    "maximum_discount_amount": 50,
    "usage_limit": 200,
    "usage_limit_per_customer": 2,
    "is_active": true,
    "starts_at": "2025-06-01 00:00:00",
    "expires_at": "2025-08-31 23:59:59",
    "admin_notes": "عرض الصيف السنوي"
  }'
```

#### إنشاء كود خصم للعملاء الجدد فقط
```bash
curl -X POST "http://localhost:8000/api/v1/admin/discount-codes" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "NEWCUSTOMER30",
    "name": "خصم العملاء الجدد 30%",
    "description": "خصم خاص للعملاء الجدد فقط",
    "type": "percentage",
    "value": 30,
    "minimum_order_amount": 40,
    "maximum_discount_amount": 30,
    "usage_limit": 500,
    "usage_limit_per_customer": 1,
    "is_active": true,
    "first_time_customer_only": true,
    "expires_at": "2025-12-31 23:59:59",
    "admin_notes": "كود ترحيبي للعملاء الجدد"
  }'
```

#### إنشاء كود شحن مجاني
```bash
curl -X POST "http://localhost:8000/api/v1/admin/discount-codes" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "FREESHIPPING",
    "name": "شحن مجاني",
    "description": "شحن مجاني على جميع الطلبات",
    "type": "free_shipping",
    "value": 0,
    "minimum_order_amount": 25,
    "usage_limit": 1000,
    "usage_limit_per_customer": 5,
    "is_active": true,
    "expires_at": "2025-12-31 23:59:59",
    "admin_notes": "عرض الشحن المجاني"
  }'
```

#### جلب تاريخ استخدام كود الخصم
```bash
curl -X GET "http://localhost:8000/api/v1/admin/discount-codes/1/usage-history" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### نسخ كود خصم
```bash
curl -X POST "http://localhost:8000/api/v1/admin/discount-codes/1/duplicate" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 🔔 إدارة الإشعارات

#### جلب الإشعارات
```bash
curl -X GET "http://localhost:8000/api/v1/admin/notifications" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### إنشاء إشعار تجريبي
```bash
curl -X POST "http://localhost:8000/api/v1/admin/notifications/test" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "إشعار تجريبي",
    "message": "هذا إشعار تجريبي لاختبار النظام",
    "type": "info"
  }'
```

#### تحديد جميع الإشعارات كمقروءة
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/notifications/mark-all-read" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### 🖼️ إدارة الصور

#### رفع صورة واحدة
```bash
curl -X POST "http://localhost:8000/api/v1/admin/images/upload" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "image=@/path/to/image.jpg" \
  -F "folder=products"
```

#### رفع عدة صور
```bash
curl -X POST "http://localhost:8000/api/v1/admin/images/upload-multiple" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg" \
  -F "folder=products"
```

#### إنشاء مجلد
```bash
curl -X POST "http://localhost:8000/api/v1/admin/images/folders" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "folder_name": "banners",
    "description": "صور البانرات الإعلانية"
  }'
```

#### تغيير حجم صورة
```bash
curl -X POST "http://localhost:8000/api/v1/admin/images/products/image.jpg/resize" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "width": 800,
    "height": 600,
    "quality": 90
  }'
```

---

## 🔧 أمثلة متقدمة

### سيناريو كامل: من إنشاء الطلب إلى الدفع

```bash
# 1. حساب المجموع
TOTAL_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/calculate-total" \
  -H "Content-Type: application/json" \
  -d '{
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
    "shipping_amount": 5
  }')

echo "Total calculation: $TOTAL_RESPONSE"

# 2. إنشاء الطلب
ORDER_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/create-order" \
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
        "quantity": 2
      },
      {
        "product_id": 2,
        "quantity": 1
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }')

echo "Order created: $ORDER_RESPONSE"

# 3. استخراج رقم الطلب
ORDER_NUMBER=$(echo $ORDER_RESPONSE | jq -r '.data.order.order_number')
echo "Order number: $ORDER_NUMBER"

# 4. بدء عملية الدفع
PAYMENT_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/payments/initiate" \
  -H "Content-Type: application/json" \
  -d "{
    \"order_number\": \"$ORDER_NUMBER\",
    \"payment_method\": \"credit_card\",
    \"amount\": 107.000,
    \"currency\": \"KWD\",
    \"customer_phone\": \"+96512345678\"
  }")

echo "Payment initiated: $PAYMENT_RESPONSE"
```

### سيناريو إدارة أكواد الخصم

```bash
# 1. تسجيل دخول المدير
LOGIN_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "admin123"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.access_token')
echo "Token: $TOKEN"

# 2. إنشاء كود خصم جديد
DISCOUNT_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/admin/discount-codes" \
  -H "Authorization: Bearer $TOKEN" \
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
  }')

echo "Discount code created: $DISCOUNT_RESPONSE"

# 3. جلب إحصائيات أكواد الخصم
STATS_RESPONSE=$(curl -s -X GET "http://localhost:8000/api/v1/admin/discount-codes/statistics" \
  -H "Authorization: Bearer $TOKEN")

echo "Discount statistics: $STATS_RESPONSE"
```

---

## 🐛 استكشاف الأخطاء

### أخطاء شائعة وحلولها

#### 1. خطأ المصادقة
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```
**الحل:** تأكد من إرسال token صحيح في header Authorization

#### 2. خطأ التحقق من البيانات
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_phone": ["The customer phone field is required."]
  }
}
```
**الحل:** تأكد من إرسال جميع الحقول المطلوبة

#### 3. خطأ كود الخصم
```json
{
  "success": false,
  "message": "كود الخصم غير صحيح",
  "error_code": "CODE_NOT_FOUND"
}
```
**الحل:** تأكد من صحة كود الخصم أو أنه نشط

#### 4. خطأ المنتج غير متوفر
```json
{
  "success": false,
  "message": "Product ID 1 is not available"
}
```
**الحل:** تأكد من أن المنتج متوفر وموجود

---

## 📊 نصائح الأداء

### 1. استخدام Pagination
```bash
# جلب الصفحة الأولى
curl -X GET "http://localhost:8000/api/v1/products?page=1&per_page=10"

# جلب الصفحة التالية
curl -X GET "http://localhost:8000/api/v1/products?page=2&per_page=10"
```

### 2. استخدام الفلاتر
```bash
# فلترة المنتجات حسب الفئة
curl -X GET "http://localhost:8000/api/v1/products?category=face-creams"

# فلترة الطلبات حسب الحالة
curl -X GET "http://localhost:8000/api/v1/admin/orders?status=pending"
```

### 3. استخدام البحث
```bash
# البحث في المنتجات
curl -X GET "http://localhost:8000/api/v1/products?search=كريم"

# البحث في الطلبات
curl -X GET "http://localhost:8000/api/v1/admin/orders?search=أحمد"
```

---

**تم إنشاء أمثلة API بواسطة فريق Soapy Shop** 🧼✨

*آخر تحديث: 3 أكتوبر 2025*
