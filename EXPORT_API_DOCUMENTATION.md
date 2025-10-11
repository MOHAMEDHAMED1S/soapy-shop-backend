# Export API Documentation

## Overview
The Export API provides comprehensive data export functionality for products, customers, and orders. It supports multiple formats including JSON, CSV, and Excel (XLSX) with advanced filtering and customization options.

## Base URL
```
https://api.soapy-bubbles.com/api/v1/admin/exports
```

## Authentication
All export endpoints require admin authentication using JWT Bearer token:
```
Authorization: Bearer {your_jwt_token}
```

## Export Endpoints

### 1. Export Products
Export product data with various filtering options.

**Endpoint:** `POST /api/v1/admin/exports/products`

**Request Body:**
```json
{
  "format": "json|csv|excel|xlsx",
  "limit": 100,
  "filters": {
    "category_id": 1,
    "is_available": true,
    "min_price": 10.00,
    "max_price": 500.00,
    "stock_quantity": {
      "min": 0,
      "max": 1000
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "export_id": "exp_abc123def456",
  "download_url": "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_abc123def456/download",
  "file_name": "products_export_2025-10-11_15-20-29_waRzvw0q.xlsx",
  "file_path": "exports/products_export_2025-10-11_15-20-29_waRzvw0q.xlsx",
  "records_count": 15,
  "estimated_completion": "2025-10-11T15:20:35Z"
}
```

**Exported Fields:**
- `id` - Product ID
- `name` - Product name
- `description` - Product description
- `price` - Product price
- `category` - Category name
- `is_available` - Availability status
- `stock_quantity` - Current stock
- `images` - Product images (JSON format)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### 2. Export Customers
Export customer data with filtering capabilities.

**Endpoint:** `POST /api/v1/admin/exports/customers`

**Request Body:**
```json
{
  "format": "json|csv|excel|xlsx",
  "limit": 50,
  "filters": {
    "created_after": "2025-01-01",
    "created_before": "2025-12-31",
    "has_orders": true
  }
}
```

**Response:**
```json
{
  "success": true,
  "export_id": "exp_def456ghi789",
  "download_url": "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_def456ghi789/download",
  "file_name": "customers_export_2025-10-11_15-25-10_xYz123Ab.json",
  "file_path": "exports/customers_export_2025-10-11_15-25-10_xYz123Ab.json",
  "records_count": 2,
  "estimated_completion": "2025-10-11T15:25:15Z"
}
```

**Exported Fields:**
- `id` - Customer ID
- `name` - Customer name
- `email` - Customer email
- `phone` - Customer phone number
- `address` - Customer address (JSON format)
- `total_orders` - Total number of orders
- `total_spent` - Total amount spent
- `created_at` - Registration date
- `updated_at` - Last update timestamp

### 3. Export Orders
Export order data with comprehensive filtering options.

**Endpoint:** `POST /api/v1/admin/exports/orders`

**Request Body:**
```json
{
  "format": "json|csv|excel|xlsx",
  "limit": 25,
  "filters": {
    "status": "completed|pending|cancelled",
    "payment_status": "paid|pending|failed",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
    "min_amount": 50.00,
    "max_amount": 1000.00,
    "customer_id": 123
  }
}
```

**Response:**
```json
{
  "success": true,
  "export_id": "exp_ghi789jkl012",
  "download_url": "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_ghi789jkl012/download",
  "file_name": "orders_export_2025-10-11_15-30-45_mNp456Qr.xlsx",
  "file_path": "exports/orders_export_2025-10-11_15-30-45_mNp456Qr.xlsx",
  "records_count": 9,
  "estimated_completion": "2025-10-11T15:30:50Z"
}
```

**Exported Fields:**
- `id` - Order ID
- `order_number` - Unique order number
- `customer_name` - Customer name
- `customer_email` - Customer email
- `customer_phone` - Customer phone
- `total_amount` - Order total
- `status` - Order status
- `payment_status` - Payment status
- `shipping_address` - Shipping address (JSON format)
- `items` - Order items (JSON format)
- `created_at` - Order date
- `updated_at` - Last update timestamp

## Export Management Endpoints

### 4. List Exports
Get a list of all exports with pagination.

**Endpoint:** `GET /api/v1/admin/exports`

**Query Parameters:**
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15, max: 100)
- `status` (optional) - Filter by status: `pending|processing|completed|failed`
- `type` (optional) - Filter by type: `products|customers|orders`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "exp_abc123def456",
      "type": "products",
      "format": "xlsx",
      "status": "completed",
      "file_name": "products_export_2025-10-11_15-20-29_waRzvw0q.xlsx",
      "file_size": 15420,
      "records_count": 15,
      "download_url": "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_abc123def456/download",
      "created_at": "2025-10-11T15:20:29Z",
      "completed_at": "2025-10-11T15:20:35Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

