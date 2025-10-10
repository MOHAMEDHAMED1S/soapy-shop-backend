# 📊 ملخص شامل لـ APIs لوحة تحكم المدير

## 🎯 **نظرة عامة**

تم تطوير نظام شامل لـ APIs لوحة تحكم المدير يشمل جميع الوظائف المطلوبة لإدارة المتجر الإلكتروني بشكل كامل ومتقدم.

---

## 📋 **قائمة APIs المطورة**
---

## 🔐 المصادقة والتفويض

### JWT Authentication
جميع APIs المدير تتطلب مصادقة JWT. احصل على token من خلال:

```http
POST /api/v1/admin/login
```

**Headers المطلوبة:**
```http
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---
### 1. 📊 **الإحصائيات الشاملة (Dashboard Overview)**

#### **APIs الأساسية:**

##### `GET /api/v1/admin/dashboard/overview` - نظرة عامة شاملة

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/overview?period=30" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_orders": 150,
      "total_products": 45,
      "total_categories": 12,
      "total_revenue": "12500.500",
      "pending_orders": 8,
      "low_stock_products": 3,
      "unread_notifications": 5,
      "total_customers": 89,
      "active_customers": 82,
      "total_discount_codes": 15,
      "active_discount_codes": 12,
      "average_order_value": "83.337",
      "conversion_rate": "12.5"
    },
    "period_stats": {
      "orders_count": 45,
      "revenue": "3750.150",
      "new_products": 8,
      "completed_orders": 42
    },
    "growth": {
      "orders_growth": 15.5,
      "revenue_growth": 22.3
    },
    "period": 30,
    "date_range": {
      "start": "2025-09-02",
      "end": "2025-10-02"
    }
  },
  "message": "Dashboard overview retrieved successfully"
}
```

##### `GET /api/v1/admin/dashboard/sales-analytics` - تحليلات المبيعات

**المعاملات:**
- `period` (اختياري): عدد الأيام (افتراضي: 30)
- `group_by` (اختياري): التجميع (day, week, month)

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/sales-analytics?period=30&group_by=day" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "sales_data": [
      {
        "date": "2025-10-01",
        "orders_count": 5,
        "revenue": "450.250"
      },
      {
        "date": "2025-10-02",
        "orders_count": 8,
        "revenue": "720.100"
      }
    ],
    "summary": {
      "total_revenue": 3750.15,
      "total_orders": 45,
      "average_order_value": 83.34,
      "period": 30,
      "group_by": "day"
    }
  },
  "message": "Sales analytics retrieved successfully"
}
```

##### `GET /api/v1/admin/dashboard/real-time-stats` - إحصائيات فورية

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/real-time-stats" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "online_visitors": 23,
    "current_orders": 3,
    "today_revenue": "1250.750",
    "today_orders": 12,
    "recent_activities": [
      {
        "type": "order_created",
        "message": "طلب جديد #12345",
        "time": "2025-10-02T15:30:00Z"
      }
    ]
  },
  "message": "Real-time statistics retrieved successfully"
}
```

##### `GET /api/v1/admin/dashboard/system-health` - صحة النظام

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/system-health" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful"
    },
    "storage": {
      "status": "healthy",
      "usage_percentage": 45.2,
      "free_space": "2.5 GB",
      "total_space": "5.0 GB"
    },
    "api_response_time": {
      "response_time_ms": 125.5,
      "status": "good"
    },
    "last_backup": "2025-10-01T02:00:00Z",
    "system_load": {
      "cpu_usage": 45,
      "memory_usage": 62,
      "status": "normal"
    }
  },
  "message": "System health retrieved successfully"
}
```

#### **الميزات:**
- إحصائيات شاملة ومفصلة
- مقارنات النمو مع الفترات السابقة
- تحليلات متقدمة للمبيعات والمنتجات
- مراقبة صحة النظام
- إحصائيات فورية

---

### 2. 📂 **إدارة التصنيفات (Categories Management)**

#### **APIs الأساسية:**

##### `GET /api/v1/admin/categories` - قائمة التصنيفات

**المعاملات:**
- `search` (اختياري): البحث في الاسم والوصف
- `parent_id` (اختياري): تصفية حسب التصنيف الأب
- `is_active` (اختياري): تصفية حسب الحالة
- `sort_by` (اختياري): ترتيب حسب (created_at, name, sort_order)
- `sort_direction` (اختياري): اتجاه الترتيب (asc, desc)
- `per_page` (اختياري): عدد العناصر في الصفحة

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/categories?search=سيروم&is_active=true&per_page=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "سيروم",
        "slug": "serum",
        "description": "سيرومات العناية بالبشرة",
        "parent_id": null,
        "image": "categories/serum.jpg",
        "is_active": true,
        "sort_order": 1,
        "meta_title": "سيرومات العناية",
        "meta_description": "أفضل السيرومات للعناية بالبشرة",
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-01T10:00:00Z",
        "parent": null,
        "children": [
          {
            "id": 2,
            "name": "سيروم فيتامين سي",
            "slug": "vitamin-c-serum"
          }
        ]
      }
    ],
    "first_page_url": "http://localhost:8000/api/v1/admin/categories?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/v1/admin/categories?page=3",
    "links": [...],
    "next_page_url": "http://localhost:8000/api/v1/admin/categories?page=2",
    "path": "http://localhost:8000/api/v1/admin/categories",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  },
  "message": "Categories retrieved successfully"
}
```

