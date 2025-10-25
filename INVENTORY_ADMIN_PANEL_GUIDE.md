# دليل لوحة التحكم - إدارة المخزون

## نظرة عامة

صفحة إدارة المخزون في لوحة التحكم تتيح للأدمن:
- ✅ عرض **جميع المنتجات** (بمخزون وبدون مخزون)
- ✅ تفعيل/تعطيل المخزون لأي منتج
- ✅ تعديل كمية المخزون مباشرة
- ✅ زيادة/نقصان المخزون
- ✅ مشاهدة الإحصائيات والتنبيهات

---

## 1. عرض جميع المنتجات مع المخزون

### API Request
```http
GET /api/v1/admin/inventory/products?page=1&per_page=20&search=صابون
Authorization: Bearer {admin_token}
```

### Query Parameters
| Parameter | Type | Required | Description | Values |
|-----------|------|----------|-------------|---------|
| `page` | integer | No | رقم الصفحة | افتراضي: 1 |
| `per_page` | integer | No | عدد النتائج في الصفحة | افتراضي: 15 |
| `search` | string | No | البحث بالاسم | أي نص |
| `stock_status` | string | No | فلترة حسب حالة المخزون | `in_stock`, `out_of_stock`, `low_stock`, `all` |
| `has_inventory` | boolean | No | فلترة حسب تتبع المخزون | `true`, `false` |
| `sort_by` | string | No | الترتيب حسب | `stock_quantity`, `title`, `price`, `created_at` |
| `sort_order` | string | No | اتجاه الترتيب | `asc`, `desc` |

### Response
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
        "currency": "KWD",
        "is_available": true,
        "has_inventory": true,
        "stock_quantity": 42,
        "low_stock_threshold": 10,
        "is_in_stock": true,
        "is_low_stock": false,
        "stock_last_updated_at": "2025-10-24T12:00:00Z",
        "category": {
          "id": 1,
          "name": "صابون",
          "slug": "soap"
        },
        "images": ["image1.jpg", "image2.jpg"],
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-24T12:00:00Z"
      },
      {
        "id": 2,
        "title": "صابون قديم",
        "slug": "old-soap",
        "price": "12.000",
        "currency": "KWD",
        "is_available": true,
        "has_inventory": false,
        "stock_quantity": null,
        "low_stock_threshold": 10,
        "is_in_stock": true,
        "is_low_stock": false,
        "stock_last_updated_at": null,
        "category": {
          "id": 1,
          "name": "صابون"
        },
        "images": ["image3.jpg"],
        "created_at": "2025-09-15T08:00:00Z",
        "updated_at": "2025-09-15T08:00:00Z"
      }
    ],
    "per_page": 20,
    "total": 75,
    "last_page": 4
  }
}
```

---

## 2. تفعيل/تعطيل المخزون لمنتج

### تفعيل المخزون
```http
PUT /api/v1/admin/products/{productId}
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
  "has_inventory": true,
  "stock_quantity": 100,
  "low_stock_threshold": 20
}
```

### تعطيل المخزون
```json
{
  "has_inventory": false
}
```

### Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "صابون طبيعي",
    "has_inventory": true,
    "stock_quantity": 100,
    "low_stock_threshold": 20,
    "is_in_stock": true,
    "is_low_stock": false
  },
  "message": "Product updated successfully"
}
```

---

## 3. تعديل كمية المخزون

