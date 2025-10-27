# نظام إدارة إعدادات WhatsApp ✨

**التاريخ:** 2025-10-27  
**الحالة:** ✅ جاهز للاستخدام

---

## 📋 نظرة عامة

نظام متكامل لإدارة إعدادات WhatsApp من لوحة التحكم، يتيح:
- ✅ تخزين أرقام WhatsApp في قاعدة البيانات
- ✅ دعم أرقام متعددة للأدمن والمندوبين
- ✅ تفعيل/إلغاء تفعيل الإشعارات بشكل منفصل
- ✅ التحكم الكامل عبر APIs
- ✅ التخزين المؤقت (Cache) للأداء

---

## 🗄️ البنية

### 1. الجدول: `whatsapp_settings`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | المعرف الفريد |
| `key` | string | مفتاح الإعداد (unique) |
| `value` | text | قيمة الإعداد |
| `type` | string | نوع البيانات (string, array, boolean, etc.) |
| `description` | text | وصف الإعداد |
| `is_active` | boolean | تفعيل/إلغاء التفعيل |
| `created_at` | timestamp | تاريخ الإنشاء |
| `updated_at` | timestamp | تاريخ آخر تحديث |

---

### 2. الإعدادات الافتراضية

| Key | Value | Type | Description |
|-----|-------|------|-------------|
| `whatsapp_enabled` | `true` | boolean | تفعيل/إلغاء تفعيل جميع رسائل WhatsApp |
| `whatsapp_base_url` | `https://api.ultramsg.com` | string | Base URL لـ WhatsApp API |
| `admin_phones` | `["201062532581"]` | array | أرقام الأدمن |
| `delivery_phones` | `["201062532581"]` | array | أرقام المندوبين |
| `admin_notification_enabled` | `true` | boolean | تفعيل إشعارات الأدمن |
| `delivery_notification_enabled` | `true` | boolean | تفعيل إشعارات المندوبين |
| `logo_url` | `https://soapy-bubbles.com/logo.png` | string | رابط الشعار |

---

## 🔧 APIs

### Base URL
```
/api/v1/admin/whatsapp
```

**Authentication:** Required (Admin JWT Token)

---

## 📝 Endpoints

### 1. Get All Settings

**GET** `/api/v1/admin/whatsapp`

