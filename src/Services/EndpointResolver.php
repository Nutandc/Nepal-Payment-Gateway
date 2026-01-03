<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Services;

use Nutandc\NepalPaymentSuite\Helpers\UrlHelper;

final class EndpointResolver
{
    /**
     * @param array<string, string|int> $tokens
     */
    public function resolve(string $baseUrl, string $endpoint, array $tokens = []): string
    {
        $path = UrlHelper::interpolate($endpoint, $tokens);

        return UrlHelper::join($baseUrl, $path);
    }
}
