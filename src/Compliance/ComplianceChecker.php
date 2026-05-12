<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;
use Imagewize\PtCli\Compliance\Rules\BaseRules;
use Imagewize\PtCli\Compliance\Rules\RuleSetInterface;
use Imagewize\PtCli\Compliance\Rules\TemplateRules;
use Imagewize\PtCli\Compliance\Rules\WooCommerceRules;

class ComplianceChecker
{
    /** @var RuleSetInterface[] Rule sets applied to PHP pattern files. */
    private array $ruleSets = [];

    /** @var RuleSetInterface[] Rule sets applied to HTML template/part files. */
    private array $templateRuleSets = [];

    public function __construct(ThemeConfig $config)
    {
        $this->ruleSets[] = new BaseRules($config);

        if ($config->woocommerceEnabled()) {
            $this->ruleSets[] = new WooCommerceRules($config);
        }

        $this->templateRuleSets[] = new TemplateRules($config);
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

    /**
     * Check a single HTML template or template-part file.
     *
     * @return array{violations: list<array{rule: string, message: string, line: int|null, severity: string}>, fixed: list<string>}
     */
    public function checkTemplateFile(string $path, bool $autofix = false): array
    {
        $fixed = [];

        if ($autofix) {
            $content  = file_get_contents($path);
            $modified = $content;

            foreach ($this->templateRuleSets as $ruleSet) {
                if ($ruleSet->isAutofixable()) {
                    $applied  = [];
                    $modified = $ruleSet->autofix($modified, $applied);
                    $fixed    = array_merge($fixed, $applied);
                }
            }

            if ($modified !== $content) {
                file_put_contents($path, $modified);
            }
        }

        $violations = [];
        foreach ($this->templateRuleSets as $ruleSet) {
            $violations = array_merge($violations, $ruleSet->check($path));
        }

        return ['violations' => $violations, 'fixed' => $fixed];
    }

    /**
     * Check all *.html files in a directory (templates/ or parts/).
     *
     * @return array<string, array{violations: list<array{rule: string, message: string, line: int|null, severity: string}>, fixed: list<string>}>
     */
    public function checkTemplateDirectory(string $dir, bool $autofix = false): array
    {
        $results = [];
        $files   = glob(rtrim($dir, '/') . '/*.html') ?: [];

        foreach ($files as $file) {
            $results[basename($file)] = $this->checkTemplateFile($file, $autofix);
        }

        return $results;
    }

    private function isExempt(string $path): bool
    {
        return str_contains($path, '/wp-content/plugins/woocommerce/patterns/');
    }
}
