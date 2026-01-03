<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Responses;

use Nutandc\NepalPaymentSuite\Contracts\PaymentResponseInterface;

final class PaymentResponse implements PaymentResponseInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $raw
     */
    public function __construct(
        private readonly array $payload,
        private readonly array $raw,
        private readonly ?string $redirectUrl = null,
        private readonly ?string $redirectForm = null,
    ) {
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function redirectForm(): ?string
    {
        return $this->redirectForm;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function raw(): array
    {
        return $this->raw;
    }
}