### طريقة 1: تحديد الكمية بشكل مطلق (Set)
```http
POST /api/v1/admin/inventory/products/{productId}/adjust
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body:**
```json
{
  "action": "set",
  "quantity": 150,
  "reason": "adjustment",
  "notes": "تعديل المخزون بعد الجرد"
}
```

### طريقة 2: زيادة المخزون (Increase)
```json
{
  "action": "increase",
  "quantity": 50,
  "reason": "purchase",
  "notes": "استلام شحنة جديدة"
}
```

### طريقة 3: تقليل المخزون (Decrease)
```json
{
  "action": "decrease",
  "quantity": 10,
  "reason": "damage",
  "notes": "منتجات تالفة"
}
```

### Actions المتاحة
| Action | Description |
|--------|-------------|
| `set` | تحديد الكمية بشكل مطلق (يستبدل القيمة الحالية) |
| `increase` | زيادة المخزون |
| `decrease` | تقليل المخزون |

### Reasons المتاحة
| Reason | Description |
|--------|-------------|
| `purchase` | استلام بضاعة/شراء |
| `return` | إرجاع من عميل |
| `adjustment` | تعديل يدوي/جرد |
| `damage` | تلف/فقدان |

### Response
```json
{
  "success": true,
  "message": "Inventory adjusted successfully",
  "data": {
    "product": {
      "id": 1,
      "title": "صابون طبيعي",
      "stock_quantity": 150,
      "is_in_stock": true,
      "is_low_stock": false
    }
  }
}
```

---

## 4. عرض الإحصائيات

```http
GET /api/v1/admin/inventory/statistics
Authorization: Bearer {admin_token}
```

### Response
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
        "slug": "lavender-soap",
        "stock_quantity": 8,
        "low_stock_threshold": 10,
        "price": "12.500",
        "category": {
          "id": 1,
          "name": "صابون"
        }
      }
    ],
    "out_of_stock_products": [
      {
        "id": 12,
        "title": "صابون الورد",
        "slug": "rose-soap",
        "price": "15.000",
        "category": {
          "id": 1,
          "name": "صابون"
        }
      }
    ]
  }
}
```

---

## 5. عرض سجل الحركات لمنتج

```http
GET /api/v1/admin/inventory/products/{productId}/transactions?page=1&per_page=20
Authorization: Bearer {admin_token}
```

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | `increase`, `decrease`, `adjustment` |
| `reason` | string | `purchase`, `sale`, `return`, `adjustment`, `damage` |
| `start_date` | date | تاريخ البداية (YYYY-MM-DD) |
| `end_date` | date | تاريخ النهاية (YYYY-MM-DD) |
| `per_page` | integer | عدد النتائج |

### Response
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
        },
        {
          "id": 14,
          "type": "increase",
          "quantity": 50,
          "quantity_before": 44,
          "quantity_after": 94,
          "reason": "purchase",
          "notes": "استلام شحنة جديدة",
          "order": null,
          "user": {
            "id": 1,
            "name": "Admin"
          },
          "created_at": "2025-10-20T10:00:00Z"
        }
      ],
      "per_page": 20,
      "total": 45,
      "last_page": 3
    }
  }
}
```

---

## أمثلة Frontend - React/Vue

### مكون صفحة المخزون الرئيسية

```jsx
import React, { useState, useEffect } from 'react';
import { Alert, Badge, Button, Table, Modal, Input, Select } from 'antd';

