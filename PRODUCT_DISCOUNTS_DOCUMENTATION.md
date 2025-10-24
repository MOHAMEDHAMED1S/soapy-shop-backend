# Product Discounts System - نظام خصومات المنتجات

## نظرة عامة

نظام شامل لإدارة الخصومات على المنتجات في متجر Soapy Shop. يسمح للمدير بإنشاء خصومات على كل المنتجات أو منتجات محددة بنسبة مئوية أو مبلغ ثابت.

---

## الميزات الرئيسية

### ✅ للأدمن (لوحة التحكم)
- إنشاء وتعديل وحذف الخصومات
- تحديد نوع الخصم (نسبة مئوية أو مبلغ ثابت)
- تطبيق الخصم على كل المنتجات أو منتجات محددة
- جدولة الخصومات (تاريخ بداية وانتهاء)
- تفعيل/تعطيل الخصومات
- نسخ الخصومات
- عرض المنتجات المتأثرة بالخصم
- إحصائيات شاملة

### ✅ للعملاء (واجهة المستخدم)
- عرض السعر الأصلي والسعر بعد الخصم
- عرض نسبة الخصم على كل منتج
- تطبيق تلقائي للخصومات في السلة

---

## هيكل قاعدة البيانات

### جدول `product_discounts`
```sql
- id: bigint (PK)
- name: string (اسم الخصم)
- description: text (وصف الخصم)
- discount_type: enum['percentage', 'fixed'] (نوع الخصم)
- discount_value: decimal(10,3) (قيمة الخصم)
- apply_to: enum['all_products', 'specific_products'] (نطاق التطبيق)
- is_active: boolean (نشط/غير نشط)
- starts_at: timestamp (تاريخ البداية)
- expires_at: timestamp (تاريخ الانتهاء)
- priority: integer (الأولوية)
- created_at, updated_at
```

### جدول `product_discount_products` (علاقة Many-to-Many)
```sql
- id: bigint (PK)
- product_discount_id: bigint (FK)
- product_id: bigint (FK)
- created_at, updated_at
```

---

## Admin APIs - للوحة التحكم

جميع الـ APIs تحتاج إلى JWT authentication و admin middleware.

### 1. الحصول على قائمة الخصومات
```http
GET /api/v1/admin/product-discounts
```

**Query Parameters:**
```javascript
{
  page: 1,                 // رقم الصفحة
  per_page: 15,            // عدد العناصر في الصفحة
  status: 'active',        // active, inactive, expired, upcoming
  discount_type: 'percentage', // percentage, fixed
  apply_to: 'all_products',    // all_products, specific_products
  search: 'خصم',          // البحث في الاسم والوصف
  sort_by: 'priority',     // حقل الترتيب
  sort_direction: 'desc'   // asc, desc
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "discounts": {
      "data": [
        {
          "id": 1,
          "name": "خصم الجمعة البيضاء",
          "description": "خصم 50% على جميع المنتجات",
          "discount_type": "percentage",
          "discount_value": "50.000",
          "apply_to": "all_products",
          "is_active": true,
          "starts_at": "2025-10-25T00:00:00.000000Z",
          "expires_at": "2025-10-27T23:59:59.000000Z",
          "priority": 10,
          "formatted_discount": "50%",
          "status_text": "نشط",
          "products": [],
          "created_at": "2025-10-24T12:00:00.000000Z",
          "updated_at": "2025-10-24T12:00:00.000000Z"
        }
      ],
      "current_page": 1,
      "per_page": 15,
      "total": 5
    },
    "summary": {
      "total_discounts": 5,
      "active_discounts": 3,
      "all_products_discounts": 1,
      "specific_products_discounts": 2
    }
  }
}
```

### 2. إنشاء خصم جديد
```http
POST /api/v1/admin/product-discounts
```

**Request Body:**
```json
{
  "name": "خصم الجمعة البيضاء",
  "description": "خصم 50% على جميع المنتجات",
  "discount_type": "percentage",   // percentage أو fixed
  "discount_value": 50,
  "apply_to": "all_products",      // all_products أو specific_products
  "product_ids": [],               // مطلوب فقط إذا apply_to = specific_products
  "is_active": true,
  "starts_at": "2025-10-25 00:00:00",
  "expires_at": "2025-10-27 23:59:59",
  "priority": 10
}
```

**مثال لخصم على منتجات محددة:**
```json
{
  "name": "خصم الصابون الطبيعي",
  "description": "خصم 3 دينار على الصابون الطبيعي",
  "discount_type": "fixed",        // مبلغ ثابت
  "discount_value": 3.000,
  "apply_to": "specific_products",
  "product_ids": [1, 2, 3, 4],    // IDs المنتجات
  "is_active": true,
  "starts_at": null,               // يبدأ فوراً
  "expires_at": null,              // لا ينتهي
  "priority": 5
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "خصم الجمعة البيضاء",
    // ... باقي البيانات
  },
  "message": "Product discount created successfully"
}
```

### 3. تعديل خصم
```http
PUT /api/v1/admin/product-discounts/{id}
```

