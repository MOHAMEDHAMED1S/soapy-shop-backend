# تنسيق عنوان الشحن في رسائل الواتساب 📍

## نظرة عامة

تم إضافة تنسيق تلقائي لعنوان الشحن في رسائل الواتساب ليظهر بشكل مقروء بدلاً من JSON.

---

## المشكلة السابقة ❌

```
*عنوان التوصيل:*
{"street":"jhgj hjfvbn hv","city":"المطار","governorate":"محافظة الفروانية","postal_code":null}
```

---

## الحل الجديد ✅

```
*عنوان التوصيل:*
الشارع: jhgj hjfvbn hv
المدينة: المطار
المحافظة: محافظة الفروانية
```

---

## كيف يعمل؟

### 1. التعرف على نوع العنوان

```php
private function formatAddress($address)
{
    // نص عادي؟ → إرجاعه كما هو
    if (is_string($address) && !$this->isJson($address)) {
        return $address;
    }

    // JSON string؟ → فك التشفير
    if (is_string($address)) {
        $address = json_decode($address, true);
    }

    // Array؟ → تنسيقه بشكل مقروء
    if (is_array($address)) {
        // ...
    }
}
```

---

### 2. الحقول المدعومة

يتم عرض الحقول التالية (إذا وُجدت):

| الحقل | الاسم العربي |
|-------|-------------|
| `street` | الشارع |
| `city` | المدينة |
| `governorate` | المحافظة |
| `postal_code` | الرمز البريدي |
| `block` | القطعة |
| `building` | البناية |
| `floor` | الطابق |
| `apartment` | الشقة |
| `notes` | ملاحظات |

---

### 3. أمثلة

#### مثال 1: عنوان كامل

**Input:**
```json
{
  "street": "شارع الخليج",
  "city": "السالمية",
  "governorate": "محافظة حولي",
  "block": "5",
  "building": "123",
  "floor": "3",
  "apartment": "12",
  "postal_code": "12345"
}
```

**Output:**
```
الشارع: شارع الخليج
المدينة: السالمية
المحافظة: محافظة حولي
الرمز البريدي: 12345
القطعة: 5
البناية: 123
الطابق: 3
الشقة: 12
```

---

#### مثال 2: عنوان بسيط

**Input:**
```json
{
  "street": "شارع 15",
  "city": "المطار",
  "governorate": "محافظة الفروانية"
}
```

**Output:**
```
الشارع: شارع 15
المدينة: المطار
المحافظة: محافظة الفروانية
```

---

#### مثال 3: نص عادي

**Input:**
```
"الكويت، السالمية، شارع الخليج، بناية 123"
```

**Output:**
```
الكويت، السالمية، شارع الخليج، بناية 123
```
(يُعرض كما هو بدون تغيير)

---

## الاستخدام

### في رسالة الأدمن

```php
// Shipping Address
if ($order->shipping_address) {
    $message .= "*عنوان الشحن:*\n";
    $address = $this->formatAddress($order->shipping_address);
    $message .= "{$address}\n\n";
}
```

---

### في رسالة المندوب

```php
// عنوان التوصيل (مهم جداً للمندوب)
$message .= "*عنوان التوصيل:*\n";
if ($order->shipping_address) {
    $address = $this->formatAddress($order->shipping_address);
    $message .= "{$address}\n\n";
} else {
    $message .= "(لم يتم تحديد العنوان)\n\n";
}
```

---

## الميزات

### ✅ يدعم أنواع مختلفة:
- JSON string
- PHP array
- نص عادي

### ✅ آمن تماماً:
- يتحقق من نوع البيانات
- يتعامل مع الأخطاء بشكل صحيح
- يعرض "غير محدد" في حالة الفشل

### ✅ مرن:
- يعرض فقط الحقول الموجودة
- يتجاهل الحقول الفارغة (`null`, `""`)
- يحافظ على النصوص العادية كما هي

---

## الأمان والأخطاء

### 1. معالجة الأخطاء

```php
try {
    if ($order->shipping_address) {
        $address = $this->formatAddress($order->shipping_address);
    }
} catch (\Exception $ex) {
    // If formatting fails, keep it as 'غير محدد'
}
```

---

### 2. التحقق من JSON

```php
private function isJson($string)
{
    if (!is_string($string)) {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
```

---

### 3. Fallback

إذا فشل التنسيق أو كان العنوان فارغاً:
```
غير محدد
```

---

## الملفات المعدلة

### `app/Services/WhatsAppService.php`

**تم إضافة:**
1. `private function formatAddress($address)` - تنسيق العنوان
2. `private function isJson($string)` - التحقق من JSON

**تم تعديل:**
1. `formatOrderMessage()` - رسالة الأدمن
2. `formatDeliveryMessage()` - رسالة المندوب

---

## الاختبار

```bash
php test_whatsapp_notification.php
```

ستظهر الرسائل بعناوين منسقة بشكل مقروء.

---

## أمثلة من الواقع

### قبل التحديث ❌

```
*عنوان التوصيل:*
{"street":"jhgj hjfvbn hv","city":"المطار","governorate":"محافظة الفروانية","postal_code":null}
```

### بعد التحديث ✅

```
*عنوان التوصيل:*
الشارع: jhgj hjfvbn hv
المدينة: المطار
المحافظة: محافظة الفروانية
```

---

## الخلاصة

✅ **تنسيق تلقائي** للعناوين المنظمة (JSON/Array)  
✅ **يحافظ على النصوص العادية** كما هي  
✅ **آمن ومرن** مع معالجة كاملة للأخطاء  
✅ **يعمل في كلا الرسالتين** (الأدمن والمندوب)  

**جاهز للاستخدام! 📍✨**

