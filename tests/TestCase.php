<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LoggerService;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\NullLogger;
use Tests\Support\InMemoryIdempotencyStore;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param array<string, mixed> $values
     */
    protected function makeConfig(array $values = []): ConfigRepository
    {
        $defaults = [
            ConfigKeys::PACKAGE . '.' . ConfigKeys::LOGGING_ENABLED => false,
            ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_ENABLED => false,
            ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_TTL => 86400,
            ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_PREFIX => 'nepal-payment-suite:idempotency:',
            ConfigKeys::PACKAGE . '.' . ConfigKeys::DEFAULT_GATEWAY => GatewayNames::ESEWA,
        ];

        $configValues = array_merge($defaults, $values);

        $config = $this->createMock(ConfigRepository::class);
        $config->method('get')->willReturnCallback(static function (string $key, mixed $default = null) use ($configValues): mixed {
            return $configValues[$key] ?? $default;
        });

        return $config;
    }

    protected function makeLogger(ConfigRepository $config): LoggerService
    {
        return new LoggerService(new NullLogger(), $config);
    }

    protected function makeIdempotency(ConfigRepository $config): IdempotencyService
    {
        return new IdempotencyService(new InMemoryIdempotencyStore(), $config, $this->makeLogger($config));
    }
}
