# إرسال رسالتي واتساب: للأدمن ولمندوب التوصيل 📱📦

> **📝 ملاحظة:** لمعرفة كيفية تنسيق عنوان الشحن (JSON → نص مقروء)، انظر: [`WHATSAPP_ADDRESS_FORMATTING.md`](WHATSAPP_ADDRESS_FORMATTING.md)

## نظرة عامة

عند دفع طلب جديد، يتم إرسال رسالتي واتساب:
1. **رسالة للأدمن** - تفاصيل كاملة عن الطلب
2. **رسالة لمندوب التوصيل** - معلومات التوصيل الأساسية

---

## الإعداد

### في ملف `.env`:

```env
# WhatsApp Configuration
WHATSAPP_API_URL=http://localhost:3000

# رقم واتساب الأدمن
ADMIN_WHATSAPP_PHONE=201062532581

# رقم واتساب مندوب التوصيل
DELIVERY_WHATSAPP_PHONE=201062532581
```

**ملاحظة:** يمكن أن يكون نفس الرقم أو رقم مختلف

---

## الرسائل

### 1️⃣ رسالة الأدمن (تفصيلية)

```
*===== طلب جديد مدفوع =====*

*معلومات الطلب:*
رقم الطلب: 9355503
حالة الطلب: مدفوع
تاريخ الطلب: 2025-10-26
وقت الطلب: 15:30:45

*معلومات العميل:*
الاسم: محمد أحمد
رقم الهاتف: +96512345678
البريد الإلكتروني: customer@example.com

*عنوان الشحن:*
الشارع: شارع الخليج
المدينة: السالمية
المحافظة: محافظة حولي
القطعة: 5
البناية: 123

*المنتجات المطلوبة:*
  - صابون الليمون
    الكمية: 2
    السعر: 7.500 KWD
    المجموع: 15.000 KWD

  - شامبو الأعشاب
    الكمية: 1
    السعر: 30.500 KWD
    المجموع: 30.500 KWD

إجمالي عدد القطع: 3

*التفاصيل المالية:*
المجموع الفرعي: 45.500 KWD
تكلفة الشحن: 2.000 KWD
الخصم: -5.000 KWD
كود الخصم المستخدم: SUMMER2024
*المبلغ الإجمالي النهائي: 42.500 KWD*

*معلومات الدفع:*
حالة الدفع: Paid
رقم الفاتورة: 6248245
طريقة الدفع: KNET

==============================
*رابط لوحة التحكم:*
لعرض تفاصيل الطلب الكاملة
```

---

### 2️⃣ رسالة مندوب التوصيل (مبسطة ومركزة)

```
*===== طلب جديد للتوصيل =====*

*رقم الطلب:* 9355503
*التاريخ:* 2025-10-26
*الوقت:* 15:30:45

*معلومات العميل:*
الاسم: محمد أحمد
الهاتف: +96512345678

*عنوان التوصيل:*
الشارع: شارع الخليج
المدينة: السالمية
المحافظة: محافظة حولي
القطعة: 5
البناية: 123

*عدد القطع:* 3 قطعة

*المبلغ الإجمالي:* 42.500 KWD
*حالة الدفع:* مدفوع مسبقاً

==============================
*يرجى التواصل مع العميل لتحديد موعد التوصيل*
```

**الفرق الرئيسي:**
- ✅ أبسط وأسهل للقراءة السريعة
- ✅ يركز على المعلومات المهمة للتوصيل
- ✅ لا يحتوي على تفاصيل مالية معقدة
- ✅ يُظهر فقط المعلومات الضرورية للمندوب

---

## المقارنة

| العنصر | رسالة الأدمن | رسالة المندوب |
|--------|--------------|---------------|
| **معلومات الطلب** | تفصيلية | أساسية |
| **معلومات العميل** | كاملة (اسم + هاتف + بريد) | أساسية (اسم + هاتف) |
| **عنوان الشحن** | ✅ منسق | ✅ منسق |
| **تفاصيل المنتجات** | كاملة (كل منتج بالتفصيل) | عدد القطع فقط |
| **التفاصيل المالية** | شاملة (فرعي + شحن + خصم) | المبلغ النهائي فقط |
| **معلومات الدفع** | كاملة (حالة + فاتورة + طريقة) | حالة فقط |
| **كود الخصم** | ✅ | ❌ |
| **الملاحظات** | ❌ | ✅ (إذا وُجدت) |

---

## التدفق

```
📱 طلب جديد مدفوع
    │
    ├─> DB::commit() ✅
    │
    ├─> 1️⃣ إرسال Email
    │   └─> try-catch (لا يؤثر على الدفع)
    │
    ├─> 2️⃣ إرسال واتساب للأدمن
    │   └─> try-catch (لا يؤثر على الدفع)
    │
    ├─> 3️⃣ إرسال واتساب لمندوب التوصيل ← جديد!
    │   └─> try-catch (لا يؤثر على الدفع)
    │
    └─> ✅ الدفع ناجح (حتى لو فشلت الإشعارات)
```

