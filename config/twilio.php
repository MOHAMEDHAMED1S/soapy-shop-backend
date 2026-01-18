<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Twilio WhatsApp messaging service.
    |
    */

    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
    
    // Store name for messages
    'store_name' => env('TWILIO_STORE_NAME', 'المتجر'),
    
    // Admin phones (comma-separated)
    'admin_phones' => env('TWILIO_ADMIN_PHONES', ''),
    
    // Delivery phones (comma-separated)
    'delivery_phones' => env('TWILIO_DELIVERY_PHONES', ''),
    
    // Notification toggles
    'customer_notifications_enabled' => env('TWILIO_CUSTOMER_NOTIFICATIONS_ENABLED', true),
    'admin_notifications_enabled' => env('TWILIO_ADMIN_NOTIFICATIONS_ENABLED', true),
    
    // Template SIDs (from Twilio Console)
    'templates' => [
        'order_created' => env('TWILIO_TEMPLATE_ORDER_CREATED'),
        'status_update' => env('TWILIO_TEMPLATE_STATUS_UPDATE'),
        'order_shipped' => env('TWILIO_TEMPLATE_ORDER_SHIPPED'),
        'order_delivered' => env('TWILIO_TEMPLATE_ORDER_DELIVERED'),
        'admin_new_order' => env('TWILIO_TEMPLATE_ADMIN_NEW_ORDER'),
        'delivery_new_order' => env('TWILIO_TEMPLATE_DELIVERY_NEW_ORDER'),
    ],
];
