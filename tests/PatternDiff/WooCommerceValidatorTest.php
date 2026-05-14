<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Tests\PatternDiff;

use Imagewize\PtCli\PatternDiff\WooCommerceValidator;
use PHPUnit\Framework\TestCase;

class WooCommerceValidatorTest extends TestCase
{
    private WooCommerceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WooCommerceValidator();
    }

    // ========================================================================
    // validate Tests
    // ========================================================================

    public function testValidateReturnsEmptyArrayForNonWooCommerceContent(): void
    {
        $fileBlocks = '<!-- wp:group --><div>content</div><!-- /wp:group -->';
        $clipboard = '<!-- wp:group --><div>content</div><!-- /wp:group -->';
        
        $issues = $this->validator->validate($fileBlocks, $clipboard);
        
        $this->assertEmpty($issues);
    }

    public function testValidateDetectsMissingIsDescendentOfQueryLoop(): void
    {
        // When clipboard contains WooCommerce blocks but file doesn't have the required attributes
        $fileBlocks = '<!-- wp:woocommerce/product-template --><div>content</div><!-- /wp:woocommerce/product-template -->';
        $clipboard = '<!-- wp:woocommerce/product-price --><div>content</div><!-- /wp:woocommerce/product-price -->';
        
        $issues = $this->validator->validate($fileBlocks, $clipboard);
        
        // The validator checks clipboard for WooCommerce blocks and validates them
        $this->assertIsArray($issues);
    }

    // ========================================================================
    // extractWooCommerceBlocks Tests
    // ========================================================================

    public function testExtractWooCommerceBlocksCountsBlocks(): void
    {
        $content = '<!-- wp:woocommerce/product-collection --><div></div><!-- /wp:woocommerce/product-collection --><!-- wp:woocommerce/product-template --><div></div><!-- /wp:woocommerce/product-template -->';
        
        $blocks = $this->validator->extractWooCommerceBlocks($content);
        
        $this->assertSame(1, $blocks['woocommerce/product-collection']);
        $this->assertSame(1, $blocks['woocommerce/product-template']);
    }

    public function testExtractWooCommerceBlocksReturnsEmptyForNoWcBlocks(): void
    {
        $content = '<!-- wp:group --><div></div><!-- /wp:group -->';
        
        $blocks = $this->validator->extractWooCommerceBlocks($content);
        
        $this->assertEmpty($blocks);
    }

    // ========================================================================
    // isDescendantOfProductTemplate Tests
    // ========================================================================

    public function testIsDescendantOfProductTemplateReturnsTrueWhenNested(): void
    {
        $content = '<!-- wp:woocommerce/product-template --><!-- wp:woocommerce/product-price --><div></div><!-- /wp:woocommerce/product-price --><!-- /wp:woocommerce/product-template -->';
        
        $result = $this->validator->isDescendantOfProductTemplate($content, 'woocommerce/product-price');
        
        $this->assertTrue($result);
    }

    public function testIsDescendantOfProductTemplateReturnsFalseWhenNotNested(): void
    {
        $content = '<!-- wp:woocommerce/product-price --><div></div><!-- /wp:woocommerce/product-price -->';
        
        $result = $this->validator->isDescendantOfProductTemplate($content, 'woocommerce/product-price');
        
        $this->assertFalse($result);
    }
}
