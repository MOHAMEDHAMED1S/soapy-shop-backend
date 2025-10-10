# 🧼 Soapy Shop - متجر الصابون الطبيعي

## 📋 نظرة عامة

Soapy Shop هو نظام تجارة إلكترونية متكامل مصمم خصيصاً لبيع منتجات الصابون الطبيعي والعناية بالبشرة. النظام مبني على Laravel ويوفر APIs شاملة لإدارة المنتجات، الطلبات، المدفوعات، العملاء، وأكواد الخصم.

## ✨ الميزات الرئيسية

### 🛍️ للعملاء
- **تصفح المنتجات** مع فلاتر متقدمة
- **نظام طلبات متكامل** مع دعم الخصومات
- **دفع آمن** عبر MyFatoorah
- **تتبع الطلبات** في الوقت الفعلي
- **أكواد خصم ذكية** مع شروط متعددة

### 👨‍💼 للمديرين
- **لوحة تحكم شاملة** مع إحصائيات مفصلة
- **إدارة المنتجات والفئات** مع دعم الصور
- **إدارة الطلبات المتقدمة** مع تحديث الحالات
- **نظام العملاء** مع تحليلات مفصلة
- **إدارة أكواد الخصم** مع تتبع الاستخدام
- **نظام الإشعارات** مع تفضيلات مخصصة

## 🚀 البدء السريع

### المتطلبات
- PHP 8.1+
- Composer
- MySQL 8.0+
- Node.js 16+ (للموارد الأمامية)

### التثبيت

1. **استنساخ المشروع**
```bash
git clone https://github.com/your-username/soapy-shop.git
cd soapy-shop
```

2. **تثبيت التبعيات**
```bash
composer install
npm install
```

3. **إعداد البيئة**
```bash
cp .env.example .env
php artisan key:generate
```

4. **تكوين قاعدة البيانات**
```bash
# تحديث ملف .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=soapy_shop
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **تشغيل Migrations**
```bash
php artisan migrate
php artisan db:seed
```

6. **تشغيل الخادم**
```bash
php artisan serve
```

## 📚 الوثائق

### 📖 وثائق API الرئيسية
- **[API Documentation](API_DOCUMENTATION.md)** - دليل شامل لجميع APIs
- **[API Examples](API_EXAMPLES.md)** - أمثلة عملية للاستخدام

### 🔧 وثائق إضافية
- **[Project Setup](PROJECT_SETUP.md)** - دليل إعداد المشروع
- **[Webhook Documentation](WEBHOOK_DOCUMENTATION.md)** - دليل Webhooks
- **[Dashboard Documentation](DASHBOARD_DOCUMENTATION.md)** - دليل لوحة التحكم
- **[Order Management](ORDER_MANAGEMENT_DOCUMENTATION.md)** - دليل إدارة الطلبات
- **[Discount System](DISCOUNT_SYSTEM_DOCUMENTATION.md)** - دليل نظام الخصومات

## 🛠️ APIs المتاحة

### 🛍️ APIs العملاء (Public)
- **المنتجات**: تصفح، بحث، فلترة
- **الفئات**: شجرة الفئات، المنتجات حسب الفئة
- **الطلبات**: إنشاء، حساب، تتبع، إلغاء
- **الدفع**: طرق الدفع، بدء الدفع، التحقق من الحالة
- **أكواد الخصم**: عرض، تفاصيل، التحقق من الصحة

### 👨‍💼 APIs المدير (Admin)
- **المصادقة**: تسجيل دخول، معلومات المدير
- **لوحة التحكم**: إحصائيات، تحليلات، تقارير
- **المنتجات**: CRUD، إدارة الصور، تبديل التوفر
- **الفئات**: CRUD، شجرة الفئات
- **الطلبات**: إدارة متقدمة، تحديث الحالات، تصدير
- **العملاء**: إدارة، تحليلات، هجرة البيانات
- **أكواد الخصم**: CRUD، إحصائيات، تتبع الاستخدام
- **الإشعارات**: إدارة، تفضيلات، إحصائيات
- **الصور**: رفع، إدارة، تحسين

## 🔐 المصادقة

### للعملاء
- لا توجد مصادقة مطلوبة للـ APIs العامة
- التحقق يتم عبر رقم الهاتف للطلبات

### للمديرين
- JWT Authentication مطلوب
- احصل على token من: `POST /api/v1/admin/login`

```bash
curl -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "admin123"
  }'
