# Inventory System - Quick Start Guide

## خطوات سريعة للبدء

### 1️⃣ تشغيل Migration
```bash
php artisan migrate
```

هذا سينشئ:
- حقول المخزون في جدول `products`
- جدول `inventory_transactions` لتتبع الحركات

---

### 2️⃣ إنشاء منتج مع مخزون

```bash
curl -X POST http://localhost:8000/api/v1/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "صابون اللافندر",
    "description": "صابون طبيعي برائحة اللافندر",
    "price": 12.5,
    "category_id": 1,
    "images": ["image1.jpg"],
    "has_inventory": true,
    "stock_quantity": 100,
    "low_stock_threshold": 20
  }'
```

---

### 3️⃣ عرض إحصائيات المخزون

```bash
curl -X GET http://localhost:8000/api/v1/admin/inventory/statistics \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 4️⃣ تعديل المخزون

```bash
curl -X POST http://localhost:8000/api/v1/admin/inventory/products/1/adjust \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "increase",
    "quantity": 50,
    "reason": "purchase",
    "notes": "استلام شحنة جديدة"
  }'
```

---

## أمثلة سريعة

### منتج بمخزون
```json
{
  "has_inventory": true,
  "stock_quantity": 50
}
```
✅ يخصم عند الدفع  
⚠️ ينبه عند الوصول للحد الأدنى

### منتج بدون مخزون
```json
{
  "has_inventory": false,
  "stock_quantity": null
}
```
✅ متاح دائماً  
✅ لا يخصم أبداً

---

## APIs الأساسية

| API | الغرض |
|-----|-------|
| `GET /api/v1/admin/inventory/statistics` | إحصائيات عامة |
| `GET /api/v1/admin/inventory/products` | قائمة المنتجات مع فلترة |
| `POST /api/v1/admin/inventory/products/{id}/adjust` | تعديل المخزون |
| `GET /api/v1/admin/inventory/products/{id}/transactions` | سجل الحركات |

---

## الملفات المهمة

📄 **التوثيق الكامل:** `INVENTORY_SYSTEM_DOCUMENTATION.md`  
📄 **Migration:** `database/migrations/2025_10_24_150000_add_inventory_to_products_table.php`  
📄 **Model:** `app/Models/Product.php`, `app/Models/InventoryTransaction.php`  
📄 **Controller:** `app/Http/Controllers/Api/Admin/InventoryController.php`  
📄 **Routes:** `routes/api.php`

---

## منطق الخصم

```
إنشاء طلب → pending → لا خصم ❌
      ↓
دفع ناجح → paid → خصم تلقائي ✅
```

**الكود:**
```php
// في PaymentController عند نجاح الدفع
if ($paymentStatus['data']['InvoiceStatus'] === 'Paid') {
    $order->update(['status' => 'paid']);
    
    // خصم المخزون
    $order->load('orderItems.product');
    $order->deductInventory();
}
```

---

## الخلاصة

✅ **جاهز للاستخدام**  
✅ **آمن** (المنتجات الحالية لا تتأثر)  
✅ **بسيط** (غير معقد)  
✅ **مرن** (اختياري لكل منتج)

**ابدأ الآن! 🚀**

