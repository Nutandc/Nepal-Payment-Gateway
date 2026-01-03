<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Contracts;

interface IdempotencyStoreInterface
{
    public function add(string $key, mixed $value, int $ttlSeconds): bool;

    public function has(string $key): bool;

    public function forget(string $key): void;
}
