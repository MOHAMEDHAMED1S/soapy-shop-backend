# نظام التحقق المزدوج من الدفع 🔐

**التاريخ:** 2025-10-27

---

## 🎯 المشكلة التي تم حلها

### السيناريو الإشكالي:

```
1. المستخدم يقوم بإنشاء طلب
   ↓
   InitiatePayment → invoice_reference = "123456" (يُخزن في DB)

2. المستخدم في صفحة MyFatoorah
   ↓
   محاولة دفع أولى → فشلت ❌
   
3. MyFatoorah يعطي خيار "Try Again"
   ↓
   محاولة ثانية → نجحت ✅
   BUT: invoice_reference جديد = "789012" ⚠️
   
4. Callback من MyFatoorah
   ↓
   نتحقق من invoice_reference = "123456" (من DB)
   ↓
   MyFatoorah يقول: Status = Failed ❌
   ↓
   verification_failed! (رغم أن الدفع الثاني نجح!)
```

---

## ✅ الحل: نظام التحقق المزدوج

### المنطق الجديد:

```
┌─────────────────────────────────────────┐
│  1. التحقق من invoice_reference المخزن │
└─────────────────────────────────────────┘
                    ↓
        ┌───────────┴───────────┐
        │                       │
    ✅ Paid               ❌ Not Paid
        │                       │
        │           ┌───────────────────────────┐
        │           │ 2. Fallback: التحقق من   │
        │           │    paymentId من callback  │
        │           └───────────────────────────┘
        │                       ↓
        │           ┌───────────┴───────────┐
        │           │                       │
        │       ✅ Paid               ❌ Not Paid
        │           │                       │
        └───────────┴───────────────────────┘
                    ↓                       ↓
              ✅ SUCCESS            ❌ verification_failed
```

---

## 🔧 التغييرات المُنفذة

### 1️⃣ `app/Services/PaymentService.php`

#### إضافة Method جديد:

```php
/**
 * Verify payment status with MyFatoorah using PaymentId (from callback)
 */
public function verifyPaymentByPaymentId($paymentId)
{
    try {
        $response = $this->callMyFatoorahAPI('/v2/GetPaymentStatus', [
            'Key' => $paymentId,
            'KeyType' => 'PaymentId'  // ← المفتاح الجديد!
        ]);

        if ($response['success']) {
            return [
                'success' => true,
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error']
        ];

    } catch (\Exception $e) {
        Log::error('Payment verification by PaymentId error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
```

---

### 2️⃣ `app/Http/Controllers/Api/Customer/PaymentController.php`

#### التحقق المزدوج في `handleSuccessCallback()`:

```php
// ============================================================
// التحقق المزدوج: invoice_reference ثم paymentId
// ============================================================

$invoiceData = null;
$verificationMethod = null;

// المحاولة 1: التحقق من invoice_reference المخزن (الطريقة الأساسية)
Log::info('Attempting to verify payment using stored invoice_reference');

$paymentStatus = $this->paymentService->verifyPayment($invoiceReference);

if (!$paymentStatus['success']) {
    return redirect()->away('/payment/failure?error=verification_failed');
}

$invoiceData = $paymentStatus['data'];
$invoiceStatus = $invoiceData['InvoiceStatus'] ?? 'unknown';

// تحقق من حالة الدفع
if ($invoiceStatus === 'Paid') {
    // ✅ الدفع ناجح باستخدام invoice_reference المخزن
    $verificationMethod = 'stored_invoice_reference';
    
    Log::info('✅ Payment verified using stored invoice_reference');
    
} else {
    // ⚠️ الدفع المخزن ليس Paid → جرب paymentId من callback
    Log::warning('Stored invoice_reference is not Paid, attempting fallback');
    
    // المحاولة 2: التحقق باستخدام paymentId من callback (fallback)
    $callbackPaymentId = $request->get('paymentId') ?? $request->get('Id');
    
    if ($callbackPaymentId) {
        $fallbackStatus = $this->paymentService->verifyPaymentByPaymentId($callbackPaymentId);
        
        if ($fallbackStatus['success']) {
            $fallbackData = $fallbackStatus['data'];
            $fallbackInvoiceStatus = $fallbackData['InvoiceStatus'] ?? 'unknown';
            
            if ($fallbackInvoiceStatus === 'Paid') {
                // ✅ الدفع ناجح باستخدام paymentId من callback!
                $invoiceData = $fallbackData;
                $invoiceStatus = $fallbackInvoiceStatus;
                $verificationMethod = 'callback_paymentId';
                
                // تحديث invoice_reference المخزن للمستقبل
                $newInvoiceReference = $fallbackData['InvoiceId'] ?? $invoiceReference;
                if ($newInvoiceReference != $invoiceReference) {
                    Log::info('Updating invoice_reference from callback');
                    $invoiceReference = $newInvoiceReference;
                }
                
                Log::info('✅ Payment verified using callback paymentId (fallback)');
                
            } else {
                // ❌ حتى paymentId من callback ليس مدفوع
                return redirect()->away('/payment/failure?error=verification_failed');
            }
        } else {
            // ❌ فشل التحقق من paymentId
            return redirect()->away('/payment/failure?error=verification_failed');
        }
    } else {
        // ❌ لا يوجد paymentId في callback
        return redirect()->away('/payment/failure?error=verification_failed');
    }
}

// ============================================================
// الآن نحن متأكدون أن الدفع ناجح (Paid)
// ============================================================
```