##### `POST /api/v1/admin/categories` - إنشاء تصنيف جديد

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/categories" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "كريمات الوجه",
    "description": "كريمات العناية بالوجه",
    "parent_id": null,
    "image": "categories/face-creams.jpg",
    "is_active": true,
    "sort_order": 2,
    "meta_title": "كريمات الوجه",
    "meta_description": "أفضل كريمات العناية بالوجه"
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "كريمات الوجه",
    "slug": "face-creams",
    "description": "كريمات العناية بالوجه",
    "parent_id": null,
    "image": "categories/face-creams.jpg",
    "is_active": true,
    "sort_order": 2,
    "meta_title": "كريمات الوجه",
    "meta_description": "أفضل كريمات العناية بالوجه",
    "created_at": "2025-10-02T15:30:00Z",
    "updated_at": "2025-10-02T15:30:00Z",
    "parent": null,
    "children": []
  },
  "message": "Category created successfully"
}
```

##### `GET /api/v1/admin/categories/{id}` - تفاصيل التصنيف

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/categories/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "سيروم",
    "slug": "serum",
    "description": "سيرومات العناية بالبشرة",
    "parent_id": null,
    "image": "categories/serum.jpg",
    "is_active": true,
    "sort_order": 1,
    "meta_title": "سيرومات العناية",
    "meta_description": "أفضل السيرومات للعناية بالبشرة",
    "created_at": "2025-10-01T10:00:00Z",
    "updated_at": "2025-10-01T10:00:00Z",
    "parent": null,
    "children": [
      {
        "id": 2,
        "name": "سيروم فيتامين سي",
        "slug": "vitamin-c-serum"
      }
    ],
    "products": [
      {
        "id": 1,
        "title": "سيروم فيتامين سي 20%",
        "price": "45.500"
      }
    ]
  },
  "message": "Category retrieved successfully"
}
```

##### `PUT /api/v1/admin/categories/{id}` - تحديث التصنيف

**مثال الطلب:**
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/categories/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "سيرومات العناية",
    "description": "أفضل السيرومات للعناية بالبشرة والوجه",
    "is_active": true,
    "sort_order": 1
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "سيرومات العناية",
    "slug": "serum",
    "description": "أفضل السيرومات للعناية بالبشرة والوجه",
    "parent_id": null,
    "image": "categories/serum.jpg",
    "is_active": true,
    "sort_order": 1,
    "meta_title": "سيرومات العناية",
    "meta_description": "أفضل السيرومات للعناية بالبشرة",
    "created_at": "2025-10-01T10:00:00Z",
    "updated_at": "2025-10-02T16:45:00Z",
    "parent": null,
    "children": []
  },
  "message": "Category updated successfully"
}
```

##### `DELETE /api/v1/admin/categories/{id}` - حذف التصنيف

**مثال الطلب:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/admin/categories/3" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

**مثال الاستجابة عند وجود منتجات:**
```json
{
  "success": false,
  "message": "Cannot delete category with products. Please move or delete products first."
}
```

#### **APIs المتقدمة:**

##### `GET /api/v1/admin/categories/tree` - شجرة التصنيفات

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/categories/tree" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "سيروم",
      "slug": "serum",
      "is_active": true,
      "sort_order": 1,
      "children": [
        {
          "id": 2,
          "name": "سيروم فيتامين سي",
          "slug": "vitamin-c-serum",
          "is_active": true,
          "sort_order": 1,
          "children": []
        }
      ]
    },
    {
      "id": 3,
      "name": "كريمات الوجه",
      "slug": "face-creams",
      "is_active": true,
      "sort_order": 2,
      "children": []
    }
  ],
  "message": "Category tree retrieved successfully"
}
```

