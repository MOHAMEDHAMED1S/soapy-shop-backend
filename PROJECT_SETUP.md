# 🛍️ soapy bubbles - E-Commerce Backend

## تم إنجاز التهيئة الأساسية للمشروع ✅

### ما تم إنجازه:

1. **تثبيت المكتبات المطلوبة:**
   - Laravel Framework 12.32.5
   - JWT Authentication (tymon/jwt-auth)
   - Laravel Sanctum
   - MyFatoorah Laravel Package

2. **إعداد قاعدة البيانات:**
   - تكوين MySQL
   - إنشاء جميع الجداول المطلوبة:
     - users (المستخدمين)
     - categories (الفئات)
     - products (المنتجات)
     - orders (الطلبات)
     - order_items (عناصر الطلبات)
     - payments (المدفوعات)
     - webhook_logs (سجلات الـ webhook)
     - admin_notifications (إشعارات المدير)

3. **إنشاء النماذج (Models):**
   - جميع النماذج مع العلاقات المطلوبة
   - إعداد JWT للمستخدمين
   - إعداد الـ casting للبيانات

4. **إعداد المصادقة:**
   - JWT Authentication
   - Admin Middleware
   - API Guards

5. **إنشاء API Routes:**
   - Public APIs للعملاء
   - Protected Admin APIs
   - هيكل منظم للـ endpoints

6. **إنشاء Controllers الأساسية:**
   - API Controllers للمنتجات والفئات والطلبات
   - Admin Controllers للإدارة
   - Auth Controller للمصادقة

### بيانات المدير الافتراضي:
- **Email:** admin@soapyshop.com
- **Password:** admin123

### إعدادات قاعدة البيانات:
```
DB_CONNECTION=mysql
DB_HOST=92.113.22.50
DB_PORT=3306
DB_DATABASE=u394448851_default
DB_USERNAME=u394448851_default
DB_PASSWORD=Boostlykw2025
```

### الخطوات التالية المطلوبة:

1. **إعداد MyFatoorah:**
   - تحديث مفاتيح API في ملف .env
   - تنفيذ منطق الدفع في PaymentController

2. **تطوير Controllers:**
   - إضافة منطق العمليات CRUD
   - تنفيذ validation
   - إضافة معالجة الأخطاء

3. **إضافة Features:**
   - رفع الصور
   - البحث والفلترة
   - إدارة المخزون
   - نظام الإشعارات

4. **Testing:**
   - اختبار APIs
   - اختبار المصادقة
   - اختبار عمليات الدفع

### API Endpoints الأساسية:

#### Public APIs:
- `GET /api/v1/products` - قائمة المنتجات
- `GET /api/v1/products/{slug}` - تفاصيل منتج
- `GET /api/v1/categories` - قائمة الفئات
- `POST /api/v1/checkout/create-order` - إنشاء طلب
- `GET /api/v1/orders/{orderNumber}` - تفاصيل طلب

#### Admin APIs:
- `POST /api/v1/admin/login` - تسجيل دخول المدير
- `GET /api/v1/admin/me` - بيانات المدير
- `GET /api/v1/admin/orders` - قائمة الطلبات
- `POST /api/v1/admin/products` - إضافة منتج
- `PUT /api/v1/admin/products/{id}` - تحديث منتج

### تشغيل المشروع:
```bash
php artisan serve
```

المشروع جاهز للتطوير! 🚀
