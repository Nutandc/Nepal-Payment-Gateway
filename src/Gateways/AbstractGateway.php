<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Gateways;

use Nutandc\NepalPaymentSuite\Contracts\GatewayInterface;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LoggerService;
use Nutandc\NepalPaymentSuite\Traits\UsesIdempotency;

abstract class AbstractGateway implements GatewayInterface
{
    use UsesIdempotency;

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly EndpointResolver $endpointResolver,
        protected readonly LoggerService $logger,
        IdempotencyService $idempotencyService,
    ) {
        $this->idempotencyService = $idempotencyService;
    }

    /**
     * @param array<string, string|int> $tokens
     */
    protected function buildUrl(string $baseUrl, string $endpoint, array $tokens = []): string
    {
        return $this->endpointResolver->resolve($baseUrl, $endpoint, $tokens);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    protected function postJson(string $url, array $payload, array $headers = []): array
    {
        $this->logger->info('Gateway request', ['url' => $url]);

        return $this->httpClient->post($url, $payload, $headers);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    protected function postForm(string $url, array $payload, array $headers = []): array
    {
        $this->logger->info('Gateway form request', ['url' => $url]);

        return $this->httpClient->postForm($url, $payload, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    protected function get(string $url, array $headers = []): array
    {
        $this->logger->info('Gateway request', ['url' => $url]);

        return $this->httpClient->get($url, $headers);
    }
}
