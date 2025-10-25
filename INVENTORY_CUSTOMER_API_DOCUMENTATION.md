# توثيق APIs المخزون للعملاء

## نظرة عامة

جميع APIs المنتجات للعملاء تُرجع بيانات المخزون تلقائياً. لا حاجة لاستدعاءات إضافية.

---

## 1. جلب قائمة المنتجات

### API Request
```http
GET /api/v1/products?page=1&per_page=20&category=soap
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | رقم الصفحة (افتراضي: 1) |
| `per_page` | integer | No | عدد النتائج (افتراضي: 15) |
| `category` | string | No | Slug الفئة للفلترة |
| `search` | string | No | البحث في العنوان |
| `sort_by` | string | No | `price`, `created_at`, `title` |
| `sort_order` | string | No | `asc`, `desc` |

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
        "description": "صابون طبيعي مصنوع من زيوت عضوية",
        "short_description": "صابون طبيعي 100%",
        "price": "10.000",
        "currency": "KWD",
        "is_available": true,
        
        // ===== بيانات المخزون =====
        "has_inventory": true,
        "stock_quantity": 42,
        "is_in_stock": true,
        "is_low_stock": false,
        // ===========================
        
        // بيانات الخصومات
        "has_discount": true,
        "discount_percentage": 20,
        "discounted_price": "8.000",
        "price_before_discount": "10.000",
        "discount_amount": "2.000",
        
        "category": {
          "id": 1,
          "name": "صابون",
          "slug": "soap"
        },
        "images": [
          "https://example.com/image1.jpg",
          "https://example.com/image2.jpg"
        ],
        "meta": {
          "weight": "100g",
          "ingredients": "زيت الزيتون، زيت جوز الهند"
        },
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-24T12:00:00Z"
      },
      {
        "id": 2,
        "title": "صابون قديم (بدون تتبع مخزون)",
        "slug": "old-soap",
        "price": "12.000",
        "currency": "KWD",
        "is_available": true,
        
        // ===== منتج بدون تتبع مخزون =====
        "has_inventory": false,
        "stock_quantity": null,
        "is_in_stock": true,        // دائماً true
        "is_low_stock": false,
        // ===========================
        
        "has_discount": false,
        "discount_percentage": null,
        "discounted_price": "12.000",
        "price_before_discount": "12.000",
        "discount_amount": "0.000",
        
        "category": {
          "id": 1,
          "name": "صابون"
        },
        "images": ["https://example.com/image3.jpg"]
      }
    ],
    "per_page": 20,
    "total": 75,
    "last_page": 4
  }
}
```

---

## 2. جلب منتج واحد

### API Request
```http
GET /api/v1/products/natural-soap
```

### Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "صابون طبيعي",
    "slug": "natural-soap",
    "description": "صابون طبيعي مصنوع من زيوت عضوية...",
    "short_description": "صابون طبيعي 100%",
    "price": "10.000",
    "currency": "KWD",
    "is_available": true,
    
    // ===== بيانات المخزون =====
    "has_inventory": true,
    "stock_quantity": 42,
    "is_in_stock": true,
    "is_low_stock": false,
    // ===========================
    
    // بيانات الخصومات
    "has_discount": true,
    "discount_percentage": 20,
    "discounted_price": "8.000",
    "price_before_discount": "10.000",
    "discount_amount": "2.000",
    
    "category": {
      "id": 1,
      "name": "صابون",
      "slug": "soap",
      "description": "منتجات صابون طبيعية"
    },
    "images": [
      "https://example.com/image1.jpg",
      "https://example.com/image2.jpg"
    ],
    "meta": {
      "weight": "100g",
      "ingredients": "زيت الزيتون، زيت جوز الهند",
      "how_to_use": "يُستخدم يومياً للبشرة"
    },
    "created_at": "2025-10-01T10:00:00Z",
    "updated_at": "2025-10-24T12:00:00Z"
  }
}
```

---

## 3. حقول المخزون - الشرح التفصيلي

### `has_inventory` (boolean)
- **المعنى:** هل المنتج يتتبع المخزون؟
- **القيم:**
  - `true`: المنتج له مخزون محدد
  - `false`: المنتج متاح دائماً (بدون حدود)

### `stock_quantity` (integer | null)
- **المعنى:** الكمية المتاحة
- **القيم:**
  - رقم (مثل `42`): الكمية المتوفرة
  - `null`: المنتج بدون تتبع مخزون

### `is_in_stock` (boolean)
- **المعنى:** هل المنتج متاح للشراء؟
- **القيم:**
  - `true`: يمكن الطلب
  - `false`: نفذ ولا يمكن الطلب
- **ملاحظة:** إذا `has_inventory = false`، فهو دائماً `true`

### `is_low_stock` (boolean)
- **المعنى:** هل المخزون قليل؟
- **القيم:**
  - `true`: الكمية أقل من الحد الأدنى
  - `false`: الكمية كافية
- **ملاحظة:** فقط للمنتجات التي `has_inventory = true`

---

## 4. السيناريوهات المختلفة

### سيناريو 1: منتج بمخزون كافي
```json
{
  "title": "صابون A",
  "price": "10.000",
  "has_inventory": true,
  "stock_quantity": 100,
  "is_in_stock": true,      // ✅ متاح
  "is_low_stock": false      // ✅ المخزون كافي
}
```
**الواجهة:** ✅ زر "أضف إلى السلة" نشط

---

### سيناريو 2: منتج بمخزون قليل
```json
{
  "title": "صابون B",
  "price": "10.000",
  "has_inventory": true,
  "stock_quantity": 5,
  "is_in_stock": true,       // ✅ متاح
  "is_low_stock": true       // ⚠️ الكمية قليلة
}
```
**الواجهة:** ✅ زر "أضف إلى السلة" نشط + تنبيه "الكمية محدودة"

---

### سيناريو 3: منتج نفذ
```json
{
  "title": "صابون C",
  "price": "10.000",
  "has_inventory": true,
  "stock_quantity": 0,
  "is_in_stock": false,      // ❌ نفذ
  "is_low_stock": false
}
```
**الواجهة:** ❌ زر "نفذت الكمية" معطل

---

### سيناريو 4: منتج بدون تتبع مخزون
```json
{
  "title": "صابون D",
  "price": "10.000",
  "has_inventory": false,
  "stock_quantity": null,
  "is_in_stock": true,       // ✅ دائماً متاح
  "is_low_stock": false
}
```
**الواجهة:** ✅ زر "أضف إلى السلة" نشط (بدون تحذيرات)

---

## 5. أمثلة Frontend

### React - عرض حالة المخزون

```jsx
const ProductCard = ({ product }) => {
  const renderStockStatus = () => {
    // منتج بدون تتبع مخزون
    if (!product.has_inventory) {
      return (
        <span className="stock-status unlimited">
          متوفر دائماً
        </span>
      );
    }

    // منتج نفذ
    if (!product.is_in_stock) {
      return (
        <span className="stock-status out-of-stock">
          🔴 نفذت الكمية
        </span>
      );
    }

    // منتج قليل
    if (product.is_low_stock) {
      return (
        <div>
          <span className="stock-status low-stock">
            ⚠️ الكمية محدودة
          </span>
          <span className="stock-quantity">
            متبقي {product.stock_quantity} فقط
          </span>
        </div>
      );
    }

    // منتج متاح بكمية كافية
    return (
      <span className="stock-status in-stock">
        ✅ متوفر ({product.stock_quantity} قطعة)
      </span>
    );
  };

  return (
    <div className="product-card">
      <img src={product.images[0]} alt={product.title} />
      <h3>{product.title}</h3>
      
      {/* السعر مع الخصم */}
      {product.has_discount ? (
        <div className="price-section">
          <span className="original-price">
            {product.price_before_discount} {product.currency}
          </span>
          <span className="discounted-price">
            {product.discounted_price} {product.currency}
          </span>
          <span className="discount-badge">
            {product.discount_percentage}% خصم
          </span>
        </div>
      ) : (
        <span className="price">
          {product.price} {product.currency}
        </span>
      )}

      {/* حالة المخزون */}
      {renderStockStatus()}

      {/* زر الشراء */}
      <button
        disabled={!product.is_in_stock}
        onClick={() => addToCart(product)}
        className={!product.is_in_stock ? 'disabled' : ''}
      >
        {product.is_in_stock ? 'أضف إلى السلة' : 'نفذت الكمية'}
      </button>
    </div>
  );
};
```

---

### Vue - مكون المنتج

```vue
<template>
  <div class="product-card">
    <img :src="product.images[0]" :alt="product.title" />
    <h3>{{ product.title }}</h3>
    
    <!-- السعر -->
    <div class="price-section">
      <template v-if="product.has_discount">
        <span class="original-price">
          {{ product.price_before_discount }} {{ product.currency }}
        </span>
        <span class="discounted-price">
          {{ product.discounted_price }} {{ product.currency }}
        </span>
        <span class="discount-badge">
          {{ product.discount_percentage }}% خصم
        </span>
      </template>
      <span v-else class="price">
        {{ product.price }} {{ product.currency }}
      </span>
    </div>

    <!-- حالة المخزون -->
    <div class="stock-status">
      <!-- بدون تتبع مخزون -->
      <span v-if="!product.has_inventory" class="unlimited">
        متوفر دائماً
      </span>
      
      <!-- نفذ -->
      <span v-else-if="!product.is_in_stock" class="out-of-stock">
        🔴 نفذت الكمية
      </span>
      
      <!-- قليل -->
      <div v-else-if="product.is_low_stock" class="low-stock">
        <span>⚠️ الكمية محدودة</span>
        <span>متبقي {{ product.stock_quantity }} فقط</span>
      </div>
      
      <!-- متاح -->
      <span v-else class="in-stock">
        ✅ متوفر ({{ product.stock_quantity }} قطعة)
      </span>
    </div>

    <!-- زر الشراء -->
    <button
      :disabled="!product.is_in_stock"
      @click="addToCart(product)"
      :class="{ disabled: !product.is_in_stock }"
    >
      {{ product.is_in_stock ? 'أضف إلى السلة' : 'نفذت الكمية' }}
    </button>
  </div>
