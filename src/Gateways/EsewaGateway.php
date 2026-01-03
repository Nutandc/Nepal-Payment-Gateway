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
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Nutandc\NepalPaymentSuite\Traits\BuildsFormRedirect;

final class EsewaGateway extends AbstractGateway
{
    use BuildsFormRedirect;

    private string $baseUrl;
    private string $productCode;
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
        private readonly SignatureService $signatureService,
        IdempotencyService $idempotencyService,
        LoggerService $logger,
    ) {
        parent::__construct($httpClient, $endpointResolver, $logger, $idempotencyService);

        if (! ($config['enabled'] ?? true)) {
            throw new InvalidConfigurationException('eSewa gateway is disabled.');
        }

        $this->baseUrl = (string) ($config['base_url'] ?? '');
        $this->productCode = (string) ($config['product_code'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->endpoints = (array) ($config['endpoints'] ?? []);

        if ($this->baseUrl === '' || $this->productCode === '') {
            throw new InvalidConfigurationException('eSewa configuration is incomplete.');
        }
    }

    public function name(): string
    {
        return GatewayNames::ESEWA;
    }

    public function payment(array $payload, ?string $idempotencyKey = null): PaymentResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['amount', 'success_url', 'failure_url']);

        $transactionId = ArrayHelper::string($payload, 'transaction_uuid', uniqid('esewa_', true));
        $amount = (float) ArrayHelper::string($payload, 'amount', '0');
        $taxAmount = (float) ArrayHelper::string($payload, 'tax_amount', '0');
        $totalAmount = (float) ArrayHelper::string($payload, 'total_amount', (string) ($amount + $taxAmount));

        $request = [
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionId,
            'product_code' => ArrayHelper::string($payload, 'product_code', $this->productCode),
            'success_url' => ArrayHelper::string($payload, 'success_url'),
            'failure_url' => ArrayHelper::string($payload, 'failure_url'),
        ];

        $this->enforceIdempotency($this->name(), $request, $idempotencyKey);

        if ($this->secretKey !== '') {
            $fields = ['total_amount', 'transaction_uuid', 'product_code'];
            $request['signature'] = $this->signatureService->signEsewa($request, $this->secretKey, $fields);
            $request['signed_field_names'] = implode(',', $fields);
        }

        $action = $this->buildUrl($this->baseUrl, $this->endpoint('initiate'));
        $form = $this->buildFormRedirect($action, $request);

        return new PaymentResponse($request, [], null, $form);
    }

    public function verify(array $payload): VerifyResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['transaction_uuid', 'total_amount']);

        $request = [
            'transaction_uuid' => ArrayHelper::string($payload, 'transaction_uuid'),
            'total_amount' => ArrayHelper::string($payload, 'total_amount'),
            'product_code' => ArrayHelper::string($payload, 'product_code', $this->productCode),
        ];

        $url = $this->buildUrl($this->baseUrl, $this->endpoint('verify'));
        $response = $this->postForm($url, $request);

        $status = strtoupper((string) ($response['status'] ?? ''));
        $success = in_array($status, ['COMPLETE', 'SUCCESS'], true);

        return new VerifyResponse($success, $response['status'] ?? null, $response);
    }

    private function endpoint(string $key): string
    {
        $endpoint = $this->endpoints[$key] ?? '';
        if ($endpoint === '') {
            throw new InvalidConfigurationException('eSewa endpoint missing: ' . $key);
        }

        return $endpoint;
    }
}