Get all WhatsApp settings.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "key": "whatsapp_enabled",
      "value": true,
      "type": "boolean",
      "description": "تفعيل/إلغاء تفعيل جميع رسائل WhatsApp",
      "is_active": true,
      "created_at": "2025-10-27T12:00:00.000000Z",
      "updated_at": "2025-10-27T12:00:00.000000Z"
    },
    {
      "id": 3,
      "key": "admin_phones",
      "value": ["201062532581", "201234567890"],
      "type": "array",
      "description": "أرقام الأدمن لاستقبال الإشعارات",
      "is_active": true,
      "created_at": "2025-10-27T12:00:00.000000Z",
      "updated_at": "2025-10-27T12:00:00.000000Z"
    }
  ]
}
```

---

### 2. Get Specific Setting

**GET** `/api/v1/admin/whatsapp/{key}`

Get a specific setting by key.

**Example:**
```bash
GET /api/v1/admin/whatsapp/admin_phones
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "key": "admin_phones",
    "value": ["201062532581", "201234567890"],
    "type": "array",
    "description": "أرقام الأدمن لاستقبال الإشعارات",
    "is_active": true
  }
}
```

---

### 3. Update Setting

**PUT** `/api/v1/admin/whatsapp/{key}`

Update a specific setting.

**Request Body:**
```json
{
  "value": ["201062532581", "201234567890", "201555555555"],
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Setting updated successfully",
  "data": {
    "key": "admin_phones",
    "value": ["201062532581", "201234567890", "201555555555"],
    "is_active": true
  }
}
```

---

### 4. Bulk Update Settings

**POST** `/api/v1/admin/whatsapp/bulk-update`

Update multiple settings at once.

**Request Body:**
```json
{
  "settings": [
    {
      "key": "admin_phones",
      "value": ["201062532581", "201234567890"],
      "is_active": true
    },
    {
      "key": "delivery_phones",
      "value": ["201062532581"],
      "is_active": true
    },
    {
      "key": "logo_url",
      "value": "https://soapy-bubbles.com/new-logo.png",
      "is_active": true
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Settings updated successfully",
  "data": {
    "updated_count": 3,
    "updated_keys": ["admin_phones", "delivery_phones", "logo_url"]
  }
}
```

---

### 5. Toggle WhatsApp Globally

**POST** `/api/v1/admin/whatsapp/toggle-global`

Enable or disable all WhatsApp notifications.

**Response:**
```json
{
  "success": true,
  "message": "WhatsApp disabled successfully",
  "data": {
    "whatsapp_enabled": false
  }
}
```

---

### 6. Toggle Admin Notifications

**POST** `/api/v1/admin/whatsapp/toggle-admin`

Enable or disable admin notifications only.

**Response:**
```json
{
  "success": true,
  "message": "Admin notifications enabled",
  "data": {
    "admin_notification_enabled": true
  }
}
```

---

### 7. Toggle Delivery Notifications

**POST** `/api/v1/admin/whatsapp/toggle-delivery`

Enable or disable delivery notifications only.

**Response:**
```json
{
  "success": true,
  "message": "Delivery notifications disabled",
  "data": {
    "delivery_notification_enabled": false
  }
}
```

---

### 8. Test WhatsApp Connection

**POST** `/api/v1/admin/whatsapp/test`

Send a test message to verify WhatsApp connection.

**Request Body:**
```json
{
  "phone": "201062532581",
  "message": "This is a test message"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Test message sent successfully",
  "data": {
    "sent": "true",
    "message": "تم إرسال الرسالة بنجاح",
    "id": "..."
  }
}
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "Failed to send test message",
  "error": "API error details..."
}
```

---

## 💻 Frontend Integration

### React/TypeScript Example

```typescript
import { useState, useEffect } from 'react';

interface WhatsAppSetting {
  id: number;
  key: string;
  value: any;
  type: string;
  description: string;
  is_active: boolean;
}

const WhatsAppSettings = () => {
  const [settings, setSettings] = useState<WhatsAppSetting[]>([]);
  const [loading, setLoading] = useState(false);

  // Load settings
  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    const response = await fetch('/api/v1/admin/whatsapp', {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
      },
    });
    const data = await response.json();
    if (data.success) {
      setSettings(data.data);
    }
  };

  // Update admin phones
  const updateAdminPhones = async (phones: string[]) => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/whatsapp/admin_phones', {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          value: phones,
          is_active: true,
        }),
      });
      const data = await response.json();
      if (data.success) {
        alert('تم تحديث أرقام الأدمن بنجاح');
        fetchSettings();
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Toggle global WhatsApp
  const toggleGlobal = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/whatsapp/toggle-global', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
        },
      });
      const data = await response.json();
      if (data.success) {
        alert(data.message);
        fetchSettings();
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Toggle admin notifications
  const toggleAdminNotifications = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/whatsapp/toggle-admin', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
        },
      });
      const data = await response.json();
      if (data.success) {
        alert(data.message);
        fetchSettings();
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Test connection
  const testConnection = async (phone: string) => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/admin/whatsapp/test', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          phone,
          message: 'This is a test message from Soapy Shop',
        }),
      });
      const data = await response.json();
      alert(data.success ? 'تم إرسال الرسالة بنجاح' : 'فشل إرسال الرسالة');
    } catch (error) {
      console.error('Error:', error);
      alert('حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="whatsapp-settings">
      <h2>إعدادات WhatsApp</h2>
      
      {/* Global Toggle */}
      <button onClick={toggleGlobal} disabled={loading}>
        تفعيل/إلغاء تفعيل WhatsApp
      </button>

      {/* Admin Notifications Toggle */}
      <button onClick={toggleAdminNotifications} disabled={loading}>
        تفعيل/إلغاء إشعارات الأدمن
      </button>

      {/* Settings List */}
      <div className="settings-list">
        {settings.map(setting => (
          <div key={setting.id} className="setting-item">
            <h4>{setting.key}</h4>
            <p>{setting.description}</p>
            <div>
              {setting.type === 'array' ? (
                <div>
                  {(setting.value as string[]).map((phone, index) => (
                    <div key={index}>{phone}</div>
                  ))}
                </div>
              ) : (
                <div>{String(setting.value)}</div>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default WhatsAppSettings;
```

---

### Vue 3 Example

```vue
<template>
  <div class="whatsapp-settings">
    <h2>إعدادات WhatsApp</h2>

    <!-- Global Toggle -->
    <button @click="toggleGlobal" :disabled="loading">
      {{ whatsappEnabled ? 'إلغاء تفعيل' : 'تفعيل' }} WhatsApp
    </button>

    <!-- Admin Phones -->
    <div class="setting-group">
      <h3>أرقام الأدمن</h3>
      <div v-for="(phone, index) in adminPhones" :key="index">
        <input v-model="adminPhones[index]" type="text" />
        <button @click="removeAdminPhone(index)">حذف</button>
      </div>
      <button @click="addAdminPhone">إضافة رقم</button>
      <button @click="saveAdminPhones">حفظ</button>
    </div>

    <!-- Delivery Phones -->
    <div class="setting-group">
      <h3>أرقام المندوبين</h3>
      <div v-for="(phone, index) in deliveryPhones" :key="index">
        <input v-model="deliveryPhones[index]" type="text" />
        <button @click="removeDeliveryPhone(index)">حذف</button>
      </div>
      <button @click="addDeliveryPhone">إضافة رقم</button>
      <button @click="saveDeliveryPhones">حفظ</button>
    </div>

    <!-- Test Connection -->
    <div class="test-section">
      <h3>اختبار الاتصال</h3>
      <input v-model="testPhone" type="text" placeholder="رقم الاختبار" />
      <button @click="testConnection">إرسال رسالة تجريبية</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const loading = ref(false);
const whatsappEnabled = ref(true);
const adminPhones = ref<string[]>([]);
const deliveryPhones = ref<string[]>([]);
const testPhone = ref('');

onMounted(() => {
  fetchSettings();
});

const fetchSettings = async () => {
  const response = await fetch('/api/v1/admin/whatsapp', {
    headers: {
      'Authorization': `Bearer ${adminToken}`,
    },
  });
  const data = await response.json();
  if (data.success) {
    data.data.forEach((setting: any) => {
      if (setting.key === 'whatsapp_enabled') {
        whatsappEnabled.value = setting.value;
      } else if (setting.key === 'admin_phones') {
        adminPhones.value = setting.value || [];
      } else if (setting.key === 'delivery_phones') {
        deliveryPhones.value = setting.value || [];
      }
    });
  }
};

const toggleGlobal = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/admin/whatsapp/toggle-global', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
      },
    });
    const data = await response.json();
    if (data.success) {
      whatsappEnabled.value = data.data.whatsapp_enabled;
      alert(data.message);
    }
  } finally {
    loading.value = false;
  }
};

const saveAdminPhones = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/admin/whatsapp/admin_phones', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        value: adminPhones.value.filter(p => p.trim()),
        is_active: true,
      }),
    });
    const data = await response.json();
    if (data.success) {
      alert('تم حفظ أرقام الأدمن');
    }
  } finally {
    loading.value = false;
  }
};

