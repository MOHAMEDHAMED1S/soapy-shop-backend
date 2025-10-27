# إصلاح حساب عدد الطلبات للعملاء ✅

**التاريخ:** 2025-10-27  
**الحالة:** ✅ تم الإصلاح

---

## ❌ المشكلة

في `/api/v1/admin/customers`، كان يتم إرجاع **عدد كل الطلبات** لكل عميل، بدلاً من إرجاع عدد الطلبات **المدفوعة والمشحونة والمسلمة** فقط.

### مثال:

عميل لديه:
- 28 طلب إجمالي
- 10 طلبات pending
- 5 طلبات awaiting_payment
- 13 طلب مدفوعة (paid/shipped/delivered)

**كان يُرجع:** `total_orders = 28` ❌  
**يجب أن يُرجع:** `total_orders = 13` ✅

---

## ✅ الحل

تم تعديل `withCount` و `withSum` و `withAvg` لتصفية الطلبات حسب الحالة:

```php
$paidStatuses = ['paid', 'shipped', 'delivered'];

$query = Customer::with(['latestOrder'])
    ->withCount(['orders as total_orders' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }])
    ->withSum(['orders as calculated_total_spent' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount')
    ->withAvg(['orders as calculated_average_order_value' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount');
```

---

## 🔧 الملفات المُعدلة

### 1. CustomerController.php

**الملف:** `app/Http/Controllers/Api/Admin/CustomerController.php`  

#### Method: `index()` - Query للعملاء
**السطور:** 27-39

**قبل:**
```php
$query = Customer::with(['latestOrder'])
    ->withCount('orders as total_orders')
    ->withSum('orders as calculated_total_spent', 'total_amount')
    ->withAvg('orders as calculated_average_order_value', 'total_amount');
```

**بعد:**
```php
$paidStatuses = ['paid', 'shipped', 'delivered'];

$query = Customer::with(['latestOrder'])
    ->withCount(['orders as total_orders' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }])
    ->withSum(['orders as calculated_total_spent' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount')
    ->withAvg(['orders as calculated_average_order_value' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount');
```

#### Method: `index()` - Summary Statistics
**السطور:** 115-117

**قبل:**
```php
'average_customer_value' => Customer::withSum('orders', 'total_amount')
    ->get()
    ->avg('orders_sum_total_amount') ?? 0,
```

**بعد:**
```php
'average_customer_value' => Customer::withSum(['orders as paid_orders_sum' => function($query) use ($revenueStatuses) {
    $query->whereIn('status', $revenueStatuses);
}], 'total_amount')->get()->avg('paid_orders_sum') ?? 0,
```

---

### 2. CustomerService.php

**الملف:** `app/Services/CustomerService.php`  

#### Method: `getCustomerDetails()`
**السطور:** 203-215

**قبل:**
```php
$customer = Customer::with(['orders.orderItems.product', 'latestOrder'])
    ->withCount('orders as calculated_total_orders')
    ->withSum('orders as calculated_total_spent', 'total_amount')
    ->withAvg('orders as calculated_average_order_value', 'total_amount')
    ->find($customerId);
```

**بعد:**
```php
$paidStatuses = ['paid', 'shipped', 'delivered'];

$customer = Customer::with(['orders.orderItems.product', 'latestOrder'])
    ->withCount(['orders as calculated_total_orders' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }])
    ->withSum(['orders as calculated_total_spent' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount')
    ->withAvg(['orders as calculated_average_order_value' => function($query) use ($paidStatuses) {
        $query->whereIn('status', $paidStatuses);
    }], 'total_amount')
    ->find($customerId);
```

#### Method: `searchCustomers()`
**السطور:** 248-264

تم تطبيق نفس التعديل.

---

## 📊 الطلبات المحسوبة

### ✅ يتم حسابها:
```php
$paidStatuses = ['paid', 'shipped', 'delivered'];
```
- `paid` - مدفوع
- `shipped` - تم الشحن
- `delivered` - تم التسليم

### ❌ لا يتم حسابها:
- `pending` - معلق
- `awaiting_payment` - في انتظار الدفع
- `cancelled` - ملغي

---

## 🧪 مثال اختبار

### البيانات:
```
العميل: محمد حامد
  • كل الطلبات: 28
  • paid: 8
  • shipped: 3
  • delivered: 2
  • pending: 10
  • awaiting_payment: 5
```

### النتيجة:

**قبل الإصلاح:**
```json
{
  "id": 8,
  "name": "محمد حامد",
  "total_orders": 28,        // ❌ كل الطلبات
  "total_spent": 2500.000,   // من كل الطلبات
  "average_order_value": 89.3
}
```

**بعد الإصلاح:**
```json
{
  "id": 8,
  "name": "محمد حامد",
  "total_orders": 13,        // ✅ المدفوعة فقط (8+3+2)
  "total_spent": 1200.000,   // من الطلبات المدفوعة فقط
  "average_order_value": 92.3
}
```

### Summary Statistics:

**قبل الإصلاح:**
```
average_customer_value = 995.175 د.ك  // ❌ من كل الطلبات (29 طلب)
```

**بعد الإصلاح:**
```
average_customer_value = 471.000 د.ك  // ✅ من الطلبات المدفوعة فقط (14 طلب)
```

**الفرق:** 524.175 د.ك (الطلبات غير المدفوعة)

---

## 📝 APIs المتأثرة