##### `GET /api/v1/admin/categories/statistics` - إحصائيات التصنيفات

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/categories/statistics" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "total_categories": 25,
    "active_categories": 22,
    "inactive_categories": 3,
    "root_categories": 8,
    "subcategories": 17,
    "categories_with_products": 20,
    "empty_categories": 5
  },
  "message": "Category statistics retrieved successfully"
}
```

##### `PUT /api/v1/admin/categories/{id}/toggle-status` - تفعيل/إلغاء التصنيف

**مثال الطلب:**
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/categories/1/toggle-status" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "سيروم",
    "is_active": false
  },
  "message": "Category status updated successfully"
}
```

##### `POST /api/v1/admin/categories/update-sort-order` - تحديث ترتيب التصنيفات

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/categories/update-sort-order" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "categories": [
      {"id": 1, "sort_order": 1},
      {"id": 2, "sort_order": 2},
      {"id": 3, "sort_order": 3}
    ]
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "message": "Category sort order updated successfully"
}
```

#### **الميزات:**
- إدارة شاملة للتصنيفات
- دعم التصنيفات الهرمية (أب وابن)
- ترتيب التصنيفات
- إحصائيات مفصلة
- منع الحذف عند وجود منتجات أو تصنيفات فرعية

---

### 3. 🛍️ **إدارة المنتجات (Products Management)**

#### **APIs الأساسية:**

##### `GET /api/v1/admin/products` - قائمة المنتجات

**المعاملات:**
- `category_id` (اختياري): تصفية حسب الفئة
- `is_available` (اختياري): تصفية حسب التوفر
- `search` (اختياري): البحث في العنوان والوصف
- `sort_by` (اختياري): ترتيب حسب (created_at, title, price)
- `sort_direction` (اختياري): اتجاه الترتيب (asc, desc)
- `per_page` (اختياري): عدد العناصر في الصفحة

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products?category_id=1&is_available=true&per_page=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "سيروم فيتامين سي 20%",
        "slug": "vitamin-c-serum-20",
        "description": "سيروم فيتامين سي عالي التركيز للعناية بالبشرة",
        "short_description": "سيروم فيتامين سي 20%",
        "price": "45.500",
        "currency": "KWD",
        "is_available": true,
        "category_id": 1,
        "images": [
          "products/vitamin-c-serum-1.jpg",
          "products/vitamin-c-serum-2.jpg"
        ],
        "meta": {
          "ingredients": ["فيتامين سي", "حمض الهيالورونيك"],
          "skin_type": "جميع أنواع البشرة"
        },
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-01T10:00:00Z",
        "category": {
          "id": 1,
          "name": "سيروم",
          "slug": "serum"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/v1/admin/products?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost:8000/api/v1/admin/products?page=5",
    "links": [...],
    "next_page_url": "http://localhost:8000/api/v1/admin/products?page=2",
    "path": "http://localhost:8000/api/v1/admin/products",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 45
  },
  "message": "Products retrieved successfully"
}
```

