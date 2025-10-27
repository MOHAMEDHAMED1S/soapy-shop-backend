# Bulk Update للمنتجات ✅

**التاريخ:** 2025-10-27  
**الحالة:** ✅ تم التحديث

---

## 📝 الإجراءات المدعومة

### 1. activate
تفعيل عدة منتجات دفعة واحدة

```json
{
  "product_ids": [1, 2, 3],
  "action": "activate"
}
```

---

### 2. deactivate
إلغاء تفعيل عدة منتجات دفعة واحدة

```json
{
  "product_ids": [1, 2, 3],
  "action": "deactivate"
}
```

---

### 3. delete
حذف عدة منتجات دفعة واحدة

```json
{
  "product_ids": [1, 2, 3],
  "action": "delete"
}
```

---

### 4. change_category (القديم)
تغيير فئة عدة منتجات

```json
{
  "product_ids": [1, 2, 3],
  "action": "change_category",
  "category_id": 5
}
```

---

### 5. update_category ✨ (جديد)
تحديث فئة عدة منتجات

```json
{
  "product_ids": [54],
  "action": "update_category",
  "category_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 1
  },
  "message": "Bulk update_category completed successfully"
}
```

---

### 6. update_price ✨ (جديد)
تحديث سعر عدة منتجات

```json
{
  "product_ids": [54],
  "action": "update_price",
  "price": "2"
}
```

**أو:**
```json
{
  "product_ids": [54],
  "action": "update_price",
  "price": 2.500
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 1
  },
  "message": "Bulk update_price completed successfully"
}
```

---

## 🔧 API Endpoint

```
POST /api/v1/admin/products/bulk-update
```

**Authentication:** Required (Admin JWT Token)

---

## 📊 Request Structure

### Headers:
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

### Body Parameters:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `product_ids` | array | ✅ Yes | مصفوفة من IDs المنتجات |
| `product_ids.*` | integer | ✅ Yes | ID كل منتج (يجب أن يكون موجود) |
| `action` | string | ✅ Yes | الإجراء المطلوب |
| `category_id` | integer | ⚠️ إذا كان action = change_category أو update_category | ID الفئة الجديدة |
| `price` | number | ⚠️ إذا كان action = update_price | السعر الجديد (min: 0) |

---

## ✅ Validation Rules

```php
[
    'product_ids' => 'required|array|min:1',
    'product_ids.*' => 'exists:products,id',
    'action' => 'required|in:activate,deactivate,delete,change_category,update_category,update_price',
    'category_id' => 'required_if:action,change_category,update_category|exists:categories,id',
    'price' => 'required_if:action,update_price|numeric|min:0',
]
```

---

## 📝 أمثلة كاملة

### Example 1: تحديث سعر منتج واحد

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/products/bulk-update" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_ids": [54],
    "action": "update_price",
    "price": "2"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 1
  },
  "message": "Bulk update_price completed successfully"
}
```

---

### Example 2: تحديث سعر عدة منتجات

**Request:**
```json
{
  "product_ids": [1, 2, 3, 4, 5],
  "action": "update_price",
  "price": 49.99
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 5
  },
  "message": "Bulk update_price completed successfully"
}
```

---

### Example 3: تحديث فئة منتج واحد

**Request:**
```json
{
  "product_ids": [54],
  "action": "update_category",
  "category_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 1
  },
  "message": "Bulk update_category completed successfully"
}
```

---

### Example 4: تحديث فئة عدة منتجات

**Request:**
```json
{
  "product_ids": [10, 11, 12, 13, 14],
  "action": "update_category",
  "category_id": 3
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 5
  },
  "message": "Bulk update_category completed successfully"
}
```

---

### Example 5: تفعيل عدة منتجات

**Request:**
```json
{
  "product_ids": [1, 2, 3],
  "action": "activate"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "updated_count": 3
  },
  "message": "Bulk activate completed successfully"
}
```

---

## ❌ Error Responses

### 1. Validation Error (422):

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "price": [
      "The price field is required when action is update_price."
    ]
  }
}
```

---

### 2. Product Not Found (422):

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "product_ids.0": [
      "The selected product_ids.0 is invalid."
    ]
  }
}
```

---

### 3. Category Not Found (422):

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "category_id": [
      "The selected category_id is invalid."
    ]
  }
}
```

---

