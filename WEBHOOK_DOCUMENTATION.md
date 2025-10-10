# 🔗 Webhook System Documentation

## Overview

The webhook system provides real-time notifications for payment status updates from MyFatoorah and other payment providers. It ensures reliable processing of payment events and maintains comprehensive logging for debugging and monitoring.

## 🏗️ Architecture

### Components

1. **WebhookController** - Handles incoming webhook requests
2. **WebhookService** - Processes webhook data and updates payment/order status
3. **WebhookLog** - Stores all webhook events for audit and debugging
4. **WebhookMiddleware** - Adds logging and CORS headers
5. **Event System** - Asynchronous webhook processing
6. **Commands** - Management and monitoring tools

### Data Flow

```
MyFatoorah → WebhookController → WebhookService → Database Updates → Admin Notifications
```

## 📡 Webhook Endpoints

### Public Endpoints

#### `POST /api/v1/webhooks/myfatoorah`
Handles MyFatoorah payment notifications.

**Headers:**
- `Content-Type: application/json`
- `X-MyFatoorah-Signature` (optional for signature verification)

**Request Body:**
```json
{
  "PaymentId": "12345",
  "InvoiceId": "67890",
  "PaymentStatus": "Paid",
  "TransactionId": "txn_123",
  "Amount": 35.75,
  "Currency": "KWD",
  "CustomerName": "محمد علي",
  "CustomerEmail": "mohammed@example.com",
  "CustomerMobile": "96555555555",
  "PaymentMethod": "vm",
  "PaymentDate": "2025-10-02T19:30:00Z",
  "ReferenceId": "ORD-20251002-33685F"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook processed successfully",
  "webhook_id": 123
}
```

#### `POST /api/v1/webhooks/test`
Test endpoint for webhook functionality.

### Admin Endpoints

#### `GET /api/v1/admin/webhooks/logs`
Retrieve webhook logs with filtering options.

**Query Parameters:**
- `provider` - Filter by provider (myfatoorah)
- `processed` - Filter by processing status (true/false)
- `date_from` - Start date filter
- `date_to` - End date filter
- `per_page` - Results per page (default: 15)

#### `GET /api/v1/admin/webhooks/statistics`
Get webhook statistics and metrics.

**Query Parameters:**
- `period` - Statistics period in days (default: 30)

#### `POST /api/v1/admin/webhooks/{id}/retry`
Retry processing a failed webhook.

## 🔧 Configuration

### Environment Variables

```env
# MyFatoorah Webhook Settings
MYFATOORAH_WEBHOOK_URL=/api/v1/webhooks/myfatoorah
MYFATOORAH_WEBHOOK_SECRET=your_webhook_secret
MYFATOORAH_WEBHOOK_ENABLED=true

# General Webhook Settings
WEBHOOK_VERIFY_SIGNATURES=false
WEBHOOK_USE_QUEUE=true
WEBHOOK_LOGGING_ENABLED=true
WEBHOOK_LOG_RETENTION_DAYS=30
WEBHOOK_CLEANUP_ENABLED=true
WEBHOOK_NOTIFICATIONS_ENABLED=true
WEBHOOK_TESTING_ENABLED=true
```

### Webhook Configuration File

The `config/webhooks.php` file contains comprehensive webhook settings:

- **Providers** - Configuration for different webhook providers
- **Security** - Signature verification and IP whitelisting
- **Processing** - Queue settings and timeout configuration
- **Logging** - Log retention and cleanup settings
- **Notifications** - Admin alert configuration
- **Testing** - Development and testing settings

## 📊 Status Mapping

### MyFatoorah to Internal Status

| MyFatoorah Status | Internal Status | Description |
|------------------|-----------------|-------------|
| Paid | paid | Payment completed successfully |
| Failed | failed | Payment failed |
| Cancelled | failed | Payment cancelled |
| Expired | failed | Payment expired |
| Refunded | refunded | Payment refunded |
| PartiallyRefunded | refunded | Partial refund |
| Pending | pending | Payment pending |
| InProgress | pending | Payment in progress |
| Authorized | pending | Payment authorized |
| Captured | paid | Payment captured |
| Voided | failed | Payment voided |

### Order Status Updates

When payment status changes, the corresponding order status is updated:

- `paid` → Order status: `paid`
- `failed` → Order status: `awaiting_payment`
- `refunded` → Order status: `refunded`
- `pending` → Order status: `awaiting_payment`

## 🛠️ Management Commands

### Webhook Statistics
```bash
php artisan webhook:stats [--period=30] [--provider=myfatoorah]
```

### Test Webhook
```bash
php artisan webhook:test [--payment-id=123] [--status=Paid] [--url=http://localhost:8000/api/v1/webhooks/test]
```

### Retry Failed Webhooks
```bash
php artisan webhook:retry-failed [--limit=10] [--provider=myfatoorah]
```

### Cleanup Old Logs
```bash
php artisan webhook:cleanup [--days=30] [--dry-run]
```

## 📝 Logging and Monitoring

### Webhook Logs

All webhook events are logged in the `webhook_logs` table:

- **Provider** - Webhook provider (myfatoorah)
- **Payload** - Complete webhook data
- **Received At** - Timestamp of webhook receipt
- **Processed** - Processing status
- **Processing Notes** - Detailed processing information

### Admin Notifications

Important webhook events trigger admin notifications:

