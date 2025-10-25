# نظام المخزون - Inventory System Documentation

## نظرة عامة

نظام مخزون بسيط وفعال يتيح تتبع المنتجات المخزنية بسهولة. النظام مصمم ليكون **اختياري** - المنتجات الحالية تبقى بدون مخزون افتراضياً.

---

## المميزات الرئيسية

### ✅ للمنتجات
- اختيار ما إذا كان المنتج يتتبع المخزون أم لا
- **المنتجات بدون مخزون:** يمكن طلبها دائماً (غير محدودة)
- **المنتجات بمخزون:** تتبع دقيق للكمية المتاحة

### ✅ للطلبات
- **خصم تلقائي** من المخزون عند الدفع (status = paid)
- **لا يتم التعليق** عند الطلب في حالة pending
- يمكن للعميل طلب المنتجات بدون مخزون بحرية

### ✅ للإدارة
- إحصائيات شاملة للمخزون
- تتبع جميع حركات المخزون
- تنبيهات للمنتجات قليلة المخزون
- تعديل المخزون يدوياً
- استيراد جماعي للمخزون

---

## حقول المنتج الجديدة

### في Product Model

```json
{
  "id": 1,
  "title": "صابون طبيعي",
  "price": "10.000",
  "has_inventory": true,              // هل المنتج يتتبع المخزون؟
  "stock_quantity": 50,                // الكمية المتاحة (null إذا has_inventory = false)
  "low_stock_threshold": 10,           // حد التنبيه (افتراضي 10)
  "stock_last_updated_at": "2025-10-24T12:00:00Z",
  "is_in_stock": true,                 // محسوب تلقائياً
  "is_low_stock": false,               // محسوب تلقائياً
  // ... باقي الحقول
}
```

### الحقول المحسوبة Automatically

| الحقل | الوصف | القيمة |
|-------|-------|--------|
| `is_in_stock` | هل المنتج متاح؟ | `true` إذا `has_inventory = false` أو `stock_quantity > 0` |
| `is_low_stock` | هل المخزون قليل؟ | `true` إذا `stock_quantity <= low_stock_threshold` |

---

## APIs - Admin

### 1. إحصائيات المخزون
```http
GET /api/v1/admin/inventory/statistics
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total_products_with_inventory": 45,
      "total_products_without_inventory": 30,
      "products_in_stock": 40,
      "products_out_of_stock": 5,
      "products_low_stock": 8,
      "total_stock_value": "5420.500",
      "total_stock_quantity": 1250
    },
    "low_stock_products": [
      {
        "id": 5,
        "title": "صابون اللافندر",
        "stock_quantity": 8,
        "low_stock_threshold": 10,
        "price": "12.500"
      }
    ],
    "out_of_stock_products": [
      {
        "id": 12,
        "title": "صابون الورد",
        "price": "15.000"
      }
    ]
  }
}
```

---

### 2. قائمة المنتجات مع المخزون
```http
GET /api/v1/admin/inventory/products?stock_status=low_stock&sort_by=stock_quantity&sort_order=asc&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Description | Values |
|-----------|------|-------------|--------|
| `stock_status` | string | فلترة حسب حالة المخزون | `in_stock`, `out_of_stock`, `low_stock` |
| `search` | string | البحث بالاسم | أي نص |
| `sort_by` | string | الترتيب حسب | `stock_quantity`, `title`, `price`, `created_at` |
| `sort_order` | string | اتجاه الترتيب | `asc`, `desc` |
| `per_page` | integer | عدد النتائج | افتراضي: 15 |

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "صابون طبيعي",
        "slug": "natural-soap",
        "price": "10.000",
        "has_inventory": true,
        "stock_quantity": 8,
        "low_stock_threshold": 10,
        "is_in_stock": true,
        "is_low_stock": true,
        "stock_last_updated_at": "2025-10-24T12:00:00Z",
        "category": {
          "id": 1,
          "name": "صابون"
        }
      }
    ],
    "per_page": 15,
    "total": 45
  }
}
```

---

### 3. تعديل المخزون لمنتج
```http
POST /api/v1/admin/inventory/products/{productId}/adjust
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "action": "set",           // set, increase, decrease
  "quantity": 100,           // الكمية الجديدة أو التغيير
  "reason": "purchase",      // purchase, return, adjustment, damage
  "notes": "استلام شحنة جديدة"
}
```

**Actions:**
- `set`: تحديد الكمية بشكل مطلق
- `increase`: زيادة المخزون
- `decrease`: تقليل المخزون

**Reasons:**
- `purchase`: شراء/استلام بضاعة
- `return`: إرجاع من عميل
- `adjustment`: تعديل يدوي
- `damage`: تلف/فقدان

**Response:**
```json
{
  "success": true,
  "message": "Inventory adjusted successfully",
  "data": {
    "product": {
      "id": 1,
      "title": "صابون طبيعي",
      "stock_quantity": 100,
      "is_in_stock": true,
      "is_low_stock": false
    }
  }
}
```

---

