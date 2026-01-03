<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Contracts;

interface PaymentResponseInterface
{
    public function redirectUrl(): ?string;

    public function redirectForm(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * @return array<string, mixed>
     */
    public function raw(): array;
}
