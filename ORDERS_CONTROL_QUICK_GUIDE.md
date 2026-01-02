# دليل سريع - التحكم في الطلبات 

---

## 🎯 الهدف

نظام بسيط لتفعيل/إلغاء تفعيل الطلبات على الموقع

---

## 📋 APIs

### للعملاء (Public - بدون Auth)

```bash
GET /api/v1/site/orders-status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders_enabled": true,  // true = مفتوحة, false = مغلقة
    "status": "open",        // "open" أو "closed"
    "message": "الطلبات مفتوحة حالياً"
  }
}
```

---

### للأدمن (Admin - يحتاج Auth)

#### 1. جلب الحالة
```bash
GET /api/v1/admin/site/orders-status
Authorization: Bearer {admin_token}
```

#### 2. تبديل الحالة (Toggle)
```bash
POST /api/v1/admin/site/toggle-orders
Authorization: Bearer {admin_token}
```

#### 3. تعيين حالة محددة
```bash
POST /api/v1/admin/site/set-orders-status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "enabled": false  // true للفتح, false للإغلاق
}
```

---

## 💻 مثال React سريع

### للعملاء:
```typescript
const { data } = await fetch('/api/v1/site/orders-status').then(r => r.json());

if (!data.orders_enabled) {
  return <div>⚠️ الطلبات مغلقة حالياً</div>;
}
```

### للأدمن:
```typescript
// Toggle
const response = await fetch('/api/v1/admin/site/toggle-orders', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` }
});

const { data, message } = await response.json();
console.log(message); // "تم إغلاق الطلبات بنجاح"
console.log(data.orders_enabled); // false
```

---

## 📁 الملفات

- **Migration:** `2025_10_27_160000_create_site_settings_table.php`
- **Model:** `SiteSetting.php`
- **Controllers:** `SiteSettingController.php`, `SiteController.php`
- **Routes:** `api.php`

---

## 🎉 جاهز!

**التوثيق الكامل:** `ORDERS_CONTROL_API.md`

