# API تصحيح الدفع 🔍

## نظرة عامة

هذا API يسمح للإدمن بالتحقق من جميع الطلبات التي حالتها "awaiting_payment" والتأكد من أنها لم تُدفع فعلياً عبر MyFatoorah.

**الهدف:** كشف الطلبات التي تم دفعها بنجاح لكن لم يتم تحديثها بسبب مشاكل في callback.

---

## Endpoint

```http
GET /api/v1/admin/payments/verify-pending
Authorization: Bearer {admin_token}
```

---

## Response Structure

### Success Response

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_checked": 10,
      "paid_but_not_updated": 2,
      "still_pending": 7,
      "errors": 1
    },
    "paid_but_not_updated": [
      {
        "order_id": 24,
        "order_number": "9355503",
        "customer_name": "أحمد محمد",
        "customer_phone": "+96512345678",
        "total_amount": "25.500",
        "currency": "KWD",
        "invoice_reference": "6248245",
        "invoice_status": "Paid",
        "order_created_at": "2025-10-25 19:00:00",
        "payment_date": "2025-10-25T19:05:00",
        "items_count": 2
      },
      {
        "order_id": 23,
        "order_number": "9355502",
        "customer_name": "سارة علي",
        "customer_phone": "+96587654321",
        "total_amount": "15.000",
        "currency": "KWD",
        "invoice_reference": "6248200",
        "invoice_status": "Paid",
        "order_created_at": "2025-10-25 18:30:00",
        "payment_date": "2025-10-25T18:35:00",
        "items_count": 1
      }
    ],
    "still_pending": [
      {
        "order_id": 22,
        "order_number": "9355501",
        "invoice_status": "Pending",
        "invoice_reference": "6248190"
      }
    ],
    "errors": [
      {
        "order_id": 20,
        "order_number": "9355499",
        "invoice_reference": "6248100",
        "error": "Invoice not found"
      }
    ]
  },
  "message": "Payment verification completed successfully"
}
```

---

## Response Fields

### `summary` Object

| Field | Type | Description |
|-------|------|-------------|
| `total_checked` | integer | عدد الطلبات التي تم فحصها |
| `paid_but_not_updated` | integer | **عدد الطلبات المدفوعة لكن لم يتم تحديثها** |
| `still_pending` | integer | عدد الطلبات ما زالت pending فعلاً |
| `errors` | integer | عدد الأخطاء أثناء الفحص |

---

### `paid_but_not_updated` Array

**هذا هو الأهم!** قائمة الطلبات التي تحتاج تصحيح:

| Field | Type | Description |
|-------|------|-------------|
| `order_id` | integer | رقم الطلب |
| `order_number` | string | رقم الطلب (للعرض) |
| `customer_name` | string | اسم العميل |
| `customer_phone` | string | هاتف العميل |
| `total_amount` | decimal | المبلغ الإجمالي |
| `currency` | string | العملة |
| `invoice_reference` | string | رقم الفاتورة في MyFatoorah |
| `invoice_status` | string | حالة الدفع في MyFatoorah |
| `order_created_at` | datetime | تاريخ إنشاء الطلب |
| `payment_date` | datetime | تاريخ الدفع الفعلي |
| `items_count` | integer | عدد المنتجات |

---

### `still_pending` Array

طلبات ما زالت pending فعلاً (لم يتم الدفع):

| Field | Type | Description |
|-------|------|-------------|
| `order_id` | integer | رقم الطلب |
| `order_number` | string | رقم الطلب |
| `invoice_status` | string | حالة الدفع في MyFatoorah |
| `invoice_reference` | string | رقم الفاتورة |

---

### `errors` Array

طلبات حدث خطأ أثناء فحصها:

| Field | Type | Description |
|-------|------|-------------|
| `order_id` | integer | رقم الطلب |
| `order_number` | string | رقم الطلب |
| `invoice_reference` | string | رقم الفاتورة |
| `error` | string | رسالة الخطأ |

---

## كيفية الاستخدام

### 1️⃣ استدعاء API

```javascript
const response = await fetch('/api/v1/admin/payments/verify-pending', {
  headers: {
    'Authorization': `Bearer ${adminToken}`
  }
});

const data = await response.json();
```

---

### 2️⃣ معالجة النتائج

```javascript
if (data.success) {
  const { summary, paid_but_not_updated } = data.data;
  
  console.log(`تم فحص ${summary.total_checked} طلب`);
  console.log(`⚠️ وُجد ${summary.paid_but_not_updated} طلب مدفوع لكن لم يتم تحديثه!`);
  
  // عرض الطلبات المدفوعة غير المحدثة
  paid_but_not_updated.forEach(order => {
    console.log(`
      🔴 طلب #${order.order_number}
      - العميل: ${order.customer_name}
      - المبلغ: ${order.total_amount} ${order.currency}
      - تم الدفع في: ${order.payment_date}
      - يحتاج تصحيح!
    `);
  });
}
```

---

### 3️⃣ تصحيح الطلبات يدوياً

بعد الحصول على القائمة، يمكنك:

#### Option A: تصحيح يدوي عبر Tinker

```bash
php artisan tinker

