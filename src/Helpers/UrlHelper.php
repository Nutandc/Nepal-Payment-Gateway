<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Helpers;

final class UrlHelper
{
    public static function join(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string, string|int> $tokens
     */
    public static function interpolate(string $template, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return $template;
    }
}
