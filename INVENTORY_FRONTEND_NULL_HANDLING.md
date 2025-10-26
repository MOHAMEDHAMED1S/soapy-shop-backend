# Frontend: التعامل مع Null Values في Inventory Transactions 🎨

## المشكلة التي تم حلها

```
❌ Cannot read properties of null (reading 'name')
```

الآن الـ Backend يُرجع بيانات آمنة، لكن من الأفضل أن يكون الـ Frontend محمي أيضاً.

---

## React + TypeScript (الحل الموصى به)

### 1. TypeScript Interface

```typescript
// types/inventory.ts

export interface InventoryTransaction {
  id: number;
  product_id: number;
  product: {
    id: number;
    title: string;
    slug: string | null;
  };
  type: 'increase' | 'decrease';
  quantity: number;
  quantity_before: number;
  quantity_after: number;
  reason: string;
  notes: string | null;
  order: {
    id: number;
    order_number: string;
  } | null;
  user: {
    id: number;
    name: string;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface TransactionsResponse {
  success: boolean;
  data: {
    current_page: number;
    data: InventoryTransaction[];
    per_page: number;
    total: number;
  };
}
```

---

### 2. Component مع الحماية الكاملة

```tsx
// components/InventoryTransactionsDialog.tsx

import React, { useState, useEffect } from 'react';
import { InventoryTransaction, TransactionsResponse } from '@/types/inventory';
import axios from 'axios';

interface Props {
  open: boolean;
  onClose: () => void;
  productId?: number;
}

const InventoryTransactionsDialog: React.FC<Props> = ({ 
  open, 
  onClose, 
  productId 
}) => {
  const [transactions, setTransactions] = useState<InventoryTransaction[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      fetchTransactions();
    }
  }, [open, productId]);

  const fetchTransactions = async () => {
    setLoading(true);
    setError(null);

    try {
      const url = productId
        ? `/api/v1/admin/inventory/products/${productId}/transactions`
        : '/api/v1/admin/inventory/transactions';

      const response = await axios.get<TransactionsResponse>(url);

      if (response.data.success) {
        const data = productId
          ? response.data.data.transactions.data
          : response.data.data.data;
        
        setTransactions(data);
      }
    } catch (err) {
      setError('فشل تحميل المعاملات');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // ✅ Helper function for safe display
  const getProductName = (transaction: InventoryTransaction): string => {
    return transaction.product?.title ?? 'منتج محذوف';
  };

  const getOrderNumber = (transaction: InventoryTransaction): string => {
    return transaction.order?.order_number ?? 'لا يوجد';
  };

  const getUserName = (transaction: InventoryTransaction): string => {
    return transaction.user?.name ?? 'النظام';
  };

  const getTypeLabel = (type: string): string => {
    return type === 'increase' ? 'زيادة' : 'نقصان';
  };

  const getReasonLabel = (reason: string): string => {
    const reasons: Record<string, string> = {
      'manual_adjustment': 'تعديل يدوي',
      'order_placed': 'طلب جديد',
      'order_cancelled': 'إلغاء طلب',
      'initial_stock': 'مخزون أولي',
      'inventory_return': 'إرجاع مخزون',
    };
    return reasons[reason] ?? reason;
  };

  if (!open) return null;

  return (
    <div className="dialog-overlay">
      <div className="dialog-content">
        <div className="dialog-header">
          <h2>سجل المعاملات</h2>
          <button onClick={onClose}>×</button>
        </div>

        <div className="dialog-body">
          {loading && <div>جاري التحميل...</div>}
          
          {error && (
            <div className="error-message">{error}</div>
          )}

          {!loading && transactions.length === 0 && (
            <div className="empty-state">لا توجد معاملات</div>
          )}

          {!loading && transactions.length > 0 && (
            <table className="transactions-table">
              <thead>
                <tr>
                  <th>المنتج</th>
                  <th>النوع</th>
                  <th>الكمية</th>
                  <th>السبب</th>
                  <th>الطلب</th>
                  <th>المستخدم</th>
                  <th>التاريخ</th>
                </tr>
              </thead>
              <tbody>
                {transactions.map((transaction) => (
                  <tr key={transaction.id}>
                    {/* ✅ Safe product name */}
                    <td>{getProductName(transaction)}</td>

                    {/* ✅ Type with styling */}
                    <td>
                      <span className={`badge badge-${transaction.type}`}>
                        {getTypeLabel(transaction.type)}
                      </span>
                    </td>

                    {/* ✅ Quantity with color */}
                    <td className={transaction.type === 'increase' ? 'text-green' : 'text-red'}>
                      {transaction.type === 'increase' ? '+' : '-'}
                      {transaction.quantity}
                    </td>

                    {/* ✅ Reason */}
                    <td>{getReasonLabel(transaction.reason)}</td>

                    {/* ✅ Safe order number */}
                    <td>
                      {transaction.order ? (
                        <a href={`/orders/${transaction.order.id}`}>
                          {transaction.order.order_number}
                        </a>
                      ) : (
                        <span className="text-muted">لا يوجد</span>
                      )}
                    </td>

                    {/* ✅ Safe user name */}
                    <td>{getUserName(transaction)}</td>

                    {/* ✅ Date */}
                    <td>{new Date(transaction.created_at).toLocaleString('ar-KW')}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="dialog-footer">
          <button onClick={onClose}>إغلاق</button>
        </div>
      </div>
    </div>
  );
};

export default InventoryTransactionsDialog;
```

