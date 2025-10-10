# 💳 دليل APIs المدفوعات - Soapy Shop

## 📋 نظرة عامة

هذا الدليل يوضح كيفية استخدام APIs المدفوعات في نظام Soapy Shop مع MyFatoorah. النظام يدعم طرق دفع متعددة ويوفر تدفق دفع آمن وموثوق.

## 🔗 APIs المتاحة

### 1. جلب طرق الدفع المتاحة
```http
GET /api/v1/payments/methods
```

**الوصف:** يحصل على قائمة بجميع طرق الدفع المتاحة من MyFatoorah.

**المعاملات:** لا توجد معاملات مطلوبة

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/payments/methods" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": [
    {
      "PaymentMethodId": 1,
      "PaymentMethodAr": "كي نت",
      "PaymentMethodEn": "KNET",
      "PaymentMethodCode": "kn",
      "IsDirectPayment": false,
      "ServiceCharge": 1.01,
      "TotalAmount": 1,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/kn.png",
      "IsEmbeddedSupported": false,
      "PaymentCurrencyIso": "KWD"
    },
    {
      "PaymentMethodId": 2,
      "PaymentMethodAr": "فيزا / ماستر",
      "PaymentMethodEn": "VISA/MASTER",
      "PaymentMethodCode": "vm",
      "IsDirectPayment": false,
      "ServiceCharge": 0.101,
      "TotalAmount": 1,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/vm.png",
      "IsEmbeddedSupported": true,
      "PaymentCurrencyIso": "KWD"
    },
    {
      "PaymentMethodId": 3,
      "PaymentMethodAr": "اميكس",
      "PaymentMethodEn": "AMEX",
      "PaymentMethodCode": "ae",
      "IsDirectPayment": false,
      "ServiceCharge": 0.125,
      "TotalAmount": 1.125,
      "CurrencyIso": "KWD",
      "ImageUrl": "https://demo.myfatoorah.com/imgs/payment-methods/ae.png",
      "IsEmbeddedSupported": true,
      "PaymentCurrencyIso": "USD"
    }
  ],
  "message": "Payment methods retrieved successfully"
}
```

### 2. بدء عملية الدفع
```http
POST /api/v1/payments/initiate
```

**الوصف:** يبدأ عملية دفع لطلب محدد باستخدام طريقة الدفع المختارة.

**المعاملات المطلوبة:**
- `order_id` (integer, required): معرف الطلب في قاعدة البيانات
- `payment_method` (string, required): كود طريقة الدفع
- `customer_ip` (string, required): عنوان IP الخاص بالعميل
- `user_agent` (string, optional): معلومات المتصفح

**طرق الدفع المدعومة:**
- `kn`: كي نت (KNET)
- `vm`: فيزا/ماستر (VISA/MASTER)
- `ae`: اميكس (AMEX)
- `md`: مدى (MADA)
- `ap`: أبل باي (Apple Pay)
- `stc`: إس تي سي باي (STC Pay)
- `uaecc`: كروت الدفع المدنية (UAE Debit Cards)
- `gp`: جوجل باي (Google Pay)
- `b`: بنفت (Benefit)

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/payments/initiate" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 11,
    "payment_method": "kn",
    "customer_ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
  }'
```

**مثال الاستجابة الناجحة:**
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

**🔗 رابط الدفع الفعلي:**
- `payment_url` و `redirect_url` يحتويان على رابط الدفع الفعلي من MyFatoorah
- يجب توجيه العميل إلى هذا الرابط لإتمام عملية الدفع
- الرابط يفتح صفحة دفع آمنة من MyFatoorah

**مثال الاستجابة الفاشلة:**
```json
{
  "success": false,
  "message": "Order not found",
  "error": "Order with ID 999 does not exist"
}
```

### 3. التحقق من حالة الدفع
```http
GET /api/v1/payments/status
```

**الوصف:** يتحقق من حالة الدفع لطلب محدد.

**Query Parameters:**
- `order_id` (integer, required): معرف الطلب في قاعدة البيانات

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/payments/status?order_id=11" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "awaiting_payment",
    "payment_status": "initiated",
    "amount": "51.000",
    "currency": "KWD",
    "payment_method": "kn",
    "invoice_id": "1"
  },
  "message": "Payment status retrieved successfully"
}
```

### 4. معالجة استجابة الدفع (Callback)
```http
POST /api/v1/payments/callback
```

**الوصف:** يعالج استجابة الدفع من MyFatoorah بعد اكتمال العملية.

**المعاملات المطلوبة:**
- `paymentId` (string, required): معرف الدفع من MyFatoorah
- `order_id` (integer, required): معرف الطلب في قاعدة البيانات

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/payments/callback" \
  -H "Content-Type: application/json" \
  -d '{
    "paymentId": "123456789",
    "order_id": 11
  }'
```

**مثال الاستجابة الناجحة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "paid",
    "payment_status": "Paid",
    "amount": "51.000",
    "currency": "KWD"
  },
  "message": "Payment processed successfully"
}
```

## 🔄 تدفق الدفع الكامل

### الخطوة 1: إنشاء الطلب
```bash
# إنشاء طلب جديد
curl -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "+96512345678",
    "shipping_address": {
      "street": "شارع الخليج",
      "city": "الكويت",
      "governorate": "الكويت"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      }
    ],
    "notes": "طلب تجريبي"
  }'
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 11,
      "order_number": "ORD-20251004-BC44A0",
      "total_amount": "51.000",
      "currency": "KWD",
      "status": "pending"
    },
    "next_step": "payment_required"
  },
  "message": "Order created successfully. Proceed to payment."
}
```

### الخطوة 2: جلب طرق الدفع
```bash
# الحصول على طرق الدفع المتاحة
curl -X GET "http://localhost:8000/api/v1/payments/methods" \
  -H "Accept: application/json"
