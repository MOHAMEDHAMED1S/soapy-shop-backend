# Report API Documentation

## Overview
The Report API provides comprehensive analytics and business intelligence for the Soapy Shop e-commerce platform. These endpoints offer insights into sales performance, customer behavior, product analytics, order patterns, and financial metrics.

## Base URL
```
https://your-domain.com/api/admin/reports
```

## Authentication
All report endpoints require admin authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Available Report Endpoints

### 1. Dashboard Overview
Get key business metrics and KPIs for the admin dashboard.

**Endpoint:** `GET /dashboard/overview`

**Query Parameters:**
- `date_from` (optional): Start date for metrics (default: 30 days ago)
- `date_to` (optional): End date for metrics (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/dashboard/overview?date_from=2024-01-01&date_to=2024-01-31" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_revenue": 15750.50,
    "period_revenue": 8420.25,
    "total_orders": 245,
    "period_orders": 89,
    "total_customers": 156,
    "active_customers": 78,
    "total_products": 89,
    "active_products": 82,
    "low_stock_products": 5,
    "average_order_value": 94.61
  }
}
```

### 2. Sales Analytics
Comprehensive sales performance analytics with trends and breakdowns.

**Endpoint:** `GET /analytics/sales`

**Query Parameters:**
- `period` (optional): Time grouping - `day`, `week`, `month`, `year` (default: `month`)
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/analytics/sales?period=week&date_from=2024-01-01" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "sales_over_time": [
      {
        "period": "2024-01",
        "total": 5420.75,
        "orders": 32
      }
    ],
    "top_products": [
      {
        "id": 1,
        "name": "Lavender Soap Bar",
        "total_sold": 145,
        "total_revenue": 2175.00
      }
    ],
    "sales_by_category": [
      {
        "name": "Bath & Body",
        "total_revenue": 8950.25,
        "total_quantity": 234
      }
    ],
    "payment_methods": [
      {
        "payment_method": "stripe",
        "count": 89,
        "total_amount": 6750.50
      }
    ]
  }
}
```

### 3. Customer Analytics
Customer behavior and demographic insights.

**Endpoint:** `GET /analytics/customers`

**Query Parameters:**
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/analytics/customers" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customer_acquisition": [
      {
        "date": "2024-01-15",
        "count": 8
      }
    ],
    "top_customers_by_orders": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "orders_count": 12
      }
    ],
    "top_customers_by_revenue": [
      {
        "id": 1,
        "name": "John Doe",
        "total_spent": 1250.75
      }
    ],
    "customers_by_city": [
      {
        "city": "New York",
        "count": 45
      }
    ]
  }
}
```

### 4. Product Analytics
Product performance and inventory insights.

**Endpoint:** `GET /analytics/products`

**Query Parameters:**
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/analytics/products" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "product_performance": [
      {
        "id": 1,
        "name": "Lavender Soap Bar",
        "total_sold": 145,
        "total_revenue": 2175.00
      }
    ],
    "low_stock_products": [
      {
        "id": 5,
        "name": "Rose Body Wash",
        "stock_quantity": 3
      }
    ],
    "out_of_stock_products": [
      {
        "id": 8,
        "name": "Mint Shampoo",
        "stock_quantity": 0
      }
    ],
    "products_by_category": [
      {
        "id": 1,
        "name": "Bath & Body",
        "products_count": 25
      }
    ]
  }
}
```

### 5. Order Analytics
Order patterns and fulfillment metrics.

**Endpoint:** `GET /analytics/orders`

**Query Parameters:**
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/analytics/orders" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders_by_status": [
      {
        "status": "completed",
        "count": 156
      },
      {
        "status": "pending",
        "count": 23
      }
    ],
    "orders_by_payment_status": [
      {
        "payment_status": "paid",
        "count": 145
      }
    ],
    "average_processing_time_hours": 24.5,
    "orders_over_time": [
      {
        "date": "2024-01-15",
        "count": 12,
        "total_amount": 1450.75
      }
    ],
    "recent_orders": [
      {
        "id": 1,
        "total_amount": 89.99,
        "status": "pending",
        "customer": {
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ]
  }
}
```

### 6. Financial Reports
Revenue breakdowns and financial metrics.

**Endpoint:** `GET /financial/overview`

**Query Parameters:**
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/financial/overview" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue_breakdown": {
      "total_subtotal": 12450.75,
      "total_tax": 1245.08,
      "total_shipping": 890.50,
      "total_discount": 245.75,
      "total_revenue": 14340.58,
      "total_orders": 156
    },
    "monthly_revenue": [
      {
        "year": 2024,
        "month": 1,
        "revenue": 8950.25,
        "orders_count": 89
      }
    ],
    "refunds_and_cancellations": {
      "cancelled_orders": 5,
      "cancelled_revenue": 450.25,
      "refunded_orders": 2,
      "refunded_amount": 189.98
    }
  }
}
```

