<?php

declare(strict_types=1);

namespace Imagewize\PtCli\PatternDiff;

/**
 * Validates WooCommerce block structure and attributes.
 * Checks for common issues like missing isDescendentOfQueryLoop,
 * incorrect attribute ordering, and required wrapper elements.
 */
class WooCommerceValidator
{
    private const WC_BLOCKS = [
        'woocommerce/product-collection',
        'woocommerce/product-template',
        'woocommerce/product-image',
        'woocommerce/product-title',
        'woocommerce/product-price',
        'woocommerce/product-button',
        'woocommerce/product-sale-badge',
        'woocommerce/product-sku',
        'woocommerce/product-rating',
        'woocommerce/product-summary',
        'woocommerce/product-stock-indicator',
        'woocommerce/filter-wrapper',
        'woocommerce/product-filters',
        'woocommerce/price-filter',
        'woocommerce/attribute-filter',
        'woocommerce/stock-filter',
    ];

    private const REQUIRED_WRAPPERS = [
        'woocommerce/product-collection' => 'div',
        'woocommerce/product-template' => 'div',
        'woocommerce/product-filters' => 'div',
    ];

    private const REQUIRED_CHILD_ATTRIBUTES = [
        'woocommerce/product-image' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-title' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-price' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-button' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-sale-badge' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-sku' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-rating' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-summary' => ['isDescendentOfQueryLoop'],
        'woocommerce/product-stock-indicator' => ['isDescendentOfQueryLoop'],
    ];

    /**
     * Validate WooCommerce blocks in the given content.
     *
     * @return array<array{message: string, severity: string, fix?: string}>
     */
    public function validate(string $fileBlocks, string $clipboard): array
    {
        $issues = [];

        // Only validate if WooCommerce blocks are present
        if (!$this->hasWooCommerceBlocks($clipboard)) {
            return $issues;
        }

        // Check for missing isDescendentOfQueryLoop in product template children
        $issues = array_merge($issues, $this->checkQueryLoopAttributes($clipboard));

        // Check for required wrapper elements
        $issues = array_merge($issues, $this->checkWrapperElements($clipboard));

        // Check for conflicting layout/displayLayout attributes
        $issues = array_merge($issues, $this->checkLayoutAttributes($clipboard));

        // Check for missing query metadata in product-collection
        $issues = array_merge($issues, $this->checkProductCollectionQuery($clipboard));

        return $issues;
    }

