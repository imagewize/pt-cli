<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;

class ComplianceChecker
{
    private ThemeConfig $config;
    private array $ruleSets = [];

    public function __construct(ThemeConfig $config)
    {
        $this->config = $config;
    }

    public function checkFile(string $path): array
    {
        return [];
    }

    public function checkDirectory(string $dir): array
    {
        return [];
    }
}