### 4. Invalid Action (422):

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "action": [
      "The selected action is invalid."
    ]
  }
}
```

---

### 5. Server Error (500):

```json
{
  "success": false,
  "message": "Error performing bulk update",
  "error": "Database connection error"
}
```

---

## 💻 Frontend Integration

### React/TypeScript Example:

```typescript
interface BulkUpdateRequest {
  product_ids: number[];
  action: 'activate' | 'deactivate' | 'delete' | 'change_category' | 'update_category' | 'update_price';
  category_id?: number;
  price?: number | string;
}

interface BulkUpdateResponse {
  success: boolean;
  data: {
    updated_count: number;
  };
  message: string;
}

const bulkUpdateProducts = async (data: BulkUpdateRequest): Promise<BulkUpdateResponse> => {
  const response = await fetch('/api/v1/admin/products/bulk-update', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${adminToken}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    throw new Error('Failed to update products');
  }

  return response.json();
};

// الاستخدام:

// تحديث السعر
await bulkUpdateProducts({
  product_ids: [54],
  action: 'update_price',
  price: 2,
});

// تحديث الفئة
await bulkUpdateProducts({
  product_ids: [54],
  action: 'update_category',
  category_id: 1,
});

// تفعيل المنتجات
await bulkUpdateProducts({
  product_ids: [1, 2, 3],
  action: 'activate',
});
```

---

### Vue 3 Example:

```vue
<script setup lang="ts">
import { ref } from 'vue';

const selectedProducts = ref<number[]>([]);
const updating = ref(false);

const updatePrice = async (newPrice: number) => {
  updating.value = true;
  
  try {
    const response = await fetch('/api/v1/admin/products/bulk-update', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        product_ids: selectedProducts.value,
        action: 'update_price',
        price: newPrice,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      alert(`تم تحديث ${data.data.updated_count} منتجات`);
    }
  } catch (error) {
    console.error('Error:', error);
  } finally {
    updating.value = false;
  }
};

const updateCategory = async (categoryId: number) => {
  updating.value = true;
  
  try {
    const response = await fetch('/api/v1/admin/products/bulk-update', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        product_ids: selectedProducts.value,
        action: 'update_category',
        category_id: categoryId,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      alert(`تم تحديث ${data.data.updated_count} منتجات`);
    }
  } catch (error) {
    console.error('Error:', error);
  } finally {
    updating.value = false;
  }
};
</script>

<template>
  <div>
    <button @click="updatePrice(99.99)" :disabled="updating">
      تحديث السعر
    </button>
    <button @click="updateCategory(1)" :disabled="updating">
      تحديث الفئة
    </button>
  </div>
</template>
```

---

## 🔍 ملاحظات هامة

### 1. Database Transaction
جميع التحديثات تتم داخل transaction لضمان سلامة البيانات:
```php
DB::transaction(function () use ($productIds, $action, $request, &$updatedCount) {
    // التحديثات هنا
});
```

### 2. تحديث عدة منتجات دفعة واحدة
يمكن تحديث عدد غير محدود من المنتجات في طلب واحد:
```json
{
  "product_ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
  "action": "update_price",
  "price": 99.99
}
```

### 3. نوع البيانات للسعر
السعر يمكن أن يكون:
- `number`: `2` أو `2.5`
- `string`: `"2"` أو `"2.500"`

سيتم تحويله تلقائياً إلى `numeric` في Validation.

### 4. التحقق من وجود المنتجات
النظام يتحقق من وجود جميع المنتجات قبل التحديث:
```php
'product_ids.*' => 'exists:products,id'
```

إذا كان أي منتج غير موجود، سيفشل الطلب بالكامل.

---

## 🎉 الخلاصة

### الإجراءات المدعومة:

| الإجراء | الوصف | Parameters |
|---------|--------|------------|
| `activate` | تفعيل المنتجات | `product_ids` |
| `deactivate` | إلغاء التفعيل | `product_ids` |
| `delete` | حذف المنتجات | `product_ids` |
| `change_category` | تغيير الفئة (قديم) | `product_ids`, `category_id` |
| `update_category` ✨ | تحديث الفئة (جديد) | `product_ids`, `category_id` |
| `update_price` ✨ | تحديث السعر (جديد) | `product_ids`, `price` |

---

**🎉 تم إضافة الدعم الكامل لتحديث السعر والفئة!**

