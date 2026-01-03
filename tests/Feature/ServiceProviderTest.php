<?php

declare(strict_types=1);

namespace Tests\Feature;

use Nutandc\NepalPaymentSuite\Providers\NepalPaymentSuiteServiceProvider;
use Nutandc\NepalPaymentSuite\Services\GatewayManager;
use Orchestra\Testbench\TestCase;

final class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [NepalPaymentSuiteServiceProvider::class];
    }

    public function testConfigIsMerged(): void
    {
        $this->assertSame('esewa', config('nepal-payment-suite.default'));
    }

    public function testGatewayManagerResolves(): void
    {
        $manager = $this->app->make(GatewayManager::class);

        $this->assertInstanceOf(GatewayManager::class, $manager);
    }
}
