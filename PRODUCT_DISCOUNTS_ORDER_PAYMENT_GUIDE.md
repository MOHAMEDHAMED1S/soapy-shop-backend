# Product Discounts - Order & Payment Integration Guide

## كيف يعمل نظام الخصومات مع الطلبات والدفع

---

## نظرة عامة

تم دمج نظام خصومات المنتجات بشكل كامل مع نظام الطلبات والدفع لضمان:
- ✅ حساب السعر الصحيح بعد الخصم في الطلبات
- ✅ الدفع بالسعر الصحيح بعد الخصم
- ✅ حفظ معلومات الخصم في سجل الطلب
- ✅ التكامل مع أكواد الخصم (Discount Codes)

---

## الفرق بين نوعي الخصومات

### 1. خصومات المنتجات (Product Discounts) ⭐ جديد
- تطبق تلقائياً على المنتجات
- تظهر في صفحة المنتج مباشرة
- لا تحتاج إلى كود من العميل
- تطبق **قبل** حساب الطلب

### 2. أكواد الخصم (Discount Codes)
- يجب على العميل إدخال الكود
- تطبق على إجمالي الطلب
- تطبق **بعد** خصومات المنتجات

---

## تسلسل حساب السعر

```
1. السعر الأصلي للمنتج (Original Price)
   ↓
2. تطبيق خصم المنتج (Product Discount)
   ↓
3. السعر بعد خصم المنتج (Discounted Price) ← يُستخدم في حساب الطلب
   ↓
4. حساب إجمالي الطلب (Subtotal) = مجموع المنتجات بأسعارها المخفضة
   ↓
5. تطبيق كود الخصم (Discount Code) - إن وجد
   ↓
6. إضافة الشحن (Shipping)
   ↓
7. السعر النهائي (Total Amount) ← يُستخدم في الدفع
```

---

## مثال عملي

### السيناريو
- منتج A: سعره الأصلي 10 KWD
- خصم منتج 50% على المنتج A
- الكمية: 2
- كود خصم إضافي: 2 KWD على إجمالي الطلب
- الشحن: 1 KWD

### الحساب
```
1. السعر الأصلي: 10 KWD
2. خصم المنتج 50%: 10 - 5 = 5 KWD (السعر المخفض)
3. الإجمالي الفرعي: 5 × 2 = 10 KWD
4. كود الخصم: 10 - 2 = 8 KWD
5. الشحن: 8 + 1 = 9 KWD
6. السعر النهائي: 9 KWD ✅
```

---

## التطبيق في الكود

### 1. إنشاء طلب جديد (`createOrder`)

```php
// في OrderController.php

foreach ($request->items as $item) {
    $product = Product::find($item['product_id']);
    
    // ✅ يستخدم السعر بعد خصم المنتج
    $finalPrice = $product->discounted_price ?? $product->price;
    
    $itemTotal = $finalPrice * $item['quantity'];
    $subtotalAmount += $itemTotal;
    
    // حفظ معلومات الخصم في سجل الطلب
    $productSnapshots[] = [
        'product_price' => $finalPrice, // السعر المستخدم فعلياً
        'product_snapshot' => [
            'price' => $product->price, // السعر الأصلي للمرجع
            'discounted_price' => $finalPrice, // السعر بعد الخصم
            'has_discount' => $product->has_discount,
            'discount_percentage' => $product->discount_percentage,
            // ... باقي البيانات
        ]
    ];
}

// ثم يتم تطبيق كود الخصم (إن وجد)
$totalAmount = $subtotalAmount - $discountCodeAmount + $shippingAmount;
```

### 2. حساب الإجمالي (`calculateTotal`)

```php
// نفس المنطق - يستخدم السعر المخفض
foreach ($request->items as $item) {
    $product = Product::find($item['product_id']);
    $finalPrice = $product->discounted_price ?? $product->price;
    $itemTotal = $finalPrice * $item['quantity'];
    $subtotalAmount += $itemTotal;
}
```

### 3. التحقق من كود الخصم (`validateDiscount`)

