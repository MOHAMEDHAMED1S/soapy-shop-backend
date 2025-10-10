# 📚 فهرس الوثائق - Soapy Shop

## 🗂️ هيكل الوثائق

### 📖 الوثائق الرئيسية
- **[README.md](README.md)** - نظرة عامة على المشروع
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - دليل APIs الشامل
- **[API_EXAMPLES.md](API_EXAMPLES.md)** - أمثلة عملية للاستخدام

### 🔧 وثائق التطوير
- **[PROJECT_SETUP.md](PROJECT_SETUP.md)** - دليل إعداد المشروع
- **[WEBHOOK_DOCUMENTATION.md](WEBHOOK_DOCUMENTATION.md)** - دليل Webhooks
- **[DASHBOARD_DOCUMENTATION.md](DASHBOARD_DOCUMENTATION.md)** - دليل لوحة التحكم
- **[ORDER_MANAGEMENT_DOCUMENTATION.md](ORDER_MANAGEMENT_DOCUMENTATION.md)** - دليل إدارة الطلبات
- **[DISCOUNT_SYSTEM_DOCUMENTATION.md](DISCOUNT_SYSTEM_DOCUMENTATION.md)** - دليل نظام الخصومات

---

## 🚀 البدء السريع

### للمطورين الجدد
1. اقرأ [README.md](README.md) للحصول على نظرة عامة
2. اتبع [PROJECT_SETUP.md](PROJECT_SETUP.md) لإعداد المشروع
3. راجع [API_DOCUMENTATION.md](API_DOCUMENTATION.md) لفهم APIs
4. جرب [API_EXAMPLES.md](API_EXAMPLES.md) للبدء السريع

### للمطورين المتقدمين
1. راجع [API_DOCUMENTATION.md](API_DOCUMENTATION.md) للتفاصيل الكاملة
2. ادرس [WEBHOOK_DOCUMENTATION.md](WEBHOOK_DOCUMENTATION.md) للتكامل
3. استخدم [API_EXAMPLES.md](API_EXAMPLES.md) للأمثلة المتقدمة

---

## 📋 دليل APIs

### 🛍️ APIs العملاء
| الوظيفة | API | الوثيقة |
|---------|-----|---------|
| المنتجات | `GET /api/v1/products` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#المنتجات) |
| الفئات | `GET /api/v1/categories` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#الفئات) |
| إنشاء طلب | `POST /api/v1/checkout/create-order` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#الطلبات-والدفع) |
| حساب المجموع | `POST /api/v1/checkout/calculate-total` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#الطلبات-والدفع) |
| التحقق من الخصم | `POST /api/v1/checkout/validate-discount` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#الطلبات-والدفع) |
| المدفوعات | `POST /api/v1/payments/initiate` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#الدفع) |
| أكواد الخصم | `GET /api/v1/discount-codes` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#أكواد-الخصم) |

