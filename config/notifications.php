<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the notification system.
    | You can configure notification types, channels, and preferences here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure which notification channels are enabled and their settings.
    |
    */

    'channels' => [
        'email' => [
            'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
            'admin_email' => env('NOTIFICATION_ADMIN_EMAIL', 'admin@soapyshop.com'),
            'from_email' => env('NOTIFICATION_FROM_EMAIL', 'noreply@soapyshop.com'),
            'from_name' => env('NOTIFICATION_FROM_NAME', 'soapy bubbles'),
        ],
        'push' => [
            'enabled' => env('NOTIFICATION_PUSH_ENABLED', true),
            'driver' => env('NOTIFICATION_PUSH_DRIVER', 'pusher'),
            'app_id' => env('PUSHER_APP_ID'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'cluster' => env('PUSHER_APP_CLUSTER'),
        ],
        'sms' => [
            'enabled' => env('NOTIFICATION_SMS_ENABLED', false),
            'provider' => env('NOTIFICATION_SMS_PROVIDER', 'twilio'),
            'from_number' => env('NOTIFICATION_SMS_FROM'),
            'admin_number' => env('NOTIFICATION_ADMIN_PHONE'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Configure which notification types are enabled and their settings.
    |
    */

    'types' => [
        'order_created' => [
            'enabled' => env('NOTIFICATION_ORDER_CREATED', true),
            'channels' => ['email', 'push'],
            'priority' => 'medium',
            'email_template' => 'emails.admin-notification',
        ],
        'order_paid' => [
            'enabled' => env('NOTIFICATION_ORDER_PAID', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'order_shipped' => [
            'enabled' => env('NOTIFICATION_ORDER_SHIPPED', true),
            'channels' => ['push'],
            'priority' => 'medium',
            'email_template' => 'emails.admin-notification',
        ],
        'order_delivered' => [
            'enabled' => env('NOTIFICATION_ORDER_DELIVERED', true),
            'channels' => ['push'],
            'priority' => 'medium',
            'email_template' => 'emails.admin-notification',
        ],
        'order_cancelled' => [
            'enabled' => env('NOTIFICATION_ORDER_CANCELLED', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'order_refunded' => [
            'enabled' => env('NOTIFICATION_ORDER_REFUNDED', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'payment_initiated' => [
            'enabled' => env('NOTIFICATION_PAYMENT_INITIATED', true),
            'channels' => ['push'],
            'priority' => 'low',
            'email_template' => 'emails.admin-notification',
        ],
        'payment_paid' => [
            'enabled' => env('NOTIFICATION_PAYMENT_PAID', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'payment_failed' => [
            'enabled' => env('NOTIFICATION_PAYMENT_FAILED', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'payment_refunded' => [
            'enabled' => env('NOTIFICATION_PAYMENT_REFUNDED', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'product_low_stock' => [
            'enabled' => env('NOTIFICATION_PRODUCT_LOW_STOCK', true),
            'channels' => ['email', 'push'],
            'priority' => 'high',
            'email_template' => 'emails.admin-notification',
        ],
        'product_out_of_stock' => [
            'enabled' => env('NOTIFICATION_PRODUCT_OUT_OF_STOCK', true),
            'channels' => ['email', 'push'],
            'priority' => 'urgent',
            'email_template' => 'emails.admin-notification',
        ],
        'product_created' => [
            'enabled' => env('NOTIFICATION_PRODUCT_CREATED', true),
            'channels' => ['push'],
            'priority' => 'low',
            'email_template' => 'emails.admin-notification',
        ],
        'product_updated' => [
            'enabled' => env('NOTIFICATION_PRODUCT_UPDATED', true),
            'channels' => ['push'],
            'priority' => 'low',
            'email_template' => 'emails.admin-notification',
        ],
        'new_customer' => [
            'enabled' => env('NOTIFICATION_NEW_CUSTOMER', true),
            'channels' => ['push'],
            'priority' => 'low',
            'email_template' => 'emails.admin-notification',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Preferences
    |--------------------------------------------------------------------------
    |
    | Default notification preferences and settings.
    |
    */

    'preferences' => [
        'email_notifications' => env('NOTIFICATION_EMAIL_ENABLED', true),
        'push_notifications' => env('NOTIFICATION_PUSH_ENABLED', true),
        'sms_notifications' => env('NOTIFICATION_SMS_ENABLED', false),
        'quiet_hours_start' => env('NOTIFICATION_QUIET_HOURS_START', '22:00'),
        'quiet_hours_end' => env('NOTIFICATION_QUIET_HOURS_END', '08:00'),
        'batch_notifications' => env('NOTIFICATION_BATCH_ENABLED', false),
        'batch_interval' => env('NOTIFICATION_BATCH_INTERVAL', 60), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Configure notification templates and their settings.
    |
    */

    'templates' => [
        'email' => [
            'default' => 'emails.admin-notification',
            'layout' => 'emails.layout',
            'styles' => [
                'primary_color' => '#e74c3c',
                'secondary_color' => '#2c3e50',
                'background_color' => '#f4f4f4',
                'text_color' => '#333333',
            ],
        ],
        'push' => [
            'title_template' => '[{priority}] {title}',
            'body_template' => '{message}',
            'icon' => '/images/notification-icon.png',
            'sound' => 'default',
        ],
        'sms' => [
            'template' => '[soapy bubbles] {title}: {message}',
            'max_length' => 160,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Queue
    |--------------------------------------------------------------------------
    |
    | Configure notification queue settings.
    |
    */

    'queue' => [
        'enabled' => env('NOTIFICATION_QUEUE_ENABLED', true),
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'database'),
        'queue' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),
        'retry_after' => env('NOTIFICATION_QUEUE_RETRY_AFTER', 60),
        'max_tries' => env('NOTIFICATION_QUEUE_MAX_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Logging
    |--------------------------------------------------------------------------
    |
    | Configure notification logging settings.
    |
    */

    'logging' => [
        'enabled' => env('NOTIFICATION_LOGGING_ENABLED', true),
        'level' => env('NOTIFICATION_LOG_LEVEL', 'info'),
        'retention_days' => env('NOTIFICATION_LOG_RETENTION_DAYS', 30),
        'cleanup_enabled' => env('NOTIFICATION_CLEANUP_ENABLED', true),
        'cleanup_schedule' => env('NOTIFICATION_CLEANUP_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for notifications.
    |
    */

    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('NOTIFICATION_MAX_PER_MINUTE', 10),
        'max_per_hour' => env('NOTIFICATION_MAX_PER_HOUR', 100),
        'max_per_day' => env('NOTIFICATION_MAX_PER_DAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Testing
    |--------------------------------------------------------------------------
    |
    | Configure notification testing settings.
    |
    */

    'testing' => [
        'enabled' => env('NOTIFICATION_TESTING_ENABLED', true),
        'test_email' => env('NOTIFICATION_TEST_EMAIL'),
        'test_phone' => env('NOTIFICATION_TEST_PHONE'),
        'mock_responses' => env('NOTIFICATION_MOCK_RESPONSES', false),
    ],
];