const saveDeliveryPhones = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/admin/whatsapp/delivery_phones', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        value: deliveryPhones.value.filter(p => p.trim()),
        is_active: true,
      }),
    });
    const data = await response.json();
    if (data.success) {
      alert('تم حفظ أرقام المندوبين');
    }
  } finally {
    loading.value = false;
  }
};

const addAdminPhone = () => {
  adminPhones.value.push('');
};

const removeAdminPhone = (index: number) => {
  adminPhones.value.splice(index, 1);
};

const addDeliveryPhone = () => {
  deliveryPhones.value.push('');
};

const removeDeliveryPhone = (index: number) => {
  deliveryPhones.value.splice(index, 1);
};

const testConnection = async () => {
  if (!testPhone.value) {
    alert('الرجاء إدخال رقم الهاتف');
    return;
  }

  loading.value = true;
  try {
    const response = await fetch('/api/v1/admin/whatsapp/test', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        phone: testPhone.value,
        message: 'This is a test message from Soapy Shop',
      }),
    });
    const data = await response.json();
    alert(data.success ? 'تم إرسال الرسالة بنجاح ✅' : `فشل: ${data.error}`);
  } finally {
    loading.value = false;
  }
};
</script>
```

---

## 🔄 كيفية عمل النظام

### 1. تخزين الإعدادات

جميع الإعدادات محفوظة في قاعدة البيانات في جدول `whatsapp_settings`:

```sql
SELECT * FROM whatsapp_settings;
```

---

### 2. التخزين المؤقت (Cache)

النظام يستخدم Laravel Cache لتحسين الأداء:
- كل إعداد يُخزن مؤقتاً لمدة ساعة
- عند التحديث، يتم مسح الـ Cache تلقائياً

---

### 3. منطق الإرسال

عند دفع طلب جديد:

```
1. التحقق من تفعيل WhatsApp العام (whatsapp_enabled)
   ↓