---

## 📊 أمثلة من الواقع

### مثال 1: الدفع الناجح من أول محاولة ✅

```
1. invoice_reference = "6249194"
2. التحقق الأول: verifyPayment("6249194")
   → Response: { "InvoiceStatus": "Paid" }
3. النتيجة: ✅ نجح من أول محاولة
4. verification_method: "stored_invoice_reference"
```

**Logs:**
```
[INFO] Attempting to verify payment using stored invoice_reference
  invoice_reference: 6249194
[INFO] ✅ Payment verified successfully using stored invoice_reference
  invoice_status: Paid
```

---

### مثال 2: Retry ناجح (الحل الجديد!) 🎯

```
1. invoice_reference = "6228583" (محاولة أولى فشلت)
2. التحقق الأول: verifyPayment("6228583")
   → Response: { "InvoiceStatus": "Failed" }
3. ⚠️ فشل! → جرب Fallback
4. paymentId = "100530020000009810" (من callback)
5. التحقق الثاني: verifyPaymentByPaymentId("100530020000009810")
   → Response: { "InvoiceStatus": "Paid", "InvoiceId": "6250944" }
6. النتيجة: ✅ نجح باستخدام paymentId!
7. تحديث invoice_reference: "6228583" → "6250944"
8. verification_method: "callback_paymentId"
```

**Logs:**
```
[INFO] Attempting to verify payment using stored invoice_reference
  invoice_reference: 6228583
  
[WARNING] Stored invoice_reference is not Paid, attempting fallback
  stored_status: Failed
  
[INFO] Attempting fallback verification using callback paymentId
  paymentId: 100530020000009810
  
[INFO] Updating invoice_reference from callback verification
  old: 6228583
  new: 6250944
  
[INFO] ✅ Payment verified successfully using callback paymentId (fallback)
  invoice_status: Paid
  verification_method: callback_paymentId
```

---

### مثال 3: فشل تام (كلا المحاولتين فشلتا) ❌

```
1. invoice_reference = "6164046"
2. التحقق الأول: verifyPayment("6164046")
   → Response: { "InvoiceStatus": "Pending" }
3. ⚠️ فشل! → جرب Fallback
4. paymentId = "100528120000023921" (من callback)
5. التحقق الثاني: verifyPaymentByPaymentId("100528120000023921")
   → Response: { "InvoiceStatus": "Failed" }
6. النتيجة: ❌ verification_failed
```

**Logs:**
```
[INFO] Attempting to verify payment using stored invoice_reference
  invoice_reference: 6164046
  
[WARNING] Stored invoice_reference is not Paid, attempting fallback
  stored_status: Pending
  
[INFO] Attempting fallback verification using callback paymentId
  paymentId: 100528120000023921
  
[ERROR] Both verifications failed - payment not paid
  stored_status: Pending
  callback_status: Failed
  
[REDIRECT] /payment/failure?error=verification_failed
```

---

## 🔍 الفرق بين InvoiceId و PaymentId

### MyFatoorah API يدعم نوعين من المفاتيح:

| النوع | المثال | الاستخدام |
|-------|---------|-----------|
| **InvoiceId** | `6249194` | الفاتورة الأساسية (يُخزن عند initiate) |
| **PaymentId** | `100530020000009810` | معرف المعاملة الفعلية (من callback) |

### API Request:

```php
// النوع 1: InvoiceId (الحالي)
POST /v2/GetPaymentStatus
{
    "Key": "6249194",
    "KeyType": "InvoiceId"
}

// النوع 2: PaymentId (الجديد - Fallback)
POST /v2/GetPaymentStatus
{
    "Key": "100530020000009810",
    "KeyType": "PaymentId"
}
```

---

## 📝 البيانات المُخزنة

### في `payments` table - `response_raw`:

```json
{
  "initiate_response": { ... },
  "callback_response": {
    "InvoiceId": 6250944,
    "InvoiceStatus": "Paid",
    "InvoiceTransactions": [ ... ]
  },
  "verification_method": "callback_paymentId",
  "verified_at": "2025-10-27 13:05:30"
}
```

**verification_method يمكن أن يكون:**
- `"stored_invoice_reference"` - نجح من أول محاولة
- `"callback_paymentId"` - نجح باستخدام fallback

---

## 🎯 المزايا

