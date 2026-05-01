<?php

return [
    // Iwexa Hub API Configuration
    'iwexa_api_base_url' => env('IWEXA_API_BASE_URL', 'https://api.iwexa.com'),
    'iwexa_api_key' => env('IWEXA_API_KEY'),
    'iwexa_hmac_secret' => env('IWEXA_HMAC_SECRET'),

    // Sync Job Configuration
    'sync_job_retry_limit' => env('IWEXA_SYNC_JOB_RETRY_LIMIT', 3),
    'sync_job_retry_delay_minutes' => env('IWEXA_SYNC_JOB_RETRY_DELAY', 5),

    // Idempotency Configuration
    'idempotency_key_expiry_hours' => env('IWEXA_IDEMPOTENCY_KEY_EXPIRY', 24),
    'webhook_event_expiry_hours' => env('IWEXA_WEBHOOK_EVENT_EXPIRY', 24),

    // Batch Processing
    'batch_size_products' => env('IWEXA_BATCH_SIZE_PRODUCTS', 100),
    'batch_size_stock' => env('IWEXA_BATCH_SIZE_STOCK', 500),

    // Logging
    'log_channel' => env('IWEXA_LOG_CHANNEL', 'daily'),
];
