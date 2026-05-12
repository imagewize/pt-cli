<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

/**
 * Compliance rules for FSE HTML template and template-part files.
 *
 * Checks .html files in templates/ and parts/ directories for issues
 * that PHP pattern compliance cannot detect: WooCommerce save-function
 * drift (missing div wrappers, CSS custom properties) and structural
 * mismatches introduced by plugin version updates.
 */
class TemplateRules extends AbstractRuleSet
{
    /**
     * WooCommerce filter sub-blocks that require an HTML div wrapper.
     * Key = block name, value = first CSS class the div must have.
     *
     * WooCommerce 9.x+ changed save() to output an empty div wrapper for
     * each of these blocks. Templates written against older versions have
     * self-closing block comments with no corresponding HTML, causing
     * client-side block validation errors.
     */
    private const WOO_FILTER_BLOCKS = [
        'woocommerce/product-filter-active'        => 'wp-block-woocommerce-product-filter-active',
        'woocommerce/product-filter-chips'         => 'wp-block-woocommerce-product-filter-chips',
        'woocommerce/product-filter-checkbox-list' => 'wp-block-woocommerce-product-filter-checkbox-list',
        'woocommerce/product-filter-price-slider'  => 'wp-block-woocommerce-product-filter-price-slider',
        'woocommerce/product-filter-price'         => 'wp-block-woocommerce-product-filter-price',
        'woocommerce/product-filter-attribute'     => 'wp-block-woocommerce-product-filter-attribute',
    ];

    /** HTML tags tracked for balance checks (mirrors BaseRules). */
    private const BALANCED_TAGS = [
        'div', 'ul', 'ol', 'li', 'figure', 'figcaption',
        'section', 'article', 'header', 'footer', 'nav', 'aside', 'main', 'p',
    ];

    public function getName(): string
    {
        return 'template';
    }

    public function isAutofixable(): bool
    {
        return true;
    }

    public function check(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $violations = [];
        $violations = array_merge($violations, $this->checkTaxQueryObject($content));
        $violations = array_merge($violations, $this->checkWooFilterMissingWrappers($content));
        $violations = array_merge($violations, $this->checkWooProductFiltersCssVars($content));
        $violations = array_merge($violations, $this->checkTemplatePartTheme($content));
        $violations = array_merge($violations, $this->checkUnbalancedHtmlTags($content));

        return $violations;
    }

    public function autofix(string $content, array &$applied = []): string
    {
        $content = $this->autofixTaxQueryObject($content, $applied);
        $content = $this->autofixTemplatePartTheme($content, $applied);
        return $content;
    }

    // ── Individual checks ──────────────────────────────────────────────

    /**
     * taxQuery must be [] (array) not {} (object).
     *
     * WordPress normalises {} → [] during parse_blocks/serialize_blocks,
     * triggering a "block structure needs normalization" warning from
     * wp-cli-pattern-validate and occasional editor save loops.
     */
    private function checkTaxQueryObject(string $content): array
    {
        if (!str_contains($content, '"taxQuery":{}')) {
            return [];
        }

        return [$this->violation(
            'taxQuery-object',
            '"taxQuery":{} uses an object — must be [] (array); causes WP-CLI "block structure needs normalization" and editor save loops',
            null,
            'error'
        )];
    }

