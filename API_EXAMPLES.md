# Soapy Shop API - Practical Examples & Use Cases

## Overview
This document provides practical examples and real-world use cases for the Soapy Shop API endpoints, including both Export and Report APIs.

## Table of Contents
1. [Authentication Setup](#authentication-setup)
2. [Export API Examples](#export-api-examples)
3. [Report API Examples](#report-api-examples)
4. [Integration Scenarios](#integration-scenarios)
5. [Automation Scripts](#automation-scripts)
6. [Error Handling Examples](#error-handling-examples)

## Authentication Setup

### Getting Your Access Token
```bash
# Login to get access token
curl -X POST "https://your-domain.com/api/admin/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@soapyshop.com",
    "password": "your_password"
  }'

# Response will include access_token
# {
#   "success": true,
#   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
#   "token_type": "bearer",
#   "expires_in": 3600
# }
```

### Setting Up Environment Variables
```bash
# Set your access token as environment variable
export SOAPY_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
export SOAPY_API_BASE="https://your-domain.com/api/admin"
```

## Export API Examples

### 1. Daily Product Export
Export all products with their current inventory status for daily backup.

```bash
# Start product export
curl -X POST "$SOAPY_API_BASE/exports/products" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "csv",
    "filters": {
      "status": "active"
    },
    "columns": ["id", "name", "sku", "price", "stock_quantity", "category", "status"]
  }'

# Response: {"success": true, "export_id": "exp_123456"}

# Check export status
curl -X GET "$SOAPY_API_BASE/exports/exp_123456/status" \
  -H "Authorization: Bearer $SOAPY_TOKEN"

# Download when ready
curl -X GET "$SOAPY_API_BASE/exports/exp_123456/download" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -o "products_$(date +%Y%m%d).csv"
```

### 2. Customer Data Export for Marketing
Export customer data for email marketing campaigns.

```bash
# Export customers who made purchases in the last 30 days
curl -X POST "$SOAPY_API_BASE/exports/customers" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "excel",
    "filters": {
      "last_order_date": {
        "from": "'$(date -d "30 days ago" +%Y-%m-%d)'",
        "to": "'$(date +%Y-%m-%d)'"
      }
    },
    "columns": ["name", "email", "phone", "city", "total_orders", "total_spent"]
  }'
```

### 3. Monthly Order Export for Accounting
Export all orders from the previous month for accounting purposes.

```bash
# Get last month's orders
LAST_MONTH_START=$(date -d "$(date +%Y-%m-01) -1 month" +%Y-%m-%d)
LAST_MONTH_END=$(date -d "$(date +%Y-%m-01) -1 day" +%Y-%m-%d)

curl -X POST "$SOAPY_API_BASE/exports/orders" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "excel",
    "filters": {
      "date_range": {
        "from": "'$LAST_MONTH_START'",
        "to": "'$LAST_MONTH_END'"
      },
      "status": ["completed", "shipped", "delivered"]
    },
    "include_items": true
  }'
```

### 4. Bulk Export Status Monitoring
Monitor multiple export jobs and download when ready.

```bash
#!/bin/bash
# monitor_exports.sh

EXPORT_IDS=("exp_123456" "exp_123457" "exp_123458")

for export_id in "${EXPORT_IDS[@]}"; do
  echo "Checking status for $export_id..."
  
  status=$(curl -s -X GET "$SOAPY_API_BASE/exports/$export_id/status" \
    -H "Authorization: Bearer $SOAPY_TOKEN" | jq -r '.status')
  
  if [ "$status" = "completed" ]; then
    echo "Downloading $export_id..."
    curl -X GET "$SOAPY_API_BASE/exports/$export_id/download" \
      -H "Authorization: Bearer $SOAPY_TOKEN" \
      -o "${export_id}_$(date +%Y%m%d).csv"
  else
    echo "Status: $status"
  fi
done
```

## Report API Examples

### 1. Daily Dashboard Metrics
Get key metrics for daily business monitoring.

```bash
# Get today's dashboard overview
curl -X GET "$SOAPY_API_BASE/reports/dashboard/overview" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Accept: application/json"

# Example response processing with jq
curl -s -X GET "$SOAPY_API_BASE/reports/dashboard/overview" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '{
    total_revenue: .data.total_revenue,
    total_orders: .data.total_orders,
    new_customers: .data.new_customers,
    conversion_rate: .data.conversion_rate
  }'
```

### 2. Weekly Sales Performance Analysis
Analyze sales performance for the current week.

```bash
# Get sales analytics for current week
WEEK_START=$(date -d "monday" +%Y-%m-%d)
WEEK_END=$(date -d "sunday" +%Y-%m-%d)

curl -X GET "$SOAPY_API_BASE/reports/analytics/sales?period=week&start_date=$WEEK_START&end_date=$WEEK_END" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Accept: application/json"

# Extract top selling products
curl -s -X GET "$SOAPY_API_BASE/reports/analytics/sales?period=week" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '.data.top_selling_products[] | {name: .name, sales: .total_sales, revenue: .total_revenue}'
```

### 3. Monthly Customer Analysis
Comprehensive customer behavior analysis for the month.

```bash
# Get customer analytics for current month
MONTH_START=$(date +%Y-%m-01)
MONTH_END=$(date -d "$(date +%Y-%m-01) +1 month -1 day" +%Y-%m-%d)

curl -X GET "$SOAPY_API_BASE/reports/analytics/customers?start_date=$MONTH_START&end_date=$MONTH_END" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Accept: application/json"

# Generate customer insights report
curl -s -X GET "$SOAPY_API_BASE/reports/analytics/customers" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '{
    new_customers: .data.customer_acquisition.new_customers,
    repeat_customers: .data.customer_acquisition.repeat_customers,
    top_customers: .data.top_customers[0:5],
    avg_order_value: .data.average_order_value
  }' > monthly_customer_report.json
```

### 4. Inventory Alert System
Monitor low stock and out-of-stock products.

```bash
# Get product analytics focusing on inventory
curl -X GET "$SOAPY_API_BASE/reports/analytics/products" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Accept: application/json"

# Extract low stock alerts
curl -s -X GET "$SOAPY_API_BASE/reports/analytics/products" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '.data.low_stock_products[] | {
    name: .name,
    sku: .sku,
    current_stock: .stock_quantity,
    reorder_level: .reorder_level
  }'

# Send email alert if low stock items found
LOW_STOCK_COUNT=$(curl -s -X GET "$SOAPY_API_BASE/reports/analytics/products" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '.data.low_stock_products | length')

if [ "$LOW_STOCK_COUNT" -gt 0 ]; then
  echo "Alert: $LOW_STOCK_COUNT products are low in stock!"
  # Add your email notification logic here
fi
```

### 5. Financial Performance Dashboard
Comprehensive financial reporting for management.

```bash
# Get financial reports
curl -X GET "$SOAPY_API_BASE/reports/financial" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Accept: application/json"

# Create financial summary
curl -s -X GET "$SOAPY_API_BASE/reports/financial" \
  -H "Authorization: Bearer $SOAPY_TOKEN" | \
  jq '{
    revenue_breakdown: .data.revenue_breakdown,
    monthly_trends: .data.monthly_revenue_trends[-6:],
    refunds_total: .data.refunds_and_cancellations.total_refunds,
    net_revenue: (.data.revenue_breakdown.total_revenue - .data.refunds_and_cancellations.total_refunds)
  }' > financial_summary.json
```

## Integration Scenarios

### 1. E-commerce Platform Integration
Sync product data with external e-commerce platforms.

```bash
#!/bin/bash
# sync_products_to_shopify.sh

# Export products from Soapy Shop
curl -X POST "$SOAPY_API_BASE/exports/products" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "json",
    "filters": {"status": "active"},
    "columns": ["id", "name", "description", "price", "stock_quantity", "images"]
  }' > export_response.json

EXPORT_ID=$(jq -r '.export_id' export_response.json)

# Wait for export to complete
while true; do
  STATUS=$(curl -s -X GET "$SOAPY_API_BASE/exports/$EXPORT_ID/status" \
    -H "Authorization: Bearer $SOAPY_TOKEN" | jq -r '.status')
  
  if [ "$STATUS" = "completed" ]; then
    break
  fi
  
  echo "Waiting for export to complete... Status: $STATUS"
  sleep 10
done

# Download and process products
curl -X GET "$SOAPY_API_BASE/exports/$EXPORT_ID/download" \
  -H "Authorization: Bearer $SOAPY_TOKEN" \
  -o products.json

# Process each product for Shopify
jq -c '.[]' products.json | while read product; do
  # Transform data for Shopify format
  shopify_product=$(echo $product | jq '{
    product: {
      title: .name,
      body_html: .description,
      vendor: "Soapy Shop",
      product_type: .category,
      variants: [{
        price: .price,
        inventory_quantity: .stock_quantity,
        sku: .sku
      }]
    }
  }')
  
  # Send to Shopify API
  curl -X POST "https://your-shop.myshopify.com/admin/api/2023-10/products.json" \
    -H "X-Shopify-Access-Token: $SHOPIFY_TOKEN" \
    -H "Content-Type: application/json" \
    -d "$shopify_product"
done
```

### 2. Business Intelligence Dashboard
Create a real-time BI dashboard using the report APIs.

```python
# bi_dashboard.py
import requests
import json
from datetime import datetime, timedelta

class SoapyShopBI:
    def __init__(self, api_base, token):
        self.api_base = api_base
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Accept': 'application/json'
        }
    
    def get_dashboard_metrics(self):
        """Get real-time dashboard metrics"""
        response = requests.get(
            f"{self.api_base}/reports/dashboard/overview",
            headers=self.headers
        )
        return response.json()
    
    def get_sales_trends(self, days=30):
        """Get sales trends for specified days"""
        end_date = datetime.now()
        start_date = end_date - timedelta(days=days)
        
        response = requests.get(
            f"{self.api_base}/reports/analytics/sales",
            headers=self.headers,
            params={
                'start_date': start_date.strftime('%Y-%m-%d'),
                'end_date': end_date.strftime('%Y-%m-%d')
            }
        )
        return response.json()
    
    def get_business_intelligence(self):
        """Get comprehensive business intelligence"""
        response = requests.get(
            f"{self.api_base}/reports/business-intelligence",
            headers=self.headers
        )
        return response.json()
    
    def generate_executive_summary(self):
        """Generate executive summary report"""
        dashboard = self.get_dashboard_metrics()
        sales = self.get_sales_trends()
        bi = self.get_business_intelligence()
        
        summary = {
            'report_date': datetime.now().isoformat(),
            'key_metrics': {
                'total_revenue': dashboard['data']['total_revenue'],
                'total_orders': dashboard['data']['total_orders'],
                'conversion_rate': bi['data']['kpis']['conversion_rate'],
                'customer_lifetime_value': bi['data']['kpis']['customer_lifetime_value']
            },
            'growth_metrics': bi['data']['growth_metrics'],
            'top_products': sales['data']['top_selling_products'][:5]
        }
        
        return summary

# Usage
bi = SoapyShopBI('https://your-domain.com/api/admin', 'your_token')
executive_summary = bi.generate_executive_summary()
print(json.dumps(executive_summary, indent=2))
```

## Automation Scripts

### 1. Automated Daily Reports
Send daily performance reports via email.

```bash
#!/bin/bash
# daily_report.sh

# Set up email configuration
EMAIL_TO="management@soapyshop.com"
EMAIL_SUBJECT="Daily Sales Report - $(date +%Y-%m-%d)"

# Get dashboard metrics
DASHBOARD=$(curl -s -X GET "$SOAPY_API_BASE/reports/dashboard/overview" \
  -H "Authorization: Bearer $SOAPY_TOKEN")

# Extract key metrics
TOTAL_REVENUE=$(echo $DASHBOARD | jq -r '.data.total_revenue')
TOTAL_ORDERS=$(echo $DASHBOARD | jq -r '.data.total_orders')
NEW_CUSTOMERS=$(echo $DASHBOARD | jq -r '.data.new_customers')

# Create email body
EMAIL_BODY="Daily Sales Report for $(date +%Y-%m-%d)

Key Metrics:
- Total Revenue: $TOTAL_REVENUE KWD
- Total Orders: $TOTAL_ORDERS
- New Customers: $NEW_CUSTOMERS

Dashboard: https://your-domain.com/admin/dashboard
"

# Send email (using mail command or your preferred method)
echo "$EMAIL_BODY" | mail -s "$EMAIL_SUBJECT" "$EMAIL_TO"

# Log the report
echo "$(date): Daily report sent - Revenue: $TOTAL_REVENUE, Orders: $TOTAL_ORDERS" >> /var/log/soapy_reports.log
```

### 2. Automated Backup and Export
Automated daily backup of all critical data.

```bash
#!/bin/bash
# automated_backup.sh

BACKUP_DIR="/backups/soapy_shop/$(date +%Y/%m/%d)"
mkdir -p "$BACKUP_DIR"

# Function to start export and wait for completion
start_and_wait_export() {
  local endpoint=$1
  local filename=$2
  local filters=$3
  
  echo "Starting export for $endpoint..."
  
  # Start export
  EXPORT_RESPONSE=$(curl -s -X POST "$SOAPY_API_BASE/exports/$endpoint" \
    -H "Authorization: Bearer $SOAPY_TOKEN" \
    -H "Content-Type: application/json" \
    -d "$filters")
  
  EXPORT_ID=$(echo $EXPORT_RESPONSE | jq -r '.export_id')
  
  if [ "$EXPORT_ID" = "null" ]; then
    echo "Failed to start export for $endpoint"
    return 1
  fi
  
  # Wait for completion
  while true; do
    STATUS=$(curl -s -X GET "$SOAPY_API_BASE/exports/$EXPORT_ID/status" \
      -H "Authorization: Bearer $SOAPY_TOKEN" | jq -r '.status')
    
    case $STATUS in
      "completed")
        echo "Export $EXPORT_ID completed"
        break
        ;;
      "failed")
        echo "Export $EXPORT_ID failed"
        return 1
        ;;
      *)
        echo "Export $EXPORT_ID status: $STATUS"
        sleep 30
        ;;
    esac
  done
  
  # Download file
  curl -X GET "$SOAPY_API_BASE/exports/$EXPORT_ID/download" \
    -H "Authorization: Bearer $SOAPY_TOKEN" \
    -o "$BACKUP_DIR/$filename"
  
  echo "Downloaded $filename to $BACKUP_DIR"
}

# Export all products
start_and_wait_export "products" "products_$(date +%Y%m%d).csv" '{
  "format": "csv",
  "filters": {}
}'

# Export all customers
start_and_wait_export "customers" "customers_$(date +%Y%m%d).csv" '{
  "format": "csv",
  "filters": {}
}'

# Export orders from last 30 days
THIRTY_DAYS_AGO=$(date -d "30 days ago" +%Y-%m-%d)
TODAY=$(date +%Y-%m-%d)

start_and_wait_export "orders" "orders_$(date +%Y%m%d).csv" '{
  "format": "csv",
  "filters": {
    "date_range": {
      "from": "'$THIRTY_DAYS_AGO'",
      "to": "'$TODAY'"
    }
  },
  "include_items": true
}'

# Compress backup
cd "$BACKUP_DIR/.."
tar -czf "soapy_backup_$(date +%Y%m%d).tar.gz" "$(date +%d)"

echo "Backup completed: soapy_backup_$(date +%Y%m%d).tar.gz"
```

## Error Handling Examples

### 1. Robust API Client with Retry Logic
Handle API errors gracefully with automatic retries.

```python
# robust_api_client.py
import requests
import time
import json
from typing import Optional, Dict, Any

class SoapyShopAPIClient:
    def __init__(self, base_url: str, token: str, max_retries: int = 3):
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        self.max_retries = max_retries
    
    def _make_request(self, method: str, endpoint: str, **kwargs) -> Optional[Dict[Any, Any]]:
        """Make HTTP request with retry logic"""
        url = f"{self.base_url}/{endpoint.lstrip('/')}"
        
        for attempt in range(self.max_retries + 1):
            try:
                response = requests.request(method, url, headers=self.headers, **kwargs)
                
                # Handle different status codes
                if response.status_code == 200:
                    return response.json()
                elif response.status_code == 401:
                    raise Exception("Authentication failed - check your token")
                elif response.status_code == 403:
                    raise Exception("Access forbidden - insufficient permissions")
                elif response.status_code == 404:
                    raise Exception(f"Endpoint not found: {endpoint}")
                elif response.status_code == 422:
                    error_data = response.json()
                    raise Exception(f"Validation error: {error_data.get('message', 'Unknown validation error')}")
                elif response.status_code == 429:
                    # Rate limited - wait and retry
                    retry_after = int(response.headers.get('Retry-After', 60))
                    print(f"Rate limited. Waiting {retry_after} seconds...")
                    time.sleep(retry_after)
                    continue
                elif response.status_code >= 500:
                    # Server error - retry with exponential backoff
                    if attempt < self.max_retries:
                        wait_time = 2 ** attempt
                        print(f"Server error (attempt {attempt + 1}). Retrying in {wait_time} seconds...")
                        time.sleep(wait_time)
                        continue
                    else:
                        raise Exception(f"Server error after {self.max_retries} retries")
                else:
                    raise Exception(f"Unexpected status code: {response.status_code}")
                    
            except requests.exceptions.ConnectionError:
                if attempt < self.max_retries:
                    wait_time = 2 ** attempt
                    print(f"Connection error (attempt {attempt + 1}). Retrying in {wait_time} seconds...")
                    time.sleep(wait_time)
                    continue
                else:
                    raise Exception("Connection failed after multiple retries")
            
            except requests.exceptions.Timeout:
                if attempt < self.max_retries:
                    print(f"Request timeout (attempt {attempt + 1}). Retrying...")
                    continue
                else:
                    raise Exception("Request timed out after multiple retries")
        
        return None
    
    def start_export(self, export_type: str, filters: Dict = None, format: str = "csv") -> str:
        """Start an export job"""
        data = {
            "format": format,
            "filters": filters or {}
        }
        
        response = self._make_request("POST", f"exports/{export_type}", json=data)
        if response and response.get('success'):
            return response.get('export_id')
        else:
            raise Exception(f"Failed to start export: {response}")
    
    def get_export_status(self, export_id: str) -> Dict:
        """Get export status"""
        response = self._make_request("GET", f"exports/{export_id}/status")
        return response
    
    def download_export(self, export_id: str, filename: str) -> bool:
        """Download completed export"""
        try:
            response = requests.get(
                f"{self.base_url}/exports/{export_id}/download",
                headers=self.headers,
                stream=True
            )
            
            if response.status_code == 200:
                with open(filename, 'wb') as f:
                    for chunk in response.iter_content(chunk_size=8192):
                        f.write(chunk)
                return True
            else:
                print(f"Download failed with status: {response.status_code}")
                return False
                
        except Exception as e:
            print(f"Download error: {e}")
            return False
    
    def get_report(self, report_type: str, params: Dict = None) -> Dict:
        """Get report data"""
        endpoint = f"reports/{report_type}"
        if params:
            # Convert params to query string
            query_params = "&".join([f"{k}={v}" for k, v in params.items()])
            endpoint += f"?{query_params}"
        
        return self._make_request("GET", endpoint)

# Usage example
try:
    client = SoapyShopAPIClient("https://your-domain.com/api/admin", "your_token")
    
    # Start export with error handling
    export_id = client.start_export("products", {"status": "active"})
    print(f"Export started: {export_id}")
    
    # Monitor export progress
    while True:
        status_response = client.get_export_status(export_id)
        status = status_response.get('status')
        
        if status == 'completed':
            print("Export completed!")
            success = client.download_export(export_id, f"products_{export_id}.csv")
            if success:
                print("File downloaded successfully")
            break
        elif status == 'failed':
            print(f"Export failed: {status_response.get('error_message')}")
            break
        else:
            print(f"Export status: {status}")
            time.sleep(10)
            
except Exception as e:
    print(f"Error: {e}")
```

### 2. Shell Script Error Handling
Comprehensive error handling in bash scripts.

```bash
#!/bin/bash
# robust_export_script.sh

set -euo pipefail  # Exit on error, undefined vars, pipe failures

# Configuration
API_BASE="${SOAPY_API_BASE:-https://your-domain.com/api/admin}"
TOKEN="${SOAPY_TOKEN:-}"
LOG_FILE="/var/log/soapy_exports.log"
MAX_RETRIES=3
RETRY_DELAY=10

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Error handling function
handle_error() {
    local exit_code=$?
    local line_number=$1
    log "ERROR: Script failed at line $line_number with exit code $exit_code"
    exit $exit_code
}

# Set up error trap
trap 'handle_error $LINENO' ERR

# Validate configuration
if [[ -z "$TOKEN" ]]; then
    log "ERROR: SOAPY_TOKEN environment variable is not set"
    exit 1
fi

# Function to make API calls with retry logic
api_call() {
    local method=$1
    local endpoint=$2
    local data=${3:-""}
    local retry_count=0
    
    while [[ $retry_count -lt $MAX_RETRIES ]]; do
        log "Making API call: $method $endpoint (attempt $((retry_count + 1)))"
        
        if [[ -n "$data" ]]; then
            response=$(curl -s -w "\n%{http_code}" -X "$method" \
                "$API_BASE/$endpoint" \
                -H "Authorization: Bearer $TOKEN" \
                -H "Content-Type: application/json" \
                -d "$data" 2>/dev/null || echo -e "\n000")
        else
            response=$(curl -s -w "\n%{http_code}" -X "$method" \
                "$API_BASE/$endpoint" \
                -H "Authorization: Bearer $TOKEN" \
                -H "Accept: application/json" 2>/dev/null || echo -e "\n000")
        fi
        
        # Extract HTTP status code
        http_code=$(echo "$response" | tail -n1)
        response_body=$(echo "$response" | head -n -1)
        
        case $http_code in
            200)
                echo "$response_body"
                return 0
                ;;
            401)
                log "ERROR: Authentication failed - check your token"
                exit 1
                ;;
            403)
                log "ERROR: Access forbidden - insufficient permissions"
                exit 1
                ;;
            404)
                log "ERROR: Endpoint not found: $endpoint"
                exit 1
                ;;
            422)
                log "ERROR: Validation error: $response_body"
                exit 1
                ;;
            429)
                log "WARNING: Rate limited. Waiting before retry..."
                sleep $((RETRY_DELAY * 2))
                ;;
            5*)
                log "WARNING: Server error ($http_code). Retrying..."
                sleep $RETRY_DELAY
                ;;
            000)
                log "WARNING: Connection failed. Retrying..."
                sleep $RETRY_DELAY
                ;;
            *)
                log "ERROR: Unexpected HTTP status: $http_code"
                log "Response: $response_body"
                exit 1
                ;;
        esac
        
        ((retry_count++))
    done
    
    log "ERROR: API call failed after $MAX_RETRIES attempts"
    exit 1
}

# Function to wait for export completion
wait_for_export() {
    local export_id=$1
    local max_wait_time=${2:-3600}  # Default 1 hour
    local start_time=$(date +%s)
    
    log "Waiting for export $export_id to complete..."
    
    while true; do
        current_time=$(date +%s)
        elapsed_time=$((current_time - start_time))
        
        if [[ $elapsed_time -gt $max_wait_time ]]; then
            log "ERROR: Export $export_id timed out after $max_wait_time seconds"
            exit 1
        fi
        
        status_response=$(api_call "GET" "exports/$export_id/status")
        status=$(echo "$status_response" | jq -r '.status // "unknown"')
        
        case $status in
            "completed")
                log "Export $export_id completed successfully"
                return 0
                ;;
            "failed")
                error_message=$(echo "$status_response" | jq -r '.error_message // "Unknown error"')
                log "ERROR: Export $export_id failed: $error_message"
                exit 1
                ;;
            "processing"|"queued")
                log "Export $export_id status: $status (elapsed: ${elapsed_time}s)"
                sleep 30
                ;;
            *)
                log "WARNING: Unknown export status: $status"
                sleep 30
                ;;
        esac
    done
}

# Main execution
main() {
    log "Starting export script"
    
    # Start product export
    export_data='{
        "format": "csv",
        "filters": {"status": "active"},
        "columns": ["id", "name", "sku", "price", "stock_quantity"]
    }'
    
    export_response=$(api_call "POST" "exports/products" "$export_data")
    export_id=$(echo "$export_response" | jq -r '.export_id // empty')
    
    if [[ -z "$export_id" ]]; then
        log "ERROR: Failed to get export ID from response: $export_response"
        exit 1
    fi
    
    log "Export started with ID: $export_id"
    
    # Wait for completion
    wait_for_export "$export_id"
    
    # Download file
    output_file="products_$(date +%Y%m%d_%H%M%S).csv"
    log "Downloading export to $output_file"
    
    if curl -f -X GET "$API_BASE/exports/$export_id/download" \
        -H "Authorization: Bearer $TOKEN" \
        -o "$output_file"; then
        log "Export downloaded successfully: $output_file"
        log "File size: $(du -h "$output_file" | cut -f1)"
    else
        log "ERROR: Failed to download export file"
        exit 1
    fi
    
    log "Export script completed successfully"
}

# Run main function
main "$@"
```

## Performance Optimization Tips

### 1. Batch Operations
Process multiple exports efficiently.

```bash
# Batch export multiple data types
export_types=("products" "customers" "orders")
export_ids=()

# Start all exports simultaneously
for type in "${export_types[@]}"; do
    response=$(curl -s -X POST "$SOAPY_API_BASE/exports/$type" \
        -H "Authorization: Bearer $SOAPY_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"format": "csv"}')
    
    export_id=$(echo "$response" | jq -r '.export_id')
    export_ids+=("$export_id")
    echo "Started $type export: $export_id"
done

# Monitor all exports
for export_id in "${export_ids[@]}"; do
    # Monitor in background
    (
        while true; do
            status=$(curl -s -X GET "$SOAPY_API_BASE/exports/$export_id/status" \
                -H "Authorization: Bearer $SOAPY_TOKEN" | jq -r '.status')
            
            if [ "$status" = "completed" ]; then
                curl -X GET "$SOAPY_API_BASE/exports/$export_id/download" \
                    -H "Authorization: Bearer $SOAPY_TOKEN" \
                    -o "${export_id}.csv"
                echo "Downloaded: ${export_id}.csv"
                break
            fi
            
            sleep 10
        done
    ) &
done

# Wait for all background jobs
wait
echo "All exports completed"
```

### 2. Caching Strategy
Implement caching for frequently accessed reports.

```python
# cached_reports.py
import requests
import json
import time
from datetime import datetime, timedelta
import hashlib

class CachedReportClient:
    def __init__(self, api_base, token, cache_dir="/tmp/soapy_cache"):
        self.api_base = api_base
        self.headers = {'Authorization': f'Bearer {token}'}
        self.cache_dir = cache_dir
        os.makedirs(cache_dir, exist_ok=True)
    
    def _get_cache_key(self, endpoint, params=None):
        """Generate cache key from endpoint and parameters"""
        cache_string = f"{endpoint}_{params or ''}"
        return hashlib.md5(cache_string.encode()).hexdigest()
    
    def _is_cache_valid(self, cache_file, ttl_minutes=30):
        """Check if cache file is still valid"""
        if not os.path.exists(cache_file):
            return False
        
        file_time = datetime.fromtimestamp(os.path.getmtime(cache_file))
        return datetime.now() - file_time < timedelta(minutes=ttl_minutes)
    
    def get_report_cached(self, endpoint, params=None, cache_ttl=30):
        """Get report with caching"""
        cache_key = self._get_cache_key(endpoint, params)
        cache_file = f"{self.cache_dir}/{cache_key}.json"
        
        # Check cache first
        if self._is_cache_valid(cache_file, cache_ttl):
            with open(cache_file, 'r') as f:
                return json.load(f)
        
        # Fetch from API
        url = f"{self.api_base}/reports/{endpoint}"
        if params:
            url += "?" + "&".join([f"{k}={v}" for k, v in params.items()])
        
        response = requests.get(url, headers=self.headers)
        data = response.json()
        
        # Cache the result
        with open(cache_file, 'w') as f:
            json.dump(data, f)
        
        return data

# Usage
client = CachedReportClient("https://your-domain.com/api/admin", "your_token")

# This will fetch from API first time, then use cache for 30 minutes
dashboard_data = client.get_report_cached("dashboard/overview", cache_ttl=30)
sales_data = client.get_report_cached("analytics/sales", {"period": "week"}, cache_ttl=60)
```

This comprehensive guide provides practical examples for integrating with the Soapy Shop API, handling errors gracefully, and optimizing performance for production use cases.
