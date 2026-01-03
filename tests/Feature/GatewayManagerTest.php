<?php

declare(strict_types=1);

namespace Tests\Feature;

use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Gateways\KhaltiGateway;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\GatewayFactory;
use Nutandc\NepalPaymentSuite\Services\GatewayManager;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class GatewayManagerTest extends TestCase
{
    public function testDefaultGatewayResolvesFromConfig(): void
    {
        $config = $this->makeConfig([
            ConfigKeys::PACKAGE . '.' . ConfigKeys::DEFAULT_GATEWAY => GatewayNames::KHALTI,
            ConfigKeys::PACKAGE . '.' . GatewayNames::KHALTI => [
                'enabled' => true,
                'base_url' => 'https://khalti.test/',
                'endpoints' => [
                    'initiate' => 'v2/epayment/initiate/',
                    'lookup' => 'v2/epayment/lookup/',
                ],
                'secret_key' => 'khalti-secret',
            ],
        ]);

        $factory = new GatewayFactory(
            new FakeHttpClient(),
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($config),
            $this->makeLogger($config),
        );

        $manager = new GatewayManager($config, $factory);

        $gateway = $manager->gateway();

        $this->assertInstanceOf(KhaltiGateway::class, $gateway);
    }
}
