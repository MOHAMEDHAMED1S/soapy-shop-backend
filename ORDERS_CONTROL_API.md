# نظام التحكم في الطلبات (Orders Control) 🔒

**التاريخ:** 2025-10-27  
**الحالة:** ✅ جاهز للاستخدام

---

## 📋 نظرة عامة

نظام بسيط للتحكم في تفعيل/إلغاء تفعيل الطلبات على الموقع.

**الاستخدام:**
- ✅ إيقاف استقبال الطلبات مؤقتاً
- ✅ فتح الطلبات مرة أخرى
- ✅ عرض رسالة للعملاء عند إغلاق الطلبات

---

## 🚀 APIs المتاحة

### 1. Public API (للعملاء - بدون Auth)

#### GET `/api/v1/site/orders-status`

جلب حالة الطلبات (مفتوحة أم مغلقة)

**Request:**
```http
GET /api/v1/site/orders-status
```

**Response (مفتوحة):**
```json
{
  "success": true,
  "data": {
    "orders_enabled": true,
    "status": "open",
    "message": "الطلبات مفتوحة حالياً"
  }
}
```

**Response (مغلقة):**
```json
{
  "success": true,
  "data": {
    "orders_enabled": false,
    "status": "closed",
    "message": "الطلبات مغلقة حالياً، يرجى المحاولة لاحقاً"
  }
}
```

---

### 2. Admin APIs (للأدمن - تحتاج Auth)

#### GET `/api/v1/admin/site/orders-status`

جلب حالة الطلبات (Admin)

**Request:**
```http
GET /api/v1/admin/site/orders-status
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders_enabled": true,
    "status": "open",
    "message": "الطلبات مفتوحة"
  }
}
```

---

#### POST `/api/v1/admin/site/toggle-orders`

تبديل حالة الطلبات (فتح ↔ إغلاق)

**Request:**
```http
POST /api/v1/admin/site/toggle-orders
Authorization: Bearer {admin_token}
```

**Response (تم الإغلاق):**
```json
{
  "success": true,
  "message": "تم إغلاق الطلبات بنجاح",
  "data": {
    "orders_enabled": false,
    "status": "closed"
  }
}
```

**Response (تم الفتح):**
```json
{
  "success": true,
  "message": "تم فتح الطلبات بنجاح",
  "data": {
    "orders_enabled": true,
    "status": "open"
  }
}
```

---

#### POST `/api/v1/admin/site/set-orders-status`

تعيين حالة محددة للطلبات

**Request:**
```http
POST /api/v1/admin/site/set-orders-status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "enabled": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم إغلاق الطلبات بنجاح",
  "data": {
    "orders_enabled": false,
    "status": "closed"
  }
}
```

---

## 💻 أمثلة Frontend

### React/TypeScript Example

