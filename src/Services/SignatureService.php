<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;

final class SignatureService
{
    /**
     * @param array<string, mixed> $payload
     * @param string[] $fields
     */
    public function signEsewa(array $payload, string $secretKey, array $fields): string
    {
        $pairs = [];

        foreach ($fields as $field) {
            $pairs[] = $field . '=' . ($payload[$field] ?? '');
        }

        $message = implode(',', $pairs);

        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

    public function signConnectIps(string $payload, string $privateKeyPath, ?string $passphrase = null): string
    {
        if (! is_file($privateKeyPath) || ! is_readable($privateKeyPath)) {
            throw new InvalidConfigurationException('ConnectIPS private key file is missing or unreadable.');
        }

        $key = openssl_pkey_get_private(file_get_contents($privateKeyPath) ?: '', $passphrase ?? '');
        if ($key === false) {
            throw new InvalidConfigurationException('Unable to load ConnectIPS private key.');
        }

        $signature = '';
        $result = openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256);

        if (! $result) {
            throw new InvalidConfigurationException('ConnectIPS signature generation failed.');
        }

        return base64_encode($signature);
    }
}
