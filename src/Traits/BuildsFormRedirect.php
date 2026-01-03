<?php

declare(strict_types=1);

namespace Nutandc\NepalPaymentSuite\Traits;

use Nutandc\NepalPaymentSuite\Helpers\ArrayHelper;

trait BuildsFormRedirect
{
    /**
     * @param array<string, mixed> $fields
     */
    protected function buildFormRedirect(string $action, array $fields, string $formId = 'nepal-payment-suite-form'): string
    {
        $safeFields = ArrayHelper::filterNull($fields);
        $inputs = '';

        foreach ($safeFields as $name => $value) {
            $inputs .= sprintf(
                "<input type=\"hidden\" name=\"%s\" value=\"%s\">\n",
                htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            );
        }

        return sprintf(
            "<form id=\"%s\" method=\"POST\" action=\"%s\">%s</form><script>document.getElementById('%s').submit();</script>",
            htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($action, ENT_QUOTES, 'UTF-8'),
            $inputs,
            htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'),
        );
    }
}
