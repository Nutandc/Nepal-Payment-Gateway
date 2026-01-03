<?php

declare(strict_types=1);

namespace Tests\Support;

use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use RuntimeException;

final class FakeHttpClient implements HttpClientInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $responses = [];

    /** @var array<int, array<string, mixed>> */
    private array $requests = [];

    /**
     * @param array<string, mixed> $response
     */
    public function when(string $method, string $url, array $response): void
    {
        $key = strtoupper($method) . ':' . $url;
        $this->responses[$key] = $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * @return array<string, mixed>
     */
    public function lastRequest(): array
    {
        return $this->requests[array_key_last($this->requests)] ?? [];
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->respond('GET', $url, [], $headers);
    }

    public function post(string $url, array $payload = [], array $headers = []): array
    {
        return $this->respond('POST', $url, $payload, $headers);
    }

    public function postForm(string $url, array $payload = [], array $headers = []): array
    {
        return $this->respond('POST_FORM', $url, $payload, $headers);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function respond(string $method, string $url, array $payload, array $headers): array
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'payload' => $payload,
            'headers' => $headers,
        ];

        $key = strtoupper($method) . ':' . $url;
        if (! array_key_exists($key, $this->responses)) {
            throw new RuntimeException('No fake response registered for ' . $method . ' ' . $url);
        }

        return $this->responses[$key];
    }
}
