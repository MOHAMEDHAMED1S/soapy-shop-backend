# Product Discounts - Quick Start Guide

## الخطوات السريعة لتشغيل نظام الخصومات

### 1. تشغيل Migration
```bash
php artisan migrate
```

هذا سينشئ جدولين:
- `product_discounts` - جدول الخصومات الرئيسي
- `product_discount_products` - جدول العلاقة بين الخصومات والمنتجات

---

### 2. اختبار API

#### أ. إنشاء خصم 50% على جميع المنتجات
```bash
curl -X POST http://localhost:8000/api/v1/admin/product-discounts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "خصم الجمعة البيضاء",
    "description": "خصم 50% على جميع المنتجات",
    "discount_type": "percentage",
    "discount_value": 50,
    "apply_to": "all_products",
    "is_active": true,
    "priority": 10
  }'
```

#### ب. إنشاء خصم 2 دينار على منتجات محددة
```bash
curl -X POST http://localhost:8000/api/v1/admin/product-discounts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "خصم الصابون الطبيعي",
    "description": "خصم 2 دينار على الصابون الطبيعي",
    "discount_type": "fixed",
    "discount_value": 2,
    "apply_to": "specific_products",
    "product_ids": [1, 2, 3],
    "is_active": true,
    "priority": 5
  }'
```

#### ج. الحصول على قائمة المنتجات (مع الخصومات)
```bash
curl http://localhost:8000/api/v1/products
```

**ستلاحظ في Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "صابون طبيعي",
      "price": "10.000",
      "has_discount": true,           // ✅ يوجد خصم
      "discount_percentage": 50,      // ✅ نسبة الخصم
      "discounted_price": "5.000",    // ✅ السعر بعد الخصم
      "price_before_discount": "10.000" // ✅ السعر الأصلي
    }
  ]
}
```

---

### 3. الاستخدام في Front-end

#### React Example - عرض المنتج مع الخصم
```jsx
function ProductCard({ product }) {
  return (
    <div className="product-card">
      <h3>{product.title}</h3>
      
      {product.has_discount ? (
        <div className="price-with-discount">
          <span className="discounted-price">
            {product.discounted_price} KWD
          </span>
          <span className="original-price">
            {product.price_before_discount} KWD
          </span>
          <span className="discount-badge">
            {Math.round(product.discount_percentage)}% OFF
          </span>
        </div>
      ) : (
        <span className="price">{product.price} KWD</span>
      )}
    </div>
  );
}
```

#### CSS للتصميم
```css
.price-with-discount {
  display: flex;
  align-items: center;
  gap: 10px;
}

.discounted-price {
  font-size: 1.5rem;
  font-weight: bold;
  color: #e74c3c;
}

.original-price {
  text-decoration: line-through;
  color: #999;
}

.discount-badge {
  background: #e74c3c;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}
```

---

### 4. واجهة الأدمن - إدارة الخصومات

#### قائمة الخصومات
```javascript
const fetchDiscounts = async () => {
  const response = await fetch('/api/v1/admin/product-discounts', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return response.json();
};
```

#### إنشاء خصم جديد
```javascript
const createDiscount = async (discountData) => {
  const response = await fetch('/api/v1/admin/product-discounts', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: discountData.name,
      discount_type: discountData.type, // 'percentage' or 'fixed'
      discount_value: discountData.value,
      apply_to: discountData.applyTo, // 'all_products' or 'specific_products'
      product_ids: discountData.productIds,
      is_active: true
    })
  });
  return response.json();
};
```

---

### 5. أمثلة عملية

#### مثال 1: خصم موسمي على جميع المنتجات
```json
{
  "name": "خصم نهاية الموسم",
  "discount_type": "percentage",
  "discount_value": 30,
  "apply_to": "all_products",
  "is_active": true,
  "starts_at": "2025-10-25 00:00:00",
  "expires_at": "2025-10-31 23:59:59"
}
```

#### مثال 2: خصم Flash Sale على منتجات محددة
```json
{
  "name": "Flash Sale - صابون طبيعي",
  "discount_type": "fixed",
  "discount_value": 3,
  "apply_to": "specific_products",
  "product_ids": [1, 2, 3, 4, 5],
  "is_active": true,
  "priority": 10
}
```

---

### 6. إدارة الخصومات

#### تفعيل/تعطيل خصم
```javascript
const toggleDiscount = async (discountId) => {
  const response = await fetch(
    `/api/v1/admin/product-discounts/${discountId}/toggle-status`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  return response.json();
};
```

#### حذف خصم
```javascript
const deleteDiscount = async (discountId) => {
  const response = await fetch(
    `/api/v1/admin/product-discounts/${discountId}`,
    {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  return response.json();
};
```

#### نسخ خصم
```javascript
const duplicateDiscount = async (discountId) => {
  const response = await fetch(
    `/api/v1/admin/product-discounts/${discountId}/duplicate`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  return response.json();
};
```

---

### 7. الإحصائيات

```javascript
const getStatistics = async () => {
  const response = await fetch(
    '/api/v1/admin/product-discounts/statistics',
    {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }
  );
  return response.json();
};
```

**Response:**
```json
{
  "total_discounts": 5,
  "active_discounts": 3,
  "products_with_discounts": 25
}
```

---

## ملاحظات مهمة

### ✅ الأولويات
- إذا كان هناك أكثر من خصم على منتج واحد، يتم تطبيق الخصم ذو الأولوية الأعلى (`priority`)

### ✅ التواريخ
- `starts_at` و `expires_at` اختياريان
- إذا كانا `null`، الخصم يكون دائماً نشط (ما دام `is_active = true`)

### ✅ الأنواع
- **percentage**: نسبة مئوية (مثال: 50 = 50%)
- **fixed**: مبلغ ثابت (مثال: 3 = 3 KWD)

### ✅ النطاق
- **all_products**: على جميع المنتجات
- **specific_products**: على منتجات محددة (يجب تحديد `product_ids`)

---

## الخطوة التالية

راجع التوثيق الكامل في: `PRODUCT_DISCOUNTS_DOCUMENTATION.md`

**النظام جاهز! 🚀**

