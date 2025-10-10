# 🎫 API التحقق من صحة كود الخصم

## 📋 نظرة عامة

هذا API يسمح للعملاء بالتحقق من صحة كود الخصم قبل إنشاء الطلب. يوفر معلومات مفصلة عن الكود والمبلغ المحسوب بعد تطبيق الخصم.

## 🔗 Endpoint

```http
POST /api/v1/checkout/validate-discount
```

## 📝 المعاملات المطلوبة

| المعامل | النوع | مطلوب | الوصف |
|---------|-------|--------|--------|
| `discount_code` | string | ✅ | كود الخصم المراد التحقق منه |
| `items` | array | ✅ | قائمة المنتجات في الطلب |
| `items[].product_id` | integer | ✅ | معرف المنتج |
| `items[].quantity` | integer | ✅ | الكمية (1-10) |
| `customer_phone` | string | ❌ | رقم هاتف العميل (للتحقق من شروط العميل) |
| `shipping_amount` | number | ❌ | مبلغ الشحن (افتراضي: 0) |

## 📤 مثال الطلب

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

## 📥 مثال الاستجابة الناجحة

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

## ❌ مثال الاستجابة الفاشلة

```json
{
  "success": false,
  "message": "الحد الأدنى للطلب هو 100.000 د.ك",
  "error_code": "MINIMUM_ORDER_NOT_MET"
}
```

## 🔍 أكواد الأخطاء

| الكود | المعنى | الوصف |
|-------|--------|--------|
| `CODE_NOT_FOUND` | كود غير موجود | كود الخصم غير موجود في النظام |
| `CODE_INACTIVE` | كود غير نشط | كود الخصم غير مفعل |
| `CODE_EXPIRED` | كود منتهي | كود الخصم منتهي الصلاحية |
| `USAGE_LIMIT_REACHED` | وصل للحد الأقصى | تم استخدام الكود بالكامل |
| `MINIMUM_ORDER_NOT_MET` | لم يصل للحد الأدنى | مبلغ الطلب أقل من المطلوب |
| `CUSTOMER_NOT_ELIGIBLE` | العميل غير مؤهل | العميل لا يمكنه استخدام الكود |
| `FIRST_TIME_ONLY` | للعملاء الجدد فقط | الكود للعملاء الجدد فقط |
| `NEW_CUSTOMER_ONLY` | للعملاء الجدد فقط | الكود للعملاء الجدد فقط |

## 🎯 الميزات

### ✅ التحقق الشامل
- صحة كود الخصم
- تاريخ الصلاحية
- حدود الاستخدام
- شروط العميل
- الحد الأدنى للطلب

### 📊 معلومات مفصلة
- تفاصيل كود الخصم
- ملخص الطلب مع الخصم
- قائمة المنتجات
- حساب دقيق للمبالغ

### 🔄 دعم جميع أنواع الخصومات
- **نسبة مئوية**: خصم بنسبة من المجموع
- **مبلغ ثابت**: خصم بمبلغ محدد
- **شحن مجاني**: إلغاء رسوم الشحن

## 🚀 أمثلة الاستخدام

### 1. التحقق من كود خصم بسيط
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_code": "WELCOME10",
    "items": [{"product_id": 1, "quantity": 2}]
  }'
```

### 2. التحقق مع معلومات العميل
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_code": "VIP30",
    "items": [{"product_id": 1, "quantity": 3}],
    "customer_phone": "+96512345678",
    "shipping_amount": 5
  }'
```

### 3. التحقق من كود شحن مجاني
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_code": "FREESHIP",
    "items": [{"product_id": 1, "quantity": 1}],
    "shipping_amount": 10
  }'
```

## 🔧 التكامل مع APIs أخرى

### مع حساب المجموع
```bash
# 1. حساب المجموع أولاً
TOTAL_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/calculate-total" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"product_id":1,"quantity":3}]}')

# 2. التحقق من كود الخصم
DISCOUNT_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{"discount_code":"SAVE20","items":[{"product_id":1,"quantity":3}]}')
```

### مع إنشاء الطلب
```bash
# 1. التحقق من كود الخصم
VALIDATE_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/validate-discount" \
  -H "Content-Type: application/json" \
  -d '{"discount_code":"SAVE20","items":[{"product_id":1,"quantity":3}]}')

# 2. إنشاء الطلب مع كود الخصم
ORDER_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "+96512345678",
    "shipping_address": {"street": "شارع الخليج", "city": "الكويت", "governorate": "الكويت"},
    "items": [{"product_id": 1, "quantity": 3}],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }')
```

## 📊 معدلات الاستخدام

### الحدود
- **معدل الطلبات**: 1000 طلب/ساعة لكل IP
- **حجم الطلب**: 10MB كحد أقصى
- **مهلة الاستجابة**: 30 ثانية

### الأداء
- **متوسط الاستجابة**: < 500ms
- **التحقق من الكود**: < 200ms
- **حساب المبالغ**: < 100ms

## 🐛 استكشاف الأخطاء

### أخطاء شائعة
1. **خطأ التحقق من البيانات**
   - تأكد من إرسال جميع الحقول المطلوبة
   - تحقق من صحة معرفات المنتجات

2. **خطأ كود الخصم**
   - تأكد من صحة الكود
   - تحقق من أن الكود نشط وغير منتهي

3. **خطأ الحد الأدنى**
   - زد كمية المنتجات أو أضف منتجات أخرى
   - تحقق من الحد الأدنى المطلوب

## 📞 الدعم

### للمساعدة
- **البريد الإلكتروني**: support@soapyshop.com
- **الهاتف**: +965 1234 5678
- **ساعات العمل**: 9:00 ص - 6:00 م (بتوقيت الكويت)

---

**تم تطوير API التحقق من صحة كود الخصم بواسطة فريق Soapy Shop** 🧼✨

*آخر تحديث: 3 أكتوبر 2025*