    /**
     * WooCommerce filter sub-blocks must have an HTML div wrapper.
     *
     * WooCommerce 9.x+ changed the save() function for these blocks to
     * output an empty <div class="wp-block-woocommerce-..."></div> wrapper.
     * Templates using the old self-closing form (<!-- wp:block /-->) or open
     * blocks with no following div trigger client-side block validation errors.
     */
    private function checkWooFilterMissingWrappers(string $content): array
    {
        $violations = [];
        $lines = explode("\n", $content);

        foreach (self::WOO_FILTER_BLOCKS as $blockName => $expectedClass) {
            $shortName = substr($blockName, strpos($blockName, '/') + 1);
            $escapedName = preg_quote($blockName, '/');

            foreach ($lines as $i => $line) {
                $lineNum = $i + 1;

                // Self-closing: <!-- wp:block-name {attrs} /--> — always missing wrapper
                if (preg_match('/<!--\s*wp:' . $escapedName . '(?:\s+\{[^}]*\})?\s*\/-->/', $line)) {
                    $violations[] = $this->violation(
                        'woo-filter-missing-wrapper',
                        "{$blockName}: self-closing block comment has no HTML wrapper — change to <!-- wp:{$shortName} --><div class=\"{$expectedClass}\"></div><!-- /wp:{$shortName} --> (WooCommerce 9.x+ save() now outputs a wrapper div)",
                        $lineNum,
                        'error'
                    );
                    continue;
                }

                // Open form: <!-- wp:block-name {attrs} --> — check that a div follows
                if (preg_match('/<!--\s*wp:' . $escapedName . '(?:\s+\{[^}]*\})?\s*-->/', $line, $m, PREG_OFFSET_CAPTURE)) {
                    $commentEnd = $m[0][1] + strlen($m[0][0]);
                    $remainder  = substr($line, $commentEnd);
                    $nextLine   = $lines[$i + 1] ?? '';

                    $inlineDiv = ltrim($remainder);
                    $nextDiv   = ltrim($nextLine);

                    if (
                        !str_starts_with($inlineDiv, '<div class="' . $expectedClass) &&
                        !str_starts_with($nextDiv,   '<div class="' . $expectedClass)
                    ) {
                        $violations[] = $this->violation(
                            'woo-filter-missing-wrapper',
                            "{$blockName}: block comment not followed by <div class=\"{$expectedClass}\"> (WooCommerce 9.x+ save() now outputs a wrapper div)",
                            $lineNum,
                            'error'
                        );
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * woocommerce/product-filters div must carry WooCommerce CSS custom properties.
     *
     * WooCommerce 9.x+ added --wc-product-filters-text-color and
     * --wc-product-filters-background-color to the save() output even when
     * no explicit color is set. Templates missing these properties show a
     * client-side block validation error.
     */
    private function checkWooProductFiltersCssVars(string $content): array
    {
        if (!str_contains($content, 'wp:woocommerce/product-filters')) {
            return [];
        }

        if (
            str_contains($content, 'wp-block-woocommerce-product-filters') &&
            !str_contains($content, '--wc-product-filters-text-color')
        ) {
            return [$this->violation(
                'woo-product-filters-css-vars',
                'woocommerce/product-filters: div wrapper missing WooCommerce CSS custom properties — add style="--wc-product-filters-text-color:#111;--wc-product-filters-background-color:#fff;--wc-product-filter-block-spacing:0" (added in WooCommerce 9.x+)',
                null,
                'error'
            )];
        }

        return [];
    }

    /**
     * wp:template-part blocks must declare the theme they belong to.
     *
     * Without "theme":"<slug>", WordPress may resolve template parts from a
     * different theme on multisite setups, causing unexpected rendering.
     * Only active when requireThemeAttribute is set in config.
     */
    private function checkTemplatePartTheme(string $content): array
    {
        $rules = $this->config->getRules();
        if (!($rules['requireThemeAttribute'] ?? false)) {
            return [];
        }

        $themeSlug = $this->config->getThemeSlug();
        if (!$themeSlug) {
            return [];
        }

        $violations = [];
        preg_match_all('/<!--\s*wp:template-part\s*(\{[^}]+\})/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $decoded = json_decode($match[1], true);
            if (!isset($decoded['theme']) || $decoded['theme'] !== $themeSlug) {
                $slug = $decoded['slug'] ?? '(unknown)';
                $violations[] = $this->violation(
                    'template-part-theme',
                    "wp:template-part \"{$slug}\" missing \"theme\":\"{$themeSlug}\" attribute — WordPress may resolve the wrong part on multisite",
                    null,
                    'warning'
                );
            }
        }

        return $violations;
    }

    /**
     * Opening and closing HTML tags must be balanced.
     *
     * Mirrors the same check in BaseRules so template HTML files get the
     * same structural safety net as PHP pattern files.
     */
    private function checkUnbalancedHtmlTags(string $content): array
    {
        $violations = [];

        foreach (self::BALANCED_TAGS as $tag) {
            $opens  = preg_match_all('/<' . $tag . '(?:\s[^>]*)?>/', $content);
            $closes = preg_match_all('/<\/' . $tag . '>/', $content);

            if ($opens !== $closes) {
                $violations[] = $this->violation(
                    'unbalanced-html-tags',
                    "Unbalanced <{$tag}> tags: {$opens} opening, {$closes} closing",
                    null,
                    'error'
                );
            }
        }

        return $violations;
    }

    // ── Autofixes ──────────────────────────────────────────────────────

    private function autofixTaxQueryObject(string $content, array &$applied): string
    {
        if (!str_contains($content, '"taxQuery":{}')) {
            return $content;
        }

        $new = str_replace('"taxQuery":{}', '"taxQuery":[]', $content);
        if ($new !== $content) {
            $count = substr_count($content, '"taxQuery":{}');
            $applied[] = "replace taxQuery:{} → taxQuery:[] ({$count} occurrence" . ($count > 1 ? 's' : '') . ')';
        }

        return $new;
    }

    private function autofixTemplatePartTheme(string $content, array &$applied): string
    {
        $rules = $this->config->getRules();
        if (!($rules['requireThemeAttribute'] ?? false)) {
            return $content;
        }

        $themeSlug = $this->config->getThemeSlug();
        if (!$themeSlug) {
            return $content;
        }

        $count = 0;

        $new = preg_replace_callback(
            '/<!--\s*wp:template-part\s*(\{[^}]+\})\s*(\/?)-->/s',
            function (array $m) use ($themeSlug, &$count): string {
                $decoded = json_decode($m[1], true);
                if (isset($decoded['theme'])) {
                    return $m[0];
                }

                // Rebuild JSON: slug first, then theme, then remaining keys
                $reordered = [];
                if (isset($decoded['slug'])) {
                    $reordered['slug'] = $decoded['slug'];
                }
                $reordered['theme'] = $themeSlug;
                foreach ($decoded as $k => $v) {
                    if ($k !== 'slug' && $k !== 'theme') {
                        $reordered[$k] = $v;
                    }
                }

                $newJson = json_encode($reordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $selfClose = $m[2] ? ' /' : '';
                ++$count;

                return "<!-- wp:template-part {$newJson}{$selfClose}-->";
            },
            $content
        );

        if ($count > 0) {
            $applied[] = "add \"theme\":\"{$themeSlug}\" to wp:template-part ({$count} block" . ($count > 1 ? 's' : '') . ')';
        }

        return $new ?? $content;
    }
}
