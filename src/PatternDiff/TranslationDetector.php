<?php

declare(strict_types=1);

namespace Imagewize\PtCli\PatternDiff;

/**
 * Detects missing translation wrappers in block markup.
 * Compares clipboard content with file content to identify
 * text that needs to be wrapped in esc_html_e().
 */
class TranslationDetector
{
    private const TRANSLATION_PATTERN = '/<?php\s+esc_html_e\s*\(\s*[\'"](.*?)[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\)\s*;\s*?>/';
    private const TEXT_NODE_PATTERN = '/>([^<]+)</';
    private const PHP_TAG_PATTERN = '/<?php.*?>/s';

    /**
     * Find text nodes in clipboard that are missing translation wrappers in the file.
     *
     * @return array<array{message: string, text: string, fix: string}>
     */
    public function findMissingTranslations(string $fileBlocks, string $clipboard): array
    {
        $missing = [];

        // Extract text nodes from clipboard
        $clipboardTextNodes = $this->extractTextNodes($clipboard);

        // Extract translated strings from file
        $fileTranslations = $this->extractTranslations($fileBlocks);

        foreach ($clipboardTextNodes as $text) {
            $cleanText = $this->cleanText($text);
            
            // Skip empty or non-translatable text
            if (!$this->isTranslatable($cleanText)) {
                continue;
            }

            // Check if this text appears in file translations
            $found = false;
            foreach ($fileTranslations as $translation) {
                if ($this->stringsMatch($translation, $cleanText)) {
                    $found = true;
                    break;
                }
            }

            // Check if text appears anywhere in file blocks (might be already translated differently)
            if (!$found && str_contains($fileBlocks, $cleanText)) {
                // It exists in file, check if wrapped in PHP
                if (preg_match(self::TRANSLATION_PATTERN, $fileBlocks, $matches)) {
                    if ($this->stringsMatch($matches[1], $cleanText)) {
                        $found = true;
                    }
                }
            }

            if (!$found) {
                $missing[] = [
                    'message' => "Missing translation wrapper for: '{$cleanText}'",
                    'text' => $cleanText,
                    'fix' => $this->generateTranslationWrapper($cleanText),
                ];
            }
        }

        return $missing;
    }

    /**
     * Extract all translated strings from block content.
     */
    private function extractTranslations(string $content): array
    {
        $translations = [];

        if (preg_match_all(self::TRANSLATION_PATTERN, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $translations[] = $match[1];
            }
        }

        return $translations;
    }

    /**
     * Extract text nodes from block content.
     */
    private function extractTextNodes(string $content): array
    {
        $nodes = [];

        // First, remove PHP tags to avoid matching inside them
        $contentWithoutPhp = preg_replace(self::PHP_TAG_PATTERN, '', $content);

        if (preg_match_all('#>([^<]+)</#', $contentWithoutPhp, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($text) {
                    $nodes[] = $text;
                }
            }
        }

        return $nodes;
    }

    /**
     * Clean text by removing HTML tags and normalizing whitespace.
     */
    private function cleanText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('#\s+#', ' ', $text);
        
        return trim($text);
    }

    /**
     * Check if text appears to be translatable.
     */
    private function isTranslatable(string $text): bool
    {
        // Skip empty text
        if (strlen(trim($text)) === 0) {
            return false;
        }

        // Must contain at least one letter
        if (!preg_match('/[a-zA-Z]/', $text)) {
            return false;
        }

        // Skip if it's just numbers and symbols
        if (preg_match('/^[0-9\s\p{P}\p{S}]+$/u', $text)) {
            return false;
        }

        // Skip single characters (might be icons or abbreviations)
        if (strlen(trim($text)) <= 1) {
            return false;
        }

        // Skip if it looks like a CSS value
        if (preg_match('/^(var\(|#|rgb\(|\d+(px|em|rem|%)?)$/', $text)) {
            return false;
        }

        return true;
    }

    /**
     * Check if two strings match (case-insensitive, whitespace normalized).
     */
    private function stringsMatch(string $a, string $b): bool
    {
        $normalizedA = strtolower(preg_replace('#\s+#', ' ', trim($a)));
        $normalizedB = strtolower(preg_replace('#\s+#', ' ', trim($b)));
        
        return $normalizedA === $normalizedB;
    }

    /**
     * Generate a translation wrapper for the given text.
     */
    public function generateTranslationWrapper(string $text): string
    {
        // Escape single quotes in the text
        $escapedText = addcslashes($text, '\'\'');
        
        return '<?php esc_html_e( \'' . $escapedText . '\', \'elayne\' ); ?>';
    }

    /**
     * Check if a string is already wrapped in a translation function.
     */
    public function isTranslated(string $text, string $content): bool
    {
        // Check if the text appears inside a translation function
        $pattern = '/esc_html_e\s*\(\s*[\'"].*' . preg_quote($text, '/') . '.*[\'"]\s*,/s';
        return preg_match($pattern, $content) === 1;
    }

    /**
     * Extract all text that needs translation from clipboard content.
     *
     * @return array<string> List of text strings that should be translated
     */
    public function extractTranslatableText(string $clipboard): array
    {
        $textNodes = $this->extractTextNodes($clipboard);
        $translatable = [];

        foreach ($textNodes as $text) {
            $clean = $this->cleanText($text);
            if ($this->isTranslatable($clean)) {
                $translatable[] = $clean;
            }
        }

        return array_unique($translatable);
    }
}