- Payment status changes (paid, failed, refunded)
- Webhook processing failures
- System errors

### Log Files

Webhook activities are logged to Laravel's log files:

- `storage/logs/laravel.log` - General webhook logs
- Webhook-specific log entries with structured data

## 🔒 Security

### Signature Verification

Webhook signatures can be verified using provider-specific secrets:

```php
// In WebhookService
public function verifyWebhookSignature($request)
{
    $signature = $request->header('X-MyFatoorah-Signature');
    $webhookSecret = config('services.myfatoorah.webhook_secret');
    
    // Implement signature verification logic
    return $this->verifySignature($request->getContent(), $signature, $webhookSecret);
}
```

### IP Whitelisting

Configure allowed IP addresses in `config/webhooks.php`:

```php
'allowed_ips' => [
    '185.166.140.61', // MyFatoorah IP 1
    '185.166.140.62', // MyFatoorah IP 2
    '185.166.140.63', // MyFatoorah IP 3
],
```

### Rate Limiting

Webhook endpoints are protected with rate limiting:

- Default: 100 requests per minute
- Configurable per provider
- Automatic blocking of excessive requests

## 🚀 Event System

### Asynchronous Processing

Webhooks can be processed asynchronously using Laravel's event system:

1. **WebhookReceived** event is fired
2. **ProcessWebhook** listener handles the event
3. Processing happens in background queue
4. Results are logged and notifications sent

### Queue Configuration

```env
WEBHOOK_USE_QUEUE=true
WEBHOOK_QUEUE_NAME=webhooks
```

## 🧪 Testing

### Test Webhook Endpoint

Use the test endpoint to verify webhook functionality:

```bash
curl -X POST "http://localhost:8000/api/v1/webhooks/test" \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### Command Line Testing

```bash
# Test with real payment data
php artisan webhook:test --payment-id=9 --status=Paid

# Test with custom URL
php artisan webhook:test --url=http://localhost:8000/api/v1/webhooks/myfatoorah
```

### Mock Responses

Enable mock responses for testing:

```env
WEBHOOK_MOCK_RESPONSES=true
```

## 📈 Monitoring and Analytics

### Statistics Dashboard

The webhook statistics provide insights into:

- Total webhook volume
- Success/failure rates
- Provider-specific metrics
- Daily/hourly trends
- Failed webhook analysis

### Health Checks

Monitor webhook system health:

- Processing success rate
- Average processing time
- Queue depth
- Error frequency
- Log file size

## 🔧 Troubleshooting

### Common Issues

1. **Webhook Not Processing**
   - Check webhook logs for errors
   - Verify payment record exists
   - Check queue status if using async processing

2. **Signature Verification Failed**
   - Verify webhook secret configuration
   - Check signature header format
   - Ensure request body integrity

3. **Order Status Not Updated**
   - Check payment record status
   - Verify order-payment relationship
   - Review status mapping logic

### Debug Commands

```bash
# Check webhook statistics
php artisan webhook:stats

# Retry failed webhooks
php artisan webhook:retry-failed

# Clean up old logs
php artisan webhook:cleanup --dry-run
```

### Log Analysis

```bash
# View recent webhook logs
tail -f storage/logs/laravel.log | grep -i webhook

# Check specific webhook processing
grep "webhook_log_id:123" storage/logs/laravel.log
```

## 🚀 Deployment

### Production Setup

1. **Configure Webhook URLs**
   - Set production webhook URLs in MyFatoorah dashboard
   - Update environment variables

2. **Enable Security**
   - Configure webhook secrets
   - Enable signature verification
   - Set up IP whitelisting

3. **Queue Configuration**
   - Set up Redis/Database queue
   - Configure queue workers
   - Monitor queue health

4. **Monitoring**
   - Set up log monitoring
   - Configure alerting
   - Monitor webhook statistics

### SSL/TLS

Ensure webhook endpoints are accessible via HTTPS:

- MyFatoorah requires HTTPS for webhook URLs
- Configure SSL certificates
- Test webhook accessibility

## 📚 API Reference

### WebhookController Methods

- `handleMyFatoorahWebhook()` - Process MyFatoorah webhooks
- `getWebhookLogs()` - Retrieve webhook logs
- `getWebhookStatistics()` - Get webhook statistics
- `retryWebhook()` - Retry failed webhook
- `testWebhook()` - Test webhook endpoint

### WebhookService Methods

- `processMyFatoorahWebhook()` - Process webhook data
- `verifyWebhookSignature()` - Verify webhook signature
- `getWebhookStatistics()` - Get statistics
- `retryWebhook()` - Retry webhook processing

## 🎯 Best Practices

1. **Always Log Webhooks**
   - Store complete webhook payloads
   - Include processing timestamps
   - Log both success and failure cases

2. **Handle Failures Gracefully**
   - Implement retry mechanisms
   - Provide clear error messages
   - Monitor failure rates

3. **Secure Webhook Endpoints**
   - Verify webhook signatures
   - Use HTTPS in production
   - Implement rate limiting

4. **Monitor Performance**
   - Track processing times
   - Monitor queue depth
   - Set up alerting

5. **Test Thoroughly**
   - Test with real webhook data
   - Verify status mappings
   - Test error scenarios

## 🔮 Future Enhancements

- Support for additional payment providers
- Real-time webhook dashboard
- Advanced analytics and reporting
- Webhook replay functionality
- Automated retry with exponential backoff
- Webhook versioning support