##### `POST /api/v1/admin/products` - إنشاء منتج جديد

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/products" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "كريم مرطب للوجه",
    "description": "كريم مرطب عميق للعناية بالوجه",
    "short_description": "كريم مرطب للوجه",
    "price": "35.750",
    "currency": "KWD",
    "is_available": true,
    "category_id": 3,
    "images": [
      "products/face-moisturizer-1.jpg",
      "products/face-moisturizer-2.jpg"
    ],
    "meta": {
      "ingredients": ["حمض الهيالورونيك", "فيتامين E"],
      "skin_type": "البشرة الجافة"
    }
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "كريم مرطب للوجه",
    "slug": "face-moisturizer",
    "description": "كريم مرطب عميق للعناية بالوجه",
    "short_description": "كريم مرطب للوجه",
    "price": "35.750",
    "currency": "KWD",
    "is_available": true,
    "category_id": 3,
    "images": [
      "products/face-moisturizer-1.jpg",
      "products/face-moisturizer-2.jpg"
    ],
    "meta": {
      "ingredients": ["حمض الهيالورونيك", "فيتامين E"],
      "skin_type": "البشرة الجافة"
    },
    "created_at": "2025-10-02T15:30:00Z",
    "updated_at": "2025-10-02T15:30:00Z",
    "category": {
      "id": 3,
      "name": "كريمات الوجه",
      "slug": "face-creams"
    }
  },
  "message": "Product created successfully"
}
```

##### `GET /api/v1/admin/products/{id}` - تفاصيل المنتج

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "سيروم فيتامين سي 20%",
    "slug": "vitamin-c-serum-20",
    "description": "سيروم فيتامين سي عالي التركيز للعناية بالبشرة",
    "short_description": "سيروم فيتامين سي 20%",
    "price": "45.500",
    "currency": "KWD",
    "is_available": true,
    "category_id": 1,
    "images": [
      "products/vitamin-c-serum-1.jpg",
      "products/vitamin-c-serum-2.jpg"
    ],
    "meta": {
      "ingredients": ["فيتامين سي", "حمض الهيالورونيك"],
      "skin_type": "جميع أنواع البشرة"
    },
    "created_at": "2025-10-01T10:00:00Z",
    "updated_at": "2025-10-01T10:00:00Z",
    "category": {
      "id": 1,
      "name": "سيروم",
      "slug": "serum"
    }
  },
  "message": "Product retrieved successfully"
}
```

##### `PUT /api/v1/admin/products/{id}` - تحديث المنتج

**مثال الطلب:**
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/products/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "سيروم فيتامين سي 25%",
    "description": "سيروم فيتامين سي عالي التركيز 25% للعناية بالبشرة",
    "price": "50.000",
    "is_available": true
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "سيروم فيتامين سي 25%",
    "slug": "vitamin-c-serum-20",
    "description": "سيروم فيتامين سي عالي التركيز 25% للعناية بالبشرة",
    "short_description": "سيروم فيتامين سي 20%",
    "price": "50.000",
    "currency": "KWD",
    "is_available": true,
    "category_id": 1,
    "images": [
      "products/vitamin-c-serum-1.jpg",
      "products/vitamin-c-serum-2.jpg"
    ],
    "meta": {
      "ingredients": ["فيتامين سي", "حمض الهيالورونيك"],
      "skin_type": "جميع أنواع البشرة"
    },
    "created_at": "2025-10-01T10:00:00Z",
    "updated_at": "2025-10-02T16:45:00Z",
    "category": {
      "id": 1,
      "name": "سيروم",
      "slug": "serum"
    }
  },
  "message": "Product updated successfully"
}
```

##### `DELETE /api/v1/admin/products/{id}` - حذف المنتج

**مثال الطلب:**
```bash
curl -X DELETE "http://localhost:8000/api/v1/admin/products/2" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "message": "Product deleted successfully"
}
```

#### **APIs المتقدمة:**

##### `GET /api/v1/admin/products/statistics` - إحصائيات المنتجات

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products/statistics" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "total_products": 45,
    "available_products": 42,
    "unavailable_products": 3,
    "low_stock_products": 5,
    "out_of_stock_products": 2,
    "products_by_category": [
      {
        "category_id": 1,
        "count": 15,
        "category": {
          "id": 1,
          "name": "سيروم"
        }
      },
      {
        "category_id": 3,
        "count": 20,
        "category": {
          "id": 3,
          "name": "كريمات الوجه"
        }
      }
    ],
    "average_price": "42.500",
    "total_value": "1912.500"
  },
  "message": "Product statistics retrieved successfully"
}
```