### 👨‍💼 APIs المدير
| الوظيفة | API | الوثيقة |
|---------|-----|---------|
| المصادقة | `POST /api/v1/admin/login` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#المصادقة) |
| لوحة التحكم | `GET /api/v1/admin/dashboard/overview` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#لوحة-التحكم) |
| إدارة المنتجات | `GET /api/v1/admin/products` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#إدارة-المنتجات) |
| إدارة الطلبات | `GET /api/v1/admin/orders` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#إدارة-الطلبات) |
| إدارة العملاء | `GET /api/v1/admin/customers` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#إدارة-العملاء) |
| إدارة الخصومات | `GET /api/v1/admin/discount-codes` | [API_DOCUMENTATION.md](API_DOCUMENTATION.md#إدارة-أكواد-الخصم) |

---

## 🎯 أمثلة الاستخدام

### سيناريوهات شائعة
| السيناريو | الملف | الوصف |
|-----------|-------|--------|
| إنشاء طلب كامل | [API_EXAMPLES.md](API_EXAMPLES.md#سيناريو-كامل-من-إنشاء-الطلب-إلى-الدفع) | من حساب المجموع إلى الدفع |
| إدارة أكواد الخصم | [API_EXAMPLES.md](API_EXAMPLES.md#سيناريو-إدارة-أكواد-الخصم) | إنشاء وإدارة الخصومات |
| تسجيل دخول المدير | [API_EXAMPLES.md](API_EXAMPLES.md#تسجيل-دخول-المدير) | المصادقة والوصول |
| رفع الصور | [API_EXAMPLES.md](API_EXAMPLES.md#رفع-صورة-واحدة) | إدارة الصور |

### أمثلة سريعة
| الوظيفة | المثال | الملف |
|---------|--------|-------|
| جلب المنتجات | `curl -X GET /api/v1/products` | [API_EXAMPLES.md](API_EXAMPLES.md#جلب-جميع-المنتجات) |
| إنشاء طلب | `curl -X POST /api/v1/checkout/create-order` | [API_EXAMPLES.md](API_EXAMPLES.md#إنشاء-طلب-جديد) |
| تسجيل دخول | `curl -X POST /api/v1/admin/login` | [API_EXAMPLES.md](API_EXAMPLES.md#تسجيل-دخول-المدير) |

---

## 🔧 الأوامر المفيدة

### Artisan Commands
| الأمر | الوصف | الملف المرجعي |
|-------|--------|---------------|
| `php artisan app:create-test-notification` | إنشاء إشعار تجريبي | [PROJECT_SETUP.md](PROJECT_SETUP.md) |
| `php artisan app:dashboard-stats` | إحصائيات لوحة التحكم | [DASHBOARD_DOCUMENTATION.md](DASHBOARD_DOCUMENTATION.md) |
| `php artisan app:discount-code-management` | إدارة أكواد الخصم | [DISCOUNT_SYSTEM_DOCUMENTATION.md](DISCOUNT_SYSTEM_DOCUMENTATION.md) |
| `php artisan app:migrate-orders-to-customers` | هجرة الطلبات | [PROJECT_SETUP.md](PROJECT_SETUP.md) |

---

## 🐛 استكشاف الأخطاء

### أخطاء شائعة
| الخطأ | الحل | الملف المرجعي |
|-------|------|---------------|
| خطأ المصادقة | تأكد من إرسال token صحيح | [API_EXAMPLES.md](API_EXAMPLES.md#أخطاء-شائعة-وحلولها) |
| خطأ التحقق | تأكد من إرسال الحقول المطلوبة | [API_EXAMPLES.md](API_EXAMPLES.md#أخطاء-شائعة-وحلولها) |
| خطأ كود الخصم | تأكد من صحة الكود | [API_EXAMPLES.md](API_EXAMPLES.md#أخطاء-شائعة-وحلولها) |

### نصائح الأداء
| النصيحة | الوصف | الملف المرجعي |
|---------|--------|---------------|
| استخدام Pagination | لتجنب تحميل البيانات الكثيرة | [API_EXAMPLES.md](API_EXAMPLES.md#نصائح-الأداء) |
| استخدام الفلاتر | لتقليل النتائج | [API_EXAMPLES.md](API_EXAMPLES.md#نصائح-الأداء) |
| استخدام البحث | للعثور على البيانات بسرعة | [API_EXAMPLES.md](API_EXAMPLES.md#نصائح-الأداء) |

---

## 📊 إحصائيات النظام

### البيانات المدعومة
- **المنتجات**: 10,000 منتج
- **الطلبات**: 100,000 طلب/شهر
- **العملاء**: 50,000 عميل
- **أكواد الخصم**: 1,000 كود نشط

### حدود API
- **معدل الطلبات**: 1,000 طلب/ساعة
- **حجم الطلب**: 10MB كحد أقصى
- **مهلة الاستجابة**: 30 ثانية
- **حجم الصفحة**: 100 عنصر كحد أقصى

---

## 🔄 التحديثات

### الإصدار الحالي: v1.0.0
- ✅ إدارة المنتجات والفئات
- ✅ نظام الطلبات والدفع
- ✅ إدارة العملاء
- ✅ نظام أكواد الخصم
- ✅ لوحة تحكم المدير
- ✅ إدارة الصور
- ✅ نظام الإشعارات
- ✅ Webhooks

### التحديثات القادمة
- 🔄 API للتقارير المتقدمة
- 🔄 نظام الكوبونات
- 🔄 إدارة المخزون
- 🔄 نظام التقييمات
- 🔄 API للهاتف المحمول

---

## 📞 الدعم والمساعدة

### الموارد المتاحة
- **الوثائق**: هذا الفهرس
- **الأمثلة**: [API_EXAMPLES.md](API_EXAMPLES.md)
- **المرجع**: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **الدعم الفني**: support@soapyshop.com

### معلومات الاتصال
- **البريد الإلكتروني**: support@soapyshop.com
- **الهاتف**: +965 1234 5678
- **ساعات العمل**: 9:00 ص - 6:00 م (بتوقيت الكويت)

---

**تم إنشاء فهرس الوثائق بواسطة فريق Soapy Shop** 🧼✨

*آخر تحديث: 3 أكتوبر 2025*