```

### الخطوة 3: بدء عملية الدفع
```bash
# بدء الدفع باستخدام كي نت
curl -X POST "http://localhost:8000/api/v1/payments/initiate" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 11,
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
    "payment_id": 4,
    "invoice_id": 1,
    "payment_url": "https://demo.myfatoorah.com/imgs/payment-methods/kn.png",
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "amount": "51.000",
    "currency": "KWD"
  },
  "message": "Payment initiated successfully"
}
```

### الخطوة 4: التحقق من حالة الدفع
```bash
# التحقق من حالة الدفع
curl -X GET "http://localhost:8000/api/v1/payments/status?order_id=11" \
  -H "Accept: application/json"
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "awaiting_payment",
    "payment_status": "initiated",
    "amount": "51.000",
    "currency": "KWD",
    "payment_method": "kn",
    "invoice_id": "1"
  },
  "message": "Payment status retrieved successfully"
}
```

### الخطوة 5: معالجة استجابة الدفع
```bash
# معالجة استجابة الدفع (يتم استدعاؤها من MyFatoorah)
curl -X POST "http://localhost:8000/api/v1/payments/callback" \
  -H "Content-Type: application/json" \
  -d '{
    "paymentId": "123456789",
    "order_id": 11
  }'
```

**الاستجابة:**
```json
{
  "success": true,
  "data": {
    "order_id": 11,
    "order_number": "ORD-20251004-BC44A0",
    "status": "paid",
    "payment_status": "Paid",
    "amount": "51.000",
    "currency": "KWD"
  },
  "message": "Payment processed successfully"
}
```

## 🚨 أكواد الأخطاء

### أخطاء التحقق من البيانات
- **422**: فشل في التحقق من البيانات
- **400**: بيانات غير صحيحة

### أخطاء الطلب
- **404**: الطلب غير موجود
- **400**: الطلب ليس في حالة مناسبة للدفع

### أخطاء الدفع
- **500**: فشل في بدء عملية الدفع
- **500**: فشل في التحقق من حالة الدفع
- **500**: فشل في معالجة استجابة الدفع

## 📊 حالات الطلب والدفع

### حالات الطلب
- `pending`: في الانتظار
- `awaiting_payment`: في انتظار الدفع
- `paid`: مدفوع
- `failed`: فشل
- `cancelled`: ملغي

### حالات الدفع
- `initiated`: تم البدء
- `Paid`: مدفوع
- `Failed`: فشل
- `Pending`: في الانتظار

## 🔧 التكامل مع Frontend

### JavaScript Example
```javascript
// 1. إنشاء الطلب
const createOrder = async (orderData) => {
  const response = await fetch('/api/v1/checkout/create-order', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(orderData)
  });
  return await response.json();
};

// 2. جلب طرق الدفع
const getPaymentMethods = async () => {
  const response = await fetch('/api/v1/payments/methods');
  return await response.json();
};

// 3. بدء عملية الدفع
const initiatePayment = async (orderId, paymentMethod, customerIP) => {
  const response = await fetch('/api/v1/payments/initiate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      order_id: orderId,
      payment_method: paymentMethod,
      customer_ip: customerIP,
      user_agent: navigator.userAgent
    })
  });
  return await response.json();
};

// 4. التحقق من حالة الدفع
const checkPaymentStatus = async (orderId) => {
  const response = await fetch(`/api/v1/payments/status?order_id=${orderId}`);
  return await response.json();
};

// مثال على الاستخدام
const processPayment = async () => {
  try {
    // إنشاء الطلب
    const order = await createOrder({
      customer_name: "أحمد محمد",
      customer_phone: "+96512345678",
      shipping_address: {
        street: "شارع الخليج",
        city: "الكويت",
        governorate: "الكويت"
      },
      items: [
        { product_id: 1, quantity: 2 }
      ]
    });

    if (!order.success) {
      throw new Error(order.message);
    }

    // جلب طرق الدفع
    const methods = await getPaymentMethods();
    if (!methods.success) {
      throw new Error('Failed to get payment methods');
    }

    // بدء عملية الدفع
    const payment = await initiatePayment(
      order.data.order.id,
      'kn', // كي نت
      '192.168.1.1'
    );

    if (!payment.success) {
      throw new Error(payment.message);
    }

    console.log('Payment initiated:', payment.data);
    
    // التحقق من حالة الدفع
    const status = await checkPaymentStatus(order.data.order.id);
    console.log('Payment status:', status.data);

  } catch (error) {
    console.error('Payment error:', error.message);
  }
};
```

## 🛡️ الأمان

### التحقق من البيانات
- جميع المعاملات يتم التحقق منها قبل المعالجة
- عنوان IP العميل مطلوب للتحقق من الأمان
- معرف الطلب يجب أن يكون موجوداً في قاعدة البيانات

### حماية من التلاعب
- يتم التحقق من حالة الطلب قبل بدء الدفع
- يتم التحقق من صحة معرف الدفع مع MyFatoorah
- جميع العمليات تتم في معاملات قاعدة البيانات

## 📞 الدعم

### للمساعدة
- **البريد الإلكتروني**: support@soapyshop.com
- **الهاتف**: +965 1234 5678
- **ساعات العمل**: 9:00 ص - 6:00 م (بتوقيت الكويت)

---

**تم تطوير APIs المدفوعات بواسطة فريق Soapy Shop** 🧼✨

*آخر تحديث: 4 أكتوبر 2025*