2. التحقق من تفعيل إشعارات الأدمن (admin_notification_enabled)
   ↓
3. التحقق من وجود أرقام أدمن (admin_phones)
   ↓
4. إرسال الرسالة لكل رقم أدمن
   ↓
5. نفس الخطوات لإشعارات المندوبين
```

---

## ⚙️ الإعدادات المتاحة

### 1. whatsapp_enabled
- **النوع:** Boolean
- **الوصف:** تفعيل/إلغاء تفعيل جميع رسائل WhatsApp
- **القيمة الافتراضية:** `true`

### 2. admin_phones
- **النوع:** Array
- **الوصف:** قائمة أرقام WhatsApp للأدمن
- **مثال:** `["201062532581", "201234567890"]`
- **يمكن أن يكون فارغاً:** نعم

### 3. delivery_phones
- **النوع:** Array
- **الوصف:** قائمة أرقام WhatsApp للمندوبين
- **مثال:** `["201062532581"]`
- **يمكن أن يكون فارغاً:** نعم

### 4. admin_notification_enabled
- **النوع:** Boolean
- **الوصف:** تفعيل/إلغاء تفعيل إشعارات الأدمن فقط
- **القيمة الافتراضية:** `true`

### 5. delivery_notification_enabled
- **النوع:** Boolean
- **الوصف:** تفعيل/إلغاء تفعيل إشعارات المندوبين فقط
- **القيمة الافتراضية:** `true`

### 6. whatsapp_base_url
- **النوع:** String
- **الوصف:** Base URL لـ WhatsApp API
- **القيمة الافتراضية:** `https://api.ultramsg.com`

### 7. logo_url
- **النوع:** String
- **الوصف:** رابط الشعار المستخدم في الرسائل
- **القيمة الافتراضية:** `https://soapy-bubbles.com/logo.png`

---

## 🎯 حالات الاستخدام

### Case 1: إيقاف جميع الإشعارات مؤقتاً

```bash
POST /api/v1/admin/whatsapp/toggle-global
```

---

### Case 2: تعطيل إشعارات المندوبين فقط

```bash
POST /api/v1/admin/whatsapp/toggle-delivery
```

---

### Case 3: إضافة رقم أدمن جديد

```bash
PUT /api/v1/admin/whatsapp/admin_phones

{
  "value": ["201062532581", "201234567890", "201555555555"]
}
```

---

### Case 4: حذف جميع أرقام المندوبين

```bash
PUT /api/v1/admin/whatsapp/delivery_phones

{
  "value": []
}
```

---

## ✅ المميزات

1. ✅ **أرقام متعددة:** يمكن إضافة عدد غير محدود من الأرقام
2. ✅ **تحكم منفصل:** تفعيل/إلغاء تفعيل كل نوع على حدة
3. ✅ **مرونة:** يمكن ترك الأرقام فارغة
4. ✅ **أداء:** التخزين المؤقت للإعدادات
5. ✅ **سهولة:** APIs بسيطة وواضحة
6. ✅ **اختبار:** إمكانية اختبار الاتصال

---

## 📊 الملفات المُنشأة

| الملف | الوصف |
|-------|--------|
| `database/migrations/2025_10_27_150000_create_whatsapp_settings_table.php` | Migration للجدول |
| `app/Models/WhatsAppSetting.php` | Model للإعدادات |
| `app/Services/WhatsAppService.php` | تم تحديثه لاستخدام النظام الجديد |
| `app/Http/Controllers/Api/Admin/WhatsAppController.php` | Controller للـ APIs |
| `routes/api.php` | تم إضافة Routes |

---

**🎉 النظام جاهز للاستخدام الآن!**