const InventoryPage = () => {
  const [products, setProducts] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    search: '',
    stock_status: 'all',
    has_inventory: null,
    page: 1,
    per_page: 20
  });

  useEffect(() => {
    fetchStatistics();
    fetchProducts();
  }, [filters]);

  const fetchStatistics = async () => {
    const response = await fetch('/api/v1/admin/inventory/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
      }
    });
    const data = await response.json();
    setStats(data.data);
  };

  const fetchProducts = async () => {
    setLoading(true);
    const params = new URLSearchParams(filters);
    const response = await fetch(`/api/v1/admin/inventory/products?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
      }
    });
    const data = await response.json();
    setProducts(data.data.data);
    setLoading(false);
  };

  return (
    <div className="inventory-page">
      <h1>إدارة المخزون</h1>

      {/* الإحصائيات */}
      <StatsCards stats={stats} />

      {/* تنبيهات المخزون القليل */}
      {stats?.low_stock_products?.length > 0 && (
        <Alert
          type="warning"
          message={`⚠️ لديك ${stats.low_stock_products.length} منتج قليل المخزون`}
          description={
            <ul>
              {stats.low_stock_products.map(p => (
                <li key={p.id}>
                  {p.title} - متبقي {p.stock_quantity} قطعة
                </li>
              ))}
            </ul>
          }
        />
      )}

      {/* تنبيهات المخزون المنتهي */}
      {stats?.out_of_stock_products?.length > 0 && (
        <Alert
          type="error"
          message={`🔴 لديك ${stats.out_of_stock_products.length} منتج نفذ`}
          description={
            <ul>
              {stats.out_of_stock_products.map(p => (
                <li key={p.id}>{p.title}</li>
              ))}
            </ul>
          }
        />
      )}

      {/* الفلاتر */}
      <div className="filters">
        <Input
          placeholder="البحث..."
          value={filters.search}
          onChange={(e) => setFilters({...filters, search: e.target.value})}
        />
        
        <Select
          value={filters.stock_status}
          onChange={(value) => setFilters({...filters, stock_status: value})}
        >
          <Select.Option value="all">الكل</Select.Option>
          <Select.Option value="in_stock">متوفر</Select.Option>
          <Select.Option value="out_of_stock">نفذ</Select.Option>
          <Select.Option value="low_stock">قليل</Select.Option>
        </Select>
        
        <Select
          value={filters.has_inventory}
          onChange={(value) => setFilters({...filters, has_inventory: value})}
        >
          <Select.Option value={null}>الكل</Select.Option>
          <Select.Option value={true}>بمخزون</Select.Option>
          <Select.Option value={false}>بدون مخزون</Select.Option>
        </Select>
      </div>

      {/* جدول المنتجات */}
      <ProductsTable 
        products={products} 
        loading={loading}
        onUpdate={fetchProducts}
      />
    </div>
  );
};

export default InventoryPage;
```

---

### مكون بطاقات الإحصائيات

```jsx
const StatsCards = ({ stats }) => {
  if (!stats) return null;

  return (
    <div className="stats-grid">
      <StatCard
        title="منتجات بمخزون"
        value={stats.statistics.total_products_with_inventory}
        icon="📦"
        color="blue"
      />
      <StatCard
        title="منتجات نفذت"
        value={stats.statistics.products_out_of_stock}
        icon="🔴"
        color="red"
      />
      <StatCard
        title="منتجات قليلة"
        value={stats.statistics.products_low_stock}
        icon="⚠️"
        color="orange"
      />
      <StatCard
        title="قيمة المخزون"
        value={`${stats.statistics.total_stock_value} KWD`}
        icon="💰"
        color="green"
      />
    </div>
  );
};
```

---

### مكون جدول المنتجات

```jsx
const ProductsTable = ({ products, loading, onUpdate }) => {
  const [editModal, setEditModal] = useState(null);

  const columns = [
    {
      title: 'المنتج',
      dataIndex: 'title',
      key: 'title',
      render: (text, record) => (
        <div>
          <img src={record.images[0]} alt={text} style={{width: 50}} />
          <span>{text}</span>
        </div>
      )
    },
    {
      title: 'السعر',
      dataIndex: 'price',
      key: 'price',
      render: (price) => `${price} KWD`
    },
    {
      title: 'حالة المخزون',
      key: 'inventory_status',
      render: (_, record) => {
        if (!record.has_inventory) {
          return <Badge color="blue" text="بدون مخزون" />;
        }
        if (!record.is_in_stock) {
          return <Badge color="red" text="نفذ" />;
        }
        if (record.is_low_stock) {
          return <Badge color="orange" text="قليل" />;
        }
        return <Badge color="green" text="متوفر" />;
      }
    },
    {
      title: 'الكمية',
      dataIndex: 'stock_quantity',
      key: 'stock_quantity',
      render: (qty, record) => {
        if (!record.has_inventory) {
          return <span style={{color: '#999'}}>غير محدود</span>;
        }
        return (
          <span style={{
            color: !record.is_in_stock ? 'red' : 
                   record.is_low_stock ? 'orange' : 'green'
          }}>
            {qty} قطعة
          </span>
        );
      }
    },
    {
      title: 'الإجراءات',
      key: 'actions',
      render: (_, record) => (
        <div>
          <Button 
            onClick={() => setEditModal(record)}
            type="primary"
          >
            تعديل المخزون
          </Button>
          <Button 
            onClick={() => handleToggleInventory(record)}
            type="link"
          >
            {record.has_inventory ? 'تعطيل المخزون' : 'تفعيل المخزون'}
          </Button>
        </div>
      )
    }
  ];

  return (
    <>
      <Table
        columns={columns}
        dataSource={products}
        loading={loading}
        rowKey="id"
      />
      
      {editModal && (
        <EditInventoryModal
          product={editModal}
          onClose={() => setEditModal(null)}
          onSuccess={() => {
            setEditModal(null);
            onUpdate();
          }}
        />
      )}
    </>
  );
};
```

---

### مكون تعديل المخزون

```jsx
const EditInventoryModal = ({ product, onClose, onSuccess }) => {
  const [action, setAction] = useState('set');
  const [quantity, setQuantity] = useState(product.stock_quantity || 0);
  const [reason, setReason] = useState('adjustment');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    setLoading(true);
    
    const response = await fetch(
      `/api/v1/admin/inventory/products/${product.id}/adjust`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action,
          quantity: parseInt(quantity),
          reason,
          notes
        })
      }
    );
    
    const data = await response.json();
    setLoading(false);
    
    if (data.success) {
      message.success('تم تحديث المخزون بنجاح');
      onSuccess();
    } else {
      message.error(data.message || 'فشل تحديث المخزون');
    }
  };

  return (
    <Modal
      title={`تعديل مخزون: ${product.title}`}
      visible={true}
      onCancel={onClose}
      onOk={handleSubmit}
      confirmLoading={loading}
    >
      <div>
        <p><strong>المخزون الحالي:</strong> {product.stock_quantity} قطعة</p>
        
        <Select
          value={action}
          onChange={setAction}
          style={{width: '100%', marginBottom: 16}}
        >
          <Select.Option value="set">تحديد الكمية</Select.Option>
          <Select.Option value="increase">زيادة المخزون</Select.Option>
          <Select.Option value="decrease">تقليل المخزون</Select.Option>
        </Select>
        
        <Input
          type="number"
          value={quantity}
          onChange={(e) => setQuantity(e.target.value)}
          placeholder="الكمية"
          style={{marginBottom: 16}}
        />
        
        <Select
          value={reason}
          onChange={setReason}
          style={{width: '100%', marginBottom: 16}}
        >
          <Select.Option value="purchase">استلام بضاعة</Select.Option>
          <Select.Option value="return">إرجاع من عميل</Select.Option>
          <Select.Option value="adjustment">تعديل يدوي</Select.Option>
          <Select.Option value="damage">تلف/فقدان</Select.Option>
        </Select>
        
        <Input.TextArea
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          placeholder="ملاحظات (اختياري)"
          rows={3}
        />
      </div>
    </Modal>
  );
};
```

---

### دالة تفعيل/تعطيل المخزون

```javascript
const handleToggleInventory = async (product) => {
  const newStatus = !product.has_inventory;
  
  const body = {
    has_inventory: newStatus
  };
  
  // إذا تم التفعيل، نحتاج كمية ابتدائية
  if (newStatus) {
    const qty = prompt('أدخل الكمية الابتدائية:', '0');
    if (qty === null) return;
    body.stock_quantity = parseInt(qty);
  }
  
  const response = await fetch(
    `/api/v1/admin/products/${product.id}`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body)
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    message.success(
      newStatus 
        ? 'تم تفعيل المخزون بنجاح' 
        : 'تم تعطيل المخزون بنجاح'
    );
    onUpdate();
  }
};
```

---

## الخلاصة

### ✅ الميزات المتاحة
1. عرض جميع المنتجات (بمخزون وبدون)
2. فلترة متقدمة
3. تفعيل/تعطيل المخزون
4. تعديل الكمية بثلاث طرق (set, increase, decrease)
5. إحصائيات شاملة
6. تنبيهات تلقائية
7. سجل كامل للحركات

### ✅ APIs الجاهزة
- `GET /api/v1/admin/inventory/products` - قائمة المنتجات
- `PUT /api/v1/admin/products/{id}` - تفعيل/تعطيل المخزون
- `POST /api/v1/admin/inventory/products/{id}/adjust` - تعديل الكمية
- `GET /api/v1/admin/inventory/statistics` - الإحصائيات
- `GET /api/v1/admin/inventory/products/{id}/transactions` - السجل

**النظام جاهز للاستخدام! 🎉**

