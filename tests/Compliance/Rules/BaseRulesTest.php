<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\Compliance\Rules;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;
use Imagewize\PtCli\Compliance\Rules\BaseRules;
use PHPUnit\Framework\TestCase;

class BaseRulesTest extends TestCase
{
    private ThemeConfig $config;
    private BaseRules $rules;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('pt-cli-test-');
        mkdir($this->tempDir);
        
        $configData = [
            'themeSlug' => 'test-theme',
            'patternPrefix' => 'test/',
            'woocommerce' => ['enabled' => false],
            'rules' => [
                'disallowed' => [
                    'hardcodedFontSizes' => true,
                    'spacerBlocks' => true,
                    'emojiIcons' => false,
                    'hardcodedMediaIds' => true,
                ],
                'requireThemeAttribute' => false,
                'requirePatternName' => false,
            ],
        ];
        
        $this->config = new ThemeConfig($configData);
        $this->rules = new BaseRules($this->config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    private function createTempFile(string $content): string
    {
        $file = $this->tempDir . '/' . uniqid('pattern-') . '.php';
        file_put_contents($file, $content);
        return $file;
    }

    // ========================================================================
    // Unbalanced HTML Tags Tests
    // ========================================================================

    public function testUnbalancedHtmlTagsDetectsMissingClosingDiv(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<!-- wp:group -->
<div class="wp-block-group">
    <p><?php esc_html_e( 'Content', 'test-theme' ); ?></p>
<!-- /wp:group -->
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <div> tags: 1 opening, 0 closing', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsDetectsExtraClosingDiv(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<div class="wp-block-group">
    <p><?php esc_html_e( 'Content', 'test-theme' ); ?></p>
</div>
</div>
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <div> tags: 1 opening, 2 closing', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsDetectsUnbalancedUl(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<ul>
    <li><?php esc_html_e( 'Item 1', 'test-theme' ); ?></li>
    <li><?php esc_html_e( 'Item 2', 'test-theme' ); ?></li>
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <ul> tags: 1 opening, 0 closing', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsDetectsUnbalancedFigure(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<figure>
    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/image.jpg" alt="<?php esc_attr_e( 'Test', 'test-theme' ); ?>">
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <figure> tags: 1 opening, 0 closing', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsPassesWithBalancedTags(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<!-- wp:group -->
<div class="wp-block-group">
    <p><?php esc_html_e( 'Content', 'test-theme' ); ?></p>
</div>
<!-- /wp:group -->
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        // Filter to only unbalanced-html-tags violations
        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(0, $tagViolations);
    }

    public function testUnbalancedHtmlTagsPassesWithNestedBalancedDivs(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<div class="outer">
    <div class="inner">
        <p><?php esc_html_e( 'Content', 'test-theme' ); ?></p>
    </div>
</div>
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        // Filter to only unbalanced-html-tags violations
        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(0, $tagViolations);
    }

    public function testUnbalancedHtmlTagsRealWorldExampleFromIssue(): void
    {
        // Simulating the real-world example from issue #2
        // where <!-- /wp:group --><!-- /wp:group --> was missing the </div>
        $content = <<<PHP
<?php
/**
 * Title: Woo Testimonials
 * Slug: test/testimonials
 */
?>

<!-- wp:group {"align":"full","style":{"margin":{"top":"0","bottom":"0"}}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0">
    <!-- wp:group -->
    <div class="wp-block-group">
        <p><?php esc_html_e( 'Testimonial content', 'test-theme' ); ?></p>
    <!-- /wp:group -->
<!-- /wp:group -->
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        // Should detect unbalanced div tags (2 opening, 0 closing) - no </div> tags at all
        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <div> tags: 2 opening, 0 closing', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsDetectsMultipleUnbalancedTags(): void
    {
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<div>
    <ul>
        <li><?php esc_html_e( 'Item', 'test-theme' ); ?></li>
</div>
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        // div is balanced (1 opening, 1 closing), but ul is not closed
        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(1, $tagViolations);
        $this->assertSame('unbalanced-html-tags', $tagViolations[0]['rule']);
        $this->assertStringContainsString('Unbalanced <ul> tags:', $tagViolations[0]['message']);
    }

    public function testUnbalancedHtmlTagsPassesWithNoHtmlTags(): void
    {
        // Edge case: pattern with no HTML at all (just PHP header + empty block comment)
        $content = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<!-- wp:paragraph {"content":""} /-->
PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        // Filter to only unbalanced-html-tags violations
        $tagViolations = array_filter($violations, fn($v) => $v['rule'] === 'unbalanced-html-tags');
        $this->assertCount(0, $tagViolations, 'Pattern with no HTML tags should have zero unbalanced-html-tags violations');
    }

    // ========================================================================
    // Other rule tests can be added here
    // ========================================================================

    public function testCheckReturnsArray(): void
    {
        $content = '<?php /* Test */ ?>';
        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $this->assertIsArray($violations);
    }

    public function testGetNameReturnsBase(): void
    {
        $this->assertSame('base', $this->rules->getName());
    }

    public function testIsAutofixableReturnsTrue(): void
    {
        $this->assertTrue($this->rules->isAutofixable());
    }

    // ========================================================================
    // Border Color Raw Slug Tests
    // ========================================================================

    public function testBorderColorRawSlugIsViolation(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"className":"elayne-engraving-option","style":{"border":{"width":"1px","color":"border-light","radius":"0"}}} -->
            <div class="wp-block-group elayne-engraving-option"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertContains('border-color-raw-slug', $ruleNames);
    }

    public function testBorderColorPresetReferencePassesClean(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"className":"elayne-engraving-option","style":{"border":{"width":"1px","color":"var:preset|color|border-light","radius":"0"}}} -->
            <div class="wp-block-group elayne-engraving-option"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertNotContains('border-color-raw-slug', $ruleNames);
    }

    public function testBorderColorEmptyObjectPassesClean(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"style":{"border":{"width":"1px","radius":"0"}}} -->
            <div class="wp-block-group"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertNotContains('border-color-raw-slug', $ruleNames);
    }