### 4. حركات المخزون لمنتج معين
```http
GET /api/v1/admin/inventory/products/{productId}/transactions?type=decrease&per_page=20
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | `increase`, `decrease`, `adjustment` |
| `reason` | string | `purchase`, `sale`, `return`, `adjustment`, `damage`, `initial_stock` |
| `start_date` | date | تاريخ البداية |
| `end_date` | date | تاريخ النهاية |
| `per_page` | integer | عدد النتائج |

**Response:**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "title": "صابون طبيعي",
      "has_inventory": true,
      "stock_quantity": 42,
      "is_in_stock": true,
      "is_low_stock": false
    },
    "transactions": {
      "current_page": 1,
      "data": [
        {
          "id": 15,
          "type": "decrease",
          "quantity": -2,
          "quantity_before": 44,
          "quantity_after": 42,
          "reason": "sale",
          "notes": "Deducted for order #1234567",
          "order": {
            "id": 123,
            "order_number": "1234567"
          },
          "user": {
            "id": 1,
            "name": "Admin"
          },
          "created_at": "2025-10-24T14:30:00Z"
        }
      ]
    }
  }
}
```

---

### 5. جميع حركات المخزون
```http
GET /api/v1/admin/inventory/transactions?product_id=1&start_date=2025-10-01&end_date=2025-10-24
Authorization: Bearer {token}
```

**Query Parameters:** نفس الفلاتر السابقة + `product_id`

---

### 6. استيراد جماعي للمخزون
```http
POST /api/v1/admin/inventory/bulk-import
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "products": [
    {
      "product_id": 1,
      "stock_quantity": 100
    },
    {
      "product_id": 2,
      "stock_quantity": 50
    }
  ],
  "notes": "تحديث المخزون الشهري"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully updated 2 products",
  "data": {
    "success_count": 2,
    "errors": []
  }
}
```

---

## إدارة المنتجات - Products Management

### إنشاء منتج مع مخزون
```http
POST /api/v1/admin/products
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "title": "صابون جديد",
  "description": "وصف المنتج",
  "price": 15.5,
  "category_id": 1,
  "images": ["url1", "url2"],
  "has_inventory": true,           // ✅ جديد
  "stock_quantity": 100,            // ✅ جديد (مطلوب إذا has_inventory = true)
  "low_stock_threshold": 20         // ✅ جديد (اختياري، افتراضي 10)
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 50,
    "title": "صابون جديد",
    "has_inventory": true,
    "stock_quantity": 100,
    "low_stock_threshold": 20,
    "is_in_stock": true,
    "is_low_stock": false
  }
}
```

---

### تعديل منتج
```http
PUT /api/v1/admin/products/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Body - تفعيل المخزون:**
```json
{
  "has_inventory": true,
  "stock_quantity": 50
}
```

**Body - تعطيل المخزون:**
```json
{
  "has_inventory": false
}
```

**Body - تحديث المخزون:**
```json
{
  "stock_quantity": 75,
  "low_stock_threshold": 15
}
```

**ملاحظة:** عند تحديث `stock_quantity` عبر هذا API، يتم إنشاء inventory transaction تلقائياً.

---

## المخزون مع العملاء

### عرض المنتجات للعملاء
```http
GET /api/v1/products
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "صابون طبيعي",
      "price": "10.000",
      "has_inventory": true,
      "stock_quantity": 42,
      "is_in_stock": true,
      "is_low_stock": false
    },
    {
      "id": 2,
      "title": "صابون بدون مخزون",
      "price": "12.000",
      "has_inventory": false,      // بدون تتبع مخزون
      "stock_quantity": null,
      "is_in_stock": true,          // دائماً متاح
      "is_low_stock": false
    }
  ]
}
```

### إنشاء طلب
```http
POST /api/v1/checkout/create-order
Content-Type: application/json
```

**Body:**
```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "+96512345678",
  "shipping_address": {...},
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

**ملاحظات:**
1. ✅ **النظام يسمح بالطلب** حتى لو المنتج `has_inventory = true` والكمية غير كافية
2. ⚠️ **الخصم يحدث فقط** عند الدفع (status = paid)
3. ✅ **المنتجات بدون مخزون** (`has_inventory = false`) تُطلب بحرية

---

## منطق الخصم من المخزون

### متى يتم الخصم؟
```
إنشاء طلب (status = pending) → لا خصم ❌
        ↓
دفع ناجح (status = paid) → خصم تلقائي ✅
```

### التنفيذ في الكود
```php
// في Order Model
public function deductInventory(): array
{
    foreach ($this->orderItems as $orderItem) {
        $product = $orderItem->product;
        
        // تخطي المنتجات بدون مخزون
        if (!$product->has_inventory) {
            continue;
        }
        
        // خصم المخزون
        $product->decreaseStock(
            $orderItem->quantity,
            $this->id,
            null,
            "Deducted for order #{$this->order_number}"
        );
    }
}
```

### في Payment Controller
```php
if ($paymentStatus['data']['InvoiceStatus'] === 'Paid') {
    $order->update(['status' => 'paid']);
    
    // خصم المخزون تلقائياً
    $order->load('orderItems.product');
    $order->deductInventory();
}
```

