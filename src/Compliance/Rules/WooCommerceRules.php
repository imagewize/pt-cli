<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

class WooCommerceRules extends AbstractRuleSet
{
    public function getName(): string
    {
        return 'woocommerce';
    }

    public function check(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if (!str_contains($content, 'woocommerce/product')) {
            return [];
        }

        $violations = [];
        $lines = explode("\n", $content);
        $inProductTemplate = false;
        $productTemplateDepth = 0;

        foreach ($lines as $line) {
            if (str_contains($line, '<!-- wp:woocommerce/product-template')) {
                $inProductTemplate = true;
                $productTemplateDepth++;
            } elseif (str_contains($line, '<!-- /wp:woocommerce/product-template -->')) {
                $productTemplateDepth--;
                if ($productTemplateDepth === 0) {
                    $inProductTemplate = false;
                }
            }

            if ($inProductTemplate && str_contains($line, '<!-- wp:woocommerce/product-title')) {
                if (!str_contains($line, '__woocommerceNamespace')) {
                    $violations[] = $this->violation('woo-product-title-namespace', 'woocommerce/product-title inside product-template missing __woocommerceNamespace — use post-title with {"__woocommerceNamespace":"woocommerce/product-collection/product-title"}');
                }
                if (str_contains($line, 'woocommerce/product-title')) {
                    $violations[] = $this->violation('woo-product-title-block', 'woocommerce/product-title inside product-template should be post-title with __woocommerceNamespace attribute');
                }
            }

            $woocommerceNativeBlocks = [
                'woocommerce/product-image',
                'woocommerce/product-price',
                'woocommerce/product-rating',
                'woocommerce/product-button',
                'woocommerce/product-sale-badge',
            ];

            foreach ($woocommerceNativeBlocks as $block) {
                if (str_contains($line, '<!-- wp:' . $block) && str_contains($line, '__woocommerceNamespace')) {
                    $violations[] = $this->violation('woo-native-block-namespace', $block . ' block should NOT have __woocommerceNamespace attribute — only core blocks like post-title need it');
                }
            }
        }

        if (preg_match('/<!--\s*wp:woocommerce\/product-collection\s+\{/', $content)) {
            if (!str_contains($content, '"query":{') && !str_contains($content, "'query':{")) {
                $violations[] = $this->violation('woo-product-collection-query', 'woocommerce/product-collection missing query metadata');
            }

            if (!preg_match('/<!--\s*wp:woocommerce\/product-collection[^>]*-->\s*<div[^>]*class="[^"]*wp-block-woocommerce-product-collection[^"]*"[^>]*>/', $content)) {
                $violations[] = $this->violation('woo-product-collection-wrapper', 'woocommerce/product-collection missing div wrapper — add <div class="wp-block-woocommerce-product-collection"> after opening block comment');
            }
        }

        if (preg_match('/<!--\s*wp:woocommerce\/product-collection\s+\{(.*?)-->/s', $content, $matches)) {
            $productCollectionAttrs = $matches[1];
            if (strpos($productCollectionAttrs, '"layout":') !== false && strpos($productCollectionAttrs, '"displayLayout":') !== false) {
                $violations[] = $this->violation('woo-product-collection-layout-conflict', 'woocommerce/product-collection has both layout and displayLayout attributes — use only displayLayout for WooCommerce blocks');
            }
        }

        return $violations;
    }
}
