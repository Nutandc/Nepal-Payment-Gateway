<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Exceptions\IdempotencyException;
use Tests\TestCase;

final class IdempotencyServiceTest extends TestCase
{
    public function testEnsureUniqueBlocksDuplicates(): void
    {
        $config = $this->makeConfig([
            ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_ENABLED => true,
        ]);

        $service = $this->makeIdempotency($config);
        $payload = ['amount' => 1000, 'reference' => 'order-1'];

        $key = $service->ensureUnique('khalti', $payload);

        $this->assertNotEmpty($key);
        $this->expectException(IdempotencyException::class);
        $service->ensureUnique('khalti', $payload);
    }
}
