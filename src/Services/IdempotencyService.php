<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use JsonException;
use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Contracts\IdempotencyStoreInterface;
use Nutandc\NepalPaymentSuite\Exceptions\IdempotencyException;

final class IdempotencyService
{
    public function __construct(
        private readonly IdempotencyStoreInterface $store,
        private readonly ConfigRepository $config,
        private readonly LoggerService $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @throws JsonException
     */
    public function ensureUnique(string $gateway, array $payload, ?string $key = null): string
    {
        if (! $this->enabled()) {
            return $this->buildKey($gateway, $payload, $key);
        }

        $finalKey = $this->buildKey($gateway, $payload, $key);
        $ttl = $this->ttl();

        $added = $this->store->add($finalKey, time(), $ttl);
        if (! $added) {
            $this->logger->warning('Idempotency key reuse blocked', [
                'gateway' => $gateway,
                'key' => $finalKey,
            ]);

            throw new IdempotencyException('Duplicate transaction detected.');
        }

        return $finalKey;
    }

    /**
     * @param array<string, mixed> $payload
     * @throws JsonException
     */
    private function buildKey(string $gateway, array $payload, ?string $key): string
    {
        $prefix = $this->prefix();

        if ($key !== null && $key !== '') {
            return $prefix . $gateway . ':' . $key;
        }

        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return $prefix . $gateway . ':' . $payloadHash;
    }

    private function enabled(): bool
    {
        return (bool) $this->config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_ENABLED, true);
    }

    private function ttl(): int
    {
        return (int) $this->config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_TTL, 86400);
    }

    private function prefix(): string
    {
        return (string) $this->config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::IDEMPOTENCY_PREFIX, 'nepal-payment-suite:idempotency:');
    }
}
