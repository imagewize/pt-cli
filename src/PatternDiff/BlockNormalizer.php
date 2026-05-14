<?php

declare(strict_types=1);

namespace Imagewize\PtCli\PatternDiff;

/**
 * Normalizes block markup by stripping editor-specific attributes
 * and normalizing CSS values for accurate comparison.
 */
class BlockNormalizer
{
    /**
     * Fully normalize block content for comparison.
     */
    public function normalize(string $blocks): string
    {
        $blocks = $this->stripEditorAttributes($blocks);
        $blocks = $this->normalizeCssValues($blocks);
        $blocks = $this->normalizeWhitespace($blocks);
        $blocks = $this->normalizeHtmlEntities($blocks);
        
        return $blocks;
    }

    /**
     * Detect editor-specific attributes in block markup.
     *
     * @return array<string> List of detected editor attributes
     */
    public function detectEditorAttributes(string $blocks): array
    {
        $attributes = [];

        // Check for metadata
        if (str_contains($blocks, '"metadata":')) {
            $attributes[] = 'metadata';
        }

        // Check for __privatePreviewState
        if (str_contains($blocks, '__privatePreviewState')) {
            $attributes[] = '__privatePreviewState';
        }

        // Check for is-not-stacked-on-mobile
        if (str_contains($blocks, 'is-not-stacked-on-mobile')) {
            $attributes[] = 'is-not-stacked-on-mobile (class)';
        }

        // Check for layout.type: constrained
        if (str_contains($blocks, '"type":"constrained"')) {
            $attributes[] = 'layout.type: constrained';
        }

        // Check for layout.type: default (might be in file but not clipboard)
        if (str_contains($blocks, '"type":"default"')) {
            $attributes[] = 'layout.type: default';
        }

        // Check for align attributes that might be editor-specific
        if (preg_match('/"align":"(wide|full)"/', $blocks)) {
            $attributes[] = 'align attribute (may be editor-specific)';
        }

        // Check for editor-only query parameters
        if (str_contains($blocks, '"isProductCollectionBlock":true')) {
            $attributes[] = 'isProductCollectionBlock';
        }

        return array_unique($attributes);
    }

    /**
     * Strip editor-only attributes from block markup.
     */
    public function stripEditorAttributes(string $blocks): string
    {
        // Remove metadata from blocks
        $blocks = preg_replace('#"metadata":\{[^}]*\},?#', '', $blocks);
        
        // Remove __privatePreviewState
        $blocks = preg_replace('#"__privatePreviewState":\{[^}]*\},?#', '', $blocks);
        
        // Remove is-not-stacked-on-mobile class from columns
        $blocks = str_replace('is-not-stacked-on-mobile', '', $blocks);
        
        // Normalize layout.type from constrained to default
        $blocks = preg_replace('#"layout":\{"type":"constrained"\}#', '"layout":{"type":"default"}', $blocks);
        
        // Remove isProductCollectionBlock flag
        $blocks = preg_replace('#"isProductCollectionBlock":true,?#', '', $blocks);
        
        // Remove trailing commas after attribute removal
        $blocks = preg_replace('#,\s*\}#', '}', $blocks);
        
        // Remove empty objects
        $blocks = preg_replace('#\{[^\{\}]*\}#', '', $blocks);
        
        return $blocks;
    }

    /**
     * Normalize CSS values to use CSS variables.
     */
    public function normalizeCssValues(string $blocks): string
    {
        // Font size presets
        $fontSizeReplacements = [
            'font-size:small' => 'font-size:var(--wp--preset--font-size--small)',
            'font-size:medium' => 'font-size:var(--wp--preset--font-size--medium)',
            'font-size:large' => 'font-size:var(--wp--preset--font-size--large)',
            'font-size:x-large' => 'font-size:var(--wp--preset--font-size--x-large)',
            'font-size:xx-large' => 'font-size:var(--wp--preset--font-size--xx-large)',
        ];

        foreach ($fontSizeReplacements as $from => $to) {
            $blocks = str_replace($from, $to, $blocks);
        }

        // Spacing presets
        $spacingPresets = ['small', 'medium', 'large', 'x-large', 'xx-large', '2-x-small', 'x-small'];
        foreach ($spacingPresets as $preset) {
            // margin-top: var:preset|spacing|{preset}
            $blocks = preg_replace(
                '/margin-top:var\(\-\-wp\-\-preset\-\-spacing\-\-' . $preset . '\)/',
                'margin-top:var(--wp--preset--spacing--' . $preset . ')',
                $blocks
            );
            
            // margin-bottom, padding-top, padding-bottom, etc.
            $properties = ['margin-bottom', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'blockGap'];
            foreach ($properties as $prop) {
                $blocks = preg_replace(
                    '/' . $prop . ':var\(\-\-wp\-\-preset\-\-spacing\-\-' . $preset . '\)/',
                    $prop . ':var(--wp--preset--spacing--' . $preset . ')',
                    $blocks
                );
            }
        }

        // Color presets
        $colorPresets = ['primary', 'secondary', 'tertiary', 'main-accent', 'base', 'contrast'];
        foreach ($colorPresets as $color) {
            $blocks = preg_replace(
                '/color:var\(\-\-wp\-\-preset\-\-color\-\-' . $color . '\)/',
                'color:var(--wp--preset--color--' . $color . ')',
                $blocks
            );
        }

        // Background color classes
        $blocks = str_replace('has-tertiary-background-color', 'has-tertiary-background-color', $blocks);
        
        return $blocks;
    }

    /**
     * Normalize whitespace for consistent comparison.
     */
    public function normalizeWhitespace(string $blocks): string
    {
        // Collapse multiple spaces to single space
        $blocks = preg_replace('#\s+#', ' ', $blocks);
        
        // Normalize line endings
        $blocks = str_replace(["\r\n", "\r"], "\n", $blocks);
        
        // Remove leading/trailing whitespace from lines
        $blocks = preg_replace('#^[ \t]+#m', '', $blocks);
        $blocks = preg_replace('#[ \t]+$#m', '', $blocks);
        
        return trim($blocks);
    }

    /**
     * Normalize HTML entities for consistent comparison.
     */
    public function normalizeHtmlEntities(string $blocks): string
    {
        // Decode HTML entities to their character form for comparison
        $blocks = html_entity_decode($blocks, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $blocks;
    }

    /**
     * Extract the outermost block type from block markup.
     */
    public function extractOuterBlockType(string $blocks): ?string
    {
        if (preg_match('/<!--\s*wp:(\w+)/', $blocks, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Check if block markup contains WooCommerce blocks.
     */
    public function hasWooCommerceBlocks(string $blocks): bool
    {
        return str_contains($blocks, 'woocommerce/');
    }
}
