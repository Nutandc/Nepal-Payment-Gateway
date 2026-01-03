<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Contracts;

interface VerifyResponseInterface
{
    public function isSuccess(): bool;

    public function message(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function raw(): array;
}
