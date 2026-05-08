<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

class BaseRules extends AbstractRuleSet
{
    public function getName(): string
    {
        return 'base';
    }

    public function check(string $filePath): array
    {
        // TODO: migrate rules from class-patterncompliancechecker.php
        return [];
    }
}
