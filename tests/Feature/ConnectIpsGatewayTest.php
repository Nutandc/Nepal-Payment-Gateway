<?php

declare(strict_types=1);

namespace Tests\Feature;

use Nutandc\NepalPaymentSuite\Gateways\ConnectIpsGateway;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class ConnectIpsGatewayTest extends TestCase
{
    public function testPaymentBuildsRedirectForm(): void
    {
        $privateKeyPath = $this->createPrivateKeyFile();

        $config = [
            'base_url' => 'https://connectips.test',
            'endpoints' => [
                'initiate' => 'connectips/login',
                'verify' => 'connectips/verify',
            ],
            'merchant_id' => 'merchant-1',
            'app_id' => 'app-1',
            'app_name' => 'TestApp',
            'private_key_path' => $privateKeyPath,
            'password' => 'password',
        ];

        try {
            $gateway = new ConnectIpsGateway(
                $config,
                new FakeHttpClient(),
                new EndpointResolver(),
                new SignatureService(),
                $this->makeIdempotency($this->makeConfig()),
                $this->makeLogger($this->makeConfig()),
            );

            $response = $gateway->payment([
                'transaction_id' => 'txn-1',
                'transaction_amount' => 1000,
                'remarks' => 'Test',
                'particulars' => 'Test',
                'reference_id' => 'ref-1',
            ]);

            $this->assertNotNull($response->redirectForm());
            $this->assertStringContainsString('https://connectips.test/connectips/login', (string) $response->redirectForm());
        } finally {
            @unlink($privateKeyPath);
        }
    }

    public function testVerifyHandlesSuccessStatus(): void
    {
        $privateKeyPath = $this->createPrivateKeyFile();
        $http = new FakeHttpClient();
        $http->when('POST_FORM', 'https://connectips.test/connectips/verify', [
            'status' => 'SUCCESS',
        ]);

        $config = [
            'base_url' => 'https://connectips.test',
            'endpoints' => [
                'initiate' => 'connectips/login',
                'verify' => 'connectips/verify',
            ],
            'merchant_id' => 'merchant-1',
            'app_id' => 'app-1',
            'app_name' => 'TestApp',
            'private_key_path' => $privateKeyPath,
            'password' => 'password',
        ];

        try {
            $gateway = new ConnectIpsGateway(
                $config,
                $http,
                new EndpointResolver(),
                new SignatureService(),
                $this->makeIdempotency($this->makeConfig()),
                $this->makeLogger($this->makeConfig()),
            );

            $response = $gateway->verify([
                'reference_id' => 'ref-1',
                'transaction_amount' => 1000,
            ]);

            $this->assertTrue($response->isSuccess());
        } finally {
            @unlink($privateKeyPath);
        }
    }

    private function createPrivateKeyFile(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            $this->fail('Unable to generate test private key.');
        }

        $privateKey = '';
        $exported = openssl_pkey_export($key, $privateKey);

        if (! $exported || $privateKey === '') {
            $this->fail('Unable to export test private key.');
        }

        $path = tempnam(sys_get_temp_dir(), 'connectips_');
        if ($path === false) {
            $this->fail('Unable to create temp key file.');
        }

        file_put_contents($path, $privateKey);

        return $path;
    }
}
