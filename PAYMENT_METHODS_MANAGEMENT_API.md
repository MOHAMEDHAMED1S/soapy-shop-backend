# توثيق إدارة طرق الدفع - Payment Methods Management API

## نظرة عامة
يوفر هذا النظام إمكانية إدارة طرق الدفع بشكل ديناميكي، حيث يمكن للمشرفين تفعيل أو إلغاء تفعيل طرق الدفع المختلفة دون الحاجة لتعديل الكود.

## الميزات الرئيسية
- ✅ **التحكم الديناميكي**: تفعيل/إلغاء تفعيل طرق الدفع
- ✅ **السلوك الافتراضي**: جميع طرق الدفع مفعلة افتراضياً
- ✅ **التكامل السلس**: نقطة النهاية الحالية للعملاء تحترم إعدادات المشرف
- ✅ **المزامنة**: مزامنة أسماء طرق الدفع من MyFatoorah
- ✅ **التوافق العكسي**: يحافظ على الوظائف الحالية

---

## 🔐 المصادقة (Authentication)

جميع APIs الخاصة بالمشرفين تتطلب مصادقة JWT. يجب إرسال التوكن في الهيدر:

```http
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 📋 APIs الخاصة بالمشرفين (Admin APIs)

### 1. عرض جميع طرق الدفع مع حالتها
**GET** `/api/v1/admin/payment-methods`

#### الوصف
يعرض جميع طرق الدفع المتاحة من MyFatoorah مع حالة التفعيل لكل منها.

#### مثال على الطلب
```bash
curl -X GET "https://your-domain.com/api/v1/admin/payment-methods" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### مثال على الاستجابة
```json
{
  "success": true,
  "data": [
    {
      "payment_method_code": "ap",
      "payment_method_name_ar": "Apple Pay",
      "payment_method_name_en": "Apple Pay",
      "is_enabled": true,
      "myfatoorah_data": {
        "PaymentMethodId": 20,
        "PaymentMethodCode": "ap",
        "PaymentMethodAr": "Apple Pay",
        "PaymentMethodEn": "Apple Pay",
        "IsDirectPayment": false,
        "ServiceCharge": 0,
        "TotalAmount": 100.000,
        "CurrencyIso": "KWD",
        "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/ap.png"
      }
    },
    {
      "payment_method_code": "md",
      "payment_method_name_ar": "مدى",
      "payment_method_name_en": "MADA",
      "is_enabled": false,
      "myfatoorah_data": {
        "PaymentMethodId": 2,
        "PaymentMethodCode": "md",
        "PaymentMethodAr": "مدى",
        "PaymentMethodEn": "MADA",
        "IsDirectPayment": false,
        "ServiceCharge": 0,
        "TotalAmount": 100.000,
        "CurrencyIso": "KWD",
        "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/md.png"
      }
    }
  ],
  "message": "Payment methods retrieved successfully"
}
```

---

### 2. تبديل حالة طريقة الدفع
**PUT** `/api/v1/admin/payment-methods/{code}/toggle`

#### الوصف
يقوم بتبديل حالة تفعيل طريقة دفع معينة (من مفعل إلى غير مفعل والعكس).

#### المعاملات
- `code` (string): رمز طريقة الدفع (مثل: `md`, `ap`, `stc`)

#### مثال على الطلب
```bash
curl -X PUT "https://your-domain.com/api/v1/admin/payment-methods/md/toggle" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### مثال على الاستجابة
```json
{
  "success": true,
  "data": {
    "payment_method_code": "md",
    "is_enabled": false
  },
  "message": "Payment method disabled successfully"
}
```

---

### 3. مزامنة طرق الدفع من MyFatoorah
**POST** `/api/v1/admin/payment-methods/sync`

#### الوصف
يقوم بمزامنة أسماء طرق الدفع من MyFatoorah دون تغيير حالة التفعيل.

#### مثال على الطلب
```bash
curl -X POST "https://your-domain.com/api/v1/admin/payment-methods/sync" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### مثال على الاستجابة
```json
{
  "success": true,
  "message": "Payment methods synced successfully"
}
```

---

### 4. تحديث إعدادات طريقة الدفع
**PUT** `/api/v1/admin/payment-methods/{code}`

#### الوصف
يقوم بتحديث إعدادات طريقة دفع معينة.

#### المعاملات
- `code` (string): رمز طريقة الدفع

#### البيانات المطلوبة
```json
{
  "is_enabled": true,
  "payment_method_name_ar": "اسم طريقة الدفع بالعربية",
  "payment_method_name_en": "Payment Method Name in English"
}
```

#### مثال على الطلب
```bash
curl -X PUT "https://your-domain.com/api/v1/admin/payment-methods/md" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "is_enabled": true,
    "payment_method_name_ar": "مدى محدث",
    "payment_method_name_en": "Updated MADA"
  }'
```

#### مثال على الاستجابة
```json
{
  "success": true,
  "data": {
    "payment_method_code": "md",
    "payment_method_name_ar": "مدى محدث",
    "payment_method_name_en": "Updated MADA",
    "is_enabled": true
  },
  "message": "Payment method updated successfully"
}
```

---

## 👥 APIs الخاصة بالعملاء (Customer APIs)

