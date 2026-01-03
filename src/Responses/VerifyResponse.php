<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Responses;

use Nutandc\NepalPaymentSuite\Contracts\VerifyResponseInterface;

final class VerifyResponse implements VerifyResponseInterface
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        private readonly bool $success,
        private readonly ?string $message,
        private readonly array $raw,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function raw(): array
    {
        return $this->raw;
    }
}
