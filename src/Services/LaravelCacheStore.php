<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nutandc\NepalPaymentSuite\Contracts\IdempotencyStoreInterface;

final class LaravelCacheStore implements IdempotencyStoreInterface
{
    public function __construct(private readonly CacheRepository $cache)
    {
    }

    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        return $this->cache->add($key, $value, $ttlSeconds);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }
}