### ✅ يحل مشكلة Retry:
```
المستخدم يحاول مرة → فشل → يحاول مرة أخرى → نجح
→ النظام الآن يتعرف على النجاح! ✅
```

### ✅ Backward Compatible:
```
الدفعات القديمة (بدون retry) تعمل كما هي
→ لا تأثير على الأداء الحالي
```

### ✅ Auto-Update:
```
إذا تغير invoice_reference بسبب retry
→ يُحدث تلقائياً في DB
```

### ✅ Detailed Logging:
```
كل خطوة مُسجلة في logs
→ سهولة التتبع والتشخيص
```

---

## 🧪 الاختبار

### اختبار التحقق باستخدام PaymentId:

```bash
php test_payment_id_verification.php
```

**مثال النتيجة:**
```
✅ الدفع ناجح! Invoice Status = Paid
✅ يمكن استخدام PaymentId للتحقق من الدفع بنجاح!

Response Time: 491ms
Invoice ID: 6250944
Payment ID: 100530020000009810
Transaction Status: Succss
```

---

## 📊 Flow Chart

```
                    ┌─────────────────┐
                    │  Callback من   │
                    │   MyFatoorah    │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │ احصل على order  │
                    │ باستخدام order_id│
                    └────────┬────────┘
                             │
          ┌──────────────────▼──────────────────┐
          │  التحقق من invoice_reference المخزن │
          └──────────────────┬──────────────────┘
                             │
                   ┌─────────▼─────────┐
                   │ InvoiceStatus؟   │
                   └─────────┬─────────┘
                             │
              ┌──────────────┼──────────────┐
              │                             │
        ✅ Paid                      ❌ Not Paid
              │                             │
              │                ┌────────────▼────────────┐
              │                │ احصل على paymentId من   │
              │                │      callback URL       │
              │                └────────────┬────────────┘
              │                             │
              │                ┌────────────▼────────────┐
              │                │ verifyPaymentByPaymentId│
              │                └────────────┬────────────┘
              │                             │
              │                   ┌─────────▼─────────┐
              │                   │ InvoiceStatus؟   │
              │                   └─────────┬─────────┘
              │                             │
              │              ┌──────────────┼──────────────┐
              │              │                             │
              │        ✅ Paid                      ❌ Not Paid
              │              │                             │
              │              │ ┌────────────────┐          │
              │              ├─┤ تحديث invoice_ │          │
              │              │ │   reference    │          │
              │              │ └────────────────┘          │
              │              │                             │
              └──────────────┴─────────────────────────────┘
                             │                             │
                    ┌────────▼────────┐         ┌─────────▼──────────┐
                    │ Update order    │         │ verification_failed│
                    │   to Paid       │         │                    │
                    └────────┬────────┘         └────────────────────┘
                             │
                    ┌────────▼────────┐
                    │ Deduct inventory│
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │ Send WhatsApp   │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    SUCCESS      │
                    └─────────────────┘
```

---

## 🔒 الأمان

### ✅ التحقق من order_id:
```php
$orderId = $request->get('order_id');
if (!$orderId) {
    return redirect()->away('/payment/failure?error=missing_order_id');
}
```

### ✅ التحقق من Order و Payment:
```php
$order = Order::with('payment')->find($orderId);
if (!$order || !$order->payment) {
    return redirect()->away('/payment/failure?error=order_not_found');
}
```

### ✅ التحقق المزدوج من MyFatoorah:
```php
// لا نثق في callback parameters فقط
// نتحقق من MyFatoorah API مباشرة
$paymentStatus = $this->paymentService->verifyPayment($invoiceReference);
```

---

## 📚 الملفات المعدلة

### 1. `app/Services/PaymentService.php`
- ✅ إضافة `verifyPaymentByPaymentId()`

### 2. `app/Http/Controllers/Api/Customer/PaymentController.php`
- ✅ تحديث `handleSuccessCallback()` بنظام التحقق المزدوج
- ✅ إضافة `verification_method` في logs و response_raw

### 3. `test_payment_id_verification.php` (جديد)
- ✅ سكريبت لاختبار التحقق باستخدام PaymentId

### 4. `PAYMENT_DOUBLE_VERIFICATION_SYSTEM.md` (هذا الملف)
- ✅ توثيق شامل للنظام الجديد

---

## ✅ الخلاصة

### المشكلة:
```
retry في MyFatoorah → invoice_reference جديد → verification_failed
```

### الحل:
```
1. جرب invoice_reference المخزن
2. إذا فشل → جرب paymentId من callback
3. أي واحد ناجح → قبول الدفع
```

### النتيجة:
```
✅ الدفعات العادية تعمل كما هي
✅ الدفعات مع retry تعمل الآن بنجاح
✅ تحديث تلقائي للـ invoice_reference
✅ logging شامل للتشخيص
```

---

**النظام الآن يدعم retry بشكل كامل! 🎉**

