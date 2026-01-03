<?php

declare(strict_types=1);

namespace Tests\Support;

use Nutandc\NepalPaymentSuite\Contracts\IdempotencyStoreInterface;

final class InMemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private array $items = [];

    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        $this->purgeExpired($key);

        if (array_key_exists($key, $this->items)) {
            return false;
        }

        $this->items[$key] = [
            'value' => $value,
            'expires' => time() + $ttlSeconds,
        ];

        return true;
    }

    public function has(string $key): bool
    {
        $this->purgeExpired($key);

        return array_key_exists($key, $this->items);
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }

    private function purgeExpired(string $key): void
    {
        $item = $this->items[$key] ?? null;
        if ($item === null) {
            return;
        }

        if ($item['expires'] <= time()) {
            unset($this->items[$key]);
        }
    }
}
