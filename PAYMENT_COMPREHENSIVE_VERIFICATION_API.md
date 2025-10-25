# API التحقق الشامل من الدفعات 🔍✨

## نظرة عامة

API متقدم للتحقق من صحة حالات الدفع لجميع الطلبات مقابل MyFatoorah.
يفحص قسمين رئيسيين بشكل منفصل ومنظم.

---

## Endpoint

```http
GET /api/v1/admin/payments/verify-pending
Authorization: Bearer {admin_token}
```

---

## Response Structure - النسخة المحسّنة

### Overall Response

```json
{
  "success": true,
  "data": {
    "overall_summary": {
      "total_orders_checked": 45,
      "critical_issues_found": 5,
      "verification_timestamp": "2025-10-25 22:00:00"
    },
    "awaiting_payment_section": {
      "summary": { /* ... */ },
      "critical_issues": [ /* ... */ ],
      "correctly_pending": [ /* ... */ ],
      "errors": [ /* ... */ ]
    },
    "completed_orders_section": {
      "summary": { /* ... */ },
      "critical_issues": [ /* ... */ ],
      "correctly_paid": [ /* ... */ ],
      "errors": [ /* ... */ ]
    }
  },
  "message": "Comprehensive payment verification completed successfully"
}
```

---

## القسم الأول: الطلبات في انتظار الدفع

### `awaiting_payment_section`

يفحص جميع الطلبات التي حالتها `awaiting_payment` ويتحقق من MyFatoorah:

```json
{
  "awaiting_payment_section": {
    "summary": {
      "total_checked": 19,
      "paid_but_not_updated": 2,
      "correctly_pending": 15,
      "errors": 2
    },
    "critical_issues": [
      {
        "order_id": 4,
        "order_number": "9451268",
        "customer_name": "محمد حامد",
        "customer_phone": "232434962",
        "customer_email": "user@example.com",
        "total_amount": "52.200",
        "currency": "KWD",
        "invoice_reference": "6163966",
        "database_status": "awaiting_payment",
        "myfatoorah_status": "Paid",
        "order_created_at": "2025-10-23 10:00:00",
        "payment_date": "2025-10-23T10:05:00",
        "items_count": 2,
        "issue": "PAID_BUT_NOT_UPDATED",
        "severity": "CRITICAL"
      }
    ],
    "correctly_pending": [
      {
        "order_id": 3,
        "order_number": "4145484",
        "myfatoorah_status": "Pending",
        "invoice_reference": "6227847"
      }
    ],
    "errors": [
      {
        "order_id": 8,
        "order_number": "6268033",
        "invoice_reference": "6164046",
        "error": "Connection timeout"
      }
    ]
  }
}
```

### Summary Fields - القسم الأول

| Field | Type | Description |
|-------|------|-------------|
| `total_checked` | integer | عدد طلبات awaiting_payment المفحوصة |
| `paid_but_not_updated` | integer | **🔴 مدفوعة لكن لم يتم تحديثها** |
| `correctly_pending` | integer | ✅ صحيحة ومازالت معلقة |
| `errors` | integer | أخطاء أثناء الفحص |

### Critical Issues - القسم الأول

**مشاكل خطيرة:** طلبات مدفوعة في MyFatoorah لكن قاعدة البيانات تقول awaiting_payment!

| Field | Description |
|-------|-------------|
| `issue` | `PAID_BUT_NOT_UPDATED` |
| `severity` | `CRITICAL` |
| `database_status` | `awaiting_payment` |
| `myfatoorah_status` | `Paid` ✅ |

**الإجراء المطلوب:** تحديث الطلب إلى "paid"

---

## القسم الثاني: الطلبات المكتملة

### `completed_orders_section`

يفحص جميع الطلبات التي حالتها `paid`, `shipped`, أو `delivered` ويتحقق من MyFatoorah:

```json
{
  "completed_orders_section": {
    "summary": {
      "total_checked": 26,
      "correctly_paid": 23,
      "not_paid_but_marked": 3,
      "errors": 0
    },
    "critical_issues": [
      {
        "order_id": 15,
        "order_number": "7809666",
        "customer_name": "سارة أحمد",
        "customer_phone": "96512345678",
        "customer_email": "sara@example.com",
        "total_amount": "35.000",
        "currency": "KWD",
        "invoice_reference": "6228056",
        "database_status": "delivered",
        "myfatoorah_status": "Pending",
        "order_created_at": "2025-10-20 15:00:00",
        "items_count": 3,
        "issue": "MARKED_AS_PAID_BUT_NOT_PAID",
        "severity": "CRITICAL"
      }
    ],
    "correctly_paid": [
      {
        "order_id": 24,
        "order_number": "9355503",
        "database_status": "paid",
        "myfatoorah_status": "Paid",
        "verified": true
      }
    ],
    "errors": []
  }
}
```

