<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Config;

class ThemeConfig
{
    public function __construct(private readonly array $config) {}

    public function getThemeName(): string
    {
        return $this->config['theme']['name'] ?? 'Generic FSE Theme';
    }

    public function getThemeSlug(): string
    {
        return $this->config['theme']['slug'] ?? '';
    }

    public function getPatternPrefix(): string
    {
        return $this->config['theme']['patternPrefix'] ?? '';
    }

    public function woocommerceEnabled(): bool
    {
        return $this->config['compliance']['rules']['woocommerce']['enabled'] ?? false;
    }

    public function autofixEnabled(): bool
    {
        return $this->config['compliance']['autofix']['enabled'] ?? false;
    }

    public function getRules(): array
    {
        return $this->config['compliance']['rules'] ?? [];
    }

    public function raw(): array
    {
        return $this->config;
    }
}
