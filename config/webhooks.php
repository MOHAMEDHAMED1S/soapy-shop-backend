<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for webhook handling in the
    | application. You can configure webhook providers, security settings,
    | and processing options here.
    |
    */

    'providers' => [
        'myfatoorah' => [
            'name' => 'MyFatoorah',
            'webhook_url' => env('MYFATOORAH_WEBHOOK_URL', '/api/v1/webhooks/myfatoorah'),
            'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),
            'signature_header' => 'X-MyFatoorah-Signature',
            'enabled' => env('MYFATOORAH_WEBHOOK_ENABLED', true),
            'retry_attempts' => env('MYFATOORAH_WEBHOOK_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('MYFATOORAH_WEBHOOK_RETRY_DELAY', 60), // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Configure webhook security settings including signature verification,
    | IP whitelisting, and rate limiting.
    |
    */

    'security' => [
        'verify_signatures' => env('WEBHOOK_VERIFY_SIGNATURES', true),
        'allowed_ips' => [
            // Add MyFatoorah IPs here
            '185.166.140.61',
            '185.166.140.62',
            '185.166.140.63',
        ],
        'rate_limit' => [
            'enabled' => env('WEBHOOK_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('WEBHOOK_RATE_LIMIT_MAX_ATTEMPTS', 100),
            'decay_minutes' => env('WEBHOOK_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Processing
    |--------------------------------------------------------------------------
    |
    | Configure webhook processing settings including queue usage,
    | timeout settings, and error handling.
    |
    */

    'processing' => [
        'use_queue' => env('WEBHOOK_USE_QUEUE', true),
        'queue_name' => env('WEBHOOK_QUEUE_NAME', 'webhooks'),
        'timeout' => env('WEBHOOK_TIMEOUT', 30), // seconds
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Logging
    |--------------------------------------------------------------------------
    |
    | Configure webhook logging settings including log level,
    | retention period, and cleanup options.
    |
    */

    'logging' => [
        'enabled' => env('WEBHOOK_LOGGING_ENABLED', true),
        'level' => env('WEBHOOK_LOG_LEVEL', 'info'),
        'retention_days' => env('WEBHOOK_LOG_RETENTION_DAYS', 30),
        'cleanup_enabled' => env('WEBHOOK_CLEANUP_ENABLED', true),
        'cleanup_schedule' => env('WEBHOOK_CLEANUP_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Notifications
    |--------------------------------------------------------------------------
    |
    | Configure webhook notification settings for admin alerts
    | and error notifications.
    |
    */

    'notifications' => [
        'enabled' => env('WEBHOOK_NOTIFICATIONS_ENABLED', true),
        'admin_email' => env('WEBHOOK_ADMIN_EMAIL'),
        'slack_webhook' => env('WEBHOOK_SLACK_WEBHOOK'),
        'notify_on_failure' => env('WEBHOOK_NOTIFY_ON_FAILURE', true),
        'notify_on_success' => env('WEBHOOK_NOTIFY_ON_SUCCESS', false),
        'failure_threshold' => env('WEBHOOK_FAILURE_THRESHOLD', 5), // consecutive failures
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Testing
    |--------------------------------------------------------------------------
    |
    | Configure webhook testing settings for development and testing.
    |
    */

    'testing' => [
        'enabled' => env('WEBHOOK_TESTING_ENABLED', true),
        'test_endpoint' => env('WEBHOOK_TEST_ENDPOINT', '/api/v1/webhooks/test'),
        'mock_responses' => env('WEBHOOK_MOCK_RESPONSES', false),
        'test_data' => [
            'myfatoorah' => [
                'PaymentId' => 'test_payment_123',
                'InvoiceId' => 'test_invoice_123',
                'PaymentStatus' => 'Paid',
                'TransactionId' => 'test_txn_123',
                'Amount' => 35.75,
                'Currency' => 'KWD',
                'CustomerName' => 'Test Customer',
                'CustomerEmail' => 'test@example.com',
                'CustomerMobile' => '96555555555',
                'PaymentMethod' => 'vm',
                'PaymentDate' => '2025-10-02T19:30:00Z',
                'ReferenceId' => 'test_ref_123'
            ],
        ],
    ],
];
