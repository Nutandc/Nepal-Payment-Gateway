<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Gateways;

use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Contracts\GatewayInterface;
use Nutandc\NepalPaymentSuite\Contracts\PaymentResponseInterface;
use Nutandc\NepalPaymentSuite\Contracts\VerifyResponseInterface;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidPayloadException;
use Nutandc\NepalPaymentSuite\Exceptions\PaymentGatewayException;
use Nutandc\NepalPaymentSuite\Helpers\ArrayHelper;
use Nutandc\NepalPaymentSuite\Responses\PaymentResponse;
use Nutandc\NepalPaymentSuite\Responses\VerifyResponse;
use Nutandc\NepalPaymentSuite\Services\IdempotencyService;
use Nutandc\NepalPaymentSuite\Services\LoggerService;
use Nutandc\NepalPaymentSuite\Traits\UsesIdempotency;
use Stripe\StripeClient;
use Throwable;

final class StripeGateway implements GatewayInterface
{
    use UsesIdempotency;

    public function __construct(
        private readonly StripeClient $stripe,
        IdempotencyService $idempotencyService,
        private readonly LoggerService $logger,
    ) {
        $this->idempotencyService = $idempotencyService;
    }

    public function name(): string
    {
        return GatewayNames::STRIPE;
    }

    public function payment(array $payload, ?string $idempotencyKey = null): PaymentResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['success_url', 'cancel_url']);

        $request = [
            'mode' => ArrayHelper::string($payload, 'mode', 'payment'),
            'success_url' => ArrayHelper::string($payload, 'success_url'),
            'cancel_url' => ArrayHelper::string($payload, 'cancel_url'),
        ];

        $request['line_items'] = $this->resolveLineItems($payload);

        $request = array_merge($request, ArrayHelper::filterNull([
            'customer_email' => $payload['customer_email'] ?? null,
            'client_reference_id' => $payload['client_reference_id'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'payment_intent_data' => $payload['payment_intent_data'] ?? null,
        ]));

        $idempotency = $this->enforceIdempotency($this->name(), $request, $idempotencyKey);

        try {
            $session = $this->stripe->checkout->sessions->create($request, [
                'idempotency_key' => $idempotency,
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Stripe payment failed', ['error' => $exception->getMessage()]);

            throw new PaymentGatewayException('Stripe payment failed.', 0, $exception);
        }

        $sessionData = $this->normalizeStripeObject($session);

        return new PaymentResponse($request, $sessionData, $sessionData['url'] ?? null, null);
    }

    public function verify(array $payload): VerifyResponseInterface
    {
        ArrayHelper::requireKeys($payload, ['session_id']);

        $sessionId = ArrayHelper::string($payload, 'session_id');

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
        } catch (Throwable $exception) {
            $this->logger->error('Stripe verification failed', ['error' => $exception->getMessage()]);

            throw new PaymentGatewayException('Stripe verification failed.', 0, $exception);
        }

        $sessionData = $this->normalizeStripeObject($session);
        $paymentStatus = strtolower((string) ($sessionData['payment_status'] ?? ''));
        $status = strtolower((string) ($sessionData['status'] ?? ''));
        $success = in_array($paymentStatus, ['paid'], true) || in_array($status, ['complete', 'completed'], true);

        return new VerifyResponse($success, $sessionData['payment_status'] ?? null, $sessionData);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function resolveLineItems(array $payload): array
    {
        $lineItems = $payload['line_items'] ?? null;

        if ($lineItems !== null) {
            if (! is_array($lineItems)) {
                throw new InvalidPayloadException('Stripe line_items must be an array.');
            }

            return $lineItems;
        }

        ArrayHelper::requireKeys($payload, ['amount', 'currency', 'product_name']);

        $amount = ArrayHelper::int($payload, 'amount');
        if ($amount <= 0) {
            throw new InvalidPayloadException('Stripe amount must be a positive integer.');
        }

        $currency = strtolower(ArrayHelper::string($payload, 'currency'));
        $productName = ArrayHelper::string($payload, 'product_name');
        $quantity = max(1, ArrayHelper::int($payload, 'quantity', 1));

        $productData = ArrayHelper::filterNull([
            'name' => $productName,
            'description' => $payload['description'] ?? null,
        ]);

        return [[
            'price_data' => [
                'currency' => $currency,
                'product_data' => $productData,
                'unit_amount' => $amount,
            ],
            'quantity' => $quantity,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeStripeObject(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return $data->toArray();
            }

            if (method_exists($data, 'toJSON')) {
                $decoded = json_decode($data->toJSON(), true);

                return is_array($decoded) ? $decoded : [];
            }

            return (array) $data;
        }

        return [];
    }
}
