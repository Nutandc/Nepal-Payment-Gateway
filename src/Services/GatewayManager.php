<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Nutandc\NepalPaymentSuite\Constants\GatewayNames;
use Nutandc\NepalPaymentSuite\Contracts\GatewayInterface;
use Nutandc\NepalPaymentSuite\Exceptions\InvalidConfigurationException;

final class GatewayManager
{
    /** @var array<string, GatewayInterface> */
    private array $instances = [];

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly GatewayFactory $factory,
    ) {
    }

    public function gateway(?string $name = null): GatewayInterface
    {
        $default = (string) $this->config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::DEFAULT_GATEWAY, GatewayNames::ESEWA);
        $gateway = strtolower($name ?? $default);

        if (! in_array($gateway, [GatewayNames::ESEWA, GatewayNames::KHALTI, GatewayNames::CONNECTIPS, GatewayNames::STRIPE], true)) {
            throw new InvalidConfigurationException('Unknown gateway: ' . $gateway);
        }

        return $this->instances[$gateway] ??= $this->factory->make(
            $gateway,
            (array) $this->config->get(ConfigKeys::PACKAGE . '.' . $gateway, [])
        );
    }

    public function esewa(): GatewayInterface
    {
        return $this->gateway(GatewayNames::ESEWA);
    }

    public function khalti(): GatewayInterface
    {
        return $this->gateway(GatewayNames::KHALTI);
    }

    public function connectIps(): GatewayInterface
    {
        return $this->gateway(GatewayNames::CONNECTIPS);
    }

    public function stripe(): GatewayInterface
    {
        return $this->gateway(GatewayNames::STRIPE);
    }
}