### 5. Get Export Details
Get detailed information about a specific export.

**Endpoint:** `GET /api/v1/admin/exports/{export_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "exp_abc123def456",
    "type": "products",
    "format": "xlsx",
    "status": "completed",
    "file_name": "products_export_2025-10-11_15-20-29_waRzvw0q.xlsx",
    "file_path": "exports/products_export_2025-10-11_15-20-29_waRzvw0q.xlsx",
    "file_size": 15420,
    "records_count": 15,
    "download_url": "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_abc123def456/download",
    "filters_applied": {
      "limit": 3
    },
    "created_at": "2025-10-11T15:20:29Z",
    "started_at": "2025-10-11T15:20:30Z",
    "completed_at": "2025-10-11T15:20:35Z"
  }
}
```

### 6. Get Export Status
Check the current status of an export operation.

**Endpoint:** `GET /api/v1/admin/exports/{export_id}/status`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "exp_abc123def456",
    "status": "completed",
    "progress": 100,
    "records_processed": 15,
    "records_total": 15,
    "estimated_completion": null,
    "error_message": null
  }
}
```

### 7. Download Export File
Download the generated export file.

**Endpoint:** `GET /api/v1/admin/exports/{export_id}/download`

**Response:** Binary file download with appropriate headers:
- `Content-Type`: `application/json`, `text/csv`, or `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- `Content-Disposition`: `attachment; filename="export_file.ext"`

### 8. Delete Export
Delete an export and its associated file.

**Endpoint:** `DELETE /api/v1/admin/exports/{export_id}`

**Response:**
```json
{
  "success": true,
  "message": "Export deleted successfully"
}
```

### 9. Export Statistics
Get overview statistics about exports.

**Endpoint:** `GET /api/v1/admin/exports/statistics/overview`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_exports": 25,
    "completed_exports": 22,
    "failed_exports": 1,
    "pending_exports": 2,
    "total_records_exported": 15420,
    "total_file_size": 2048576,
    "exports_by_type": {
      "products": 10,
      "customers": 8,
      "orders": 7
    },
    "exports_by_format": {
      "json": 5,
      "csv": 8,
      "xlsx": 12
    }
  }
}
```

## Export Status Values

| Status | Description |
|--------|-------------|
| `pending` | Export request received, waiting to be processed |
| `processing` | Export is currently being generated |
| `completed` | Export completed successfully and file is ready for download |
| `failed` | Export failed due to an error |

## Supported Formats

| Format | Extension | MIME Type |
|--------|-----------|-----------|
| JSON | `.json` | `application/json` |
| CSV | `.csv` | `text/csv` |
| Excel | `.xlsx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Invalid format specified",
  "errors": {
    "format": ["The format field must be one of: json, csv, excel, xlsx"]
  }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Export not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "limit": ["The limit field must be a number between 1 and 10000"]
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Export generation failed",
  "error": "Detailed error message for debugging"
}
```

## Rate Limiting
- Maximum 10 export requests per minute per user
- Maximum 100 concurrent exports per user
- Large exports (>10,000 records) may be queued

## File Storage
- Export files are stored for 7 days after generation
- Files are automatically deleted after expiration
- Maximum file size: 100MB per export

## Best Practices

1. **Use Appropriate Limits**: Start with smaller limits for testing, increase as needed
2. **Apply Filters**: Use filters to reduce export size and improve performance
3. **Choose Right Format**: 
   - JSON for API integration
   - CSV for spreadsheet applications
   - Excel for formatted reports with styling
4. **Monitor Status**: Check export status for large datasets
5. **Download Promptly**: Download files within 7 days of generation
6. **Handle Errors**: Implement proper error handling for failed exports

## Example Usage

### Basic Product Export
```bash
curl -X POST "https://api.soapy-bubbles.com/api/v1/admin/exports/products" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "xlsx",
    "limit": 100
  }'
```

### Filtered Customer Export
```bash
curl -X POST "https://api.soapy-bubbles.com/api/v1/admin/exports/customers" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "csv",
    "limit": 50,
    "filters": {
      "created_after": "2025-01-01",
      "has_orders": true
    }
  }'
```

### Check Export Status
```bash
curl -X GET "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_abc123def456/status" \
  -H "Authorization: Bearer your_jwt_token"
```

### Download Export File
```bash
curl -X GET "https://api.soapy-bubbles.com/api/v1/admin/exports/exp_abc123def456/download" \
  -H "Authorization: Bearer your_jwt_token" \
  -o "export_file.xlsx"
```