```typescript
import { useState, useEffect } from 'react';

interface OrdersStatus {
  orders_enabled: boolean;
  status: 'open' | 'closed';
  message: string;
}

// ==========================================
// 1. للعملاء (Public)
// ==========================================

const CheckoutPage = () => {
  const [ordersStatus, setOrdersStatus] = useState<OrdersStatus | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkOrdersStatus();
  }, []);

  const checkOrdersStatus = async () => {
    try {
      const response = await fetch('/api/v1/site/orders-status');
      const data = await response.json();
      
      if (data.success) {
        setOrdersStatus(data.data);
      }
    } catch (error) {
      console.error('Error checking orders status:', error);
      // في حالة الخطأ، نفترض أن الطلبات مفتوحة
      setOrdersStatus({
        orders_enabled: true,
        status: 'open',
        message: 'الطلبات مفتوحة حالياً'
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div>جاري التحميل...</div>;
  }

  if (!ordersStatus?.orders_enabled) {
    return (
      <div className="orders-closed-notice">
        <h2>⚠️ الطلبات مغلقة حالياً</h2>
        <p>{ordersStatus.message}</p>
        <button onClick={() => window.location.href = '/'}>
          العودة للصفحة الرئيسية
        </button>
      </div>
    );
  }

  return (
    <div className="checkout-page">
      {/* عرض صفحة الطلب العادية */}
      <h1>إتمام الطلب</h1>
      {/* ... باقي الصفحة */}
    </div>
  );
};

// ==========================================
// 2. للأدمن (Admin Panel)
// ==========================================

const OrdersControlPanel = () => {
  const [ordersStatus, setOrdersStatus] = useState<OrdersStatus | null>(null);
  const [loading, setLoading] = useState(false);

  const adminToken = localStorage.getItem('admin_token');

  useEffect(() => {
    fetchOrdersStatus();
  }, []);

  const fetchOrdersStatus = async () => {
    try {
      const response = await fetch('/api/v1/admin/site/orders-status', {
        headers: {
          'Authorization': `Bearer ${adminToken}`
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setOrdersStatus(data.data);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const toggleOrders = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/site/toggle-orders', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${adminToken}`
        }
      });
      const data = await response.json();
      
      if (data.success) {
        setOrdersStatus(data.data);
        alert(data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      alert('حدث خطأ أثناء تغيير حالة الطلبات');
    } finally {
      setLoading(false);
    }
  };

  const setOrdersStatus = async (enabled: boolean) => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/site/set-orders-status', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ enabled })
      });
      const data = await response.json();
      
      if (data.success) {
        setOrdersStatus(data.data);
        alert(data.message);
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="orders-control-panel">
      <h2>التحكم في الطلبات</h2>
      
      <div className="status-card">
        <h3>الحالة الحالية:</h3>
        {ordersStatus && (
          <div className={`status-badge ${ordersStatus.status}`}>
            {ordersStatus.orders_enabled ? '🟢 مفتوحة' : '🔴 مغلقة'}
          </div>
        )}
        <p>{ordersStatus?.message}</p>
      </div>

      <div className="controls">
        {/* Toggle Button */}
        <button 
          onClick={toggleOrders}
          disabled={loading}
          className="toggle-button"
        >
          {loading ? 'جاري التحديث...' : (
            ordersStatus?.orders_enabled ? 'إغلاق الطلبات' : 'فتح الطلبات'
          )}
        </button>

        {/* Specific Controls */}
        <div className="button-group">
          <button 
            onClick={() => setOrdersStatus(true)}
            disabled={loading}
            className="open-button"
          >
            فتح الطلبات
          </button>
          <button 
            onClick={() => setOrdersStatus(false)}
            disabled={loading}
            className="close-button"
          >
            إغلاق الطلبات
          </button>
        </div>
      </div>
    </div>
  );
};

export { CheckoutPage, OrdersControlPanel };
```

---

### Vue 3 Example

```vue
<template>
  <!-- للعملاء (Public) -->
  <div v-if="!loading">
    <div v-if="!ordersStatus?.orders_enabled" class="orders-closed">
      <h2>⚠️ الطلبات مغلقة حالياً</h2>
      <p>{{ ordersStatus.message }}</p>
      <button @click="goHome">العودة للصفحة الرئيسية</button>
    </div>
    
    <div v-else class="checkout-page">
      <!-- صفحة الطلب العادية -->
      <h1>إتمام الطلب</h1>
    </div>
  </div>
  
  <!-- للأدمن (Admin Panel) -->
  <div class="admin-panel">
    <h2>التحكم في الطلبات</h2>
    
    <div class="status-card">
      <h3>الحالة الحالية:</h3>
      <div :class="['status-badge', ordersStatus?.status]">
        {{ ordersStatus?.orders_enabled ? '🟢 مفتوحة' : '🔴 مغلقة' }}
      </div>
      <p>{{ ordersStatus?.message }}</p>
    </div>

    <div class="controls">
      <button 
        @click="toggleOrders" 
        :disabled="loading"
        class="toggle-button"
      >
        {{ loading ? 'جاري التحديث...' : 
           (ordersStatus?.orders_enabled ? 'إغلاق الطلبات' : 'فتح الطلبات') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface OrdersStatus {
  orders_enabled: boolean;
  status: 'open' | 'closed';
  message: string;
}

const ordersStatus = ref<OrdersStatus | null>(null);
const loading = ref(false);
const adminToken = localStorage.getItem('admin_token');

onMounted(() => {
  checkOrdersStatus();
});

const checkOrdersStatus = async () => {
  try {
    const response = await fetch('/api/v1/site/orders-status');
    const data = await response.json();
    
    if (data.success) {
      ordersStatus.value = data.data;
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

const toggleOrders = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/admin/site/toggle-orders', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`
      }
    });
    const data = await response.json();
    
    if (data.success) {
      ordersStatus.value = data.data;
      alert(data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  } finally {
    loading.value = false;
  }
};

const goHome = () => {
  window.location.href = '/';
};
</script>

<style scoped>
.orders-closed {
  text-align: center;
  padding: 40px;
}

.status-badge.open {
  color: green;
}

.status-badge.closed {
  color: red;
}
</style>
```

---

## 🎯 حالات الاستخدام

### 1. إيقاف الطلبات مؤقتاً

```typescript
// عند الضغط على زر "إغلاق الطلبات"
await fetch('/api/v1/admin/site/toggle-orders', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${adminToken}`
  }
});
```

---

### 2. فتح الطلبات مرة أخرى

```typescript
// نفس الطريقة - Toggle
await fetch('/api/v1/admin/site/toggle-orders', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${adminToken}`
  }
});
```

---

### 3. التحقق قبل عرض صفحة الطلب

```typescript
const canMakeOrders = async () => {
  const response = await fetch('/api/v1/site/orders-status');
  const data = await response.json();
  
  return data.data.orders_enabled;
};

