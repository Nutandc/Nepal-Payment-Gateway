<?php

declare(strict_types=1);

namespace Tests\Feature;

use Nutandc\NepalPaymentSuite\Gateways\KhaltiGateway;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class KhaltiGatewayTest extends TestCase
{
    public function testPaymentReturnsRedirectUrl(): void
    {
        $http = new FakeHttpClient();
        $http->when('POST', 'https://khalti.test/v2/epayment/initiate/', [
            'payment_url' => 'https://khalti.test/pay/redirect',
        ]);

        $config = [
            'base_url' => 'https://khalti.test/',
            'endpoints' => [
                'initiate' => 'v2/epayment/initiate/',
                'lookup' => 'v2/epayment/lookup/',
            ],
            'secret_key' => 'khalti-secret',
        ];

        $gateway = new KhaltiGateway(
            $config,
            $http,
            new EndpointResolver(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );

        $response = $gateway->payment([
            'return_url' => 'https://example.com/return',
            'website_url' => 'https://example.com',
            'amount' => 1000,
            'purchase_order_id' => 'order-1',
            'purchase_order_name' => 'Test Order',
        ]);

        $this->assertSame('https://khalti.test/pay/redirect', $response->redirectUrl());
    }

    public function testVerifyHandlesCompletedStatus(): void
    {
        $http = new FakeHttpClient();
        $http->when('POST', 'https://khalti.test/v2/epayment/lookup/', [
            'status' => 'COMPLETED',
        ]);

        $config = [
            'base_url' => 'https://khalti.test/',
            'endpoints' => [
                'initiate' => 'v2/epayment/initiate/',
                'lookup' => 'v2/epayment/lookup/',
            ],
            'secret_key' => 'khalti-secret',
        ];

        $gateway = new KhaltiGateway(
            $config,
            $http,
            new EndpointResolver(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );

        $response = $gateway->verify([
            'pidx' => 'pidx_123',
        ]);

        $this->assertTrue($response->isSuccess());
    }
}
