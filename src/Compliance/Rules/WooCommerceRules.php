<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

class WooCommerceRules extends AbstractRuleSet
{
    public function getName(): string
    {
        return 'woocommerce';
    }

    public function check(string $filePath): array
    {
        // TODO: migrate WooCommerce rules from class-patterncompliancechecker.php
        return [];
    }
}