    /**
     * Check if content contains WooCommerce blocks.
     */
    private function hasWooCommerceBlocks(string $content): bool
    {
        foreach (self::WC_BLOCKS as $block) {
            if (str_contains($content, $block)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check for missing isDescendentOfQueryLoop attributes on product template children.
     */
    private function checkQueryLoopAttributes(string $content): array
    {
        $issues = [];

        // Find all WooCommerce blocks that are children of product-template
        foreach (self::REQUIRED_CHILD_ATTRIBUTES as $blockType => $requiredAttrs) {
            // Check if this block type exists in content
            if (str_contains($content, $blockType)) {
                // Extract the block JSON
                if (preg_match_all('#<!--\s*wp:' . preg_quote($blockType, '#') . '\s+(\{.*?\})\s*-->#', $content, $matches)) {
                    foreach ($matches[1] as $index => $attrsJson) {
                        // Parse the JSON attributes
                        $attrs = $this->parseBlockAttributes($attrsJson);
                        
                        // Check for required attributes
                        foreach ($requiredAttrs as $requiredAttr) {
                            if (!isset($attrs[$requiredAttr])) {
                                $issues[] = [
                                    'message' => "Missing '{$requiredAttr}' attribute on {$blockType} block",
                                    'severity' => 'error',
                                    'fix' => "Add \"{$requiredAttr}\":true to the block attributes",
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for required wrapper elements around WooCommerce blocks.
     */
    private function checkWrapperElements(string $content): array
    {
        $issues = [];

        foreach (self::REQUIRED_WRAPPERS as $blockType => $requiredTag) {
            if (str_contains($content, $blockType)) {
                // Check if the block is wrapped in the required tag
                if (preg_match('/<!--\s*wp:' . preg_quote($blockType, '/') . '\s+.*?-->/', $content)) {
                    // Look for the opening tag after the block comment
                    $pattern = '/<!--\s*wp:' . preg_quote($blockType, '/') . '\s+.*?-->\s*<' . $requiredTag . '/';
                    if (!preg_match($pattern, $content)) {
                        $issues[] = [
                            'message' => "Missing <{$requiredTag}> wrapper for {$blockType}",
                            'severity' => 'error',
                            'fix' => "Wrap the {$blockType} block in <{$requiredTag}>",
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for conflicting layout and displayLayout attributes.
     */
    private function checkLayoutAttributes(string $content): array
    {
        $issues = [];

        // Check product-collection blocks
        if (preg_match_all('#<!--\s*wp:woocommerce\/product-collection\s+(\{.*?\})\s*-->#', $content, $matches)) {
            foreach ($matches[1] as $attrsJson) {
                $attrs = $this->parseBlockAttributes($attrsJson);
                
                if (isset($attrs['layout']) && isset($attrs['displayLayout'])) {
                    $issues[] = [
                        'message' => 'Product collection block has both layout and displayLayout attributes',
                        'severity' => 'error',
                        'fix' => 'Use either layout or displayLayout, not both',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Check for missing query metadata in product-collection blocks.
     */
    private function checkProductCollectionQuery(string $content): array
    {
        $issues = [];

        if (preg_match_all('#<!--\s*wp:woocommerce\/product-collection\s+(\{.*?\})\s*-->#', $content, $matches)) {
            foreach ($matches[1] as $index => $attrsJson) {
                $attrs = $this->parseBlockAttributes($attrsJson);
                
                // Check if query exists
                if (!isset($attrs['query']) || empty($attrs['query'])) {
                    $issues[] = [
                        'message' => "Product collection block at position " . ($index + 1) . " is missing query attribute",
                        'severity' => 'warning',
                        'fix' => 'Add "query":{"perPage":4,"orderBy":"menu_order"} or similar',
                    ];
                } elseif (isset($attrs['query']['taxQuery']) && $attrs['query']['taxQuery'] === (object)[]) {
                    // Check for empty object (should be empty array)
                    $issues[] = [
                        'message' => 'taxQuery should be an empty array [] not empty object {}',
                        'severity' => 'error',
                        'fix' => 'Change "taxQuery":{} to "taxQuery":[]',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Parse block attributes JSON string into an array.
     * Handles both JSON objects and arrays.
     */
    private function parseBlockAttributes(string $json): array
    {
        // Clean up the JSON string
        $json = trim($json);
        
        // Remove trailing commas (common in WordPress block JSON)
        $json = preg_replace('#,\s*\}#', '}', $json);
        $json = preg_replace('#,\s*\]#', ']', $json);

        // Try to decode as JSON
        $decoded = json_decode($json, true);
        
        if (is_array($decoded)) {
            return $decoded;
        }

        // If JSON decode fails, try to extract key-value pairs manually
        $attrs = [];
        if (preg_match_all('#"([^"]+)":([^,}]+)#', $json, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = trim($match[2]);
                
                // Handle different value types
                if ($value === 'true') {
                    $attrs[$key] = true;
                } elseif ($value === 'false') {
                    $attrs[$key] = false;
                } elseif (is_numeric($value)) {
                    $attrs[$key] = (int) $value;
                } elseif (preg_match('/^\[.*\]$/', $value) || preg_match('/^\{.*\}$/', $value)) {
                    // Keep as string for now (complex nested structures)
                    $attrs[$key] = $value;
                } elseif (preg_match('/^"(.*?)"$/', $value, $strMatch)) {
                    $attrs[$key] = $strMatch[1];
                } else {
                    $attrs[$key] = $value;
                }
            }
        }

        return $attrs;
    }

    /**
     * Check if a WooCommerce block is properly nested within product-template.
     */
    public function isDescendantOfProductTemplate(string $content, string $blockType): bool
    {
        // Find the block
        $blockPattern = '#<!--\s*wp:' . preg_quote($blockType, '#') . '#';
        
        if (preg_match($blockPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $blockPos = $matches[0][1];
            
            // Find the nearest product-template before this block
            $templatePattern = '#<!--\s*wp:woocommerce\/product-template#';
            if (preg_match($templatePattern, substr($content, 0, $blockPos))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract all WooCommerce block types from content.
     *
     * @return array<string, int> Block type => count
     */
    public function extractWooCommerceBlocks(string $content): array
    {
        $blocks = [];

        foreach (self::WC_BLOCKS as $blockType) {
            if (preg_match_all('#<!--\s*wp:' . preg_quote($blockType, '#') . '#', $content, $matches)) {
                $blocks[$blockType] = count($matches[0]);
            }
        }

        return $blocks;
    }
}
