<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;
use Nutandc\NepalPaymentSuite\Gateways\ConnectIpsGateway;
use Nutandc\NepalPaymentSuite\Gateways\EsewaGateway;
use Nutandc\NepalPaymentSuite\Gateways\KhaltiGateway;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Tests\Support\FakeHttpClient;
use Tests\TestCase;

final class GatewayDisabledTest extends TestCase
{
    public function testEsewaCannotBeConstructedWhenDisabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new EsewaGateway(
            ['enabled' => false],
            new FakeHttpClient(),
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );
    }

    public function testKhaltiCannotBeConstructedWhenDisabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new KhaltiGateway(
            ['enabled' => false],
            new FakeHttpClient(),
            new EndpointResolver(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );
    }

    public function testConnectIpsCannotBeConstructedWhenDisabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ConnectIpsGateway(
            ['enabled' => false],
            new FakeHttpClient(),
            new EndpointResolver(),
            new SignatureService(),
            $this->makeIdempotency($this->makeConfig()),
            $this->makeLogger($this->makeConfig()),
        );
    }
}
