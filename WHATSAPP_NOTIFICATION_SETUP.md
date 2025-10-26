# إعداد إشعارات واتساب للطلبات المدفوعة 📱

> **📝 ملاحظة:** هذا التوثيق يغطي رسالة الأدمن فقط. لمعرفة كيفية إرسال رسالتين (للأدمن + للمندوب)، انظر: [`WHATSAPP_DUAL_NOTIFICATIONS.md`](WHATSAPP_DUAL_NOTIFICATIONS.md)

## نظرة عامة

تم إضافة ميزة إرسال إشعار واتساب تلقائي للأدمن عند دفع طلب جديد، مع صورة اللوجو وتفاصيل الطلب الكاملة.

---

## ✨ الميزات

- ✅ إرسال واتساب للأدمن عند كل طلب مدفوع
- ✅ يحتوي على صورة اللوجو (https://soapy-bubbles.com/logo.png)
- ✅ تفاصيل كاملة عن الطلب (رقم الطلب، العميل، المبلغ، المنتجات، إلخ)
- ✅ **آمن تماماً**: إذا فشل إرسال الواتساب لا يؤثر على عملية الدفع
- ✅ Logging كامل لنجاح/فشل الإرسال

---

## 📋 المتطلبات

### 1. WhatsApp API Server

يجب أن يكون لديك WhatsApp API Server يعمل على:
- Port: `3000` (افتراضي)
- Endpoint: `POST /api/send/image-url`

### 2. متغيرات البيئة (.env)

أضف هذه المتغيرات في ملف `.env`:

```env
# WhatsApp Configuration
WHATSAPP_API_URL=http://localhost:3000
ADMIN_WHATSAPP_PHONE=201062532581
```

**شرح المتغيرات:**
- `WHATSAPP_API_URL`: رابط API سيرفر الواتساب
- `ADMIN_WHATSAPP_PHONE`: رقم واتساب الأدمن (بصيغة دولية بدون +)

---

## 🚀 الإعداد

### الخطوة 1: تحديث ملف .env

```bash
# في ملف .env
WHATSAPP_API_URL=http://localhost:3000
ADMIN_WHATSAPP_PHONE=201062532581
```

### الخطوة 2: تشغيل WhatsApp API Server

تأكد من أن WhatsApp API Server يعمل على المنفذ المحدد.

### الخطوة 3: اختبار الاتصال

يمكنك اختبار الاتصال بـ API:

```bash
curl -X POST http://localhost:3000/api/send/image-url \
  -H "Content-Type: application/json" \
  -d '{
    "to": "201062532581",
    "imageUrl": "https://soapy-bubbles.com/logo.png",
    "caption": "اختبار الاتصال ✅"
  }'
```

---

## 📱 شكل الرسالة

عند دفع طلب جديد، يتم إرسال رسالة للأدمن بهذا الشكل:

```
🎉 *طلب جديد مدفوع!*

📦 *رقم الطلب:* 9355503
👤 *العميل:* محمد أحمد
📞 *الهاتف:* +96512345678
📧 *البريد:* customer@example.com

💰 *المبلغ الإجمالي:* 45.500 KWD

🏷️ *الخصم:* 5.000 KWD

📋 *المنتجات:*
  • صابون الليمون × 2 = 15.000 KWD
  • شامبو الأعشاب × 1 = 30.500 KWD

📍 *عنوان الشحن:*
الكويت، السالمية، شارع الخليج، بناية 123، شقة 45

⏰ *وقت الطلب:* 2025-10-26 15:30:45
✅ *الحالة:* مدفوع

---
🔗 عرض في لوحة التحكم
```

**مع صورة اللوجو** 🖼️

---

## 🔧 الملفات المضافة/المعدلة

### 1. `app/Services/WhatsAppService.php` (جديد)

Service مخصص لإرسال رسائل الواتساب:

```php
// إرسال إشعار للأدمن
$this->whatsappService->notifyAdminNewPaidOrder($order);

// إرسال رسالة عادية (للمستقبل)
$this->whatsappService->sendMessage($to, $message);

// إرسال صورة مع نص
$this->whatsappService->sendImageWithCaption($to, $imageUrl, $caption);
```

### 2. `app/Http/Controllers/Api/Customer/PaymentController.php` (معدّل)

تم إضافة استدعاء `WhatsAppService` بعد نجاح الدفع:

```php
// Send notifications after commit
if ($invoiceStatus === 'Paid') {
    // 1. Send Email
    try {
        $this->notificationService->createOrderNotification($order, 'order_paid');
    } catch (\Exception $e) {
        Log::warning('Email failed');
    }

    // 2. Send WhatsApp
    try {
        $this->whatsappService->notifyAdminNewPaidOrder($order);
    } catch (\Exception $e) {
        Log::warning('WhatsApp failed');
    }
}
```

---

## 🔒 الأمان

### ✅ Fail-Safe Design

```php
try {
    $this->whatsappService->notifyAdminNewPaidOrder($order);
} catch (\Exception $e) {
    Log::warning('WhatsApp notification failed');
    // المعاملة تستمر بنجاح!
}
```

**النتيجة:**
- ✅ إذا نجح إرسال الواتساب → ممتاز!
- ✅ إذا فشل إرسال الواتساب → لا مشكلة، الطلب يتم بنجاح!
- ✅ يتم تسجيل الفشل في Logs للمراجعة

### Timeout

- Timeout: 10 ثوانٍ
- إذا لم يرد API خلال 10 ثوانٍ، يتم تخطي الواتساب والاستمرار

---

## 📊 Logging

### عند النجاح

```
[2025-10-26 15:30:45] INFO: Attempting to send WhatsApp notification for order
{
  "order_id": 24,
  "order_number": "9355503"
}

[2025-10-26 15:30:46] INFO: WhatsApp notification sent successfully
{
  "order_id": 24,
  "response": {"success": true, "messageId": "ABC123"}
}
```

### عند الفشل

```
[2025-10-26 15:30:45] WARNING: Failed to send WhatsApp notification
{
  "order_id": 24,
  "error": "Connection timeout"
}
```

---

## 🧪 الاختبار

### 1. اختبار يدوي

```bash
# في tinker
php artisan tinker

# إرسال واتساب لطلب موجود
$order = App\Models\Order::find(24);
$whatsapp = app(App\Services\WhatsAppService::class);
$whatsapp->notifyAdminNewPaidOrder($order);
```

### 2. اختبار عملي

1. أنشئ طلب جديد
2. ادفع الطلب
3. بعد نجاح الدفع، يجب أن يصل واتساب للأدمن تلقائياً!

---

## ❓ استكشاف الأخطاء

### المشكلة: لم يصل الواتساب

**التحقق:**
```bash
# 1. تحقق من الـ logs
tail -f storage/logs/laravel.log | grep WhatsApp

# 2. تحقق من API URL
echo $WHATSAPP_API_URL

# 3. اختبر الاتصال
curl http://localhost:3000/api/send/image-url
```

**الحلول:**
- ✅ تأكد من أن WhatsApp API Server يعمل
- ✅ تأكد من `WHATSAPP_API_URL` صحيح في `.env`
- ✅ تأكد من أن رقم الأدمن صحيح
- ✅ راجع الـ logs في `storage/logs/laravel.log`

### المشكلة: الواتساب يفشل لكن الدفع لم ينجح

**هذا مستحيل! 🎯**

التصميم الحالي يضمن:
```php
DB::commit(); // ✅ الدفع نجح

// بعد commit:
try {
    WhatsApp::send(); // إذا فشل هنا، الدفع بقى ناجح!
} catch {}
```

---

## 🔮 الاستخدام المستقبلي

يمكن استخدام `WhatsAppService` لإشعارات أخرى:

```php
// إشعار العميل بالشحن
$whatsappService->sendMessage(
    $order->customer_phone,
    "تم شحن طلبك #{$order->order_number}!"
);

// إشعار بتحديث الطلب
$whatsappService->sendImageWithCaption(
    $adminPhone,
    "https://example.com/tracking.png",
    "تحديث حالة الطلب"
);
```

---

## 📝 الخلاصة

### ما تم إضافته:
- ✅ `WhatsAppService` جديد
- ✅ تكامل مع Payment Callback
- ✅ Fail-safe design
- ✅ Logging شامل
- ✅ رسالة منسقة مع صورة

### الإعداد المطلوب:
1. إضافة متغيرات في `.env`
2. تشغيل WhatsApp API Server
3. الاختبار

### النتيجة:
🎉 **عند كل طلب مدفوع، يصل واتساب للأدمن تلقائياً!**

---

## API Endpoint Details

### POST /api/send/image-url

**Request:**
```json
{
  "to": "201062532581",
  "imageUrl": "https://soapy-bubbles.com/logo.png",
  "caption": "نص الرسالة مع الصورة",
  "message": "نص إضافي (اختياري)"
}
```

**Response (Success):**
```json
{
  "success": true,
  "messageId": "ABC123XYZ"
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Connection timeout"
}
```

---

**جاهز للاستخدام! 🚀📱**