    // ========================================================================
    // Border Style Solid in HTML Tests
    // ========================================================================

    public function testBorderStyleSolidInHtmlIsViolation(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"style":{"border":{"width":"1px","color":"var:preset|color|border-light","radius":"0"}}} -->
            <div class="wp-block-group has-border-color" style="border-width:1px;border-style:solid;border-radius:0;border-color:var(--wp--preset--color--border-light)"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertContains('border-style-solid-in-html', $ruleNames);
    }

    public function testBorderStyleSolidInHtmlPassesClean(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"style":{"border":{"width":"1px","color":"var:preset|color|border-light","radius":"0"}}} -->
            <div class="wp-block-group has-border-color" style="border-color:var(--wp--preset--color--border-light);border-width:1px;border-radius:0"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertNotContains('border-style-solid-in-html', $ruleNames);
    }

    public function testBorderStyleSolidInBlockCommentIsIgnored(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"note":"border-style:solid is fine in comments"} -->
            <div class="wp-block-group"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertNotContains('border-style-solid-in-html', $ruleNames);
    }

    // ========================================================================
    // Border Property Order in HTML Tests
    // ========================================================================

    public function testBorderWidthBeforeBorderColorIsViolation(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"style":{"border":{"width":"1px","color":"var:preset|color|border-light","radius":"0"}}} -->
            <div class="wp-block-group has-border-color" style="border-width:1px;border-radius:0;border-color:var(--wp--preset--color--border-light)"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertContains('border-property-order-in-html', $ruleNames);
    }

    public function testBorderColorBeforeBorderWidthPassesClean(): void
    {
        $content = <<<'PHP'
            <?php ?>
            <!-- wp:group {"style":{"border":{"width":"1px","color":"var:preset|color|border-light","radius":"0"}}} -->
            <div class="wp-block-group has-border-color" style="border-color:var(--wp--preset--color--border-light);border-width:1px;border-radius:0"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rules->check($file);

        $ruleNames = array_column($violations, 'rule');
        $this->assertNotContains('border-property-order-in-html', $ruleNames);
    }

    // ========================================================================
    // Pattern Name Tests
    // ========================================================================

    private function rulesRequiringPatternName(): BaseRules
    {
        return new BaseRules(new ThemeConfig([
            'theme' => ['slug' => 'test-theme', 'patternPrefix' => 'test/'],
            'compliance' => ['rules' => ['requirePatternName' => true]],
        ]));
    }

    public function testCompositePatternOfPatternRefsIsExemptFromPatternName(): void
    {
        $content = <<<'PHP'
            <?php
            /**
             * Title: Test Page
             * Slug: test/page-home
             */
            if ( ! defined( 'ABSPATH' ) ) {
                exit;
            }
            ?>
            <!-- wp:pattern {"slug":"test/hero"} /-->
            <!-- wp:pattern {"slug":"test/cta"} /-->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rulesRequiringPatternName()->check($file);

        $this->assertNotContains('pattern-name', array_column($violations, 'rule'));
    }

    public function testNonCompositePatternStillRequiresPatternName(): void
    {
        $content = <<<'PHP'
            <?php
            /**
             * Title: Test Page
             * Slug: test/page-home
             */
            ?>
            <!-- wp:pattern {"slug":"test/hero"} /-->
            <!-- wp:group {"align":"full"} -->
            <div class="wp-block-group alignfull"></div>
            <!-- /wp:group -->
            PHP;

        $file = $this->createTempFile($content);
        $violations = $this->rulesRequiringPatternName()->check($file);

        $this->assertContains('pattern-name', array_column($violations, 'rule'));
    }

    // ========================================================================
    // Image height:auto Tests
    // ========================================================================

    private function imagePattern(string $style): string
    {
        return <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/test
 */
?>

<!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="a.svg" alt="" style="{$style}"/></figure>
<!-- /wp:image -->
PHP;
    }

    public function testImageHeightAutoFlagsWidthOnlyStyle(): void
    {
        $file = $this->createTempFile($this->imagePattern('width:32px'));
        $found = array_values(array_filter($this->rules->check($file), fn($v) => $v['rule'] === 'image-height-auto'));

        $this->assertCount(1, $found);
        $this->assertSame(9, $found[0]['line']);
    }

    public function testImageHeightAutoAcceptsHeightAuto(): void
    {
        $file = $this->createTempFile($this->imagePattern('width:32px;height:auto'));
        $found = array_filter($this->rules->check($file), fn($v) => $v['rule'] === 'image-height-auto');

        $this->assertCount(0, $found);
    }

    public function testImageHeightAutoAutofixAppendsHeightAuto(): void
    {
        $applied = [];
        $fixed = $this->rules->autofix($this->imagePattern('width:32px'), $applied);

        $this->assertStringContainsString('style="width:32px;height:auto"', $fixed);
        $this->assertNotEmpty($applied);

        $again = [];
        $this->assertSame($fixed, $this->rules->autofix($fixed, $again));
    }
}
