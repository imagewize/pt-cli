<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;

abstract class AbstractRuleSet implements RuleSetInterface
{
    public function __construct(protected readonly ThemeConfig $config) {}

    public function isAutofixable(): bool
    {
        return false;
    }

    public function autofix(string $content, array &$applied = []): string
    {
        return $content;
    }

    protected function violation(string $rule, string $message, ?int $line = null, string $severity = 'error'): array
    {
        return compact('rule', 'message', 'line', 'severity');
    }
}