##### `POST /api/v1/admin/products/bulk-update` - تحديث جماعي

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/products/bulk-update" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "product_ids": [1, 2, 3],
    "action": "activate"
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "updated_count": 3
  },
  "message": "Bulk activate completed successfully"
}
```

##### `POST /api/v1/admin/products/{id}/duplicate` - نسخ المنتج

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/products/1/duplicate" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "title": "سيروم فيتامين سي 20% (Copy)",
    "slug": "vitamin-c-serum-20-copy",
    "description": "سيروم فيتامين سي عالي التركيز للعناية بالبشرة",
    "price": "45.500",
    "is_available": false,
    "category_id": 1,
    "created_at": "2025-10-02T16:50:00Z",
    "category": {
      "id": 1,
      "name": "سيروم"
    }
  },
  "message": "Product duplicated successfully"
}
```

##### `PUT /api/v1/admin/products/{id}/images` - تحديث صور المنتج

**مثال الطلب:**
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/products/1/images" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "images": [
      "products/vitamin-c-serum-new-1.jpg",
      "products/vitamin-c-serum-new-2.jpg",
      "products/vitamin-c-serum-new-3.jpg"
    ]
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "سيروم فيتامين سي 20%",
    "images": [
      "products/vitamin-c-serum-new-1.jpg",
      "products/vitamin-c-serum-new-2.jpg",
      "products/vitamin-c-serum-new-3.jpg"
    ],
    "category": {
      "id": 1,
      "name": "سيروم"
    }
  },
  "message": "Product images updated successfully"
}
```

##### `GET /api/v1/admin/products/export` - تصدير المنتجات

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products/export?format=csv&category_id=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة (CSV):**
```csv
ID,Title,Slug,Description,Price,Currency,Is Available,Category,Stock Quantity,Created At
1,سيروم فيتامين سي 20%,vitamin-c-serum-20,سيروم فيتامين سي عالي التركيز,45.500,KWD,Yes,سيروم,0,2025-10-01 10:00:00
2,كريم مرطب للوجه,face-moisturizer,كريم مرطب عميق للعناية بالوجه,35.750,KWD,Yes,كريمات الوجه,0,2025-10-02 15:30:00
```

#### **الميزات:**
- إدارة شاملة للمنتجات
- دعم رفع وتحديث الصور
- تحديث جماعي للمنتجات
- نسخ المنتجات
- إحصائيات مفصلة
- تصدير البيانات

---

### 4. 👥 **إدارة المستخدمين (Users Management)**

#### **APIs الأساسية:**

##### `GET /api/v1/admin/users` - قائمة المستخدمين

**المعاملات:**
- `search` (اختياري): البحث في الاسم والبريد والهاتف
- `role` (اختياري): تصفية حسب الدور (admin, customer)
- `sort_by` (اختياري): ترتيب حسب (created_at, name, email)
- `sort_direction` (اختياري): اتجاه الترتيب (asc, desc)
- `per_page` (اختياري): عدد العناصر في الصفحة

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/users?role=admin&per_page=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "أحمد محمد",
        "email": "ahmed@example.com",
        "phone": "+96512345678",
        "role": "admin",
        "is_active": true,
        "created_at": "2025-10-01T10:00:00Z",
        "updated_at": "2025-10-01T10:00:00Z"
      }
    ],
    "first_page_url": "http://localhost:8000/api/v1/admin/users?page=1",
    "from": 1,
    "last_page": 2,
    "last_page_url": "http://localhost:8000/api/v1/admin/users?page=2",
    "links": [...],
    "next_page_url": "http://localhost:8000/api/v1/admin/users?page=2",
    "path": "http://localhost:8000/api/v1/admin/users",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 15
  },
  "message": "Users retrieved successfully"
}
```

##### `POST /api/v1/admin/users` - إنشاء مستخدم جديد

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/users" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "سارة أحمد",
    "email": "sara@example.com",
    "phone": "+96598765432",
    "password": "password123",
    "role": "admin",
    "is_active": true
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "سارة أحمد",
    "email": "sara@example.com",
    "phone": "+96598765432",
    "role": "admin",
    "is_active": true,
    "created_at": "2025-10-02T15:30:00Z",
    "updated_at": "2025-10-02T15:30:00Z"
  },
  "message": "User created successfully"
}
```

##### `GET /api/v1/admin/users/statistics` - إحصائيات المستخدمين

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/users/statistics" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "admin_users": 5,
    "customer_users": 145,
    "active_users": 142,
    "inactive_users": 8,
    "users_this_month": 25,
    "users_this_year": 150
  },
  "message": "User statistics retrieved successfully"
}
```

##### `POST /api/v1/admin/users/bulk-update` - تحديث جماعي