```php
// يحسب الإجمالي بأسعار المنتجات المخفضة أولاً
$subtotal = calculateSubtotalWithProductDiscounts();

// ثم يطبق كود الخصم على الإجمالي
$discountAmount = calculateDiscountCodeAmount($subtotal);
```

### 4. الدفع (`PaymentService`)

```php
// في PaymentService::initiatePayment()

// ✅ يستخدم order->total_amount الذي يشمل:
// - خصومات المنتجات
// - كود الخصم
// - الشحن

$paymentData = [
    'InvoiceValue' => (float)$order->total_amount, // السعر النهائي الصحيح
    // ...
];
```

---

## Response Structure

### عند جلب المنتج
```json
{
  "id": 1,
  "title": "صابون طبيعي",
  "price": "10.000",                    // السعر الأصلي
  "has_discount": true,                 // يوجد خصم
  "discount_percentage": 50,            // نسبة الخصم
  "discounted_price": "5.000",          // السعر بعد الخصم ← يُستخدم
  "price_before_discount": "10.000",    // السعر الأصلي
  "discount_amount": "5.000"            // مبلغ الخصم
}
```

### عند إنشاء طلب
```json
{
  "success": true,
  "data": {
    "order": {
      "order_number": "1234567",
      "subtotal_amount": "10.000",      // بعد خصومات المنتجات
      "discount_amount": "2.000",        // كود الخصم
      "shipping_amount": "1.000",
      "total_amount": "9.000",           // السعر النهائي ← للدفع
      "order_items": [
        {
          "product_id": 1,
          "product_price": "5.000",      // السعر المخفض المستخدم
          "quantity": 2,
          "product_snapshot": {
            "price": "10.000",           // الأصلي للمرجع
            "discounted_price": "5.000", // المستخدم فعلياً
            "has_discount": true,
            "discount_percentage": 50
          }
        }
      ]
    }
  }
}
```

### عند الدفع
```json
{
  "success": true,
  "data": {
    "payment_url": "https://...",
    "amount": "9.000",                   // ✅ السعر الصحيح بعد جميع الخصومات
    "currency": "KWD"
  }
}
```

---

## أمثلة استخدام من Front-end

### 1. عرض السلة مع الخصومات

```jsx
const Cart = ({ items }) => {
  // حساب الإجمالي (المنتجات بالفعل لها أسعار مخفضة)
  const subtotal = items.reduce((sum, item) => {
    const price = item.product.discounted_price || item.product.price;
    return sum + (price * item.quantity);
  }, 0);

  return (
    <div>
      <h2>السلة</h2>
      {items.map(item => (
        <div key={item.product.id}>
          <span>{item.product.title}</span>
          
          {item.product.has_discount ? (
            <div>
              <span className="original-price">
                {item.product.price} KWD
              </span>
              <span className="discounted-price">
                {item.product.discounted_price} KWD
              </span>
              <span className="discount-badge">
                {item.product.discount_percentage}% خصم
              </span>
            </div>
          ) : (
            <span>{item.product.price} KWD</span>
          )}
          
          <span>الكمية: {item.quantity}</span>
        </div>
      ))}
      
      <div className="subtotal">
        الإجمالي الفرعي: {subtotal.toFixed(3)} KWD
      </div>
    </div>
  );
};
```

### 2. حساب الإجمالي قبل إنشاء الطلب

```javascript
const calculateTotal = async (items, discountCode = null, shipping = 0) => {
  const response = await fetch('/api/v1/checkout/calculate-total', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      items: items.map(item => ({
        product_id: item.product.id,
        quantity: item.quantity
      })),
      discount_code: discountCode,
      shipping_amount: shipping
    })
  });
  
  const result = await response.json();
  
  // result.data يحتوي على:
  // - subtotal_amount (بعد خصومات المنتجات)
  // - discount_amount (كود الخصم)
  // - shipping_amount
  // - total_amount (السعر النهائي)
  
  return result.data;
};
```

### 3. إنشاء طلب

