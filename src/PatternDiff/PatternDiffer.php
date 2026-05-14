<?php

declare(strict_types=1);

namespace Imagewize\PtCli\PatternDiff;

use Imagewize\PtCli\Compliance\Config\ThemeConfig;

/**
 * Compares Gutenberg clipboard content with PHP pattern files.
 * Handles normalization of editor-specific attributes and generates
 * actionable diff reports.
 */
class PatternDiffer
{
    private ThemeConfig $config;
    private BlockNormalizer $normalizer;
    private TranslationDetector $translationDetector;
    private WooCommerceValidator $wcValidator;

    public function __construct(ThemeConfig $config)
    {
        $this->config = $config;
        $this->normalizer = new BlockNormalizer();
        $this->translationDetector = new TranslationDetector();
        $this->wcValidator = new WooCommerceValidator();
    }

    /**
     * Compare clipboard content with a pattern file.
     *
     * @return array{differences: array, suggestions: array, similarity: float}
     */
    public function diffClipboardWithFile(string $clipboard, string $filePath): array
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \RuntimeException("Cannot read file: {$filePath}");
        }

        // Extract just the block content (remove PHP header)
        $fileBlocks = $this->extractBlocksFromPhp($fileContent);
        
        // Normalize both for comparison
        $normalizedFile = $this->normalizer->normalize($fileBlocks);
        $normalizedClipboard = $this->normalizer->normalize($clipboard);

        // Calculate similarity
        $similarity = $this->calculateSimilarity($normalizedFile, $normalizedClipboard);

        // Find specific differences
        $differences = $this->findDifferences($fileBlocks, $clipboard);

        // Generate suggestions
        $suggestions = $this->generateSuggestions($clipboard);

        return [
            'differences' => $differences,
            'suggestions' => $suggestions,
            'similarity' => $similarity,
        ];
    }

    /**
     * Compare two pattern files directly.
     *
     * @return array{differences: array, suggestions: array, similarity: float}
     */
    public function diffFiles(string $filePath1, string $filePath2): array
    {
        $content1 = file_get_contents($filePath1);
        $content2 = file_get_contents($filePath2);

        if ($content1 === false || $content2 === false) {
            throw new \RuntimeException("Cannot read files");
        }

        $blocks1 = $this->extractBlocksFromPhp($content1);
        $blocks2 = $this->extractBlocksFromPhp($content2);

        $normalized1 = $this->normalizer->normalize($blocks1);
        $normalized2 = $this->normalizer->normalize($blocks2);

        $similarity = $this->calculateSimilarity($normalized1, $normalized2);

        $differences = $this->findDifferences($blocks1, $blocks2);
        $suggestions = [];

        return [
            'differences' => $differences,
            'suggestions' => $suggestions,
            'similarity' => $similarity,
        ];
    }

    /**
     * Extract block content from PHP pattern file (remove header).
     */
    public function extractBlocksFromPhp(string $content): string
    {
        // Remove PHP opening tag and docblock
        if (preg_match('#^\s*<?php\s*(?:/\*.*?\*/\s*)?\?>\s*#s', $content, $matches)) {
            $content = substr($content, strlen($matches[0]));
        }
        return trim($content);
    }

    /**
     * Calculate similarity between two strings (0-1).
     */
    private function calculateSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);
        return (float) ($percent / 100);
    }

    /**
     * Find all differences between file and clipboard content.
     *
     * @return array<array{type: string, message: string, severity: string, fix?: string}>
     */
    private function findDifferences(string $fileBlocks, string $clipboard): array
    {
        $differences = [];

        // Check for editor-specific attributes in clipboard
        $editorAttrs = $this->normalizer->detectEditorAttributes($clipboard);
        foreach ($editorAttrs as $attr) {
            $differences[] = [
                'type' => 'editor_attribute',
                'message' => "Editor-specific attribute detected: {$attr}",
                'severity' => 'info',
                'fix' => 'This will be stripped during normalization',
            ];
        }

        // Check for missing translation wrappers
        $missingTranslations = $this->translationDetector->findMissingTranslations($fileBlocks, $clipboard);
        foreach ($missingTranslations as $issue) {
            $differences[] = [
                'type' => 'translation',
                'message' => $issue['message'],
                'severity' => 'error',
                'fix' => $issue['fix'],
                'text' => $issue['text'],
            ];
        }

        // Check for WooCommerce issues
        $wcIssues = $this->wcValidator->validate($fileBlocks, $clipboard);
        foreach ($wcIssues as $issue) {
            $differences[] = [
                'type' => 'woocommerce',
                'message' => $issue['message'],
                'severity' => $issue['severity'],
                'fix' => $issue['fix'] ?? null,
            ];
        }

        // Check for CSS value differences
        $cssDiffs = $this->findCssDifferences($fileBlocks, $clipboard);
        foreach ($cssDiffs as $diff) {
            $differences[] = [
                'type' => 'css_value',
                'message' => $diff['message'],
                'severity' => 'warning',
                'fix' => $diff['fix'],
            ];
        }

        // Check for structural differences
        $structuralDiffs = $this->findStructuralDifferences($fileBlocks, $clipboard);
        foreach ($structuralDiffs as $diff) {
            $differences[] = [
                'type' => 'structural',
                'message' => $diff,
                'severity' => 'warning',
            ];
        }

        return $differences;
    }

    /**
     * Find CSS value differences that need normalization.
     */
    private function findCssDifferences(string $fileBlocks, string $clipboard): array
    {
        $differences = [];

        // Look for hardcoded font sizes in clipboard
        $hardcodedSizes = [
            'font-size:small',
            'font-size:medium', 
            'font-size:large',
            'font-size:x-large',
            'font-size:xx-large',
        ];

        foreach ($hardcodedSizes as $size) {
            if (str_contains($clipboard, $size) && !str_contains($fileBlocks, $size)) {
                $preset = str_replace('font-size:', '', $size);
                $differences[] = [
                    'message' => "Hardcoded font-size: {$size}",
                    'fix' => "Replace with: font-size:var(--wp--preset--font-size--{$preset})",
                ];
            }
        }

        return $differences;
    }

    /**
     * Find structural differences between blocks.
     */
    private function findStructuralDifferences(string $fileBlocks, string $clipboard): array
    {
        $differences = [];

        // Count block types
        $fileBlockTypes = $this->countBlockTypes($fileBlocks);
        $clipboardBlockTypes = $this->countBlockTypes($clipboard);

        foreach ($clipboardBlockTypes as $blockType => $count) {
            $fileCount = $fileBlockTypes[$blockType] ?? 0;
            if ($count !== $fileCount) {
                $differences[] = "Block type mismatch: {$blockType} (file: {$fileCount}, clipboard: {$count})";
            }
        }

        foreach ($fileBlockTypes as $blockType => $count) {
            if (!isset($clipboardBlockTypes[$blockType])) {
                $differences[] = "Block type missing in clipboard: {$blockType}";
            }
        }

        // Check for duplicate/nested tags
        if (preg_match('#<p[^>]*><p[^>]*>#', $clipboard)) {
            $differences[] = "Nested <p> tags detected in clipboard";
        }

        return $differences;
    }

    /**
     * Count occurrences of each block type.
     */
    private function countBlockTypes(string $content): array
    {
        $counts = [];
        if (preg_match_all('#<!--\s*wp:(\w+)#', $content, $matches)) {
            foreach ($matches[1] as $blockType) {
                $counts[$blockType] = ($counts[$blockType] ?? 0) + 1;
            }
        }
        return $counts;
    }

    /**
     * Generate suggestions for fixing differences.
     */
    private function generateSuggestions(string $clipboard): array
    {
        $suggestions = [];

        // Suggest translation wrappers for text content
        $textNodes = $this->extractTextNodes($clipboard);
        foreach ($textNodes as $text) {
            $text = trim($text);
            if ($text && strlen($text) > 1 && $this->isTranslatable($text)) {
                $escapedText = addcslashes($text, "'\\");
                $suggestions[] = "Wrap '{$text}' in translation: <?php esc_html_e( '{$escapedText}', 'elayne' ); ?>";
            }
        }

        // Suggest CSS variable normalization
        if (str_contains($clipboard, 'font-size:')) {
            $suggestions[] = "Normalize font-size values to use CSS variables (e.g., var(--wp--preset--font-size--small))";
        }

        // Suggest removing editor-only attributes
        if (str_contains($clipboard, 'metadata')) {
            $suggestions[] = "Remove 'metadata' attribute from outermost block";
        }

        // Check for hardcoded image URLs that should use PHP placeholders
        if (preg_match('#src=["\'](https?://[^"\']+/patterns/images/[^"\']+)#i', $clipboard)) {
            $suggestions[] = "Replace hardcoded image URLs with PHP placeholders: <?php echo esc_url( get_template_directory_uri() ); ?>/patterns/images/...";
        }

        // Check for hardcoded alt text
        if (preg_match('#alt=["\'][^"\']+["\']#', $clipboard)) {
            $suggestions[] = "Replace hardcoded alt text with translation: alt=\"?<?php esc_attr_e( '...', 'elayne' ); ?>\"";
        }

        return array_unique($suggestions);
    }

    /**
     * Extract text nodes from block content.
     */
    private function extractTextNodes(string $content): array
    {
        $nodes = [];
        if (preg_match_all('#>([^<]+)</#', $content, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim(strip_tags($text));
                if ($text) {
                    $nodes[] = $text;
                }
            }
        }
        return $nodes;
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
        if (!preg_match('#[a-zA-Z]#', $text)) {
            return false;
        }

        // Skip if it's just numbers and symbols
        if (preg_match('#^[0-9\s\p{P}\p{S}]+$#u', $text)) {
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
     * Check if a path is exempt from checking.
     */
    public function isExempt(string $path): bool
    {
        return str_contains($path, '/wp-content/plugins/woocommerce/patterns/');
    }
}