---

## أمثلة للـ Front-end

### عرض حالة المخزون في بطاقة المنتج

```jsx
const ProductCard = ({ product }) => {
  return (
    <div className="product-card">
      <h3>{product.title}</h3>
      <p>{product.price} KWD</p>
      
      {/* حالة المخزون */}
      {product.has_inventory ? (
        <>
          {product.is_in_stock ? (
            <>
              <span className="stock-badge in-stock">
                متوفر ({product.stock_quantity} قطعة)
              </span>
              {product.is_low_stock && (
                <span className="stock-warning">
                  ⚠️ الكمية محدودة
                </span>
              )}
            </>
          ) : (
            <span className="stock-badge out-of-stock">
              نفذت الكمية
            </span>
          )}
        </>
      ) : (
        <span className="stock-badge unlimited">
          متوفر دائماً
        </span>
      )}
      
      <button disabled={!product.is_in_stock}>
        أضف إلى السلة
      </button>
    </div>
  );
};
```

---

### صفحة إدارة المخزون - Admin

```jsx
const InventoryPage = () => {
  const [stats, setStats] = useState(null);
  const [filter, setFilter] = useState('all');
  
  useEffect(() => {
    fetchInventoryStats();
  }, []);
  
  const fetchInventoryStats = async () => {
    const response = await fetch('/api/v1/admin/inventory/statistics', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    const data = await response.json();
    setStats(data.data);
  };
  
  return (
    <div>
      <h1>إدارة المخزون</h1>
      
      {/* إحصائيات */}
      <div className="stats-grid">
        <StatCard 
          title="منتجات بمخزون"
          value={stats?.statistics.total_products_with_inventory}
        />
        <StatCard 
          title="منتجات نفذت"
          value={stats?.statistics.products_out_of_stock}
          alert
        />
        <StatCard 
          title="منتجات قليلة"
          value={stats?.statistics.products_low_stock}
          warning
        />
        <StatCard 
          title="قيمة المخزون"
          value={`${stats?.statistics.total_stock_value} KWD`}
        />
      </div>
      
      {/* تنبيهات المخزون القليل */}
      {stats?.low_stock_products.length > 0 && (
        <Alert variant="warning">
          <h3>⚠️ منتجات قليلة المخزون</h3>
          <ul>
            {stats.low_stock_products.map(p => (
              <li key={p.id}>
                {p.title} - {p.stock_quantity} قطعة متبقية
              </li>
            ))}
          </ul>
        </Alert>
      )}
      
      {/* جدول المنتجات */}
      <ProductsTable filter={filter} />
    </div>
  );
};
```

---

### تعديل المخزون - Admin

```jsx
const AdjustInventoryModal = ({ product, onSuccess }) => {
  const [action, setAction] = useState('set');
  const [quantity, setQuantity] = useState(product.stock_quantity);
  const [reason, setReason] = useState('adjustment');
  const [notes, setNotes] = useState('');
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const response = await fetch(
      `/api/v1/admin/inventory/products/${product.id}/adjust`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action,
          quantity,
          reason,
          notes
        })
      }
    );
    
    const data = await response.json();
    
    if (data.success) {
      toast.success('تم تحديث المخزون بنجاح');
      onSuccess();
    }
  };
  
  return (
    <form onSubmit={handleSubmit}>
      <h2>تعديل مخزون: {product.title}</h2>
      <p>المخزون الحالي: {product.stock_quantity}</p>
      
      <select value={action} onChange={(e) => setAction(e.target.value)}>
        <option value="set">تحديد الكمية</option>
        <option value="increase">زيادة المخزون</option>
        <option value="decrease">تقليل المخزون</option>
      </select>
      
      <input
        type="number"
        value={quantity}
        onChange={(e) => setQuantity(e.target.value)}
        placeholder="الكمية"
        required
      />
      
      <select value={reason} onChange={(e) => setReason(e.target.value)}>
        <option value="purchase">استلام بضاعة</option>
        <option value="return">إرجاع من عميل</option>
        <option value="adjustment">تعديل يدوي</option>
        <option value="damage">تلف/فقدان</option>
      </select>
      
      <textarea
        value={notes}
        onChange={(e) => setNotes(e.target.value)}
        placeholder="ملاحظات (اختياري)"
      />
      
      <button type="submit">حفظ</button>
    </form>
  );
};
```

---

## الخلاصة

### ✅ المميزات
- نظام بسيط وغير معقد
- اختياري للمنتجات
- خصم تلقائي عند الدفع فقط
- تتبع دقيق لجميع الحركات
- إحصائيات شاملة
- تنبيهات للمخزون القليل

### ✅ آمن
- المنتجات الحالية لا تتأثر (has_inventory = false افتراضياً)
- لا يتم حجز المخزون عند الطلب pending
- الخصم يحدث فقط عند الدفع الناجح

### ✅ مرن
- إمكانية تفعيل/تعطيل المخزون لأي منتج
- تعديل يدوي سهل للمخزون
- استيراد جماعي
- فلترة وبحث متقدم

**النظام جاهز للاستخدام! 🎉**