### Summary Fields - القسم الثاني

| Field | Type | Description |
|-------|------|-------------|
| `total_checked` | integer | عدد الطلبات المكتملة المفحوصة |
| `correctly_paid` | integer | ✅ مدفوعة بشكل صحيح |
| `not_paid_but_marked` | integer | **🔴 موضوعة كمدفوعة لكن ليست مدفوعة!** |
| `errors` | integer | أخطاء أثناء الفحص |

### Critical Issues - القسم الثاني

**مشاكل خطيرة جداً:** طلبات محدّدة كـ paid/shipped/delivered لكن MyFatoorah تقول NOT Paid!

| Field | Description |
|-------|-------------|
| `issue` | `MARKED_AS_PAID_BUT_NOT_PAID` |
| `severity` | `CRITICAL` |
| `database_status` | `paid` / `shipped` / `delivered` |
| `myfatoorah_status` | `Pending` / `Failed` ❌ |

**الإجراء المطلوب:** مراجعة فورية! قد تكون عملية احتيال أو خطأ كبير!

---

## Overall Summary

```json
{
  "overall_summary": {
    "total_orders_checked": 45,
    "critical_issues_found": 5,
    "verification_timestamp": "2025-10-25 22:00:00"
  }
}
```

| Field | Description |
|-------|-------------|
| `total_orders_checked` | إجمالي الطلبات المفحوصة (كلا القسمين) |
| `critical_issues_found` | **عدد المشاكل الخطيرة الكلي** |
| `verification_timestamp` | وقت إجراء الفحص |

---

## Issue Types & Severity

### 🔴 PAID_BUT_NOT_UPDATED
- **الوصف:** طلب مدفوع في MyFatoorah لكن قاعدة البيانات تقول awaiting_payment
- **الخطورة:** CRITICAL
- **الحل:** تحديث status إلى paid + خصم مخزون

### 🔴 MARKED_AS_PAID_BUT_NOT_PAID
- **الوصف:** طلب محدّد كـ paid/shipped/delivered لكن MyFatoorah تقول NOT Paid
- **الخطورة:** CRITICAL
- **الحل:** مراجعة فورية + تحقيق + إيقاف الشحن إن أمكن

---

## مثال React - واجهة محسّنة