---

## الأمان

### Fail-Safe Design ✅

```php
// 2. Admin WhatsApp
try {
    $this->whatsappService->notifyAdminNewPaidOrder($order);
} catch (\Exception $e) {
    Log::warning('Admin WhatsApp failed');
    // الدفع يستمر!
}

// 3. Delivery WhatsApp
try {
    $this->whatsappService->notifyDeliveryNewPaidOrder($order);
} catch (\Exception $e) {
    Log::warning('Delivery WhatsApp failed');
    // الدفع يستمر!
}
```

**النتيجة:**
- ✅ نجح كل شيء → ممتاز!
- ✅ فشل واتساب الأدمن → الدفع ناجح + رسالة المندوب تُرسل
- ✅ فشل واتساب المندوب → الدفع ناجح + رسالة الأدمن تُرسل
- ✅ فشل الاثنين → الدفع ناجح!

---

## الملفات المعدلة

### 1. `app/Services/WhatsAppService.php`

**إضافات:**
```php
// متغير جديد
protected $deliveryPhone;

// في __construct
$this->deliveryPhone = env('DELIVERY_WHATSAPP_PHONE', '201062532581');

// Method جديد
public function notifyDeliveryNewPaidOrder($order) { ... }

// Method مساعد جديد
private function formatDeliveryMessage($order) { ... }
```

---

### 2. `app/Http/Controllers/Api/Customer/PaymentController.php`

**إضافات:**
```php
// 3. Send WhatsApp Notification to Delivery
try {
    $this->whatsappService->notifyDeliveryNewPaidOrder($order);
} catch (\Exception $e) {
    Log::warning('Failed to send WhatsApp notification to delivery');
}
```

---

## الاختبار

### اختبار يدوي

```bash
php artisan tinker

# اختبار رسالة الأدمن
$order = App\Models\Order::where('status', 'paid')->first();
$whatsapp = app(App\Services\WhatsAppService::class);
$whatsapp->notifyAdminNewPaidOrder($order);

# اختبار رسالة المندوب
$whatsapp->notifyDeliveryNewPaidOrder($order);
```

---

### اختبار عملي

1. إنشاء طلب جديد
2. دفع الطلب
3. بعد النجاح:
   - ✅ يصل واتساب للأدمن (تفصيلي)
   - ✅ يصل واتساب للمندوب (مبسط)

---

## Logging

### عند النجاح

```
[INFO] Attempting to send WhatsApp notification for order
  order_id: 24
  
[INFO] WhatsApp notification sent successfully
  order_id: 24

[INFO] Attempting to send WhatsApp notification to delivery
  order_id: 24
  
[INFO] WhatsApp notification sent successfully to delivery
  order_id: 24
```

---

### عند الفشل

```
[WARNING] Failed to send WhatsApp notification to admin
  order_id: 24
  error: Connection timeout
  
[WARNING] Failed to send WhatsApp notification to delivery
  order_id: 24
  error: Connection timeout
```

**الدفع يستمر بنجاح!** ✅

---

## Use Cases

### نفس الرقم لكليهما
```env
ADMIN_WHATSAPP_PHONE=201062532581
DELIVERY_WHATSAPP_PHONE=201062532581
```
**النتيجة:** يصل رسالتان لنفس الرقم (الأدمن يرى التفاصيل الكاملة + معلومات التوصيل)

---

### أرقام مختلفة
```env
ADMIN_WHATSAPP_PHONE=201062532581
DELIVERY_WHATSAPP_PHONE=201055667788
```
**النتيجة:** 
- الأدمن يستقبل تفاصيل الطلب الكاملة
- المندوب يستقبل معلومات التوصيل فقط

---

## الخلاصة

### ✅ تم إضافته:
- رسالة ثانية لمندوب التوصيل
- رسالة مبسطة ومركزة على التوصيل
- إمكانية تحديد رقم منفصل للمندوب
- Fail-safe design كامل
- Logging منفصل لكل رسالة
- **تنسيق تلقائي للعناوين** (JSON → نص مقروء)

### 📱 الرسائل:
1. **للأدمن:** تفصيلية شاملة
2. **للمندوب:** مبسطة ومركزة

### 🔧 الإعداد:
- إضافة `DELIVERY_WHATSAPP_PHONE` في `.env`
- يمكن نفس الرقم أو رقم مختلف

### 📍 تنسيق العنوان:
العناوين المخزنة كـ JSON تُعرض تلقائياً بشكل مقروء:

**قبل:**
```json
{"street":"شارع 15","city":"المطار","governorate":"محافظة الفروانية"}
```

**بعد:**
```
الشارع: شارع 15
المدينة: المطار
المحافظة: محافظة الفروانية
```

للتفاصيل الكاملة: [`WHATSAPP_ADDRESS_FORMATTING.md`](WHATSAPP_ADDRESS_FORMATTING.md)

---

**جاهز للاستخدام! 📱📦**

