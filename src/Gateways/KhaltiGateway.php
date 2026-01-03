<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Gateways;

use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Contracts\PaymentResponseInterface;
use Nutandc\NepalPaymentSuite\Contracts\VerifyResponseInterface;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;
use Nutandc\NepalPaymentSuite\Helpers\ArrayHelper;
use Nutandc\NepalPaymentSuite\Responses\PaymentResponse;
use Nutandc\NepalPaymentSuite\Responses\VerifyResponse;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LoggerService;

final class KhaltiGateway extends AbstractGateway
{
    private string $baseUrl;
    private string $secretKey;
    /** @var array<string, string> */
    private array $endpoints;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        array $config,
        HttpClientInterface $httpClient,
        EndpointResolver $endpointResolver,
        IdempotencyService $idempotencyService,
        LoggerService $logger,
    ) {
        parent::__construct($httpClient, $endpointResolver, $logger, $idempotencyService);

        if (! ($config['enabled'] ?? true)) {
            throw new InvalidConfigurationException('Khalti gateway is disabled.');
        }

        $this->baseUrl = (string) ($config['base_url'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->endpoints = (array) ($config['endpoints'] ?? []);

        if ($this->baseUrl === '' || $this->secretKey === '') {
            throw new InvalidConfigurationException('Khalti configuration is incomplete.');
        }
    }

    public function name(): string
    {
        return GatewayNames::KHALTI;
    }

    public function payment(array $payload, ?string $idempotencyKey = null): PaymentResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['return_url', 'website_url', 'amount', 'purchase_order_id', 'purchase_order_name']);

        $request = [
            'return_url' => ArrayHelper::string($payload, 'return_url'),
            'website_url' => ArrayHelper::string($payload, 'website_url'),
            'amount' => ArrayHelper::int($payload, 'amount'),
            'purchase_order_id' => ArrayHelper::string($payload, 'purchase_order_id'),
            'purchase_order_name' => ArrayHelper::string($payload, 'purchase_order_name'),
        ];

        $this->enforceIdempotency($this->name(), $request, $idempotencyKey);

        $url = $this->buildUrl($this->baseUrl, $this->endpoint('initiate'));
        $response = $this->postJson($url, $request, $this->headers());

        return new PaymentResponse($request, $response, $response['payment_url'] ?? null, null);
    }

    public function verify(array $payload): VerifyResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['pidx']);

        $request = [
            'pidx' => ArrayHelper::string($payload, 'pidx'),
        ];

        $url = $this->buildUrl($this->baseUrl, $this->endpoint('lookup'));
        $response = $this->postJson($url, $request, $this->headers());

        $status = strtoupper((string) ($response['status'] ?? ''));
        $success = in_array($status, ['COMPLETED', 'PAID', 'SUCCESS'], true);

        return new VerifyResponse($success, $response['status'] ?? null, $response);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'key ' . $this->secretKey,
        ];
    }

    private function endpoint(string $key): string
    {
        $endpoint = $this->endpoints[$key] ?? '';
        if ($endpoint === '') {
            throw new InvalidConfigurationException('Khalti endpoint missing: ' . $key);
        }

        return $endpoint;
    }
}