```jsx
import React, { useState } from 'react';
import { Alert, Button, Tabs, Table, Badge, Statistic, Row, Col } from 'antd';
import { WarningOutlined, CheckCircleOutlined } from '@ant-design/icons';

const ComprehensivePaymentVerification = () => {
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState(null);

  const verifyAllPayments = async () => {
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
    <div style={{ padding: 20 }}>
      <h1>🔍 التحقق الشامل من الدفعات</h1>
      <p>فحص شامل لجميع الطلبات مقابل MyFatoorah</p>

      <Button 
        type="primary" 
        size="large"
        onClick={verifyAllPayments}
        loading={loading}
        icon={<CheckCircleOutlined />}
      >
        بدء الفحص الشامل
      </Button>

      {results && (
        <>
          {/* Overall Summary */}
          <div style={{ marginTop: 30 }}>
            <h2>📊 الملخص العام</h2>
            <Row gutter={16}>
              <Col span={8}>
                <Statistic 
                  title="إجمالي الطلبات المفحوصة"
                  value={results.overall_summary.total_orders_checked}
                />
              </Col>
              <Col span={8}>
                <Statistic 
                  title="مشاكل خطيرة وُجدت"
                  value={results.overall_summary.critical_issues_found}
                  valueStyle={{ color: results.overall_summary.critical_issues_found > 0 ? '#cf1322' : '#3f8600' }}
                  prefix={results.overall_summary.critical_issues_found > 0 ? <WarningOutlined /> : <CheckCircleOutlined />}
                />
              </Col>
              <Col span={8}>
                <Statistic 
                  title="وقت الفحص"
                  value={results.overall_summary.verification_timestamp}
                  valueStyle={{ fontSize: 14 }}
                />
              </Col>
            </Row>
          </div>

          {/* Tabs for two sections */}
          <Tabs defaultActiveKey="1" style={{ marginTop: 30 }}>
            {/* Tab 1: Awaiting Payment */}
            <Tabs.TabPane 
              tab={
                <span>
                  🕐 في انتظار الدفع
                  {results.awaiting_payment_section.summary.paid_but_not_updated > 0 && (
                    <Badge 
                      count={results.awaiting_payment_section.summary.paid_but_not_updated} 
                      style={{ marginLeft: 8, backgroundColor: '#ff4d4f' }}
                    />
                  )}
                </span>
              } 
              key="1"
            >
              <AwaitingPaymentSection data={results.awaiting_payment_section} />
            </Tabs.TabPane>

            {/* Tab 2: Completed Orders */}
            <Tabs.TabPane 
              tab={
                <span>
                  ✅ الطلبات المكتملة
                  {results.completed_orders_section.summary.not_paid_but_marked > 0 && (
                    <Badge 
                      count={results.completed_orders_section.summary.not_paid_but_marked} 
                      style={{ marginLeft: 8, backgroundColor: '#ff4d4f' }}
                    />
                  )}
                </span>
              } 
              key="2"
            >
              <CompletedOrdersSection data={results.completed_orders_section} />
            </Tabs.TabPane>
          </Tabs>
        </>
      )}
    </div>
  );
};

// Component for Awaiting Payment Section
const AwaitingPaymentSection = ({ data }) => {
  return (
    <div>
      {/* Summary Cards */}
      <Row gutter={16} style={{ marginBottom: 20 }}>
        <Col span={6}>
          <Statistic title="تم فحصه" value={data.summary.total_checked} />
        </Col>
        <Col span={6}>
          <Statistic 
            title="🔴 مدفوع لكن لم يحدّث"
            value={data.summary.paid_but_not_updated}
            valueStyle={{ color: '#cf1322' }}
          />
        </Col>
        <Col span={6}>
          <Statistic 
            title="✅ صحيح ومعلق"
            value={data.summary.correctly_pending}
            valueStyle={{ color: '#3f8600' }}
          />
        </Col>
        <Col span={6}>
          <Statistic 
            title="أخطاء"
            value={data.summary.errors}
          />
        </Col>
      </Row>

      {/* Critical Issues */}
      {data.critical_issues.length > 0 && (
        <>
          <Alert
            type="error"
            message={`⚠️ تنبيه: ${data.critical_issues.length} طلب مدفوع لكن لم يتم تحديثه!`}
            description="هذه الطلبات تم دفعها في MyFatoorah لكن حالتها في قاعدة البيانات ما زالت 'awaiting_payment'"
            showIcon
            style={{ marginBottom: 20 }}
          />

          <Table
            dataSource={data.critical_issues}
            rowKey="order_id"
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
                    <div><strong>{record.customer_name}</strong></div>
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
                title: 'الحالة',
                key: 'status',
                render: (_, record) => (
                  <div>
                    <div>
                      <Badge status="error" text={`DB: ${record.database_status}`} />
                    </div>
                    <div>
                      <Badge status="success" text={`MF: ${record.myfatoorah_status}`} />
                    </div>
                  </div>
                ),
              },
              {
                title: 'تاريخ الدفع',
                dataIndex: 'payment_date',
                key: 'payment_date',
              },
              {
                title: 'الإجراءات',
                key: 'actions',
                render: (_, record) => (
                  <Button 
                    type="primary" 
                    danger
                    size="small"
                    onClick={() => fixOrder(record.order_id)}
                  >
                    تصحيح فوري
                  </Button>
                ),
              },
            ]}
          />
        </>
      )}
    </div>
  );
};

// Component for Completed Orders Section
const CompletedOrdersSection = ({ data }) => {
  return (
    <div>
      {/* Summary Cards */}
      <Row gutter={16} style={{ marginBottom: 20 }}>
        <Col span={8}>
          <Statistic title="تم فحصه" value={data.summary.total_checked} />
        </Col>
        <Col span={8}>
          <Statistic 
            title="✅ صحيح ومدفوع"
            value={data.summary.correctly_paid}
            valueStyle={{ color: '#3f8600' }}
          />
        </Col>
        <Col span={8}>
          <Statistic 
            title="🔴 موضوع كمدفوع لكن ليس مدفوع"
            value={data.summary.not_paid_but_marked}
            valueStyle={{ color: '#cf1322' }}
          />
        </Col>
      </Row>

      {/* Critical Issues */}
      {data.critical_issues.length > 0 && (
        <>
          <Alert
            type="error"
            message={`🚨 خطر: ${data.critical_issues.length} طلب محدّد كمدفوع لكنه ليس مدفوعاً!`}
            description="هذه مشكلة خطيرة جداً! هذه الطلبات موضوعة كـ paid/shipped/delivered لكن MyFatoorah تقول أنها ليست مدفوعة!"
            showIcon
            style={{ marginBottom: 20 }}
          />

          <Table
            dataSource={data.critical_issues}
            rowKey="order_id"
            columns={[
              {
                title: 'رقم الطلب',
                dataIndex: 'order_number',
                key: 'order_number',
                render: (text) => <strong style={{ color: '#cf1322' }}>{text}</strong>
              },
              {
                title: 'العميل',
                key: 'customer',
                render: (_, record) => (
                  <div>
                    <div><strong>{record.customer_name}</strong></div>
                    <div style={{ fontSize: 12 }}>{record.customer_phone}</div>
                  </div>
                ),
              },
              {
                title: 'المبلغ',
                key: 'amount',
                render: (_, record) => 
                  <strong>{record.total_amount} {record.currency}</strong>,
              },
              {
                title: 'الحالة',
                key: 'status',
                render: (_, record) => (
                  <div>
                    <div>
                      <Badge status="success" text={`DB: ${record.database_status}`} />
                    </div>
                    <div>
                      <Badge status="error" text={`MF: ${record.myfatoorah_status}`} />
                    </div>
                  </div>
                ),
              },
              {
                title: 'الإجراءات',
                key: 'actions',
                render: (_, record) => (
                  <Button 
                    type="primary" 
                    danger
                    size="small"
                    onClick={() => investigateOrder(record.order_id)}
                  >
                    تحقيق فوري
                  </Button>
                ),
              },
            ]}
          />
        </>
      )}
    </div>
  );
};

const fixOrder = (orderId) => {
  console.log('تصحيح الطلب:', orderId);
  // يمكن استخدام callback URL أو API منفصل
};

const investigateOrder = (orderId) => {
  console.log('التحقيق في الطلب:', orderId);
  // فتح modal للتحقيق
};

export default ComprehensivePaymentVerification;
```