</template>

<script>
export default {
  props: ['product'],
  methods: {
    addToCart(product) {
      if (product.is_in_stock) {
        this.$store.dispatch('cart/addItem', product);
      }
    }
  }
}
</script>
```

---

### JavaScript Vanilla - دالة عرض المنتج

```javascript
function renderProduct(product) {
  const stockHTML = getStockHTML(product);
  const priceHTML = getPriceHTML(product);
  
  return `
    <div class="product-card">
      <img src="${product.images[0]}" alt="${product.title}">
      <h3>${product.title}</h3>
      ${priceHTML}
      ${stockHTML}
      <button 
        ${!product.is_in_stock ? 'disabled' : ''}
        onclick="addToCart(${product.id})"
      >
        ${product.is_in_stock ? 'أضف إلى السلة' : 'نفذت الكمية'}
      </button>
    </div>
  `;
}

function getStockHTML(product) {
  if (!product.has_inventory) {
    return '<span class="stock unlimited">متوفر دائماً</span>';
  }
  
  if (!product.is_in_stock) {
    return '<span class="stock out">🔴 نفذت الكمية</span>';
  }
  
  if (product.is_low_stock) {
    return `
      <div class="stock low">
        <span>⚠️ الكمية محدودة</span>
        <span>متبقي ${product.stock_quantity} فقط</span>
      </div>
    `;
  }
  
  return `<span class="stock in">✅ متوفر (${product.stock_quantity} قطعة)</span>`;
}

function getPriceHTML(product) {
  if (product.has_discount) {
    return `
      <div class="price-section">
        <span class="original">${product.price_before_discount} ${product.currency}</span>
        <span class="discounted">${product.discounted_price} ${product.currency}</span>
        <span class="badge">${product.discount_percentage}% خصم</span>
      </div>
    `;
  }
  
  return `<span class="price">${product.price} ${product.currency}</span>`;
}
```

---

## 6. التحقق من المخزون قبل إضافة للسلة

```javascript
function addToCart(product, quantity = 1) {
  // 1. التحقق من التوفر
  if (!product.is_in_stock) {
    showError('عذراً، هذا المنتج غير متوفر حالياً');
    return false;
  }

  // 2. التحقق من الكمية (إذا كان له مخزون)
  if (product.has_inventory && product.stock_quantity < quantity) {
    showError(`عذراً، المتوفر فقط ${product.stock_quantity} قطعة`);
    return false;
  }

  // 3. إضافة للسلة
  cart.addItem({
    product_id: product.id,
    title: product.title,
    price: product.has_discount ? product.discounted_price : product.price,
    quantity: quantity,
    image: product.images[0]
  });

  showSuccess('تم الإضافة للسلة بنجاح');
  return true;
}
```

---

## 7. CSS للتنسيق

```css
/* حالات المخزون */
.stock-status {
  margin: 10px 0;
  font-size: 14px;
}

.stock-status.unlimited {
  color: #1890ff;
  font-weight: 500;
}

.stock-status.in-stock {
  color: #52c41a;
  font-weight: 500;
}

.stock-status.low-stock {
  color: #fa8c16;
  font-weight: 500;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stock-status.out-of-stock {
  color: #f5222d;
  font-weight: 600;
}

/* الأسعار */
.price-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 10px 0;
}

.original-price {
  text-decoration: line-through;
  color: #999;
  font-size: 14px;
}

.discounted-price {
  color: #52c41a;
  font-size: 18px;
  font-weight: bold;
}

.discount-badge {
  background: #ff4d4f;
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
}

/* زر الشراء */
button {
  width: 100%;
  padding: 12px;
  background: #1890ff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s;
}

button:hover:not(:disabled) {
  background: #096dd9;
}

button:disabled {
  background: #d9d9d9;
  color: #999;
  cursor: not-allowed;
}
```

---

## الخلاصة

### ✅ للعملاء (Frontend)
1. **لا حاجة لـ APIs إضافية** - كل شيء في `GET /api/v1/products`
2. **4 حقول فقط:** `has_inventory`, `stock_quantity`, `is_in_stock`, `is_low_stock`
3. **منطق بسيط:** تحقق من `is_in_stock` فقط لتفعيل/تعطيل الشراء

### ✅ السيناريوهات
- منتج بدون مخزون → متاح دائماً
- منتج بمخزون كافي → متاح
- منتج قليل → متاح + تنبيه
- منتج نفذ → غير متاح

**التكامل سهل ومباشر! 🎉**

