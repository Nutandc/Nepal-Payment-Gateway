<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Traits;

use Nutandc\NepalPaymentSuite\Services\IdempotencyService;

trait UsesIdempotency
{
    protected IdempotencyService $idempotencyService;

    /**
     * @param array<string, mixed> $payload
     */
    protected function enforceIdempotency(string $gateway, array $payload, ?string $key): string
    {
        return $this->idempotencyService->ensureUnique($gateway, $payload, $key);
    }
}