```

## 📊 قاعدة البيانات

### الجداول الرئيسية
- **users** - المستخدمين (المديرين)
- **customers** - العملاء
- **categories** - الفئات
- **products** - المنتجات
- **orders** - الطلبات
- **order_items** - عناصر الطلبات
- **payments** - المدفوعات
- **discount_codes** - أكواد الخصم
- **discount_code_usage** - استخدام أكواد الخصم
- **admin_notifications** - إشعارات المدير
- **webhook_logs** - سجلات Webhooks

## 🎯 أمثلة سريعة

### إنشاء طلب مع خصم
```bash
curl -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "+96512345678",
    "customer_email": "ahmed@example.com",
    "shipping_address": {
      "street": "شارع الخليج العربي",
      "city": "الكويت",
      "governorate": "الكويت"
    },
    "items": [
      {
        "product_id": 1,
        "quantity": 2
      }
    ],
    "discount_code": "SAVE20",
    "shipping_amount": 5
  }'
```

### جلب إحصائيات المدير
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/overview" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 🔧 الأوامر المفيدة

### Artisan Commands
```bash
# إنشاء إشعار تجريبي
php artisan app:create-test-notification

# إحصائيات لوحة التحكم
php artisan app:dashboard-stats

# إدارة أكواد الخصم
php artisan app:discount-code-management

# هجرة الطلبات للعملاء
php artisan app:migrate-orders-to-customers

# تنظيف الإشعارات القديمة
php artisan app:cleanup-notifications

# إحصائيات الصور
php artisan app:image-stats
```

## 🧪 الاختبار

### تشغيل الاختبارات
```bash
# جميع الاختبارات
php artisan test

# اختبارات محددة
php artisan test --filter=OrderTest
```

### اختبار APIs
```bash
# اختبار إنشاء طلب
curl -X POST "http://localhost:8000/api/v1/checkout/create-order" \
  -H "Content-Type: application/json" \
  -d '{"customer_name":"Test","customer_phone":"+96512345678","shipping_address":{"street":"Test","city":"Test","governorate":"Test"},"items":[{"product_id":1,"quantity":1}]}'
```

## 📈 الأداء

### تحسينات مطبقة
- **Pagination** لجميع القوائم
- **Eager Loading** للعلاقات
- **Caching** للإحصائيات
- **Image Optimization** للصور
- **Database Indexing** للاستعلامات السريعة

### مراقبة الأداء
- **Laravel Telescope** للتطوير
- **Query Logging** لمراقبة الاستعلامات
- **Performance Metrics** في لوحة التحكم

## 🔒 الأمان

### إجراءات الأمان
- **JWT Authentication** للمديرين
- **CSRF Protection** للنماذج
- **Input Validation** لجميع المدخلات
- **SQL Injection Protection** عبر Eloquent
- **XSS Protection** في المخرجات
- **Rate Limiting** للـ APIs

## 🚀 النشر

### متطلبات الإنتاج
- **PHP 8.1+** مع OPcache
- **MySQL 8.0+** مع InnoDB
- **Redis** للتخزين المؤقت
- **Nginx/Apache** كخادم ويب
- **SSL Certificate** للأمان

### خطوات النشر
1. إعداد الخادم
2. رفع الملفات
3. تثبيت التبعيات
4. تشغيل Migrations
5. تكوين الخادم
6. اختبار النظام

## 🤝 المساهمة

### كيفية المساهمة
1. Fork المشروع
2. إنشاء branch جديد
3. إجراء التغييرات
4. إضافة الاختبارات
5. إرسال Pull Request

### معايير الكود
- اتباع PSR-12
- كتابة تعليقات واضحة
- إضافة اختبارات للوظائف الجديدة
- تحديث الوثائق

## 📞 الدعم

### معلومات الاتصال
- **البريد الإلكتروني**: support@soapyshop.com
- **الهاتف**: +965 1234 5678
- **ساعات العمل**: 9:00 ص - 6:00 م (بتوقيت الكويت)

### الموارد
- **GitHub Issues**: للإبلاغ عن الأخطاء
- **Documentation**: للدليل الشامل
- **API Reference**: لمرجع APIs
- **Examples**: للأمثلة العملية

## 📄 الترخيص

هذا المشروع مرخص تحت رخصة MIT. راجع ملف [LICENSE](LICENSE) للتفاصيل.

## 🙏 شكر وتقدير

- **Laravel Framework** - إطار العمل الأساسي
- **MyFatoorah** - بوابة الدفع
- **Intervention Image** - معالجة الصور
- **JWT** - المصادقة
- **جميع المساهمين** - للمساعدة في التطوير

---

**تم تطوير Soapy Shop بحب ❤️ لبيع أجود منتجات الصابون الطبيعي** 🧼✨

*آخر تحديث: 3 أكتوبر 2025*