# لكل order_id من القائمة:
$order = App\Models\Order::with('payment')->find(24);
$order->update(['status' => 'paid']);
$order->payment->update(['status' => 'Paid']);
$order->deductInventory();
```

#### Option B: إنشاء API لتصحيح تلقائي

يمكن إنشاء API إضافي يقوم بتحديث الطلبات تلقائياً:

```http
POST /api/v1/admin/payments/fix-pending
{
  "order_ids": [24, 23, 22]
}
```

---

## مثال واجهة React

```jsx
import React, { useState } from 'react';
import { Alert, Button, Table, Badge } from 'antd';

const PaymentVerificationPage = () => {
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState(null);

  const verifyPayments = async () => {
    setLoading(true);
    const response = await fetch('/api/v1/admin/payments/verify-pending', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
      }
    });
    const data = await response.json();
    setResults(data.data);
    setLoading(false);
  };

  return (
    <div>
      <h1>تصحيح الدفعات المعلقة</h1>
      
      <Button 
        type="primary" 
        onClick={verifyPayments}
        loading={loading}
        size="large"
      >
        فحص الطلبات المعلقة
      </Button>

      {results && (
        <>
          {/* ملخص النتائج */}
          <div className="summary-cards" style={{ marginTop: 20 }}>
            <div className="card">
              <h3>تم فحصه</h3>
              <p>{results.summary.total_checked}</p>
            </div>
            <div className="card alert">
              <h3>⚠️ يحتاج تصحيح</h3>
              <p>{results.summary.paid_but_not_updated}</p>
            </div>
            <div className="card">
              <h3>ما زال معلق</h3>
              <p>{results.summary.still_pending}</p>
            </div>
            <div className="card error">
              <h3>أخطاء</h3>
              <p>{results.summary.errors}</p>
            </div>
          </div>

          {/* الطلبات المدفوعة غير المحدثة */}
          {results.paid_but_not_updated.length > 0 && (
            <>
              <Alert
                type="warning"
                message={`تم العثور على ${results.paid_but_not_updated.length} طلب مدفوع لكن لم يتم تحديثه!`}
                description="هذه الطلبات تحتاج تصحيح يدوي"
                showIcon
                style={{ marginTop: 20 }}
              />

              <Table
                dataSource={results.paid_but_not_updated}
                rowKey="order_id"
                style={{ marginTop: 20 }}
                columns={[
                  {
                    title: 'رقم الطلب',
                    dataIndex: 'order_number',
                    key: 'order_number',
                  },
                  {
                    title: 'العميل',
                    key: 'customer',
                    render: (_, record) => (
                      <div>
                        <div>{record.customer_name}</div>
                        <div style={{ fontSize: 12, color: '#999' }}>
                          {record.customer_phone}
                        </div>
                      </div>
                    ),
                  },
                  {
                    title: 'المبلغ',
                    key: 'amount',
                    render: (_, record) => 
                      `${record.total_amount} ${record.currency}`,
                  },
                  {
                    title: 'تاريخ الدفع',
                    dataIndex: 'payment_date',
                    key: 'payment_date',
                  },
                  {
                    title: 'الحالة',
                    key: 'status',
                    render: () => (
                      <Badge status="success" text="مدفوع في MyFatoorah" />
                    ),
                  },
                  {
                    title: 'الإجراءات',
                    key: 'actions',
                    render: (_, record) => (
                      <Button 
                        type="primary" 
                        size="small"
                        onClick={() => fixOrder(record.order_id)}
                      >
                        تصحيح
                      </Button>
                    ),
                  },
                ]}
              />
            </>
          )}

          {/* الطلبات ما زالت معلقة */}
          {results.still_pending.length > 0 && (
            <>
              <h3 style={{ marginTop: 40 }}>طلبات ما زالت معلقة (لم يتم الدفع)</h3>
              <Table
                dataSource={results.still_pending}
                rowKey="order_id"
                columns={[
                  { title: 'رقم الطلب', dataIndex: 'order_number' },
                  { title: 'حالة MyFatoorah', dataIndex: 'invoice_status' },
                  { title: 'رقم الفاتورة', dataIndex: 'invoice_reference' },
                ]}
              />
            </>
          )}
        </>
      )}
    </div>
  );
};

const fixOrder = async (orderId) => {
  // يمكن إنشاء API منفصل للتصحيح التلقائي
  console.log('تصحيح الطلب:', orderId);
};

export default PaymentVerificationPage;
```

---

## Logs

يتم تسجيل كل عملية فحص في logs:

```
[2025-10-25 20:00:00] INFO: Starting verification of pending payments
[2025-10-25 20:00:01] INFO: Found 10 orders with awaiting_payment status
[2025-10-25 20:00:02] INFO: Checking order #24 - Invoice: 6248245
[2025-10-25 20:00:03] INFO: Checking order #23 - Invoice: 6248200
[2025-10-25 20:00:10] INFO: Verification complete. Found 2 paid orders not updated
```

---

## الخلاصة

### ✅ ما يفعله API:
1. يجلب كل الطلبات `awaiting_payment`
2. يتحقق من كل واحد عبر MyFatoorah
3. يرجع قائمة بالطلبات المدفوعة لكن لم يتم تحديثها

### ❌ ما لا يفعله API:
- **لا يغير أي شيء** في قاعدة البيانات
- **لا يحدّث الطلبات** تلقائياً
- **فقط يرجع معلومات** للمراجعة

### 🎯 الاستخدام:
- تشغيله بشكل دوري للكشف عن مشاكل
- استخدام النتائج لتصحيح يدوي أو تلقائي
- مراقبة صحة نظام الدفع

**API جاهز للاستخدام! 🚀**

