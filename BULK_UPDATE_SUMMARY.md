# ملخص Bulk Update للمنتجات ✅

## ✨ الإجراءات الجديدة المضافة

### 1. update_price
تحديث سعر عدة منتجات دفعة واحدة

```json
{
  "product_ids": [54],
  "action": "update_price",
  "price": "2"
}
```

---

### 2. update_category
تحديث فئة عدة منتجات دفعة واحدة

```json
{
  "product_ids": [54],
  "action": "update_category",
  "category_id": 1
}
```

---

## 📝 جميع الإجراءات المدعومة

1. ✅ `activate` - تفعيل المنتجات
2. ✅ `deactivate` - إلغاء تفعيل المنتجات
3. ✅ `delete` - حذف المنتجات
4. ✅ `change_category` - تغيير الفئة (قديم)
5. ✨ `update_category` - تحديث الفئة (جديد)
6. ✨ `update_price` - تحديث السعر (جديد)

---

## 🔧 API

```
POST /api/v1/admin/products/bulk-update
```

---

## 📄 التوثيق الكامل

انظر: `BULK_UPDATE_PRODUCTS.md`

---

**🎉 تم إضافة الدعم بنجاح!**

