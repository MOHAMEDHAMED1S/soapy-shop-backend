# دليل WhatsApp Settings Seeder 🌱

**التاريخ:** 2025-10-27  
**الحالة:** ✅ جاهز للاستخدام

---

## 📋 نظرة عامة

تم إنشاء `WhatsAppSettingsSeeder` لتعبئة جدول `whatsapp_settings` بالبيانات الافتراضية تلقائياً.

---

## 📁 الملف

```
database/seeders/WhatsAppSettingsSeeder.php
```

---

## 🌱 البيانات الافتراضية

يقوم الـ Seeder بإدخال 7 إعدادات:

| Key | القيمة الافتراضية | النوع | الوصف |
|-----|-------------------|-------|--------|
| `whatsapp_enabled` | `true` | Boolean | تفعيل/إلغاء تفعيل WhatsApp عموماً |
| `whatsapp_base_url` | `https://wapi.soapy-bubbles.com` | String | Base URL للـ API |
| `admin_phones` | `["201062532581"]` | Array | أرقام الأدمن |
| `delivery_phones` | `["201062532581"]` | Array | أرقام المندوبين |
| `admin_notification_enabled` | `true` | Boolean | تفعيل إشعارات الأدمن |
| `delivery_notification_enabled` | `true` | Boolean | تفعيل إشعارات المندوبين |
| `logo_url` | `https://soapy-bubbles.com/logo.png` | String | رابط الشعار |

**ملاحظة:** القيم يتم قراءتها من `.env` إذا كانت موجودة:
- `WHATSAPP_API_URL` → `whatsapp_base_url`
- `ADMIN_WHATSAPP_PHONE` → `admin_phones`
- `DELIVERY_WHATSAPP_PHONE` → `delivery_phones`

---

## 🚀 طرق التشغيل

### 1. تشغيل WhatsAppSettingsSeeder فقط

```bash
php artisan db:seed --class=WhatsAppSettingsSeeder
```

**النتيجة:**
```
✅ تم إدخال 7 إعدادات WhatsApp بنجاح!

📋 الإعدادات المُدخلة:
┌───────────────────────────────┬────────────────────────────────────┬─────────┬────────┐
│ Key                           │ Value                              │ Type    │ Active │
├───────────────────────────────┼────────────────────────────────────┼─────────┼────────┤
│ whatsapp_enabled              │ true                               │ boolean │ ✓      │
│ whatsapp_base_url             │ https://wapi.soapy-bubbles.com     │ string  │ ✓      │
│ admin_phones                  │ ["201062532581"]                   │ array   │ ✓      │
│ delivery_phones               │ ["201062532581"]                   │ array   │ ✓      │
│ admin_notification_enabled    │ true                               │ boolean │ ✓      │
│ delivery_notification_enabled │ true                               │ boolean │ ✓      │
│ logo_url                      │ https://soapy-bubbles.com/logo.png │ string  │ ✓      │
└───────────────────────────────┴────────────────────────────────────┴─────────┴────────┘
```

---

### 2. تشغيل جميع الـ Seeders (يشمل WhatsApp)

```bash
php artisan db:seed
```

سيقوم بتشغيل:
- `AdminUserSeeder`
- `CategorySeeder`
- `ProductSeeder`
- `PaymentSeeder`
- ✅ `WhatsAppSettingsSeeder` (جديد)

---

### 3. إعادة التشغيل مع Migration

```bash
php artisan migrate:fresh --seed
```

⚠️ **تحذير:** هذا الأمر سيحذف جميع البيانات ويعيد إنشائها!

---

## 🔄 إعادة التعبئة

إذا كنت تريد إعادة تعبئة إعدادات WhatsApp فقط:

```bash
# سيقوم بحذف البيانات القديمة وإدخال البيانات الجديدة
php artisan db:seed --class=WhatsAppSettingsSeeder
```

**ملاحظة:** الـ Seeder يستخدم `truncate()` لحذف البيانات القديمة قبل إدخال الجديدة.

---

## 🛠️ تخصيص القيم الافتراضية

### الطريقة 1: عبر `.env`

