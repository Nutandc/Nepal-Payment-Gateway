<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nutandc\NepalPaymentSuite\Constants\ConfigKeys;
use Psr\Log\LoggerInterface;

final class LoggerService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        if ($this->enabled()) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        if ($this->enabled()) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        if ($this->enabled()) {
            $this->logger->error($message, $context);
        }
    }

    private function enabled(): bool
    {
        return (bool) $this->config->get(ConfigKeys::PACKAGE . '.' . ConfigKeys::LOGGING_ENABLED, true);
    }
}
