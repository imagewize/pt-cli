<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

interface RuleSetInterface
{
    public function getName(): string;

    /** @return array<array{rule: string, message: string, line: int|null, severity: string}> */
    public function check(string $filePath): array;

    public function isAutofixable(): bool;

    public function autofix(string $content): string;
}
