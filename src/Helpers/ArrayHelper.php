<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Helpers;

use Nutandc\NepalPaymentSuite\Exceptions\InvalidPayloadException;

final class ArrayHelper
{
    /**
     * @param array<string, mixed> $data
     * @param string[] $required
     */
    public static function requireKeys(array $data, array $required): void
    {
        foreach ($required as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidPayloadException(sprintf('Missing required field: %s', $key));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function filterNull(array $data): array
    {
        return array_filter($data, static fn ($value) => $value !== null);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? $default;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