---

### 3. استخدام Optional Chaining مباشرة

```tsx
// ✅ الطريقة الأبسط
<td>{transaction.product?.title ?? 'منتج محذوف'}</td>

// ✅ مع رابط
<td>
  {transaction.order ? (
    <a href={`/orders/${transaction.order.id}`}>
      {transaction.order.order_number}
    </a>
  ) : (
    'لا يوجد'
  )}
</td>

// ✅ مع أيقونة
<td>
  {transaction.user ? (
    <div className="user-info">
      <span className="user-icon">👤</span>
      {transaction.user.name}
    </div>
  ) : (
    <div className="system-info">
      <span className="system-icon">⚙️</span>
      النظام
    </div>
  )}
</td>
```

---

## Vue 3 + TypeScript

### 1. TypeScript Interface (نفسه)

```typescript
// types/inventory.ts
export interface InventoryTransaction {
  // ... same as React
}
```

---

### 2. Composition API Component

```vue
<!-- components/InventoryTransactionsDialog.vue -->

<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import type { InventoryTransaction } from '@/types/inventory';

interface Props {
  open: boolean;
  productId?: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'close'): void;
}>();

const transactions = ref<InventoryTransaction[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    fetchTransactions();
  }
});

const fetchTransactions = async () => {
  loading.value = true;
  error.value = null;

  try {
    const url = props.productId
      ? `/api/v1/admin/inventory/products/${props.productId}/transactions`
      : '/api/v1/admin/inventory/transactions';

    const response = await axios.get(url);

    if (response.data.success) {
      const data = props.productId
        ? response.data.data.transactions.data
        : response.data.data.data;
      
      transactions.value = data;
    }
  } catch (err) {
    error.value = 'فشل تحميل المعاملات';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

// ✅ Helper functions
const getProductName = (transaction: InventoryTransaction): string => {
  return transaction.product?.title ?? 'منتج محذوف';
};

const getOrderNumber = (transaction: InventoryTransaction): string => {
  return transaction.order?.order_number ?? 'لا يوجد';
};

const getUserName = (transaction: InventoryTransaction): string => {
  return transaction.user?.name ?? 'النظام';
};

const getTypeLabel = (type: string): string => {
  return type === 'increase' ? 'زيادة' : 'نقصان';
};

const getReasonLabel = (reason: string): string => {
  const reasons: Record<string, string> = {
    'manual_adjustment': 'تعديل يدوي',
    'order_placed': 'طلب جديد',
    'order_cancelled': 'إلغاء طلب',
    'initial_stock': 'مخزون أولي',
    'inventory_return': 'إرجاع مخزون',
  };
  return reasons[reason] ?? reason;
};
</script>

<template>
  <div v-if="open" class="dialog-overlay">
    <div class="dialog-content">
      <div class="dialog-header">
        <h2>سجل المعاملات</h2>
        <button @click="emit('close')">×</button>
      </div>

      <div class="dialog-body">
        <div v-if="loading">جاري التحميل...</div>
        
        <div v-if="error" class="error-message">{{ error }}</div>

        <div v-if="!loading && transactions.length === 0" class="empty-state">
          لا توجد معاملات
        </div>

        <table v-if="!loading && transactions.length > 0" class="transactions-table">
          <thead>
            <tr>
              <th>المنتج</th>
              <th>النوع</th>
              <th>الكمية</th>
              <th>السبب</th>
              <th>الطلب</th>
              <th>المستخدم</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactions" :key="transaction.id">
              <!-- ✅ Safe product name -->
              <td>{{ getProductName(transaction) }}</td>

              <!-- ✅ Type with styling -->
              <td>
                <span :class="`badge badge-${transaction.type}`">
                  {{ getTypeLabel(transaction.type) }}
                </span>
              </td>

              <!-- ✅ Quantity with color -->
              <td :class="transaction.type === 'increase' ? 'text-green' : 'text-red'">
                {{ transaction.type === 'increase' ? '+' : '-' }}{{ transaction.quantity }}
              </td>

              <!-- ✅ Reason -->
              <td>{{ getReasonLabel(transaction.reason) }}</td>

              <!-- ✅ Safe order number -->
              <td>
                <a v-if="transaction.order" :href="`/orders/${transaction.order.id}`">
                  {{ transaction.order.order_number }}
                </a>
                <span v-else class="text-muted">لا يوجد</span>
              </td>

              <!-- ✅ Safe user name -->
              <td>{{ getUserName(transaction) }}</td>

              <!-- ✅ Date -->
              <td>{{ new Date(transaction.created_at).toLocaleString('ar-KW') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="dialog-footer">
        <button @click="emit('close')">إغلاق</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dialog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.dialog-content {
  background: white;
  border-radius: 8px;
  max-width: 1200px;
  width: 90%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.badge-increase {
  background: #d4edda;
  color: #155724;
  padding: 4px 8px;
  border-radius: 4px;
}

.badge-decrease {
  background: #f8d7da;
  color: #721c24;
  padding: 4px 8px;
  border-radius: 4px;
}

.text-green {
  color: #28a745;
}

.text-red {
  color: #dc3545;
}

.text-muted {
  color: #6c757d;
}
</style>
```

