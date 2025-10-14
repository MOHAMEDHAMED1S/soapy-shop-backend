# Reports API Documentation

## نظام التقارير والتحليلات - API Documentation

هذا التوثيق يشرح جميع endpoints المتاحة في نظام التقارير والتحليلات للمتجر الإلكتروني.

## Base URL
```
/api/v1/reports
```

## Authentication
جميع endpoints تتطلب authentication باستخدام Bearer Token في header:
```
Authorization: Bearer {your_token}
```

---

## 1. Dashboard Reports

### 1.1 Dashboard Overview
**Endpoint:** `GET /reports/dashboard/overview`

**الوصف:** يحصل على نظرة عامة شاملة للإحصائيات الأساسية للمتجر.

**Parameters:**
- `date_from` (optional): تاريخ البداية (Y-m-d format) - افتراضي: آخر 30 يوم
- `date_to` (optional): تاريخ النهاية (Y-m-d format) - افتراضي: اليوم الحالي

**مثال على الطلب:**
```http
GET /api/v1/reports/dashboard/overview?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "total_customers": 1250,
    "total_orders": 890,
    "total_revenue": 125000.50,
    "pending_orders": 25,
    "processing_orders": 45,
    "delivered_orders": 780,
    "cancelled_orders": 40,
    "low_stock_products": 12,
    "out_of_stock_products": 3
  }
}
```

### 1.2 Business Intelligence
**Endpoint:** `GET /reports/dashboard/business-intelligence`

**الوصف:** يحصل على تقرير ذكاء الأعمال مع مؤشرات الأداء الرئيسية.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية

**مثال على الطلب:**
```http
GET /api/v1/reports/dashboard/business-intelligence?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "conversion_rate": 3.5,
      "customer_lifetime_value": 450.75,
      "average_order_value": 140.25,
      "repeat_customer_rate": 25.8,
      "cart_abandonment_rate": 68.2
    },
    "growth_metrics": {
      "revenue_growth": 15.3,
      "current_period_revenue": 125000.50,
      "previous_period_revenue": 108500.25
    },
    "seasonal_trends": [
      {
        "month": 1,
        "revenue": 95000.00,
        "orders": 650
      },
      {
        "month": 2,
        "revenue": 110000.00,
        "orders": 720
      }
    ]
  }
}
```

---

## 2. Analytics Reports

### 2.1 Sales Analytics
**Endpoint:** `GET /reports/analytics/sales`

**الوصف:** يحصل على تحليلات المبيعات التفصيلية.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية
- `period` (optional): فترة التجميع (day, week, month, year) - افتراضي: day

**مثال على الطلب:**
```http
GET /api/v1/reports/analytics/sales?date_from=2024-01-01&date_to=2024-01-31&period=day
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "sales_over_time": [
      {
        "period": "2024-01-01",
        "total": 2500.00,
        "orders": 18
      },
      {
        "period": "2024-01-02",
        "total": 3200.00,
        "orders": 22
      }
    ],
    "top_selling_products": [
      {
        "id": 1,
        "name": "صابون الورد الطبيعي",
        "total_sold": 150,
        "total_revenue": 4500.00
      },
      {
        "id": 2,
        "name": "شامبو الأعشاب",
        "total_sold": 120,
        "total_revenue": 3600.00
      }
    ],
    "sales_by_category": [
      {
        "name": "العناية بالبشرة",
        "total_revenue": 25000.00,
        "total_quantity": 850
      },
      {
        "name": "العناية بالشعر",
        "total_revenue": 18000.00,
        "total_quantity": 600
      }
    ],
    "payment_methods": [
      {
        "method": "credit_card",
        "count": 450,
        "total_amount": 67500.00
      },
      {
        "method": "cash_on_delivery",
        "count": 320,
        "total_amount": 48000.00
      }
    ]
  }
}
```

### 2.2 Customer Analytics
**Endpoint:** `GET /reports/analytics/customers`

**الوصف:** يحصل على تحليلات العملاء التفصيلية.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية

**مثال على الطلب:**
```http
GET /api/v1/reports/analytics/customers?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "customer_acquisition": [
      {
        "date": "2024-01-01",
        "count": 15
      },
      {
        "date": "2024-01-02",
        "count": 22
      }
    ],
    "top_customers_by_orders": [
      {
        "id": 1,
        "name": "أحمد محمد",
        "email": "ahmed@example.com",
        "orders_count": 25
      },
      {
        "id": 2,
        "name": "فاطمة علي",
        "email": "fatima@example.com",
        "orders_count": 18
      }
    ],
    "top_customers_by_revenue": [
      {
        "id": 1,
        "name": "أحمد محمد",
        "email": "ahmed@example.com",
        "total_spent": 5500.00
      },
      {
        "id": 3,
        "name": "محمد حسن",
        "email": "mohamed@example.com",
        "total_spent": 4200.00
      }
    ],
    "customers_by_city": [
      {
        "city": "القاهرة",
        "count": 450
      },
      {
        "city": "الإسكندرية",
        "count": 320
      },
      {
        "city": "الجيزة",
        "count": 280
      }
    ]
  }
}
```