قم بإضافة هذه المتغيرات في `.env`:

```env
WHATSAPP_API_URL=https://wapi.soapy-bubbles.com
ADMIN_WHATSAPP_PHONE=201062532581
DELIVERY_WHATSAPP_PHONE=201062532581
```

ثم قم بتشغيل الـ Seeder:

```bash
php artisan db:seed --class=WhatsAppSettingsSeeder
```

---

### الطريقة 2: تعديل الـ Seeder مباشرة

قم بتعديل الملف:

```php
database/seeders/WhatsAppSettingsSeeder.php
```

وغيّر القيم الافتراضية حسب الحاجة.

---

## 📊 التحقق من البيانات

### 1. عبر Tinker

```bash
php artisan tinker

>>> use App\Models\WhatsAppSetting;
>>> WhatsAppSetting::count();
=> 7

>>> WhatsAppSetting::all(['key', 'value']);
```

---

### 2. عبر قاعدة البيانات

```bash
php artisan db:table whatsapp_settings
```

---

### 3. عبر API

```bash
GET /api/v1/admin/whatsapp
Authorization: Bearer {admin_token}
```

---

## 🔍 استكشاف الأخطاء

### خطأ: "Table 'whatsapp_settings' doesn't exist"

**الحل:**
```bash
php artisan migrate
```

---

### خطأ: "Class 'WhatsAppSettingsSeeder' not found"

**الحل:**
```bash
composer dump-autoload
php artisan db:seed --class=WhatsAppSettingsSeeder
```

---

### خطأ: "Duplicate key"

**السبب:** الجدول يحتوي على بيانات بالفعل  
**الحل:** الـ Seeder يستخدم `truncate()` تلقائياً، لكن إذا كانت المشكلة مستمرة:

```bash
# حذف البيانات يدوياً
php artisan tinker
>>> DB::table('whatsapp_settings')->truncate();

# إعادة التشغيل
php artisan db:seed --class=WhatsAppSettingsSeeder
```

---

## 🎯 حالات الاستخدام

### 1. الإعداد الأولي للمشروع

```bash
# تشغيل جميع الـ migrations والـ seeders
php artisan migrate:fresh --seed
```

---

### 2. إضافة إعدادات WhatsApp لمشروع موجود

```bash
# تشغيل الـ migration فقط
php artisan migrate

# تشغيل الـ seeder
php artisan db:seed --class=WhatsAppSettingsSeeder
```

---

### 3. إعادة تعيين إعدادات WhatsApp للافتراضية

```bash
php artisan db:seed --class=WhatsAppSettingsSeeder
```

---

## ✅ ما بعد التشغيل

بعد تشغيل الـ Seeder بنجاح:

1. ✅ **التحقق:** تأكد من وجود 7 إعدادات
2. ✅ **الاختبار:** اختبر الـ APIs
3. ✅ **التخصيص:** عدّل الإعدادات حسب الحاجة عبر API

---

## 📝 ملاحظات مهمة

1. **Truncate:** الـ Seeder يحذف البيانات القديمة قبل إدخال الجديدة
2. **Environment Variables:** يقرأ من `.env` إذا كانت موجودة
3. **Timestamps:** يتم تعيين `created_at` و `updated_at` تلقائياً
4. **Active by Default:** جميع الإعدادات تكون مفعلة افتراضياً

---

## 🔗 ملفات مرتبطة

- **Migration:** `database/migrations/2025_10_27_150000_create_whatsapp_settings_table.php`
- **Model:** `app/Models/WhatsAppSetting.php`
- **Controller:** `app/Http/Controllers/Api/Admin/WhatsAppController.php`
- **Service:** `app/Services/WhatsAppService.php`

---

## 📚 المزيد من التوثيق

- **التوثيق الكامل:** `WHATSAPP_SETTINGS_MANAGEMENT.md`
- **نتائج الاختبارات:** `WHATSAPP_TESTING_RESULTS.md`
- **الملخص النهائي:** `WHATSAPP_FINAL_SUMMARY.md`

---

**✅ الآن نظام WhatsApp جاهز بالكامل مع البيانات الافتراضية!**