### 7. Business Intelligence
Advanced KPIs and business metrics.

**Endpoint:** `GET /business-intelligence`

**Query Parameters:**
- `date_from` (optional): Start date (default: 30 days ago)
- `date_to` (optional): End date (default: today)

**Example Request:**
```bash
curl -X GET "https://your-domain.com/api/admin/reports/business-intelligence" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "conversion_rate": 3.45,
      "customer_lifetime_value": 245.75,
      "average_order_value": 89.50,
      "repeat_customer_rate": 28.5,
      "cart_abandonment_rate": 15.2
    },
    "growth_metrics": {
      "revenue_growth": 12.5,
      "current_period_revenue": 8950.25,
      "previous_period_revenue": 7956.89
    },
    "seasonal_trends": [
      {
        "month": 1,
        "revenue": 8950.25,
        "orders": 89
      }
    ]
  }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `401` - Unauthorized (invalid or missing token)
- `403` - Forbidden (insufficient permissions)
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting
- **Limit:** 100 requests per minute per IP address
- **Headers:** Rate limit information is included in response headers
  - `X-RateLimit-Limit`: Request limit per window
  - `X-RateLimit-Remaining`: Remaining requests in current window
  - `X-RateLimit-Reset`: Time when the rate limit resets

## Data Filtering and Pagination

### Date Filtering
Most endpoints support date filtering:
- Use `YYYY-MM-DD` format for dates
- `date_from` and `date_to` parameters are inclusive
- Default period is last 30 days if not specified

### Time Periods
Sales analytics supports different time groupings:
- `day` - Daily aggregation
- `week` - Weekly aggregation  
- `month` - Monthly aggregation
- `year` - Yearly aggregation

## Best Practices

1. **Caching**: Report data is cached for 5 minutes to improve performance
2. **Date Ranges**: Limit date ranges to reasonable periods (max 1 year) for better performance
3. **Pagination**: Large datasets are automatically limited (typically 20-100 records)
4. **Error Handling**: Always check the `success` field before processing data
5. **Rate Limits**: Implement proper rate limiting in your client applications

## Example Usage Scenarios

### Daily Dashboard Update
```bash
# Get today's key metrics
curl -X GET "https://your-domain.com/api/admin/reports/dashboard/overview?date_from=$(date +%Y-%m-%d)&date_to=$(date +%Y-%m-%d)" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Monthly Sales Report
```bash
# Get last month's sales analytics
curl -X GET "https://your-domain.com/api/admin/reports/analytics/sales?period=month&date_from=2024-01-01&date_to=2024-01-31" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Inventory Management
```bash
# Check product performance and stock levels
curl -X GET "https://your-domain.com/api/admin/reports/analytics/products" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Financial Analysis
```bash
# Get comprehensive financial overview
curl -X GET "https://your-domain.com/api/admin/reports/financial/overview?date_from=2024-01-01&date_to=2024-12-31" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Security Considerations

1. **Authentication Required**: All endpoints require valid admin authentication
2. **HTTPS Only**: All API calls must use HTTPS in production
3. **Token Security**: Store and transmit access tokens securely
4. **Data Privacy**: Report data may contain sensitive business information
5. **Access Control**: Ensure proper role-based access control for different report types

## Support and Troubleshooting

### Common Issues
1. **401 Unauthorized**: Check your access token and ensure it's valid
2. **Empty Results**: Verify date ranges and ensure there's data for the specified period
3. **Slow Response**: Consider using smaller date ranges or caching results
4. **Rate Limiting**: Implement exponential backoff for rate-limited requests

### Performance Tips
1. Use appropriate date ranges (avoid very large periods)
2. Cache report results when possible
3. Consider using webhooks for real-time updates instead of frequent polling
4. Implement client-side pagination for large datasets

For additional support, please contact the development team or refer to the main API documentation.