### عرض طرق الدفع المتاحة للعملاء
**GET** `/api/v1/payments/methods`

#### الوصف
يعرض طرق الدفع المفعلة فقط للعملاء. هذا الـ API موجود مسبقاً ولكن تم تحديثه ليعرض فقط طرق الدفع المفعلة من قبل المشرف.

#### مثال على الطلب
```bash
curl -X GET "https://your-domain.com/api/v1/payments/methods" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

#### مثال على الاستجابة
```json
{
  "success": true,
  "data": [
    {
      "PaymentMethodId": 20,
      "PaymentMethodCode": "ap",
      "PaymentMethodAr": "Apple Pay",
      "PaymentMethodEn": "Apple Pay",
      "IsDirectPayment": false,
      "ServiceCharge": 0,
      "TotalAmount": 100.000,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/ap.png"
    },
    {
      "PaymentMethodId": 1,
      "PaymentMethodCode": "stc",
      "PaymentMethodAr": "STC Pay",
      "PaymentMethodEn": "STC Pay",
      "IsDirectPayment": false,
      "ServiceCharge": 0,
      "TotalAmount": 100.000,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/stc.png"
    }
  ]
}
```

**ملاحظة**: طرق الدفع غير المفعلة (مثل MADA في هذا المثال) لن تظهر في الاستجابة.

---

## 🔧 رموز طرق الدفع الشائعة

| الرمز | الاسم العربي | الاسم الإنجليزي |
|-------|-------------|-----------------|
| `ap` | Apple Pay | Apple Pay |
| `stc` | STC Pay | STC Pay |
| `md` | مدى | MADA |
| `uaecc` | بطاقة ائتمان الإمارات | UAE Credit Card |
| `ae` | American Express | American Express |
| `gp` | Google Pay | Google Pay |
| `b` | البنك | Bank |
| `kn` | KNET | KNET |
| `vm` | Visa/MasterCard | Visa/MasterCard |

---

## 📊 حالات الاستجابة

### حالات النجاح
- **200 OK**: العملية تمت بنجاح
- **201 Created**: تم إنشاء المورد بنجاح

### حالات الخطأ
- **400 Bad Request**: بيانات الطلب غير صحيحة
- **401 Unauthorized**: غير مصرح بالوصول (مطلوب توكن صحيح)
- **403 Forbidden**: ممنوع الوصول
- **404 Not Found**: المورد غير موجود
- **422 Unprocessable Entity**: بيانات غير صالحة
- **500 Internal Server Error**: خطأ في الخادم

### مثال على استجابة الخطأ
```json
{
  "success": false,
  "message": "Payment method not found",
  "error": "The specified payment method code does not exist"
}
```

---

## 🚀 سيناريوهات الاستخدام

### 1. إعداد طرق الدفع لأول مرة
```bash
# 1. مزامنة طرق الدفع من MyFatoorah
curl -X POST "https://your-domain.com/api/v1/admin/payment-methods/sync" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# 2. عرض جميع طرق الدفع
curl -X GET "https://your-domain.com/api/v1/admin/payment-methods" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# 3. إلغاء تفعيل طريقة دفع معينة
curl -X PUT "https://your-domain.com/api/v1/admin/payment-methods/md/toggle" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 2. التحقق من طرق الدفع المتاحة للعملاء
```bash
# عرض طرق الدفع المفعلة فقط
curl -X GET "https://your-domain.com/api/v1/payments/methods"
```

### 3. إدارة طرق الدفع بشكل دوري
```bash
# مزامنة دورية مع MyFatoorah
curl -X POST "https://your-domain.com/api/v1/admin/payment-methods/sync" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# تحديث أسماء طرق الدفع
curl -X PUT "https://your-domain.com/api/v1/admin/payment-methods/ap" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "payment_method_name_ar": "آبل باي",
    "payment_method_name_en": "Apple Pay"
  }'
```

---

## 🔍 نصائح للاستخدام

1. **المزامنة الدورية**: قم بمزامنة طرق الدفع مع MyFatoorah بشكل دوري للحصول على أحدث البيانات
2. **السلوك الافتراضي**: جميع طرق الدفع مفعلة افتراضياً إذا لم يتم تعيين إعدادات لها
3. **التحقق من الحالة**: استخدم API العرض للتحقق من حالة طرق الدفع قبل إجراء تغييرات
4. **التوافق العكسي**: API العملاء الحالي يعمل بنفس الطريقة ولكن مع فلترة طرق الدفع المفعلة

---

## 🛠️ استكشاف الأخطاء

### المشكلة: طريقة دفع لا تظهر للعملاء
**الحل**: تحقق من أن طريقة الدفع مفعلة في لوحة الإدارة

### المشكلة: خطأ 401 Unauthorized
**الحل**: تأكد من إرسال توكن JWT صحيح في الهيدر

### المشكلة: طريقة دفع غير موجودة
**الحل**: قم بمزامنة طرق الدفع من MyFatoorah أولاً

---

## 📝 ملاحظات مهمة

- جميع APIs تدعم CORS للاستخدام من المتصفح
- البيانات محفوظة في قاعدة البيانات المحلية
- التغييرات تؤثر فوراً على API العملاء
- لا يتم حذف طرق الدفع، فقط تفعيل/إلغاء تفعيل