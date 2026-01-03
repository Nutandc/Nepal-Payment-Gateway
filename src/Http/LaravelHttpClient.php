<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Http;

use Illuminate\Http\Client\Factory;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Exceptions\HttpClientException;

final class LaravelHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly Factory $factory,
        private readonly int $timeout,
    ) {
    }

    public function get(string $url, array $headers = []): array
    {
        $response = $this->factory
            ->timeout($this->timeout)
            ->withHeaders($headers)
            ->get($url);

        if (! $response->successful()) {
            throw new HttpClientException(sprintf('GET request failed: %s', $url));
        }

        return $response->json() ?? ['raw' => $response->body()];
    }

    public function post(string $url, array $payload = [], array $headers = []): array
    {
        $response = $this->factory
            ->timeout($this->timeout)
            ->withHeaders($headers)
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new HttpClientException(sprintf('POST request failed: %s', $url));
        }

        return $response->json() ?? ['raw' => $response->body()];
    }

    public function postForm(string $url, array $payload = [], array $headers = []): array
    {
        $response = $this->factory
            ->timeout($this->timeout)
            ->withHeaders($headers)
            ->asForm()
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new HttpClientException(sprintf('POST form request failed: %s', $url));
        }

        return $response->json() ?? ['raw' => $response->body()];
    }
}
