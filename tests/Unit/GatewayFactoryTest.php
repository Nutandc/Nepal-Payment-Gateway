<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\GatewayFactory;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class GatewayFactoryTest extends TestCase
{
    public function testStripeThrowsWhenDisabled(): void
    {
        $config = $this->makeConfig();
        $factory = new GatewayFactory(
            new FakeHttpClient(),
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($config),
            $this->makeLogger($config),
        );

        $this->expectException(InvalidConfigurationException::class);
        $factory->make(GatewayNames::STRIPE, ['enabled' => false]);
    }
}
