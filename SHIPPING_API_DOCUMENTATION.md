# وثائق API مصاريف الشحن

## نظرة عامة
يوفر هذا API إدارة مصاريف الشحن في النظام، حيث يمكن للمديرين تحديث مصاريف الشحن ويمكن للمستخدمين العاديين الاستعلام عنها.



## المصادقة (Authentication)
- **للمستخدمين العاديين**: لا تحتاج مصادقة للاستعلام عن مصاريف الشحن
- **للمديرين**: تحتاج إلى JWT token في header مع middleware `auth:api` و `admin`

### إضافة Token للطلبات الإدارية
```bash
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## Endpoints

### 1. جلب مصاريف الشحن الحالية (للمستخدمين العاديين)

**GET** `/shipping/cost`

#### الوصف
يسترجع مصاريف الشحن النشطة الحالية للمستخدمين العاديين.

#### المصادقة
❌ غير مطلوبة

#### Parameters
لا توجد parameters مطلوبة.

#### مثال على الطلب
```bash
curl -X GET "http://localhost:8001/api/v1/shipping/cost" \
  -H "Accept: application/json"
```

#### مثال على الاستجابة الناجحة
```json
{
  "success": true,
  "data": {
    "shipping_cost": "15.00"
  },
  "message": "تم جلب مصاريف الشحن بنجاح"
}
```

#### حالات الخطأ
```json
{
  "success": false,
  "message": "لا توجد مصاريف شحن نشطة",
  "error": "No active shipping cost found"
}
```

---

### 2. جلب جميع مصاريف الشحن (للمديرين)

**GET** `/admin/shipping/`

#### الوصف
يسترجع جميع مصاريف الشحن المحفوظة في النظام (للمديرين فقط).

#### المصادقة
✅ مطلوبة (Admin JWT Token)

#### Parameters
لا توجد parameters مطلوبة.

#### مثال على الطلب
```bash
curl -X GET "http://localhost:8001/api/v1/admin/shipping/" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### مثال على الاستجابة الناجحة
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "cost": "15.00",
      "is_active": true,
      "created_at": "2025-10-17T17:51:56.000000Z",
      "updated_at": "2025-10-17T17:51:56.000000Z"
    },
    {
      "id": 2,
      "cost": "10.00",
      "is_active": false,
      "created_at": "2025-10-17T16:30:00.000000Z",
      "updated_at": "2025-10-17T17:51:56.000000Z"
    }
  ],
  "message": "تم جلب جميع مصاريف الشحن بنجاح"
}
```

#### حالات الخطأ
```json
{
  "success": false,
  "message": "Unauthenticated. Please log in.",
  "error": "Authentication required"
}
```

---

### 3. جلب مصاريف الشحن النشطة (للمديرين)

**GET** `/admin/shipping/active`

#### الوصف
يسترجع مصاريف الشحن النشطة فقط (للمديرين).

#### المصادقة
✅ مطلوبة (Admin JWT Token)

#### Parameters
لا توجد parameters مطلوبة.

#### مثال على الطلب
```bash
curl -X GET "http://localhost:8001/api/v1/admin/shipping/active" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### مثال على الاستجابة الناجحة
```json
{
  "success": true,
  "data": {
    "id": 1,
    "cost": "15.00",
    "is_active": true,
    "created_at": "2025-10-17T17:51:56.000000Z",
    "updated_at": "2025-10-17T17:51:56.000000Z"
  },
  "message": "تم جلب مصاريف الشحن النشطة بنجاح"
}
```

#### حالات الخطأ
```json
{
  "success": false,
  "message": "لا توجد مصاريف شحن نشطة",
  "error": "No active shipping cost found"
}
```

---

### 4. تحديث مصاريف الشحن (للمديرين)

**PUT** `/admin/shipping/update`

#### الوصف
يحدث مصاريف الشحن النشطة أو ينشئ مصاريف شحن جديدة (للمديرين فقط).

#### المصادقة
✅ مطلوبة (Admin JWT Token)

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cost` | decimal | ✅ | مصاريف الشحن الجديدة (يجب أن تكون أكبر من 0) |

#### مثال على الطلب
```bash
curl -X PUT "http://localhost:8001/api/v1/admin/shipping/update" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cost": 20.50
  }'
```

#### مثال على الاستجابة الناجحة
```json
{
  "success": true,
  "data": {
    "id": 1,
    "cost": "20.50",
    "is_active": true,
    "created_at": "2025-10-17T17:51:56.000000Z",
    "updated_at": "2025-10-17T18:30:00.000000Z"
  },
  "message": "تم تحديث مصاريف الشحن بنجاح"
}
```

#### حالات الخطأ

**خطأ في التحقق من البيانات:**
```json
{
  "success": false,
  "message": "The cost field is required.",
  "errors": {
    "cost": [
      "The cost field is required."
    ]
  }
}
```

**مصاريف شحن غير صالحة:**
```json
{
  "success": false,
  "message": "The cost field must be greater than 0.",
  "errors": {
    "cost": [
      "The cost field must be greater than 0."
    ]
  }
}
```

**عدم وجود مصادقة:**
```json
{
  "success": false,
  "message": "Unauthenticated. Please log in.",
  "error": "Authentication required"
}
```

---

## أمثلة شاملة للاستخدام

### 1. سيناريو المستخدم العادي
```bash
# جلب مصاريف الشحن الحالية
curl -X GET "http://localhost:8001/api/v1/shipping/cost" \
  -H "Accept: application/json"
```

### 2. سيناريو المدير
```bash
# 1. تسجيل الدخول للحصول على token (مثال)
curl -X POST "http://localhost:8001/api/v1/admin/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'

# 2. جلب جميع مصاريف الشحن
curl -X GET "http://localhost:8001/api/v1/admin/shipping/" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# 3. تحديث مصاريف الشحن
curl -X PUT "http://localhost:8001/api/v1/admin/shipping/update" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cost": 25.00
  }'

# 4. التحقق من التحديث
curl -X GET "http://localhost:8001/api/v1/admin/shipping/active" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| `200` | نجح الطلب |
| `400` | خطأ في البيانات المرسلة |
| `401` | غير مصرح له (يحتاج مصادقة) |
| `403` | ممنوع (ليس مدير) |
| `404` | لم يتم العثور على البيانات |
| `422` | خطأ في التحقق من البيانات |
| `500` | خطأ في الخادم |

---

## ملاحظات مهمة

1. **التحقق من البيانات**: جميع البيانات المرسلة يتم التحقق منها قبل المعالجة
2. **الأمان**: endpoints الإدارية محمية بـ JWT authentication و admin middleware
3. **التنسيق**: جميع الاستجابات بتنسيق JSON
4. **العملة**: مصاريف الشحن محفوظة كـ decimal بدقة 8,2 (مثال: 99999999.99)
5. **الحالة النشطة**: يمكن أن يكون هناك مصاريف شحن نشطة واحدة فقط في أي وقت

---

## الملفات ذات الصلة

- **Controller**: `app/Http/Controllers/ShippingController.php`
- **Model**: `app/Models/ShippingCost.php`
- **Migration**: `database/migrations/2025_10_17_175156_add_cost_and_is_active_to_shipping_costs_table.php`
- **Routes**: `routes/api.php`
- **Test File**: `test_shipping_api.php`

---

## الدعم والمساعدة

إذا واجهت أي مشاكل في استخدام API، تأكد من:
1. صحة الـ JWT token للطلبات الإدارية
2. تنسيق البيانات المرسلة (JSON)
3. استخدام الـ HTTP methods الصحيحة
4. إضافة Headers المطلوبة (`Accept: application/json`)