**Request Body:** (كل الحقول اختيارية)
```json
{
  "name": "خصم الجمعة البيضاء المحدث",
  "discount_value": 60,
  "is_active": false
}
```

### 4. حذف خصم
```http
DELETE /api/v1/admin/product-discounts/{id}
```

### 5. تفعيل/تعطيل خصم
```http
PUT /api/v1/admin/product-discounts/{id}/toggle-status
```

### 6. نسخ خصم
```http
POST /api/v1/admin/product-discounts/{id}/duplicate
```

### 7. عرض المنتجات المتأثرة بالخصم
```http
GET /api/v1/admin/product-discounts/{id}/affected-products
```

**Response:**
```json
{
  "success": true,
  "data": {
    "discount": {
      "id": 1,
      "name": "خصم الجمعة البيضاء",
      // ...
    },
    "products": {
      "data": [
        {
          "id": 1,
          "title": "صابون طبيعي",
          "price": "10.000",
          "has_discount": true,
          "discount_percentage": 50,
          "discounted_price": "5.000",
          "price_before_discount": "10.000",
          // ...
        }
      ]
    }
  }
}
```

### 8. إحصائيات الخصومات
```http
GET /api/v1/admin/product-discounts/statistics
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_discounts": 5,
    "active_discounts": 3,
    "inactive_discounts": 2,
    "expired_discounts": 1,
    "upcoming_discounts": 0,
    "all_products_discounts": 1,
    "specific_products_discounts": 2,
    "percentage_discounts": 3,
    "fixed_discounts": 2,
    "products_with_discounts": 25
  }
}
```

---

## Customer APIs - للعملاء

### الحصول على قائمة المنتجات (مع بيانات الخصم)
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
      "title": "صابون طبيعي بزيت الزيتون",
      "price": "10.000",              // السعر الأصلي
      "currency": "KWD",
      "has_discount": true,           // ✅ هل يوجد خصم
      "discount_percentage": 50.00,   // ✅ نسبة الخصم
      "discounted_price": "5.000",    // ✅ السعر بعد الخصم
      "price_before_discount": "10.000", // ✅ السعر قبل الخصم
      "discount_amount": "5.000",     // مبلغ الخصم
      "images": [...],
      "category": {...}
    }
  ]
}
```

### الحصول على منتج واحد (مع بيانات الخصم)
```http
GET /api/v1/products/{slug}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "صابون طبيعي بزيت الزيتون",
    "slug": "natural-olive-soap",
    "description": "...",
    "price": "10.000",
    "has_discount": true,
    "discount_percentage": 50.00,
    "discounted_price": "5.000",
    "price_before_discount": "10.000",
    // ... باقي البيانات
  }
}
```

---

## التطبيق في Front-end

### 1. صفحة إدارة الخصومات (Admin Panel)

#### عرض قائمة الخصومات
```javascript
// React/Vue/Angular Example
import axios from 'axios';

