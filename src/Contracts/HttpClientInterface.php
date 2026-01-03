<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Contracts;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function get(string $url, array $headers = []): array;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function post(string $url, array $payload = [], array $headers = []): array;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function postForm(string $url, array $payload = [], array $headers = []): array;
}
