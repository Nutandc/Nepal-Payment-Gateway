<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Contracts\GatewayInterface;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;
use Nutandc\NepalPaymentSuite\Gateways\ConnectIpsGateway;
use Nutandc\NepalPaymentSuite\Gateways\EsewaGateway;
use Nutandc\NepalPaymentSuite\Gateways\KhaltiGateway;
use Nutandc\NepalPaymentSuite\Gateways\StripeGateway;
use Stripe\StripeClient;

final class GatewayFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EndpointResolver $endpointResolver,
        private readonly SignatureService $signatureService,
        private readonly IdempotencyService $idempotencyService,
        private readonly LoggerService $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function make(string $gateway, array $config, ?HttpClientInterface $client = null): GatewayInterface
    {
        $name = strtolower($gateway);
        $httpClient = $client ?? $this->httpClient;

        return match ($name) {
            GatewayNames::ESEWA => new EsewaGateway($config, $httpClient, $this->endpointResolver, $this->signatureService, $this->idempotencyService, $this->logger),
            GatewayNames::KHALTI => new KhaltiGateway($config, $httpClient, $this->endpointResolver, $this->idempotencyService, $this->logger),
            GatewayNames::CONNECTIPS => new ConnectIpsGateway($config, $httpClient, $this->endpointResolver, $this->signatureService, $this->idempotencyService, $this->logger),
            GatewayNames::STRIPE => $this->makeStripe($config),
            default => throw new InvalidConfigurationException('Unsupported gateway: ' . $gateway),
        };
    }

    /**
     * @param array<string, mixed> $config
     */
    private function makeStripe(array $config): StripeGateway
    {
        if (! ($config['enabled'] ?? false)) {
            throw new InvalidConfigurationException('Stripe gateway is disabled.');
        }

        if (! class_exists(StripeClient::class)) {
            throw new InvalidConfigurationException('stripe/stripe-php is not installed.');
        }

        $secretKey = (string) ($config['secret_key'] ?? '');
        if ($secretKey === '') {
            throw new InvalidConfigurationException('Stripe secret key is required.');
        }

        return new StripeGateway(new StripeClient($secretKey), $this->idempotencyService, $this->logger);
    }
}
