<?php

declare(strict_types=1);

namespace Imagewize\PtCli\PatternDiff;

/**
 * Merges Gutenberg clipboard HTML back into a PHP pattern file while
 * preserving all PHP translation wrappers (esc_html_e, esc_attr_e,
 * get_template_directory_uri, wp_kses_post) from the original.
 *
 * Strategy:
 *  1. Extract the PHP docblock header from the original file.
 *  2. Build a text → PHP-call map from every translation wrapper in the file.
 *  3. Apply structural fixes to the clipboard (strip editor-only attrs,
 *     normalise font-size slugs, remove nested <p> copy artefacts).
 *  4. Walk every text node in the fixed clipboard and restore the PHP call
 *     from the map; generate a new esc_html_e() wrapper for any new text.
 *  5. Prepend the original PHP header and return the merged result.
 */
class PatternSyncer
{
    private const FONT_SIZE_SLUGS = ['xx-small', 'x-small', 'small', 'base', 'medium', 'large', 'x-large', 'xx-large'];

    // --- public API ----------------------------------------------------------

    /**
     * Merge clipboard into file content, preserving PHP.
     */
    public function sync(string $clipboard, string $fileContent): string
    {
        $header    = $this->extractHeader($fileContent);
        $fileBlocks = $this->stripHeader($fileContent);
        $phpMap    = $this->buildPhpCallsMap($fileBlocks);

        $synced = $this->applyStructuralFixes($clipboard);
        $synced = $this->restorePhpCalls($synced, $phpMap);

        return $header . $synced . "\n";
    }

    // --- header extraction ---------------------------------------------------

    /**
     * Return everything up to and including the closing PHP tag of the docblock.
     */
    public function extractHeader(string $fileContent): string
    {
        // Match opening PHP tag, optional docblock comment, closing PHP tag
        if (preg_match('/^(<\?php\s.*?\?' . '>)\s*/s', $fileContent, $m)) {
            return $m[1] . "\n";
        }
        return '';
    }

    /**
     * Return block content only (strip PHP header).
     */
    public function stripHeader(string $fileContent): string
    {
        if (preg_match('/^<\?php\s.*?\?' . '>\s*/s', $fileContent, $m)) {
            return trim(substr($fileContent, strlen($m[0])));
        }
        return trim($fileContent);
    }

    // --- PHP call map --------------------------------------------------------

    /**
     * Build a map of plain-text → full PHP call for all translation/URL wrappers
     * found in the original file blocks.
     *
     * Handles:
     *   <?php esc_html_e( 'text', 'domain' ); ?>
     *   <?php esc_attr_e( 'text', 'domain' ); ?>
     *   <?php echo wp_kses_post( __( 'text', 'domain' ) ); ?>
     *   <?php echo esc_url( get_template_directory_uri() ); ?>/path/to/image.jpg
     */
    public function buildPhpCallsMap(string $fileBlocks): array
    {
        $map = [];

        // esc_html_e / esc_attr_e with single-quoted strings
        $pattern = '/(<\?php\s+(?:esc_html_e|esc_attr_e)\s*\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'[^)]*\)\s*;\s*\?>)/';
        if (preg_match_all($pattern, $fileBlocks, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $map[stripslashes($m[2])] = $m[1];
            }
        }

        // esc_html_e / esc_attr_e with double-quoted strings
        $pattern = '/(<\?php\s+(?:esc_html_e|esc_attr_e)\s*\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*,\s*"[^"]*"\s*\)\s*;\s*\?>)/';
        if (preg_match_all($pattern, $fileBlocks, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $map[stripslashes($m[2])] = $m[1];
            }
        }

        // wp_kses_post with single-quoted strings
        $pattern = '/(<\?php\s+echo\s+wp_kses_post\s*\(\s*__\s*\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'[^)]*\)\s*\)\s*;\s*\?>)/';
        if (preg_match_all($pattern, $fileBlocks, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $map[stripslashes($m[2])] = $m[1];
            }
        }

        return $map;
    }

    // --- structural fixes ----------------------------------------------------

    /**
     * Strip editor-only attributes and normalise CSS values in clipboard HTML.
     */
    public function applyStructuralFixes(string $clipboard): string
    {
        // 1. Remove __privatePreviewState (WooCommerce editor-only state)
        $clipboard = $this->removeJsonAttribute($clipboard, '__privatePreviewState');

        // 2. Fix font-size slug values → CSS variable in inline styles
        foreach (self::FONT_SIZE_SLUGS as $slug) {
            // Handles: font-size:small; and font-size:small" (end of style attr)
            $clipboard = preg_replace(
                '/\bfont-size:' . preg_quote($slug, '/') . '([;"])/',
                'font-size:var(--wp--preset--font-size--' . $slug . ')$1',
                $clipboard
            );
        }

        // 3. Remove nested <p> copy artefact: <p attrs><p attrs>text</p></p> → inner only
        $clipboard = preg_replace(
            '/<p([^>]*)>\s*(<p[^>]*>.*?<\/p>)\s*<\/p>/s',
            '$2',
            $clipboard
        );

        return $clipboard;
    }

    // --- PHP restoration -----------------------------------------------------

    /**
     * Walk every text node in $html and replace raw text with the PHP call
     * from $phpMap (or generate a new esc_html_e() wrapper for new strings).
     */
    public function restorePhpCalls(string $html, array $phpMap): string
    {
        return preg_replace_callback(
            '/>([^<>]+)</',
            function (array $m) use ($phpMap): string {
                $raw  = $m[1];
                $text = trim($raw);

                if ($text === '') {
                    return $m[0];
                }

                // Known PHP call from original file
                if (isset($phpMap[$text])) {
                    return '>' . $phpMap[$text] . '<';
                }

                // New translatable text — generate wrapper
                if ($this->isTranslatable($text)) {
                    return '>' . $this->generateWrapper($text) . '<';
                }

                return $m[0];
            },
            $html
        );
    }

    // --- helpers -------------------------------------------------------------

    /**
     * Remove a named JSON attribute (and its value) from all block comment JSON.
     * Handles flat objects: "key":{...} where the value has no nested {}.
     */
    private function removeJsonAttribute(string $content, string $key): string
    {
        // Remove trailing-comma form: ,"key":{...}
        $content = preg_replace('/(,\s*"' . preg_quote($key, '/') . '":\{[^{}]*\})/', '', $content);
        // Remove leading-comma form: "key":{...},
        $content = preg_replace('/("' . preg_quote($key, '/') . '":\{[^{}]*\},\s*)/', '', $content);
        // Remove standalone (only attribute): "key":{...}
        $content = preg_replace('/("' . preg_quote($key, '/') . '":\{[^{}]*\})/', '', $content);
        return $content;
    }

    /**
     * Determine whether a text node should receive a translation wrapper.
     */
    private function isTranslatable(string $text): bool
    {
        if (strlen(trim($text)) <= 1) {
            return false;
        }
        if (!preg_match('/[a-zA-Z]/', $text)) {
            return false;
        }
        // Pure numbers / punctuation / symbols
        if (preg_match('/^[0-9\s\p{P}\p{S}]+$/u', $text)) {
            return false;
        }
        // CSS values
        if (preg_match('/^(var\(|#|rgb\(|\d+(px|em|rem|vh|vw|%)?)/', $text)) {
            return false;
        }
        return true;
    }

    /**
     * Generate an esc_html_e() wrapper for a new string not in the original file.
     */
    public function generateWrapper(string $text): string
    {
        $escaped = str_replace("'", "\\'", $text);
        return '<?php esc_html_e( \'' . $escaped . '\', \'elayne\' ); ' . '?>';
    }
}