// في صفحة Cart/Checkout
if (!await canMakeOrders()) {
  // عرض رسالة وإعادة التوجيه
  showMessage('الطلبات مغلقة حالياً');
  router.push('/');
}
```

---

## 🔍 ملاحظات مهمة

### 1. Public API لا يحتاج Auth

```typescript
// ✅ صحيح
fetch('/api/v1/site/orders-status');

// ❌ خطأ - لا تحتاج Authorization
fetch('/api/v1/site/orders-status', {
  headers: { 'Authorization': '...' }  // غير مطلوب
});
```

---

### 2. Admin APIs تحتاج Auth

```typescript
// ✅ صحيح
fetch('/api/v1/admin/site/toggle-orders', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${adminToken}`
  }
});

// ❌ خطأ - بدون Auth سيفشل
fetch('/api/v1/admin/site/toggle-orders', {
  method: 'POST'
});
```

---

### 3. Fallback في حالة الخطأ

```typescript
try {
  const response = await fetch('/api/v1/site/orders-status');
  const data = await response.json();
  setOrdersStatus(data.data);
} catch (error) {
  // في حالة الخطأ، نفترض أن الطلبات مفتوحة
  setOrdersStatus({
    orders_enabled: true,
    status: 'open',
    message: 'الطلبات مفتوحة حالياً'
  });
}
```

---

## 📊 الحالات المختلفة

| الحالة | `orders_enabled` | `status` | ماذا يحدث |
|-------|-----------------|----------|-----------|
| **مفتوحة** | `true` | `"open"` | العملاء يمكنهم الطلب ✅ |
| **مغلقة** | `false` | `"closed"` | العملاء لا يمكنهم الطلب ❌ |

---

## 🎨 UI Suggestions

### عرض الحالة في Admin Panel

```html
<div class="orders-status-widget">
  <div class="status-indicator">
    <span class="dot" :class="ordersEnabled ? 'green' : 'red'"></span>
    <span>{{ ordersEnabled ? 'الطلبات مفتوحة' : 'الطلبات مغلقة' }}</span>
  </div>
  
  <button @click="toggleOrders" class="toggle-btn">
    {{ ordersEnabled ? 'إغلاق' : 'فتح' }} الطلبات
  </button>
</div>
```

---

### عرض رسالة للعملاء عند الإغلاق

```html
<div class="alert alert-warning" v-if="!ordersEnabled">
  <i class="icon-warning"></i>
  <div>
    <h3>الطلبات مغلقة حالياً</h3>
    <p>نعتذر عن الإزعاج، الطلبات مغلقة مؤقتاً. يرجى المحاولة لاحقاً.</p>
  </div>
</div>
```

---

## ✅ Checklist للـ Frontend

- [ ] إضافة Public API للتحقق من حالة الطلبات
- [ ] إضافة Admin Panel للتحكم في الطلبات
- [ ] عرض رسالة للعملاء عند إغلاق الطلبات
- [ ] منع الوصول لصفحة Checkout عند الإغلاق
- [ ] إضافة Toggle Button في لوحة التحكم
- [ ] إضافة Status Indicator في Dashboard
- [ ] معالجة الأخطاء (Fallback)

---

## 📚 الملفات

### Backend Files
- `database/migrations/2025_10_27_160000_create_site_settings_table.php`
- `app/Models/SiteSetting.php`
- `app/Http/Controllers/Api/Admin/SiteSettingController.php`
- `app/Http/Controllers/Api/SiteController.php`
- `routes/api.php`

### Documentation
- `ORDERS_CONTROL_API.md` (هذا الملف)

---

**🎉 النظام جاهز للاستخدام في Frontend!**

