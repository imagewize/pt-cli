<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\Compliance\Rules;

use Imagewize\PtCli\Compliance\Config\ConfigLoader;
use Imagewize\PtCli\Compliance\Rules\TemplateRules;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateRules — the third-step HTML template compliance checker.
 */
class TemplateRulesTest extends TestCase
{
    private TemplateRules $rules;
    private string $tmpDir;

    protected function setUp(): void
    {
        $loader      = new ConfigLoader();
        $config      = $loader->load('elayne');
        $this->rules = new TemplateRules($config);
        $this->tmpDir = sys_get_temp_dir();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/pt-cli-test-*.html') ?: [] as $f) {
            unlink($f);
        }
    }

    private function createTempTemplate(string $content): string
    {
        $path = $this->tmpDir . '/pt-cli-test-' . uniqid() . '.html';
        file_put_contents($path, $content);
        return $path;
    }

    private function violationRules(string $filePath): array
    {
        return array_column($this->rules->check($filePath), 'rule');
    }

    // ── getName / meta ──────────────────────────────────────────────────

    public function testGetNameReturnsTemplate(): void
    {
        $this->assertSame('template', $this->rules->getName());
    }

    public function testIsAutofixableReturnsTrue(): void
    {
        $this->assertTrue($this->rules->isAutofixable());
    }

    // ── taxQuery-object ─────────────────────────────────────────────────

    public function testTaxQueryObjectDetectsEmptyObject(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-collection {"query":{"taxQuery":{}}} -->'
        );
        $this->assertContains('taxQuery-object', $this->violationRules($file));
    }

    public function testTaxQueryArrayPassesClean(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-collection {"query":{"taxQuery":[]}} -->'
        );
        $this->assertNotContains('taxQuery-object', $this->violationRules($file));
    }

    public function testAutofixTaxQueryObjectReplacesInFile(): void
    {
        $file    = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-collection {"query":{"taxQuery":{}}} -->'
        );
        $applied = [];
        $fixed   = $this->rules->autofix(file_get_contents($file), $applied);

        $this->assertStringContainsString('"taxQuery":[]', $fixed);
        $this->assertStringNotContainsString('"taxQuery":{}', $fixed);
        $this->assertNotEmpty($applied);
    }

    // ── woo-filter-missing-wrapper ──────────────────────────────────────

    public function testSelfClosingFilterActiveIsViolation(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filter-active /-->'
        );
        $this->assertContains('woo-filter-missing-wrapper', $this->violationRules($file));
    }

    public function testSelfClosingFilterChipsIsViolation(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filter-chips /-->'
        );
        $this->assertContains('woo-filter-missing-wrapper', $this->violationRules($file));
    }

    public function testFilterActiveWithDivPasses(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filter-active --><div class="wp-block-woocommerce-product-filter-active"></div><!-- /wp:woocommerce/product-filter-active -->'
        );
        $this->assertNotContains('woo-filter-missing-wrapper', $this->violationRules($file));
    }

    public function testFilterCheckboxListWithDivPasses(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filter-checkbox-list --><div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div><!-- /wp:woocommerce/product-filter-checkbox-list -->'
        );
        $this->assertNotContains('woo-filter-missing-wrapper', $this->violationRules($file));
    }

    public function testFilterAttributeOpenBlockMissingDivIsViolation(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filter-attribute {"attributeId":1} -->' . "\n" .
            '<!-- wp:heading {"level":6} --><h6>Colour</h6><!-- /wp:heading -->' . "\n" .
            '<!-- /wp:woocommerce/product-filter-attribute -->'
        );
        $this->assertContains('woo-filter-missing-wrapper', $this->violationRules($file));
    }

    // ── woo-product-filters-css-vars ────────────────────────────────────

    public function testProductFiltersMissingCssVarsIsViolation(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filters {"className":"is-style-elayne"} -->' . "\n" .
            '<div class="wp-block-woocommerce-product-filters wc-block-product-filters is-style-elayne">' . "\n" .
            '</div>' . "\n" .
            '<!-- /wp:woocommerce/product-filters -->'
        );
        $this->assertContains('woo-product-filters-css-vars', $this->violationRules($file));
    }

    public function testProductFiltersWithCssVarsPasses(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:woocommerce/product-filters {"className":"is-style-elayne"} -->' . "\n" .
            '<div class="wp-block-woocommerce-product-filters wc-block-product-filters is-style-elayne" style="--wc-product-filters-text-color:#111;--wc-product-filters-background-color:#fff;--wc-product-filter-block-spacing:0">' . "\n" .
            '</div>' . "\n" .
            '<!-- /wp:woocommerce/product-filters -->'
        );
        $this->assertNotContains('woo-product-filters-css-vars', $this->violationRules($file));
    }

    // ── template-part-theme ─────────────────────────────────────────────

    public function testTemplatepartMissingThemeIsWarning(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
        );
        $violations = $this->rules->check($file);
        $found = array_filter($violations, fn($v) => $v['rule'] === 'template-part-theme');
        $this->assertNotEmpty($found);
        $this->assertSame('warning', array_values($found)[0]['severity']);
    }

    public function testTemplatepartWithCorrectThemePasses(): void
    {
        $file = $this->createTempTemplate(
            '<!-- wp:template-part {"slug":"header","theme":"elayne","tagName":"header"} /-->'
        );
        $this->assertNotContains('template-part-theme', $this->violationRules($file));
    }

    public function testAutofixAddsThemeToTemplatepart(): void
    {
        $content = '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
        $applied = [];
        $fixed   = $this->rules->autofix($content, $applied);

        $this->assertStringContainsString('"theme":"elayne"', $fixed);
        $this->assertNotEmpty($applied);
    }

    // ── unbalanced-html-tags ────────────────────────────────────────────

    public function testUnbalancedDivIsViolation(): void
    {
        $file = $this->createTempTemplate(
            '<div class="outer"><div class="inner">content</div>'
        );
        $this->assertContains('unbalanced-html-tags', $this->violationRules($file));
    }

    public function testBalancedTagsPass(): void
    {
        $file = $this->createTempTemplate(
            '<main class="wp-block-group"><div class="inner">content</div></main>'
        );
        $this->assertNotContains('unbalanced-html-tags', $this->violationRules($file));
    }

    // ── Clean template passes all checks ───────────────────────────────

    public function testCleanTemplatePassesAllChecks(): void
    {
        $file = $this->createTempTemplate(<<<'HTML'
<!-- wp:template-part {"slug":"header","theme":"elayne","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group"><!-- wp:woocommerce/product-filter-active --><div class="wp-block-woocommerce-product-filter-active"></div><!-- /wp:woocommerce/product-filter-active -->
<!-- wp:woocommerce/product-filters {"className":"is-style-elayne"} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters is-style-elayne" style="--wc-product-filters-text-color:#111;--wc-product-filters-background-color:#fff;--wc-product-filter-block-spacing:0">
</div>
<!-- /wp:woocommerce/product-filters --></main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","theme":"elayne","tagName":"footer"} /-->
HTML);
        $violations = $this->rules->check($file);
        // taxQuery not present, filter has divs, css vars present, theme set, tags balanced
        $this->assertEmpty($violations, implode('; ', array_column($violations, 'message')));
    }
}
