Nepal Payment Gateway
=====================

[![CI](https://github.com/Nutandc/nepal-payment-gateway/actions/workflows/ci.yml/badge.svg)](https://github.com/Nutandc/nepal-payment-gateway/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/nutandc/nepal-payment-gateway)](https://packagist.org/packages/nutandc/nepal-payment-gateway)
[![Total Downloads](https://img.shields.io/packagist/dt/nutandc/nepal-payment-gateway)](https://packagist.org/packages/nutandc/nepal-payment-gateway)
[![License](https://img.shields.io/github/license/Nutandc/nepal-payment-gateway)](LICENSE)

Laravel-first payment gateway package for Nepal gateways with optional Stripe support. Built for PHP 8.2+ and Laravel 10+ with clean, typed, and configurable services.

Features
--------
- Nepal gateways: eSewa, Khalti, ConnectIPS
- Optional Stripe integration (Checkout Session)
- Config-driven endpoints and credentials (no hardcoded URLs)
- Idempotency support to prevent duplicate transactions
- PSR-3 logging and structured error handling
- SOLID, typed, and testable services

Gateway flags
-------------
| Gateway    | Config Key                         | Default |
|------------|------------------------------------|---------|
| eSewa      | `NEPAL_PAYMENT_ESEWA_ENABLED`      | `true`  |
| Khalti     | `NEPAL_PAYMENT_KHALTI_ENABLED`     | `true`  |
| ConnectIPS | `NEPAL_PAYMENT_CONNECTIPS_ENABLED` | `true`  |
| Stripe     | `NEPAL_PAYMENT_STRIPE_ENABLED`     | `false` |

Requirements
------------
- PHP 8.2+
- Laravel 10/11/12
- Extensions: `ext-curl`, `ext-openssl`

Installation
------------
```bash
composer require nutandc/nepal-payment-gateway
```

Publish config:
```bash
php artisan vendor:publish --tag=nepal-payment-suite-config
```

Configuration
-------------
Copy `.env.example` values into your Laravel `.env` file and update them for your gateway accounts.

Set `.env` values as needed (examples):
```env
NEPAL_PAYMENT_GATEWAY=esewa

NEPAL_PAYMENT_ESEWA_ENABLED=true
NEPAL_PAYMENT_ESEWA_BASE_URL=your_esewa_base_url
NEPAL_PAYMENT_ESEWA_ENDPOINT_INITIATE=your_esewa_initiate_path
NEPAL_PAYMENT_ESEWA_ENDPOINT_VERIFY=your_esewa_verify_path
NEPAL_PAYMENT_ESEWA_PRODUCT_CODE=your_product_code
NEPAL_PAYMENT_ESEWA_SECRET_KEY=your_secret_key

NEPAL_PAYMENT_KHALTI_ENABLED=true
NEPAL_PAYMENT_KHALTI_BASE_URL=your_khalti_base_url
NEPAL_PAYMENT_KHALTI_ENDPOINT_INITIATE=your_khalti_initiate_path
NEPAL_PAYMENT_KHALTI_ENDPOINT_LOOKUP=your_khalti_lookup_path
NEPAL_PAYMENT_KHALTI_SECRET_KEY=your_secret_key

NEPAL_PAYMENT_CONNECTIPS_ENABLED=true
NEPAL_PAYMENT_CONNECTIPS_BASE_URL=your_connectips_base_url
NEPAL_PAYMENT_CONNECTIPS_ENDPOINT_INITIATE=your_connectips_initiate_path
NEPAL_PAYMENT_CONNECTIPS_ENDPOINT_VERIFY=your_connectips_verify_path
NEPAL_PAYMENT_CONNECTIPS_MERCHANT_ID=your_merchant_id
NEPAL_PAYMENT_CONNECTIPS_APP_ID=your_app_id
NEPAL_PAYMENT_CONNECTIPS_APP_NAME=your_app_name
NEPAL_PAYMENT_CONNECTIPS_PRIVATE_KEY=/path/to/private.pem
NEPAL_PAYMENT_CONNECTIPS_PRIVATE_KEY_PASSPHRASE=optional_passphrase
NEPAL_PAYMENT_CONNECTIPS_PASSWORD=your_password

NEPAL_PAYMENT_LOGGING_ENABLED=true
NEPAL_PAYMENT_IDEMPOTENCY_ENABLED=true
NEPAL_PAYMENT_IDEMPOTENCY_TTL=86400

NEPAL_PAYMENT_STRIPE_ENABLED=false
NEPAL_PAYMENT_STRIPE_SECRET=your_stripe_secret
```

Usage
-----
All gateways are accessed via the `GatewayManager` service.

eSewa (payment + verify)
```php
use Nutandc\NepalPaymentSuite\Services\GatewayManager;

$gateway = app(GatewayManager::class)->esewa();

$payment = $gateway->payment([
    'amount' => '1000',
    'tax_amount' => '0',
    'total_amount' => '1000',
    'transaction_uuid' => 'txn_123',
    'product_code' => 'YOUR_PRODUCT_CODE',
    'success_url' => route('esewa.success'),
    'failure_url' => route('esewa.failure'),
]);

// Auto-submit HTML form:
echo $payment->redirectForm();

$verify = $gateway->verify([
    'transaction_uuid' => 'txn_123',
    'total_amount' => '1000',
    'product_code' => 'YOUR_PRODUCT_CODE',
]);

if ($verify->isSuccess()) {
    // handle success
}
```

Khalti (payment + verify)
```php
use Nutandc\NepalPaymentSuite\Services\GatewayManager;

$gateway = app(GatewayManager::class)->khalti();

$payment = $gateway->payment([
    'return_url' => route('khalti.return'),
    'website_url' => config('app.url'),
    'amount' => 1000,
    'purchase_order_id' => 'order_123',
    'purchase_order_name' => 'Order #123',
]);

return redirect()->away($payment->redirectUrl());

$verify = $gateway->verify([
    'pidx' => 'pidx_123',
]);
```

ConnectIPS (payment + verify)
```php
use Nutandc\NepalPaymentSuite\Services\GatewayManager;

$gateway = app(GatewayManager::class)->connectIps();

$payment = $gateway->payment([
    'transaction_id' => 'txn_123',
    'transaction_amount' => 1000,
    'remarks' => 'Order payment',
    'particulars' => 'Order #123',
    'reference_id' => 'ref_123',
    'success_url' => route('connectips.success'),
    'failure_url' => route('connectips.failure'),
]);

echo $payment->redirectForm();

$verify = $gateway->verify([
    'reference_id' => 'ref_123',
    'transaction_amount' => 1000,
]);
```

Stripe (optional)
-----------------
Install the Stripe SDK and enable the gateway:
```bash
composer require stripe/stripe-php
```

```env
NEPAL_PAYMENT_STRIPE_ENABLED=true
NEPAL_PAYMENT_STRIPE_SECRET=sk_test_xxx
```

Usage (amount is in the smallest currency unit, e.g. cents):
```php
use Nutandc\NepalPaymentSuite\Services\GatewayManager;

$gateway = app(GatewayManager::class)->stripe();

$payment = $gateway->payment([
    'amount' => 1500,
    'currency' => 'usd',
    'product_name' => 'Subscription',
    'success_url' => route('stripe.success'),
    'cancel_url' => route('stripe.cancel'),
]);

return redirect()->away($payment->redirectUrl());
```

Idempotency
-----------
Pass a custom idempotency key for any payment call:
```php
$gateway->payment($payload, 'order-123');
```

Testing
-------
```bash
composer install
composer test
composer analyse
composer fix:dry
```

License
-------
MIT