**مثال الطلب:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/users/bulk-update" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "user_ids": [1, 2, 3],
    "action": "activate"
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "updated_count": 3
  },
  "message": "Bulk activate completed successfully"
}
```

##### `PUT /api/v1/admin/users/{id}/change-password` - تغيير كلمة المرور

**مثال الطلب:**
```bash
curl -X PUT "http://localhost:8000/api/v1/admin/users/1/change-password" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }'
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

#### **الميزات:**
- إدارة شاملة للمستخدمين
- دعم الأدوار (مدير/عميل)
- حماية من حذف آخر مدير
- تحديث جماعي
- تغيير كلمات المرور
- إحصائيات مفصلة

---

### 5. 📊 **التقارير والتصدير (Reports & Export)**

#### **APIs التقارير:**

##### `GET /api/v1/admin/reports/sales` - تقرير المبيعات

**المعاملات:**
- `start_date` (مطلوب): تاريخ البداية
- `end_date` (مطلوب): تاريخ النهاية
- `format` (اختياري): تنسيق التصدير (json, csv, xlsx)
- `group_by` (اختياري): التجميع (day, week, month)

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/reports/sales?start_date=2025-10-01&end_date=2025-10-31&format=json&group_by=day" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_orders": 150,
      "total_revenue": "12500.500",
      "average_order_value": "83.337",
      "period": {
        "start": "2025-10-01",
        "end": "2025-10-31",
        "group_by": "day"
      }
    },
    "data": [
      {
        "period": "2025-10-01",
        "orders_count": 5,
        "total_revenue": "450.250"
      },
      {
        "period": "2025-10-02",
        "orders_count": 8,
        "total_revenue": "720.100"
      }
    ]
  },
  "message": "Sales report generated successfully"
}
```

##### `GET /api/v1/admin/reports/products` - تقرير المنتجات

**المعاملات:**
- `category_id` (اختياري): تصفية حسب الفئة
- `format` (اختياري): تنسيق التصدير (json, csv, xlsx)
- `include_inactive` (اختياري): تضمين المنتجات غير المتاحة

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/reports/products?category_id=1&format=csv" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة (CSV):**
```csv
Statistics
Total Products,45
Available Products,42
Unavailable Products,3
Low Stock Products,5
Average Price,42.500

Products
ID,Title,Slug,Description,Price,Currency,Is Available,Category,Stock Quantity,Created At
1,سيروم فيتامين سي 20%,vitamin-c-serum-20,سيروم فيتامين سي عالي التركيز,45.500,KWD,Yes,سيروم,0,2025-10-01 10:00:00
```

##### `GET /api/v1/admin/reports/customers` - تقرير العملاء

**المعاملات:**
- `start_date` (اختياري): تاريخ البداية
- `end_date` (اختياري): تاريخ النهاية
- `format` (اختياري): تنسيق التصدير (json, csv, xlsx)
- `customer_type` (اختياري): نوع العميل (all, active, inactive, vip, new)

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/reports/customers?customer_type=vip&format=json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة:**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "total_customers": 89,
      "active_customers": 82,
      "inactive_customers": 7,
      "vip_customers": 15,
      "new_customers": 25,
      "total_spent": "12500.500",
      "average_order_value": "83.337",
      "customers_with_orders": 75
    },
    "customers": [
      {
        "id": 1,
        "name": "أحمد محمد",
        "email": "ahmed@example.com",
        "phone": "+96512345678",
        "total_spent": "1250.750",
        "orders_count": 15,
        "is_active": true,
        "created_at": "2025-10-01T10:00:00Z"
      }
    ]
  },
  "message": "Customers report generated successfully"
}
```

##### `GET /api/v1/admin/reports/dashboard` - تقرير لوحة التحكم

**المعاملات:**
- `start_date` (مطلوب): تاريخ البداية
- `end_date` (مطلوب): تاريخ النهاية
- `format` (اختياري): تنسيق التصدير (json, csv, xlsx)

**مثال الطلب:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/reports/dashboard?start_date=2025-10-01&end_date=2025-10-31&format=csv" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**مثال الاستجابة (CSV):**
```csv
Dashboard Report - 2025-10-01 to 2025-10-31

Orders
Total Orders,150
Paid Orders,142
Total Revenue,12500.500
Average Order Value,83.337

Products
Total Products,45
Available Products,42
Low Stock Products,5

Customers
Total Customers,89
Active Customers,82
New Customers,25

Payments
Total Payments,142
Successful Payments,140
Total Amount,12500.500
```