---

## Inline Protection (أبسط طريقة)

### React:

```tsx
{transactions.map(transaction => (
  <div key={transaction.id}>
    {/* ✅ Product name */}
    <h3>{transaction.product?.title ?? 'منتج محذوف'}</h3>
    
    {/* ✅ Order number */}
    <p>الطلب: {transaction.order?.order_number ?? 'لا يوجد'}</p>
    
    {/* ✅ User name */}
    <p>المستخدم: {transaction.user?.name ?? 'النظام'}</p>
  </div>
))}
```

---

### Vue:

```vue
<div v-for="transaction in transactions" :key="transaction.id">
  <!-- ✅ Product name -->
  <h3>{{ transaction.product?.title ?? 'منتج محذوف' }}</h3>
  
  <!-- ✅ Order number -->
  <p>الطلب: {{ transaction.order?.order_number ?? 'لا يوجد' }}</p>
  
  <!-- ✅ User name -->
  <p>المستخدم: {{ transaction.user?.name ?? 'النظام' }}</p>
</div>
```

---

## إصلاح الخطأ الأصلي في السطر 263

### قبل (❌ خطأ):

```tsx
// Line 263 - InventoryTransactionsDialog.tsx
{transactions.map(transaction => (
  <TableCell>{transaction.product.name}</TableCell>  // ← Error!
))}
```

---

### بعد (✅ صحيح):

```tsx
// Option 1: Optional chaining + nullish coalescing
{transactions.map(transaction => (
  <TableCell>{transaction.product?.name ?? 'منتج محذوف'}</TableCell>
))}

// Option 2: Helper function
{transactions.map(transaction => (
  <TableCell>{getProductName(transaction)}</TableCell>
))}

// Option 3: Conditional rendering
{transactions.map(transaction => (
  <TableCell>
    {transaction.product ? transaction.product.name : 'منتج محذوف'}
  </TableCell>
))}
```

---

## الخلاصة

### ✅ Backend (تم):
- API يُرجع بيانات آمنة دائماً
- المنتجات المحذوفة → `"منتج محذوف"`
- الطلبات/المستخدمين المحذوفين → `null`

### ✅ Frontend (موصى به):
```typescript
// Always use optional chaining
transaction.product?.title ?? 'منتج محذوف'
transaction.order?.order_number ?? 'لا يوجد'
transaction.user?.name ?? 'النظام'
```

### 🛡️ Double Protection:
- Backend يحمي من `null`
- Frontend يحمي أيضاً (defensive programming)

**الآن الكود آمن تماماً! 🎉**

