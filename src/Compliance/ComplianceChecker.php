<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;
use Imagewize\PtCli\Compliance\Rules\BaseRules;
use Imagewize\PtCli\Compliance\Rules\RuleSetInterface;
use Imagewize\PtCli\Compliance\Rules\WooCommerceRules;

class ComplianceChecker
{
    /** @var RuleSetInterface[] */
    private array $ruleSets = [];

    public function __construct(ThemeConfig $config)
    {
        $this->ruleSets[] = new BaseRules($config);

        if ($config->woocommerceEnabled()) {
            $this->ruleSets[] = new WooCommerceRules($config);
        }
    }

    /**
     * @return array{violations: list<array{rule: string, message: string, line: int|null, severity: string}>, fixed: list<string>}
     */
    public function checkFile(string $path, bool $autofix = false): array
    {
        $fixed = [];

        if ($autofix) {
            $content = file_get_contents($path);
            $modified = $content;

            foreach ($this->ruleSets as $ruleSet) {
                if ($ruleSet->isAutofixable()) {
                    $applied = [];
                    $modified = $ruleSet->autofix($modified, $applied);
                    $fixed = array_merge($fixed, $applied);
                }
            }

            if ($modified !== $content) {
                file_put_contents($path, $modified);
            }
        }

        $violations = [];
        foreach ($this->ruleSets as $ruleSet) {
            $violations = array_merge($violations, $ruleSet->check($path));
        }

        return ['violations' => $violations, 'fixed' => $fixed];
    }

    /**
     * @return array<string, array{violations: list<array{rule: string, message: string, line: int|null, severity: string}>, fixed: list<string>}>
     */
    public function checkDirectory(string $dir, bool $autofix = false): array
    {
        $results = [];
        $files = glob(rtrim($dir, '/') . '/*.php') ?: [];

        foreach ($files as $file) {
            if ($this->isExempt($file)) {
                continue;
            }
            $results[basename($file)] = $this->checkFile($file, $autofix);
        }

        return $results;
    }

    private function isExempt(string $path): bool
    {
        return str_contains($path, '/wp-content/plugins/woocommerce/patterns/');
    }
}
