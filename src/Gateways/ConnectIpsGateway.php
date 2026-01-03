<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Gateways;

use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Contracts\HttpClientInterface;
use Nutandc\NepalPaymentSuite\Contracts\PaymentResponseInterface;
use Nutandc\NepalPaymentSuite\Contracts\VerifyResponseInterface;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;
use Nutandc\NepalPaymentSuite\Helpers\ArrayHelper;
use Nutandc\NepalPaymentSuite\Helpers\UrlHelper;
use Nutandc\NepalPaymentSuite\Responses\PaymentResponse;
use Nutandc\NepalPaymentSuite\Responses\VerifyResponse;
use Nutandc\NepalPaymentSuite\Services\EndpointResolver;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LoggerService;
use Nutandc\NepalPaymentSuite\Services\SignatureService;
use Nutandc\NepalPaymentSuite\Traits\BuildsFormRedirect;

final class ConnectIpsGateway extends AbstractGateway
{
    use BuildsFormRedirect;

    private string $baseUrl;
    private string $merchantId;
    private string $appId;
    private string $appName;
    private string $privateKeyPath;
    private ?string $privateKeyPassphrase;
    private string $password;
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
            throw new InvalidConfigurationException('ConnectIPS gateway is disabled.');
        }

        $this->baseUrl = (string) ($config['base_url'] ?? '');
        $this->merchantId = (string) ($config['merchant_id'] ?? '');
        $this->appId = (string) ($config['app_id'] ?? '');
        $this->appName = (string) ($config['app_name'] ?? '');
        $this->privateKeyPath = (string) ($config['private_key_path'] ?? '');
        $this->privateKeyPassphrase = $config['private_key_passphrase'] ?? null;
        $this->password = (string) ($config['password'] ?? '');
        $this->endpoints = (array) ($config['endpoints'] ?? []);

        if ($this->baseUrl === '' || $this->merchantId === '' || $this->appId === '' || $this->appName === '' || $this->privateKeyPath === '') {
            throw new InvalidConfigurationException('ConnectIPS configuration is incomplete.');
        }
    }

    public function name(): string
    {
        return GatewayNames::CONNECTIPS;
    }

    public function payment(array $payload, ?string $idempotencyKey = null): PaymentResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['transaction_id', 'transaction_amount', 'remarks', 'particulars', 'reference_id']);

        $transactionId = ArrayHelper::string($payload, 'transaction_id');
        $transactionDate = ArrayHelper::string($payload, 'transaction_date', date('d-m-Y'));
        $transactionAmount = ArrayHelper::int($payload, 'transaction_amount');
        $referenceId = ArrayHelper::string($payload, 'reference_id');

        $signaturePayload = sprintf(
            'merchantId=%s,appId=%s,txnId=%s,txnAmt=%s,txnDate=%s,refId=%s',
            $this->merchantId,
            $this->appId,
            $transactionId,
            $transactionAmount,
            $transactionDate,
            $referenceId,
        );

        $request = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'appName' => $this->appName,
            'txnId' => $transactionId,
            'txnAmount' => $transactionAmount,
            'txnDate' => $transactionDate,
            'remarks' => ArrayHelper::string($payload, 'remarks'),
            'particulars' => ArrayHelper::string($payload, 'particulars'),
            'referenceId' => $referenceId,
            'merchantPassword' => $this->password,
            'signature' => $this->signatureService->signConnectIps($signaturePayload, $this->privateKeyPath, $this->privateKeyPassphrase),
        ];

        $request = array_merge($request, ArrayHelper::filterNull([
            'successUrl' => $payload['success_url'] ?? null,
            'failureUrl' => $payload['failure_url'] ?? null,
        ]));

        $this->enforceIdempotency($this->name(), $request, $idempotencyKey);

        $action = $this->buildUrl($this->baseUrl, $this->endpoint('initiate'));
        $form = $this->buildFormRedirect($action, $request);

        return new PaymentResponse($request, [], null, $form);
    }

    public function verify(array $payload): VerifyResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['reference_id', 'transaction_amount']);

        $transactionAmount = ArrayHelper::int($payload, 'transaction_amount');
        $referenceId = ArrayHelper::string($payload, 'reference_id');

        $signaturePayload = sprintf(
            'merchantId=%s,appId=%s,refId=%s,txnAmt=%s',
            $this->merchantId,
            $this->appId,
            $referenceId,
            $transactionAmount,
        );

        $request = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'referenceId' => $referenceId,
            'transactionAmount' => $transactionAmount,
            'signature' => $this->signatureService->signConnectIps($signaturePayload, $this->privateKeyPath, $this->privateKeyPassphrase),
        ];

        $url = $this->buildUrl($this->baseUrl, $this->endpoint('verify'));
        $response = $this->postForm($url, $request);

        $status = strtoupper((string) ($response['status'] ?? $response['response_code'] ?? ''));
        $success = in_array($status, ['SUCCESS', 'COMPLETE', '00'], true);

        return new VerifyResponse($success, $response['status'] ?? null, $response);
    }

    private function endpoint(string $key): string
    {
        $endpoint = $this->endpoints[$key] ?? '';
        if ($endpoint === '') {
            throw new InvalidConfigurationException('ConnectIPS endpoint missing: ' . $key);
        }

        return UrlHelper::interpolate($endpoint, []);
    }
}
