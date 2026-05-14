<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\PatternDiff;

use Imagewize\PtCli\PatternDiff\PatternSyncer;
use PHPUnit\Framework\TestCase;

class PatternSyncerTest extends TestCase
{
    private PatternSyncer $syncer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncer = new PatternSyncer();
    }

    // ========================================================================
    // sync Tests
    // ========================================================================

    public function testSyncPreservesPhpHeader(): void
    {
        $clipboard = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $fileContent = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p><?php esc_html_e( 'Hello', 'elayne' ); ?></p><!-- /wp:paragraph -->
PHP;

        $result = $this->syncer->sync($clipboard, $fileContent);

        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('Title: Test Pattern', $result);
    }

    public function testSyncRestoresTranslationWrappers(): void
    {
        $clipboard = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
        $fileContent = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p><?php esc_html_e( 'Hello', 'elayne' ); ?></p><!-- /wp:paragraph -->
PHP;

        $result = $this->syncer->sync($clipboard, $fileContent);

        $this->assertStringContainsString("esc_html_e( 'Hello', 'elayne' )", $result);
    }

    public function testSyncAddsNewTranslationWrappers(): void
    {
        $clipboard = '<!-- wp:paragraph --><p>New Text</p><!-- /wp:paragraph -->';
        $fileContent = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p><?php esc_html_e( 'Old Text', 'elayne' ); ?></p><!-- /wp:paragraph -->
PHP;

        $result = $this->syncer->sync($clipboard, $fileContent);

        $this->assertStringContainsString("esc_html_e( 'New Text', 'elayne' )", $result);
    }

    // ========================================================================
    // extractHeader Tests
    // ========================================================================

    public function testExtractHeaderExtractsFullHeader(): void
    {
        $fileContent = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p>content</p><!-- /wp:paragraph -->
PHP;

        $header = $this->syncer->extractHeader($fileContent);

        $this->assertStringContainsString('<?php', $header);
        $this->assertStringContainsString('Title: Test Pattern', $header);
        $this->assertStringContainsString('?>', $header);
    }

    public function testExtractHeaderReturnsEmptyForNoHeader(): void
    {
        $fileContent = '<!-- wp:paragraph --><p>content</p><!-- /wp:paragraph -->';
        $header = $this->syncer->extractHeader($fileContent);
        $this->assertSame('', $header);
    }

    // ========================================================================
    // stripHeader Tests
    // ========================================================================

    public function testStripHeaderRemovesHeader(): void
    {
        $fileContent = <<<PHP
<?php
/**
 * Title: Test Pattern
 * Slug: test/pattern
 */
?>

<!-- wp:paragraph --><p>content</p><!-- /wp:paragraph -->
PHP;

        $blocks = $this->syncer->stripHeader($fileContent);

        $this->assertStringNotContainsString('<?php', $blocks);
        $this->assertStringContainsString('<!-- wp:paragraph -->', $blocks);
        $this->assertStringContainsString('<p>content</p>', $blocks);
    }

    public function testStripHeaderHandlesNoHeader(): void
    {
        $fileContent = '<!-- wp:paragraph --><p>content</p><!-- /wp:paragraph -->';
        $blocks = $this->syncer->stripHeader($fileContent);
        $this->assertSame($fileContent, $blocks);
    }

    // ========================================================================
    // buildPhpCallsMap Tests
    // ========================================================================

    public function testBuildPhpCallsMapFindsEscHtmlE(): void
    {
        $fileBlocks = '<p><?php esc_html_e( \'Hello\', \'elayne\' ); ?></p>';
        $map = $this->syncer->buildPhpCallsMap($fileBlocks);

        $this->assertArrayHasKey('Hello', $map);
        $this->assertStringContainsString("esc_html_e( 'Hello', 'elayne' )", $map['Hello']);
    }

    public function testBuildPhpCallsMapFindsEscAttrE(): void
    {
        $fileBlocks = '<div alt="<?php esc_attr_e( \'Alt Text\', \'elayne\' ); ?>">content</div>';
        $map = $this->syncer->buildPhpCallsMap($fileBlocks);

        $this->assertArrayHasKey('Alt Text', $map);
        $this->assertStringContainsString('esc_attr_e', $map['Alt Text']);
    }

    public function testBuildPhpCallsMapHandlesDoubleQuotes(): void
    {
        $fileBlocks = '<p><?php esc_html_e( "Hello", "elayne" ); ?></p>';
        $map = $this->syncer->buildPhpCallsMap($fileBlocks);

        $this->assertArrayHasKey('Hello', $map);
    }

    public function testBuildPhpCallsMapFindsWpKsesPost(): void
    {
        $fileBlocks = '<div><?php echo wp_kses_post( __( \'Content\', \'elayne\' ) ); ?></div>';
        $map = $this->syncer->buildPhpCallsMap($fileBlocks);

        $this->assertArrayHasKey('Content', $map);
        $this->assertStringContainsString('wp_kses_post', $map['Content']);
    }

    public function testBuildPhpCallsMapHandlesEscapedQuotes(): void
    {
        $fileBlocks = '<p><?php esc_html_e( \'It\\\'s a test\', \'elayne\' ); ?></p>';
        $map = $this->syncer->buildPhpCallsMap($fileBlocks);

        $this->assertArrayHasKey("It's a test", $map);
    }

    // ========================================================================
    // applyStructuralFixes Tests
    // ========================================================================

    public function testApplyStructuralFixesRemovesPrivatePreviewState(): void
    {
        $clipboard = '<!-- wp:group {"__privatePreviewState":{"foo":"bar"}} --><div>content</div><!-- /wp:group -->';
        $result = $this->syncer->applyStructuralFixes($clipboard);

        $this->assertStringNotContainsString('__privatePreviewState', $result);
    }

    public function testApplyStructuralFixesNormalizesFontSizeSlugs(): void
    {
        $clipboard = '<div style="font-size:small">text</div>';
        $result = $this->syncer->applyStructuralFixes($clipboard);

        $this->assertStringContainsString('font-size:var(--wp--preset--font-size--small)', $result);
        $this->assertStringNotContainsString('font-size:small', $result);
    }

    public function testApplyStructuralFixesNormalizesMediumFontSize(): void
    {
        $clipboard = '<div style="font-size:medium">text</div>';
        $result = $this->syncer->applyStructuralFixes($clipboard);

        $this->assertStringContainsString('font-size:var(--wp--preset--font-size--medium)', $result);
    }

    public function testApplyStructuralFixesRemovesNestedParagraphs(): void
    {
        $clipboard = '<p><p>Nested</p></p>';
        $result = $this->syncer->applyStructuralFixes($clipboard);

        $this->assertSame('<p>Nested</p>', $result);
    }

    public function testApplyStructuralFixesHandlesMultipleNestedParagraphs(): void
    {
        $clipboard = '<p class="foo"><p class="bar">Nested</p></p>';
        $result = $this->syncer->applyStructuralFixes($clipboard);

        $this->assertSame('<p class="bar">Nested</p>', $result);
    }

    // ========================================================================
    // restorePhpCalls Tests
    // ========================================================================

    public function testRestorePhpCallsRestoresKnownText(): void
    {
        $html = '<p>Hello</p>';
        $phpMap = ['Hello' => '<?php esc_html_e( \'Hello\', \'elayne\' ); ?>'];
        
        $result = $this->syncer->restorePhpCalls($html, $phpMap);

        $this->assertStringContainsString("<?php esc_html_e( 'Hello', 'elayne' ); ?>", $result);
    }

    public function testRestorePhpCallsAddsNewWrapperForUnknownText(): void
    {
        $html = '<p>New Text</p>';
        $phpMap = [];
        
        $result = $this->syncer->restorePhpCalls($html, $phpMap);

        $this->assertStringContainsString("esc_html_e( 'New Text', 'elayne' )", $result);
    }

    public function testRestorePhpCallsSkipsNonTranslatableText(): void
    {
        $html = '<div style="color: #fff">123</div>';
        $phpMap = [];
        
        $result = $this->syncer->restorePhpCalls($html, $phpMap);

        $this->assertSame($html, $result);
    }

    public function testRestorePhpCallsPreservesExistingPhpTags(): void
    {
        $html = '<p><?php esc_html_e( \'Existing\', \'elayne\' ); ?></p>';
        $phpMap = ['Existing' => '<?php esc_html_e( \'Existing\', \'elayne\' ); ?>'];
        
        $result = $this->syncer->restorePhpCalls($html, $phpMap);

        $this->assertStringContainsString("<?php esc_html_e( 'Existing', 'elayne' ); ?>", $result);
    }

    // ========================================================================
    // generateWrapper Tests
    // ========================================================================

    public function testGenerateWrapperCreatesEscHtmlE(): void
    {
        $wrapper = $this->syncer->generateWrapper('Test');
        
        $this->assertStringContainsString("esc_html_e( 'Test', 'elayne' )", $wrapper);
        $this->assertStringContainsString('<?php', $wrapper);
        $this->assertStringContainsString('?>', $wrapper);
    }

    public function testGenerateWrapperEscapesSingleQuotes(): void
    {
        $wrapper = $this->syncer->generateWrapper("It's a test");
        
        $this->assertStringContainsString("It\\'s a test", $wrapper);
    }
}