### 2.3 Product Analytics
**Endpoint:** `GET /reports/analytics/products`

**الوصف:** يحصل على تحليلات المنتجات التفصيلية.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية

**مثال على الطلب:**
```http
GET /api/v1/reports/analytics/products?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "product_performance": [
      {
        "id": 1,
        "name": "صابون الورد الطبيعي",
        "price": 30.00,
        "stock_quantity": 85,
        "total_sold": 150,
        "total_revenue": 4500.00
      },
      {
        "id": 2,
        "name": "شامبو الأعشاب",
        "price": 45.00,
        "stock_quantity": 62,
        "total_sold": 120,
        "total_revenue": 5400.00
      }
    ],
    "low_stock_products": [
      {
        "id": 15,
        "name": "كريم الوجه المرطب",
        "stock_quantity": 8,
        "price": 65.00
      },
      {
        "id": 23,
        "name": "زيت الأرغان الطبيعي",
        "stock_quantity": 5,
        "price": 120.00
      }
    ],
    "out_of_stock_products": [
      {
        "id": 45,
        "name": "ماسك الطين الطبيعي",
        "stock_quantity": 0,
        "price": 55.00
      }
    ],
    "products_by_category": [
      {
        "id": 1,
        "name": "العناية بالبشرة",
        "products_count": 45
      },
      {
        "id": 2,
        "name": "العناية بالشعر",
        "products_count": 32
      }
    ]
  }
}
```

### 2.4 Order Analytics
**Endpoint:** `GET /reports/analytics/orders`

**الوصف:** يحصل على تحليلات الطلبات التفصيلية.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية

**مثال على الطلب:**
```http
GET /api/v1/reports/analytics/orders?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "orders_by_status": [
      {
        "status": "delivered",
        "count": 780
      },
      {
        "status": "processing",
        "count": 45
      },
      {
        "status": "pending",
        "count": 25
      },
      {
        "status": "cancelled",
        "count": 40
      }
    ],
    "orders_by_payment_status": [
      {
        "payment_status": "paid",
        "count": 825
      },
      {
        "payment_status": "pending",
        "count": 25
      },
      {
        "payment_status": "failed",
        "count": 15
      },
      {
        "payment_status": "refunded",
        "count": 25
      }
    ],
    "average_processing_time_hours": 24.5,
    "orders_over_time": [
      {
        "date": "2024-01-01",
        "count": 18,
        "total_amount": 2500.00
      },
      {
        "date": "2024-01-02",
        "count": 22,
        "total_amount": 3200.00
      }
    ],
    "recent_orders": [
      {
        "id": 1001,
        "order_number": "ORD-2024-001001",
        "total": 145.50,
        "status": "processing",
        "payment_status": "paid",
        "created_at": "2024-01-31T14:30:00Z",
        "customer": {
          "id": 1,
          "name": "أحمد محمد",
          "email": "ahmed@example.com"
        }
      }
    ]
  }
}
```

---

## 3. Financial Reports

### 3.1 Financial Overview
**Endpoint:** `GET /reports/financial/overview`

**الوصف:** يحصل على التقارير المالية الشاملة.

**Parameters:**
- `date_from` (optional): تاريخ البداية
- `date_to` (optional): تاريخ النهاية

**مثال على الطلب:**
```http
GET /api/v1/reports/financial/overview?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer your_token_here
```

**مثال على الاستجابة:**
```json
{
  "success": true,
  "data": {
    "revenue_breakdown": {
      "total_subtotal": 115000.00,
      "total_tax": 5750.00,
      "total_shipping": 4500.00,
      "total_discount": 2250.00,
      "total_revenue": 125000.00,
      "total_orders": 890
    },
    "monthly_revenue": [
      {
        "year": 2024,
        "month": 1,
        "revenue": 125000.00,
        "orders_count": 890
      },
      {
        "year": 2023,
        "month": 12,
        "revenue": 108500.00,
        "orders_count": 765
      }
    ],
    "refunds_and_cancellations": {
      "cancelled_orders": 40,
      "cancelled_revenue": 5600.00,
      "refunded_orders": 25,
      "refunded_amount": 3500.00
    }
  }
}
```

---

## Error Responses

جميع endpoints قد ترجع الأخطاء التالية:

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "date_from": ["The date from field must be a valid date."],
    "date_to": ["The date to field must be a valid date."]
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to get dashboard overview: Database connection error"
}
```

---

## Rate Limiting

جميع endpoints محدودة بـ 60 طلب في الدقيقة لكل مستخدم.

## Notes

1. جميع التواريخ يجب أن تكون بصيغة `Y-m-d` (مثل: 2024-01-31)
2. جميع المبالغ المالية بالجنيه المصري
3. الأوقات بصيغة UTC
4. يُنصح بحفظ النتائج في cache للتقارير الكبيرة
5. بعض التقارير قد تستغرق وقتاً أطول للمعالجة حسب حجم البيانات

## Support

للدعم الفني أو الاستفسارات، يرجى التواصل مع فريق التطوير.