const fetchDiscounts = async (filters = {}) => {
  const response = await axios.get('/api/v1/admin/product-discounts', {
    params: {
      page: filters.page || 1,
      per_page: 15,
      status: filters.status,
      search: filters.search,
    },
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
  
  return response.data;
};
```

#### نموذج إنشاء خصم
```javascript
const createDiscount = async (discountData) => {
  const response = await axios.post(
    '/api/v1/admin/product-discounts',
    {
      name: discountData.name,
      description: discountData.description,
      discount_type: discountData.type, // 'percentage' or 'fixed'
      discount_value: discountData.value,
      apply_to: discountData.applyTo, // 'all_products' or 'specific_products'
      product_ids: discountData.productIds, // array of product IDs
      is_active: true,
      starts_at: discountData.startsAt,
      expires_at: discountData.expiresAt,
      priority: discountData.priority || 0
    },
    {
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  );
  
  return response.data;
};
```

### 2. عرض المنتجات مع الخصم (Customer UI)

#### عرض المنتج في بطاقة
```jsx
// React Component Example
const ProductCard = ({ product }) => {
  return (
    <div className="product-card">
      <img src={product.images[0]} alt={product.title} />
      
      <h3>{product.title}</h3>
      
      <div className="price-section">
        {product.has_discount ? (
          <>
            {/* السعر بعد الخصم */}
            <span className="discounted-price">
              {product.discounted_price} {product.currency}
            </span>
            
            {/* السعر الأصلي مشطوب */}
            <span className="original-price">
              {product.price_before_discount} {product.currency}
            </span>
            
            {/* نسبة الخصم */}
            <span className="discount-badge">
              {Math.round(product.discount_percentage)}% خصم
            </span>
          </>
        ) : (
          <span className="price">
            {product.price} {product.currency}
          </span>
        )}
      </div>
    </div>
  );
};
```

#### CSS للتصميم
```css
.price-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
}

.discounted-price {
  font-size: 1.5rem;
  font-weight: bold;
  color: #e74c3c; /* أحمر للسعر المخفض */
}

.original-price {
  font-size: 1rem;
  color: #95a5a6;
  text-decoration: line-through;
}

.discount-badge {
  background: #e74c3c;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: bold;
}
```

### 3. واجهة إدارة الخصومات (Admin UI)

#### Form Component للخصم
```jsx
const DiscountForm = ({ onSubmit, initialData = {} }) => {
  const [formData, setFormData] = useState({
    name: initialData.name || '',
    description: initialData.description || '',
    discountType: initialData.discount_type || 'percentage',
    discountValue: initialData.discount_value || 0,
    applyTo: initialData.apply_to || 'all_products',
    productIds: initialData.product_ids || [],
    isActive: initialData.is_active ?? true,
    startsAt: initialData.starts_at || null,
    expiresAt: initialData.expires_at || null,
    priority: initialData.priority || 0
  });

  return (
    <form onSubmit={(e) => {
      e.preventDefault();
      onSubmit(formData);
    }}>
      {/* اسم الخصم */}
      <input
        type="text"
        placeholder="اسم الخصم"
        value={formData.name}
        onChange={(e) => setFormData({...formData, name: e.target.value})}
        required
      />

      {/* نوع الخصم */}
      <select
        value={formData.discountType}
        onChange={(e) => setFormData({...formData, discountType: e.target.value})}
      >
        <option value="percentage">نسبة مئوية (%)</option>
        <option value="fixed">مبلغ ثابت (KWD)</option>
      </select>

      {/* قيمة الخصم */}
      <input
        type="number"
        step="0.001"
        placeholder={formData.discountType === 'percentage' ? 'النسبة' : 'المبلغ'}
        value={formData.discountValue}
        onChange={(e) => setFormData({...formData, discountValue: e.target.value})}
        required
      />

      {/* نطاق التطبيق */}
      <select
        value={formData.applyTo}
        onChange={(e) => setFormData({...formData, applyTo: e.target.value})}
      >
        <option value="all_products">جميع المنتجات</option>
        <option value="specific_products">منتجات محددة</option>
      </select>

      {/* اختيار المنتجات (إذا كان منتجات محددة) */}
      {formData.applyTo === 'specific_products' && (
        <ProductMultiSelect
          selectedIds={formData.productIds}
          onChange={(ids) => setFormData({...formData, productIds: ids})}
        />
      )}

      {/* تاريخ البداية والانتهاء */}
      <input
        type="datetime-local"
        placeholder="تاريخ البداية"
        value={formData.startsAt}
        onChange={(e) => setFormData({...formData, startsAt: e.target.value})}
      />

      <input
        type="datetime-local"
        placeholder="تاريخ الانتهاء"
        value={formData.expiresAt}
        onChange={(e) => setFormData({...formData, expiresAt: e.target.value})}
      />

      <button type="submit">حفظ</button>
    </form>
  );
};
```

---

## قواعد العمل (Business Logic)

### 1. أولوية الخصومات
- إذا كان هناك أكثر من خصم يطبق على منتج واحد، يتم اختيار الخصم ذو **الأولوية الأعلى** (`priority`)
- الخصومات المحددة للمنتج لها نفس الأولوية مع الخصومات على كل المنتجات

### 2. صلاحية الخصم
الخصم يعتبر صالح إذا:
- ✅ `is_active = true`
- ✅ التاريخ الحالي >= `starts_at` (أو `starts_at` null)
- ✅ التاريخ الحالي <= `expires_at` (أو `expires_at` null)

### 3. حساب السعر المخفض

#### للنسبة المئوية:
```
discount_amount = (original_price × discount_value) / 100
final_price = original_price - discount_amount
```

#### للمبلغ الثابت:
```
final_price = original_price - discount_value
```

**ملاحظة:** السعر النهائي لا يمكن أن يكون سالب (minimum = 0)

---

## أمثلة عملية

### مثال 1: خصم 30% على جميع المنتجات
```json
{
  "name": "خصم نهاية الموسم",
  "discount_type": "percentage",
  "discount_value": 30,
  "apply_to": "all_products",
  "is_active": true
}
```

**النتيجة:**
- منتج بسعر 10 KWD → 7 KWD
- منتج بسعر 15 KWD → 10.5 KWD

### مثال 2: خصم 2 دينار على منتجات محددة
```json
{
  "name": "خصم الصابون الطبيعي",
  "discount_type": "fixed",
  "discount_value": 2,
  "apply_to": "specific_products",
  "product_ids": [1, 2, 3]
}
```

**النتيجة:**
- منتج 1 بسعر 10 KWD → 8 KWD
- منتج 2 بسعر 8 KWD → 6 KWD
- منتج 4 بسعر 10 KWD → 10 KWD (لا خصم)

---

## الخلاصة

نظام خصومات المنتجات يوفر:
- ✅ مرونة كاملة في إدارة الخصومات
- ✅ تطبيق تلقائي للخصومات على المنتجات
- ✅ عرض واضح للسعر قبل وبعد الخصم
- ✅ جدولة الخصومات
- ✅ أولويات للخصومات المتداخلة
- ✅ واجهة سهلة للأدمن والعملاء

**النظام جاهز للاستخدام! 🎉**

