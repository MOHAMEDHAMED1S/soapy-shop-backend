# Payment Discount Integration Fix

## المشكلة

عند تهيئة الدفع مع MyFatoorah، كان يظهر خطأ:

```json
{
    "success": false,
    "message": "Payment initiation failed",
    "error": "Failed to create payment: HTTP Error: 400 - Invoice total value must be the same total items value"
}
```

## السبب

MyFatoorah يتحقق من أن مجموع عناصر الفاتورة (`InvoiceItems`) يساوي تماماً قيمة الفاتورة (`InvoiceValue`).

كان الكود السابق:
```php
// حساب مجموع العناصر
$itemsTotal = 0;
foreach ($order->orderItems as $item) {
    $itemsTotal += $item->product_price * $item->quantity;
}
$itemsTotal += $order->shipping_amount;
$itemsTotal -= $order->discount_amount;

// ✗ استخدام order->total_amount (قد يختلف قليلاً)
'InvoiceValue' => (float)$order->total_amount,
```

**المشكلة:** قد يحدث اختلاف طفيف بين `$itemsTotal` و `order->total_amount` بسبب:
- دقة الأرقام العشرية (Floating point precision)
- ترتيب العمليات الحسابية
- التقريب في مراحل مختلفة

---

## الحل

استخدام `$itemsTotal` المحسوب من `InvoiceItems` مباشرة كـ `InvoiceValue`:

```php
// حساب مجموع العناصر
$itemsTotal = 0;

// المنتجات (بالسعر المخفض)
foreach ($order->orderItems as $item) {
    $itemTotal = (float)$item->product_price * $item->quantity;
    $itemsTotal += $itemTotal;
    
    $invoiceItems[] = [
        'ItemName' => $item->product_snapshot['title'],
        'Quantity' => $item->quantity,
        'UnitPrice' => (float)$item->product_price, // السعر المخفض
    ];
}

// الشحن
if ($order->shipping_amount > 0) {
    $invoiceItems[] = [
        'ItemName' => 'رسوم الشحن',
        'Quantity' => 1,
        'UnitPrice' => (float)$order->shipping_amount,
    ];
    $itemsTotal += (float)$order->shipping_amount;
}

// كود الخصم (سالب)
if ($order->discount_amount > 0) {
    $invoiceItems[] = [
        'ItemName' => 'خصم - ' . ($order->discount_code ?? 'كود الخصم'),
        'Quantity' => 1,
        'UnitPrice' => -(float)$order->discount_amount, // سالب
    ];
    $itemsTotal -= (float)$order->discount_amount;
}

// ✓ تقريب لـ 3 خانات عشرية
$itemsTotal = round($itemsTotal, 3);

// ✓ استخدام itemsTotal المحسوب
$paymentData = [
    'InvoiceValue' => $itemsTotal, // يساوي مجموع InvoiceItems بالضبط
    'InvoiceItems' => $invoiceItems,
];
```

---

## النتيجة

### قبل الإصلاح ✗
```
InvoiceItems:
  - صابون طبيعي × 2:    10.000
  - رسوم الشحن:         1.000
  - خصم - SAVE2:       -2.000
  المجموع:              9.000

InvoiceValue: 8.999999 (من order->total_amount)

❌ خطأ: القيم لا تتطابق!
```

### بعد الإصلاح ✓
```
InvoiceItems:
  - صابون طبيعي × 2:    10.000
  - رسوم الشحن:         1.000
  - خصم - SAVE2:       -2.000
  المجموع:              9.000

InvoiceValue: 9.000 (من itemsTotal)

✅ صحيح: القيم متطابقة تماماً!
```

---

## مثال كامل

### الطلب
```
منتج: صابون طبيعي
السعر الأصلي: 10 KWD
خصم المنتج: 50% → 5 KWD
الكمية: 2
إجمالي المنتجات: 10 KWD

كود الخصم: SAVE2 → -2 KWD
الشحن: 1 KWD

المبلغ النهائي: 9 KWD
```

### البيانات المرسلة لـ MyFatoorah
```json
{
  "InvoiceValue": 9.000,
  "InvoiceItems": [
    {
      "ItemName": "صابون طبيعي",
      "Quantity": 2,
      "UnitPrice": 5.000
    },
    {
      "ItemName": "رسوم الشحن",
      "Quantity": 1,
      "UnitPrice": 1.000
    },
    {
      "ItemName": "خصم - SAVE2",
      "Quantity": 1,
      "UnitPrice": -2.000
    }
  ]
}
```

### التحقق
```
مجموع InvoiceItems:
  5.000 × 2 = 10.000
  1.000 × 1 = 1.000
 -2.000 × 1 = -2.000
 ─────────────────────
              9.000 ✅

InvoiceValue = 9.000 ✅

9.000 == 9.000 ✓ متطابق!
```

---

## الملفات المعدلة

### `app/Services/PaymentService.php`

**التغييرات:**
1. إضافة تقريب `round($itemsTotal, 3)`
2. استخدام `$itemsTotal` بدلاً من `order->total_amount` في `InvoiceValue`

**الأسطر:**
- السطر 95: `$itemsTotal = round($itemsTotal, 3);`
- السطر 105: `'InvoiceValue' => $itemsTotal,`

---

## التأثير على النظام

### ✅ لا يؤثر على:
- حساب الطلبات (يظل كما هو)
- عرض المبالغ للعميل
- حفظ البيانات في قاعدة البيانات
- خصومات المنتجات
- أكواد الخصم

### ✅ يؤثر إيجابياً على:
- تهيئة الدفع مع MyFatoorah (الآن يعمل بشكل صحيح)
- دقة المبالغ المرسلة
- تطابق الفاتورة

---

## الاختبار

### قبل الإصلاح
```bash
curl -X POST http://localhost:8000/api/v1/payments/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "payment_method": "kn"
  }'

# النتيجة: ❌ خطأ "Invoice total value must be the same total items value"
```

### بعد الإصلاح
```bash
curl -X POST http://localhost:8000/api/v1/payments/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "payment_method": "kn"
  }'

# النتيجة: ✅ نجاح وإنشاء رابط الدفع
```

---

## ملاحظات مهمة

### 1. التقريب
نستخدم `round($itemsTotal, 3)` لأن الدينار الكويتي يستخدم 3 خانات عشرية.

### 2. الدقة
الحساب الآن دقيق 100% لأننا نستخدم نفس الطريقة لحساب `InvoiceValue` و `InvoiceItems`.

### 3. التوافق
الحل متوافق تماماً مع:
- خصومات المنتجات
- أكواد الخصم
- الشحن المجاني
- المنتجات المتعددة

### 4. السعر المخفض
`$item->product_price` في `order_items` يحتوي بالفعل على السعر بعد خصم المنتج، لذلك الحساب صحيح تلقائياً.

---

## الخلاصة

### ✅ المشكلة
عدم تطابق `InvoiceValue` مع مجموع `InvoiceItems`

### ✅ السبب
استخدام `order->total_amount` بدلاً من الحساب المباشر

### ✅ الحل
استخدام `$itemsTotal` المحسوب من `InvoiceItems` مباشرة

### ✅ النتيجة
- الدفع يعمل بشكل صحيح ✓
- المبالغ متطابقة تماماً ✓
- خصومات المنتجات مطبقة ✓
- أكواد الخصم تعمل ✓

**النظام الآن جاهز للاستخدام! 🎉**