#### **الميزات:**
- تقارير مفصلة لجميع الجوانب
- دعم تصدير CSV و JSON
- فلاتر متقدمة للبيانات
- إحصائيات شاملة
- تصدير قابل للتخصيص

---

## 🔧 **الميزات التقنية المتقدمة**

### **1. الأمان والحماية:**
- مصادقة JWT لجميع APIs
- حماية من حذف البيانات المهمة
- التحقق من الصلاحيات
- حماية من التحديثات الجماعية الخطيرة

### **2. الأداء والتحسين:**
- استعلامات محسنة
- دعم التصفح (Pagination)
- فهرسة قاعدة البيانات
- استعلامات مجمعة

### **3. المرونة والتخصيص:**
- فلاتر متقدمة
- ترتيب قابل للتخصيص
- دعم البحث
- تصدير متعدد التنسيقات

### **4. سهولة الاستخدام:**
- رسائل خطأ واضحة
- استجابات موحدة
- وثائق شاملة
- أمثلة واضحة

---

## 📈 **إحصائيات التطوير**

### **الملفات المطورة:**
- **Controllers:** 6 controllers جديدة
- **Routes:** 50+ route جديد
- **Methods:** 100+ method جديد
- **Features:** 8 ميزات رئيسية

### **الوظائف المغطاة:**
- ✅ إحصائيات شاملة
- ✅ إدارة التصنيفات
- ✅ إدارة المنتجات
- ✅ إدارة المستخدمين
- ✅ التقارير والتصدير
- ⏳ إدارة الطلبات (موجود جزئياً)
- ⏳ إدارة العملاء (موجود جزئياً)
- ⏳ إدارة أكواد الخصم (موجود جزئياً)

---

## 🚀 **الخطوات التالية**

### **1. إكمال APIs المتبقية:**
- تحسين APIs إدارة الطلبات
- تحسين APIs إدارة العملاء
- تحسين APIs إدارة أكواد الخصم

### **2. اختبارات شاملة:**
- اختبار جميع APIs
- اختبار الأداء
- اختبار الأمان

### **3. وثائق تفصيلية:**
- تحديث API_DOCUMENTATION.md
- إنشاء أمثلة تفصيلية
- دليل المطور

---

## 📞 **الدعم والمساعدة**

للاستفسارات أو المساعدة:
- مراجعة الوثائق
- اختبار APIs
- التواصل مع فريق التطوير

---

## ⚠️ **أمثلة الأخطاء الشائعة**

### **أخطاء المصادقة:**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### **أخطاء الصلاحيات:**
```json
{
  "success": false,
  "message": "Admin access required"
}
```

### **أخطاء التحقق من البيانات:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

### **أخطاء عدم وجود البيانات:**
```json
{
  "success": false,
  "message": "Category not found"
}
```

### **أخطاء الحذف:**
```json
{
  "success": false,
  "message": "Cannot delete category with products. Please move or delete products first."
}
```

### **أخطاء التحديث الجماعي:**
```json
{
  "success": false,
  "message": "Error performing bulk update: Cannot delete all admin users"
}
```

---

## 📚 **دليل الاستخدام السريع**

### **1. الحصول على JWT Token:**
```bash
curl -X POST "http://localhost:8000/api/v1/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### **2. استخدام Token في الطلبات:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard/overview" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### **3. تصدير البيانات:**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/reports/sales?start_date=2025-10-01&end_date=2025-10-31&format=csv" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json" \
  --output sales_report.csv
```

---

## 🔗 **روابط مفيدة**

- **API Documentation:** `/API_DOCUMENTATION.md`
- **API Examples:** `/API_EXAMPLES.md`
- **Dashboard Documentation:** `/DASHBOARD_DOCUMENTATION.md`
- **Project Setup:** `/PROJECT_SETUP.md`

---

**تم التطوير بنجاح! 🎉**

النظام جاهز للاستخدام ويوفر جميع الوظائف المطلوبة لإدارة المتجر الإلكتروني بشكل احترافي ومتقدم.