### 1. GET /api/v1/admin/customers

```bash
GET /api/v1/admin/customers?page=1&per_page=15
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customers": {
      "data": [
        {
          "id": 8,
          "name": "محمد حامد",
          "phone": "+96512345678",
          "email": "customer@example.com",
          "total_orders": 13,           // ✅ المدفوعة فقط
          "total_spent": 1200.000,      // ✅ من الطلبات المدفوعة
          "average_order_value": 92.3   // ✅ متوسط الطلبات المدفوعة
        }
      ]
    },
    "summary": {
      "total_customers": 150,
      "active_customers": 120,
      "vip_customers": 25,
      "new_customers": 10,
      "total_revenue": 45000.000,
      "average_customer_value": 300.000    // ✅ من الطلبات المدفوعة فقط
    }
  }
}
```

---

### 2. GET /api/v1/admin/customers/{id}

```bash
GET /api/v1/admin/customers/8
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 8,
      "name": "محمد حامد",
      "total_orders": 13,           // ✅ المدفوعة فقط
      "total_spent": 1200.000,
      "average_order_value": 92.3
    },
    "order_history": [...],
    "statistics": {
      "total_orders": 13,
      "total_spent": 1200.000,
      "average_order_value": 92.3,
      "is_vip": true,
      "is_new": false
    }
  }
}
```

---

### 3. GET /api/v1/admin/customers/search

```bash
GET /api/v1/admin/customers/search?query=محمد
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 8,
      "name": "محمد حامد",
      "total_orders": 13,           // ✅ المدفوعة فقط
      "total_spent": 1200.000,
      "average_order_value": 92.3
    }
  ]
}
```

---

## 💻 Frontend Integration

### TypeScript Interface:

```typescript
interface Customer {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  total_orders: number;         // عدد الطلبات المدفوعة فقط
  total_spent: number;          // مجموع الطلبات المدفوعة
  average_order_value: number;  // متوسط الطلبات المدفوعة
  last_order_at: string | null;
  created_at: string;
  is_vip: boolean;
  is_active: boolean;
}
```

### React Example:

```tsx
const CustomerList = () => {
  const [customers, setCustomers] = useState<Customer[]>([]);

  useEffect(() => {
    fetch('/api/v1/admin/customers')
      .then(res => res.json())
      .then(data => setCustomers(data.data.customers.data));
  }, []);

  return (
    <table>
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الطلبات</th>
          <th>الإنفاق الكلي</th>
          <th>متوسط الطلب</th>
        </tr>
      </thead>
      <tbody>
        {customers.map(customer => (
          <tr key={customer.id}>
            <td>{customer.name}</td>
            <td>{customer.total_orders}</td> {/* المدفوعة فقط ✅ */}
            <td>{customer.total_spent.toFixed(3)} د.ك</td>
            <td>{customer.average_order_value.toFixed(3)} د.ك</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};
```

---

## ⚠️ ملاحظات هامة

### 1. البيانات المُحسوبة

جميع الإحصائيات الآن تُحسب **ديناميكياً** من قاعدة البيانات:
```php
withCount(['orders as total_orders' => ...])  // ✅ ديناميكي
withSum(['orders as total_spent' => ...])     // ✅ ديناميكي
withAvg(['orders as average_order_value' => ...])  // ✅ ديناميكي
```

### 2. الحقول المخزنة

قد توجد حقول مخزنة في جدول `customers`:
- `total_orders` (عمود مُخزن)
- `total_spent` (عمود مُخزن)
- `average_order_value` (عمود مُخزن)

**ولكن:** النظام الآن يستخدم القيم **الديناميكية المحسوبة** وليس القيم المخزنة.

### 3. الأداء

استخدام `withCount`, `withSum`, `withAvg` أفضل من:
- تخزين القيم في الجدول (قد تكون قديمة)
- حساب القيم في حلقات PHP (بطيء)

هذه الطريقة تُنفذ كـ `JOIN` في SQL وتكون سريعة جداً.

---

## ✅ التحقق

### Test Query:

```sql
SELECT 
  c.id,
  c.name,
  COUNT(CASE WHEN o.status IN ('paid', 'shipped', 'delivered') THEN 1 END) as paid_orders,
  COUNT(*) as all_orders
FROM customers c
LEFT JOIN orders o ON c.id = o.customer_id
GROUP BY c.id, c.name
HAVING paid_orders > 0
ORDER BY paid_orders DESC
LIMIT 5;
```

### في API:

```bash
# اختبار
curl -X GET "http://localhost:8000/api/v1/admin/customers?per_page=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎉 الخلاصة

### تم الإصلاح:

1. ✅ `total_orders` الآن يحسب **الطلبات المدفوعة فقط** (paid, shipped, delivered)
2. ✅ `total_spent` يحسب **مجموع الطلبات المدفوعة فقط**
3. ✅ `average_order_value` يحسب **متوسط الطلبات المدفوعة فقط**
4. ✅ **Summary: `average_customer_value`** يحسب من **الطلبات المدفوعة فقط**
5. ✅ الحسابات ديناميكية ودقيقة
6. ✅ تم تطبيق الإصلاح على جميع APIs

### APIs المتأثرة:

- ✅ `GET /api/v1/admin/customers`
- ✅ `GET /api/v1/admin/customers/{id}`
- ✅ `GET /api/v1/admin/customers/search`

---

**🎉 تم حل المشكلة بالكامل!**