```javascript
const createOrder = async (orderData) => {
  const response = await fetch('/api/v1/checkout/create-order', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      customer_name: orderData.name,
      customer_phone: orderData.phone,
      customer_email: orderData.email,
      shipping_address: orderData.address,
      items: orderData.items.map(item => ({
        product_id: item.product.id,
        quantity: item.quantity
        // ✅ لا حاجة لإرسال السعر - سيتم حسابه تلقائياً من المنتج
      })),
      discount_code: orderData.discountCode,
      shipping_amount: orderData.shipping
    })
  });
  
  const result = await response.json();
  
  // result.data.order.total_amount هو السعر النهائي الصحيح
  return result.data;
};
```

### 4. الدفع

```javascript
const initiatePayment = async (orderId, paymentMethod) => {
  const response = await fetch('/api/v1/payments/initiate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      order_id: orderId,
      payment_method: paymentMethod,
      customer_ip: await getCustomerIP(),
      user_agent: navigator.userAgent
    })
  });
  
  const result = await response.json();
  
  // result.data.amount يحتوي السعر الصحيح بعد جميع الخصومات
  // result.data.redirect_url رابط الدفع
  
  window.location.href = result.data.redirect_url;
};
```

---

## سيناريوهات الاستخدام

### سيناريو 1: منتج مخفض فقط
```
منتج: 10 KWD
خصم منتج: 30% → 7 KWD
كمية: 1
كود خصم: لا يوجد
شحن: 1 KWD

الحساب:
7 + 1 = 8 KWD ✅
```

### سيناريو 2: منتج مخفض + كود خصم
```
منتج: 10 KWD
خصم منتج: 30% → 7 KWD
كمية: 2
إجمالي فرعي: 14 KWD
كود خصم: 2 KWD
شحن: 1 KWD

الحساب:
14 - 2 + 1 = 13 KWD ✅
```

### سيناريو 3: منتجين أحدهما مخفض
```
منتج A: 10 KWD (خصم 50% → 5 KWD) × 1 = 5 KWD
منتج B: 8 KWD (بدون خصم) × 1 = 8 KWD
إجمالي فرعي: 13 KWD
كود خصم: 3 KWD
شحن: 1 KWD

الحساب:
13 - 3 + 1 = 11 KWD ✅
```

---

## التحقق من صحة الحساب

### في Front-end
```javascript
// تأكد من استخدام discounted_price
const itemPrice = product.discounted_price || product.price;
const itemTotal = itemPrice * quantity;
```

### في Backend
```php
// التأكد من استخدام السعر الصحيح
$finalPrice = $product->discounted_price ?? $product->price;
$itemTotal = $finalPrice * $quantity;
```

### في الدفع
```php
// استخدام order->total_amount دائماً
$paymentAmount = $order->total_amount; // ✅ صحيح
```

---

## ملاحظات مهمة

### ✅ الأولويات
1. خصومات المنتجات (تطبق أولاً)
2. أكواد الخصم (تطبق ثانياً)
3. الشحن (يضاف أخيراً)

### ✅ الحفظ
- يتم حفظ السعر المستخدم فعلياً في `order_items.product_price`
- يتم حفظ السعر الأصلي في `product_snapshot.price` للمرجع
- يتم حفظ معلومات الخصم في `product_snapshot`

### ✅ الدفع
- الدفع يستخدم `order.total_amount` دائماً
- هذا المبلغ يشمل جميع الخصومات والشحن
- لا حاجة لحساب إضافي في الدفع

---

## الخلاصة

✅ **نظام متكامل**
- خصومات المنتجات تطبق تلقائياً
- الطلبات تحسب بالسعر الصحيح
- الدفع يتم بالمبلغ الصحيح
- جميع المعلومات محفوظة للمرجع

✅ **سهل الاستخدام**
- لا حاجة لحسابات معقدة في Front-end
- كل شيء يتم تلقائياً في Backend
- الـ Response واضح ومفصل

✅ **موثوق**
- لا تعارض بين أنواع الخصومات
- التسلسل واضح ومنطقي
- السعر النهائي دائماً صحيح

**النظام جاهز ومتكامل! 🎉**

