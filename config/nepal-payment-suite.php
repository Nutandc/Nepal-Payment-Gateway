<?php

declare(strict_types=1);

return [
    'default' => env('NEPAL_PAYMENT_GATEWAY', 'esewa'),

    'http' => [
        'timeout' => (int) env('NEPAL_PAYMENT_HTTP_TIMEOUT', 10),
    ],

    'logging' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_LOGGING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'idempotency' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_IDEMPOTENCY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'ttl' => (int) env('NEPAL_PAYMENT_IDEMPOTENCY_TTL', 86400),
        'prefix' => env('NEPAL_PAYMENT_IDEMPOTENCY_PREFIX', 'nepal-payment-suite:idempotency:'),
    ],

    'esewa' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_ESEWA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('NEPAL_PAYMENT_ESEWA_BASE_URL'),
        'endpoints' => [
            'initiate' => env('NEPAL_PAYMENT_ESEWA_ENDPOINT_INITIATE'),
            'verify' => env('NEPAL_PAYMENT_ESEWA_ENDPOINT_VERIFY'),
        ],
        'product_code' => env('NEPAL_PAYMENT_ESEWA_PRODUCT_CODE'),
        'secret_key' => env('NEPAL_PAYMENT_ESEWA_SECRET_KEY'),
    ],

    'khalti' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_KHALTI_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('NEPAL_PAYMENT_KHALTI_BASE_URL'),
        'endpoints' => [
            'initiate' => env('NEPAL_PAYMENT_KHALTI_ENDPOINT_INITIATE'),
            'lookup' => env('NEPAL_PAYMENT_KHALTI_ENDPOINT_LOOKUP'),
        ],
        'secret_key' => env('NEPAL_PAYMENT_KHALTI_SECRET_KEY'),
    ],

    'connectips' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_CONNECTIPS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'base_url' => env('NEPAL_PAYMENT_CONNECTIPS_BASE_URL'),
        'endpoints' => [
            'initiate' => env('NEPAL_PAYMENT_CONNECTIPS_ENDPOINT_INITIATE'),
            'verify' => env('NEPAL_PAYMENT_CONNECTIPS_ENDPOINT_VERIFY'),
        ],
        'merchant_id' => env('NEPAL_PAYMENT_CONNECTIPS_MERCHANT_ID'),
        'app_id' => env('NEPAL_PAYMENT_CONNECTIPS_APP_ID'),
        'app_name' => env('NEPAL_PAYMENT_CONNECTIPS_APP_NAME'),
        'private_key_path' => env('NEPAL_PAYMENT_CONNECTIPS_PRIVATE_KEY'),
        'private_key_passphrase' => env('NEPAL_PAYMENT_CONNECTIPS_PRIVATE_KEY_PASSPHRASE'),
        'password' => env('NEPAL_PAYMENT_CONNECTIPS_PASSWORD'),
    ],

    'stripe' => [
        'enabled' => filter_var(env('NEPAL_PAYMENT_STRIPE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'secret_key' => env('NEPAL_PAYMENT_STRIPE_SECRET'),
    ],
];
