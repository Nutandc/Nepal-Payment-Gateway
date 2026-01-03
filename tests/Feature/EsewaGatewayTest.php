<?php

declare(strict_types=1);

namespace Tests\Feature;

use Nutandc\NepalPaymentSuite\Gateways\EsewaGateway;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class EsewaGatewayTest extends TestCase
{
    public function testPaymentBuildsRedirectForm(): void
    {
        $config = [
            'base_url' => 'https://example.com/',
            'endpoints' => [
                'initiate' => 'pay',
                'verify' => 'verify',
            ],
            'product_code' => 'PROD001',
            'secret_key' => '',
        ];

        $gateway = new EsewaGateway(
            $config,
            new FakeHttpClient(),
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );

        $response = $gateway->payment([
            'amount' => '1000',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertNotNull($response->redirectForm());
        $this->assertStringContainsString('https://example.com/pay', (string) $response->redirectForm());
    }

    public function testVerifyMarksCompletePayment(): void
    {
        $http = new FakeHttpClient();
        $http->when('POST_FORM', 'https://example.com/verify', [
            'status' => 'COMPLETE',
        ]);

        $config = [
            'base_url' => 'https://example.com/',
            'endpoints' => [
                'initiate' => 'pay',
                'verify' => 'verify',
            ],
            'product_code' => 'PROD001',
            'secret_key' => '',
        ];

        $gateway = new EsewaGateway(
            $config,
            $http,
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );

        $response = $gateway->verify([
            'transaction_uuid' => 'txn-1',
            'total_amount' => '1000',
        ]);

        $this->assertTrue($response->isSuccess());
    }
}
