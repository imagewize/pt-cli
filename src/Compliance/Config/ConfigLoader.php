<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Config;

class ConfigLoader
{
    private string $toolConfigDir;

    public function __construct()
    {
        $this->toolConfigDir = dirname(__DIR__, 3) . '/config';
    }

    public function load(string $theme, ?string $projectDir = null): ThemeConfig
    {
        $config = $this->loadBase();

        if ($theme !== 'base') {
            $themeConfig = $this->loadThemeConfig($theme, $projectDir);
            $config = $this->merge($config, $themeConfig);
        }

        return new ThemeConfig($config);
    }

    private function loadBase(): array
    {
        return $this->readJson($this->toolConfigDir . '/base.json');
    }

    private function loadThemeConfig(string $theme, ?string $projectDir): array
    {
        // Check project-level override first
        if ($projectDir !== null) {
            $override = $projectDir . '/.pt-cli/' . $theme . '.json';
            if (file_exists($override)) {
                return $this->readJson($override);
            }
        }

        $toolConfig = $this->toolConfigDir . '/' . $theme . '.json';
        if (file_exists($toolConfig)) {
            return $this->readJson($toolConfig);
        }

        return [];
    }

    private function readJson(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read config file: {$path}");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in config file: {$path}");
        }

        return $data;
    }

    private function merge(array $base, array $override): array
    {
        return array_replace_recursive($base, $override);
    }
}
