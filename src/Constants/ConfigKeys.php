<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Constants;

final class ConfigKeys
{
    public const PACKAGE = 'nepal-payment-suite';
    public const DEFAULT_GATEWAY = 'default';
    public const HTTP_TIMEOUT = 'http.timeout';
    public const LOGGING_ENABLED = 'logging.enabled';
    public const IDEMPOTENCY_ENABLED = 'idempotency.enabled';
    public const IDEMPOTENCY_TTL = 'idempotency.ttl';
    public const IDEMPOTENCY_PREFIX = 'idempotency.prefix';
}
