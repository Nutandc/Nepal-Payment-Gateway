<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Contracts;

interface GatewayInterface
{
    public function name(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function payment(array $payload, ?string $idempotencyKey = null): PaymentResponseInterface;

    /**
     * @param array<string, mixed> $payload
     */
    public function verify(array $payload): VerifyResponseInterface;
}