---

## Use Cases

### 1️⃣ فحص دوري (مُوصى به)

```javascript
// تشغيل كل 6 ساعات
setInterval(async () => {
  const result = await api.get('/admin/payments/verify-pending');
  
  if (result.data.overall_summary.critical_issues_found > 0) {
    sendAdminAlert(`⚠️ وُجد ${result.data.overall_summary.critical_issues_found} مشكلة خطيرة!`);
  }
}, 6 * 60 * 60 * 1000);
```

---

### 2️⃣ فحص بعد مشكلة SMTP

```javascript
// بعد إصلاح مشكلة SMTP، تحقق من الطلبات المفقودة
async function checkMissedPayments() {
  const result = await api.get('/admin/payments/verify-pending');
  
  const missedPayments = result.data.awaiting_payment_section.critical_issues;
  
  for (const order of missedPayments) {
    await fixPaymentCallback(order.order_id);
  }
}
```

---

### 3️⃣ تقرير يومي

```javascript
// إرسال تقرير يومي للإدارة
async function generateDailyReport() {
  const result = await api.get('/admin/payments/verify-pending');
  
  const report = `
    📊 تقرير التحقق اليومي من الدفعات
    
    ✅ الطلبات المفحوصة: ${result.data.overall_summary.total_orders_checked}
    🔴 مشاكل خطيرة: ${result.data.overall_summary.critical_issues_found}
    
    القسم الأول (في انتظار الدفع):
    - مدفوع لكن لم يحدّث: ${result.data.awaiting_payment_section.summary.paid_but_not_updated}
    
    القسم الثاني (الطلبات المكتملة):
    - موضوع كمدفوع لكن ليس مدفوع: ${result.data.completed_orders_section.summary.not_paid_but_marked}
  `;
  
  sendEmailToAdmin(report);
}
```

---

## الخلاصة

### ✅ الميزات الجديدة:
1. **قسمان منفصلان ومنظمان**
   - awaiting_payment orders
   - completed orders (paid/shipped/delivered)

2. **إحصائيات شاملة**
   - overall summary
   - summary لكل قسم
   - issue types واضحة

3. **severity levels**
   - CRITICAL issues مميزة
   - معلومات مفصلة لكل مشكلة

4. **أنواع مشاكل واضحة**
   - PAID_BUT_NOT_UPDATED
   - MARKED_AS_PAID_BUT_NOT_PAID

### 🎯 الاستخدامات:
- فحص دوري للنظام
- كشف الاحتيال
- تدقيق المدفوعات
- تقارير للإدارة

**API شامل ومتقدم وجاهز! 🚀✨**

