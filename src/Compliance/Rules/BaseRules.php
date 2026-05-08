<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Compliance\Rules;

class BaseRules extends AbstractRuleSet
{
    private const FONT_SIZE_SLUGS = ['xx-small', 'x-small', 'small', 'base', 'medium', 'large', 'x-large', 'xx-large'];

    public function getName(): string
    {
        return 'base';
    }

    public function isAutofixable(): bool
    {
        return true;
    }

    public function check(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $patternName = basename($filePath);
        $isStructural = str_starts_with($patternName, 'template-')
            || str_starts_with($patternName, 'header-')
            || str_starts_with($patternName, 'footer-');

        $violations = [];

        $rules = $this->config->getRules();

        if ($rules['disallowed']['hardcodedFontSizes'] ?? true) {
            $violations = array_merge($violations, $this->checkHardcodedFontSizes($content));
        }

        if ($rules['disallowed']['spacerBlocks'] ?? true) {
            $violations = array_merge($violations, $this->checkSpacerBlocks($content));
        }

        if ($rules['requireThemeAttribute'] ?? false) {
            $violations = array_merge($violations, $this->checkTemplatePartTheme($content));
        }

        if ($rules['disallowed']['emojiIcons'] ?? false) {
            $violations = array_merge($violations, $this->checkEmojiIcons($content));
        }

        $violations = array_merge($violations, $this->checkHtmlCommentsBetweenTags($content));
        $violations = array_merge($violations, $this->checkAlignfullMarginReset($content));

        if (!$isStructural) {
            $violations = array_merge($violations, $this->checkColumnsThreePlus($content));
        }

        if ($rules['disallowed']['hardcodedMediaIds'] ?? true) {
            $violations = array_merge($violations, $this->checkHardcodedMediaIds($content));
        }

        $violations = array_merge($violations, $this->checkCustomDomainEmails($content));
        $violations = array_merge($violations, $this->checkExternalUrls($content));
        $violations = array_merge($violations, $this->checkButtonRootFontSize($content));
        $violations = array_merge($violations, $this->checkButtonClassNameOrder($content));
        $violations = array_merge($violations, $this->checkCoverBlockMinHeight($content));
        $violations = array_merge($violations, $this->checkEmptyBorderSideObjects($content));
        $violations = array_merge($violations, $this->checkButtonsWrapper($content));
        $violations = array_merge($violations, $this->checkGroupOverflowHidden($content));
        $violations = array_merge($violations, $this->checkOpacityInlineStyle($content));
        $violations = array_merge($violations, $this->checkFontPresetClasses($content));
        $violations = array_merge($violations, $this->checkBlockGapInlineStyles($content));
        $violations = array_merge($violations, $this->checkUntranslatedStrings($content));

        if ($rules['requirePatternName'] ?? false) {
            $isTemplate = str_starts_with($patternName, 'template-');
            $violations = array_merge($violations, $this->checkPatternName($content, $isTemplate));
        }

        return $violations;
    }

    public function autofix(string $content, array &$applied = []): string
    {
        $content = $this->autofixStripInlineGap($content, $applied);
        $content = $this->autofixStripInlineMargin($content, $applied);
        $content = $this->autofixButtonAttributeOrder($content, $applied);
        $content = $this->autofixButtonFontSize($content, $applied);
        $content = $this->autofixInjectFontSizeClass($content, $applied);
        return $content;
    }

    private function checkHardcodedFontSizes(string $content): array
    {
        if (preg_match('/(font-size):\s*\d+\.?\d*(px|rem|em)/', $content)) {
            return [$this->violation('hardcoded-font-sizes', 'Hardcoded CSS values found (use semantic variables)')];
        }
        return [];
    }

    private function checkSpacerBlocks(string $content): array
    {
        if (str_contains($content, '<!-- wp:spacer ')) {
            return [$this->violation('spacer-blocks', 'Spacer blocks found (use blockGap instead)')];
        }
        return [];
    }

    private function checkTemplatePartTheme(string $content): array
    {
        $themeSlug = $this->config->getThemeSlug();
        foreach (explode("\n", $content) as $line) {
            if (str_contains($line, '<!-- wp:template-part') && !str_contains($line, '"theme":')) {
                return [$this->violation('template-part-theme', 'wp:template-part missing "theme":"' . $themeSlug . '" attribute — WordPress may resolve to wrong theme parts in multisite')];
            }
        }
        return [];
    }

    private function checkEmojiIcons(string $content): array
    {
        if (preg_match('/[🅿🍽📶♿🎉🍷🅿️🍽️📶️♿️🎉️🍷️]/u', $content)) {
            return [$this->violation('emoji-icons', 'Emoji icons found (use SVG icons instead)')];
        }
        return [];
    }

    private function checkHtmlCommentsBetweenTags(string $content): array
    {
        if (preg_match('/<div[^>]*>\s+<!--\s*(?!\/?\s*wp:)[^-]/', $content)) {
            return [$this->violation('html-comments-between-tags', 'HTML comments between opening tags and block comments (causes validation errors)')];
        }
        return [];
    }

    private function checkAlignfullMarginReset(string $content): array
    {
        if (
            str_contains($content, 'alignfull')
            && !str_contains($content, '"margin":{"top":"0","bottom":"0"}')
            && !str_contains($content, 'margin-top:0;margin-bottom:0')
        ) {
            return [$this->violation('alignfull-margin-reset', 'Full-width pattern missing margin reset')];
        }
        return [];
    }

    private function checkColumnsThreePlus(string $content): array
    {
        if (!str_contains($content, 'wp:columns')) {
            return [];
        }

        $lines = explode("\n", $content);
        $depth = 0;
        $currentBlockLines = [];
        $topLevelBlocks = [];

        foreach ($lines as $line) {
            if (str_contains($line, '<!-- wp:columns')) {
                if ($depth === 0) {
                    $currentBlockLines = [];
                }
                $currentBlockLines[] = $line;
                $depth++;
            } elseif (str_contains($line, '<!-- /wp:columns -->')) {
                $currentBlockLines[] = $line;
                $depth--;
                if ($depth === 0) {
                    $topLevelBlocks[] = implode("\n", $currentBlockLines);
                }
            } elseif ($depth > 0) {
                $currentBlockLines[] = $line;
            }
        }

        foreach ($topLevelBlocks as $block) {
            $blockLines = explode("\n", $block);
            $colDepth = 0;
            $directColumns = 0;

            foreach ($blockLines as $line) {
                if (str_contains($line, '<!-- wp:columns')) {
                    $colDepth++;
                } elseif (str_contains($line, '<!-- /wp:columns -->')) {
                    $colDepth--;
                } elseif (str_contains($line, '<!-- wp:column') && $colDepth === 1) {
                    $directColumns++;
                }
            }

            if ($directColumns >= 3) {
                return [$this->violation('columns-three-plus', 'Using wp:columns for 3+ columns instead of responsive grid layout (use minimumColumnWidth)')];
            }
        }

        return [];
    }

    private function checkHardcodedMediaIds(string $content): array
    {
        if (preg_match('/"id":\d+/', $content)) {
            return [$this->violation('hardcoded-media-ids', 'Hardcoded media IDs found')];
        }
        return [];
    }

    private function checkCustomDomainEmails(string $content): array
    {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@(?!example\.com)[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $content)) {
            return [$this->violation('custom-domain-emails', 'Custom domain email found — use example@example.com instead (WP.org requirement)')];
        }
        return [];
    }

    private function checkExternalUrls(string $content): array
    {
        if (preg_match('/src=["\']https?:\/\//', $content)) {
            return [$this->violation('external-urls', 'Hardcoded external URL in src attribute — use get_template_directory_uri() instead')];
        }
        return [];
    }

    private function checkButtonRootFontSize(string $content): array
    {
        $slugPattern = implode('|', self::FONT_SIZE_SLUGS);
        if (preg_match('/<!-- wp:button\s[^\n]*"fontSize":"(?:' . $slugPattern . ')"/', $content)) {
            return [$this->violation('button-root-font-size', 'wp:button has root-level fontSize — use "style":{"typography":{"fontSize":"var:preset|font-size|base"}} instead; root fontSize on wp:button causes block validation errors')];
        }
        return [];
    }

    private function checkButtonClassNameOrder(string $content): array
    {
        if (preg_match_all('/<!-- wp:button\s+(\{[^\n]+\})\s*(?:\/)?-->/', $content, $matches)) {
            foreach ($matches[1] as $btnJson) {
                $stylePos = strpos($btnJson, '"style":');
                $classPos = strpos($btnJson, '"className":');
                if ($stylePos !== false && $classPos !== false && $stylePos < $classPos) {
                    return [$this->violation('button-classname-order', 'wp:button has className after style in JSON — move className before style (root-level attributes must come first)')];
                }
            }
        }
        return [];
    }

    private function checkCoverBlockMinHeight(string $content): array
    {
        foreach (explode("\n", $content) as $line) {
            if (!str_contains($line, '<!-- wp:cover')) {
                continue;
            }
            if (preg_match('/"minHeight":"[0-9]+px"/', $line) && !preg_match('/"minHeight":[0-9]+[,}]/', $line)) {
                return [$this->violation('cover-block-min-height', 'Cover block has style.dimensions.minHeight but missing root minHeight/minHeightUnit — rendered div will be missing min-height inline style, causing block validation failure')];
            }
        }
        return [];
    }

    private function checkEmptyBorderSideObjects(string $content): array
    {
        if (preg_match('/"border"\s*:\s*\{[^}]*"(?:right|bottom|left|top)"\s*:\s*\{\}/', $content)) {
            return [$this->violation('empty-border-side-objects', 'Empty border side objects {} found in block JSON — WordPress serializes these as [] causing a validation mismatch. Remove the empty sides entirely (e.g. "border":{"top":{...}} not "border":{"top":{...},"right":{}})')];
        }
        return [];
    }

    private function checkButtonsWrapper(string $content): array
    {
        $lines = explode("\n", $content);
        $total = count($lines);
        for ($bi = 0; $bi < $total; $bi++) {
            $bl = $lines[$bi];
            if (!str_contains($bl, '<!-- wp:buttons') || str_contains($bl, '<!-- /wp:buttons')) {
                continue;
            }
            $wrapperFound = false;
            for ($bj = $bi; $bj <= $bi + 2 && $bj < $total; $bj++) {
                if (str_contains($lines[$bj], 'wp-block-buttons')) {
                    $wrapperFound = true;
                    break;
                }
                if ($bj > $bi && str_contains($lines[$bj], '<!-- wp:button')) {
                    break;
                }
            }
            if (!$wrapperFound) {
                return [$this->violation('buttons-wrapper', 'wp:buttons block missing <div class="wp-block-buttons"> HTML wrapper — causes core/buttons save() mismatch and stray </div> tags that break surrounding layout')];
            }
        }
        return [];
    }

    private function checkGroupOverflowHidden(string $content): array
    {
        if (preg_match('/<div[^>]+class="[^"]*wp-block-group[^"]*"[^>]*style="[^"]*overflow:\s*hidden/', $content)) {
            return [$this->violation('group-overflow-hidden', 'Group block has overflow:hidden as inline style — not a WP block attribute, causes JSON/HTML validation mismatch. Move to a CSS class via className instead.')];
        }
        return [];
    }

    private function checkOpacityInlineStyle(string $content): array
    {
        if (preg_match('/<(?:p|div|h[1-6]|span)[^>]+style="[^"]*opacity:\s*0\.[0-9]+/', $content)) {
            return [$this->violation('opacity-inline-style', 'Element has opacity as inline style — not a standard WP block attribute, causes JSON/HTML validation mismatch. Use a className with CSS instead.')];
        }
        return [];
    }

    private function checkFontPresetClasses(string $content): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        $totalLines = count($lines);

        for ($i = 0; $i < $totalLines; $i++) {
            $line = $lines[$i];
            if (!str_contains($line, '<!-- wp:heading') && !str_contains($line, '<!-- wp:paragraph')) {
                continue;
            }

            foreach (self::FONT_SIZE_SLUGS as $slug) {
                if (!str_contains($line, '"fontSize":"' . $slug . '"')) {
                    continue;
                }
                if (str_contains($line, '"typography"')) {
                    continue;
                }
                $expectedClass = 'has-' . $slug . '-font-size';
                $foundClass = false;
                for ($j = $i + 1; $j <= $i + 3 && $j < $totalLines; $j++) {
                    $htmlLine = $lines[$j];
                    if (preg_match('/<(?:h[1-6]|p)[^>]*class="[^"]*' . preg_quote($expectedClass, '/') . '[^"]*"/', $htmlLine)) {
                        $foundClass = true;
                        break;
                    }
                    if (str_contains($htmlLine, '<!-- wp:')) {
                        break;
                    }
                }
                if (!$foundClass) {
                    $issueMsg = 'Block has root-level "fontSize":"' . $slug . '" but HTML element is missing class "' . $expectedClass . '" — causes block validation mismatch';
                    $exists = false;
                    foreach ($violations as $v) {
                        if ($v['message'] === $issueMsg) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $violations[] = $this->violation('font-preset-classes', $issueMsg);
                    }
                }
            }

            if (preg_match('/"fontFamily":"var:preset\|font-family\|([a-z0-9_-]+)"/', $line, $ffMatch)) {
                if (str_contains($line, '"typography"')) {
                    continue;
                }
                $ffSlug = $ffMatch[1];
                $expectedClass = 'has-' . str_replace([':', '|', '/'], '-', 'var:preset|font-family|' . $ffSlug) . '-font-family';
                $foundClass = false;
                for ($j = $i + 1; $j <= $i + 3 && $j < $totalLines; $j++) {
                    $htmlLine = $lines[$j];
                    if (preg_match('/<(?:h[1-6]|p)[^>]*class="[^"]*' . preg_quote($expectedClass, '/') . '[^"]*"/', $htmlLine)) {
                        $foundClass = true;
                        break;
                    }
                    if (str_contains($htmlLine, '<!-- wp:')) {
                        break;
                    }
                }
                if (!$foundClass) {
                    $issueMsg = 'Block has root-level fontFamily preset "' . $ffSlug . '" but HTML element is missing class "' . $expectedClass . '" — causes block validation mismatch';
                    $exists = false;
                    foreach ($violations as $v) {
                        if ($v['message'] === $issueMsg) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $violations[] = $this->violation('font-preset-classes', $issueMsg);
                    }
                }
            }
        }

        return $violations;
    }

    private function checkBlockGapInlineStyles(string $content): array
    {
        $violations = [];
        $lines = explode("\n", $content);
        $totalLines = count($lines);

        for ($i = 0; $i < $totalLines; $i++) {
            $line = $lines[$i];
            $isGroup = str_contains($line, '<!-- wp:group');
            $isColumns = str_contains($line, '<!-- wp:columns');

            if (str_contains($line, '<!-- wp:column ') || trim($line) === '<!-- wp:column-->') {
                continue;
            }
            if (!$isGroup && !$isColumns) {
                continue;
            }
            if (!preg_match('/"blockGap":"([^"]+)"/', $line, $bgMatch)) {
                continue;
            }
            $blockGapValue = $bgMatch[1];

            $layoutType = 'default';
            if ($isGroup && preg_match('/"layout":\s*\{"type":"([^"]+)"/', $line, $layoutMatch)) {
                $layoutType = $layoutMatch[1];
            }

            if (str_starts_with($blockGapValue, 'var:preset|')) {
                $cssGap = 'var(--wp--' . str_replace(['var:preset|', '|'], ['preset--', '--'], $blockGapValue) . ')';
            } else {
                $cssGap = $blockGapValue;
            }

            $hasInlineGap = false;
            $hasInlineMargin = false;
            for ($j = $i + 1; $j <= $i + 3 && $j < $totalLines; $j++) {
                $htmlLine = $lines[$j];
                $isGroupDiv = str_contains($htmlLine, '<div class="wp-block-group') || str_contains($htmlLine, "<div class='wp-block-group");
                $isColumnsDiv = str_contains($htmlLine, '<div class="wp-block-columns') || str_contains($htmlLine, "<div class='wp-block-columns");
                if ($isGroupDiv || $isColumnsDiv) {
                    if (str_contains($htmlLine, 'gap:')) {
                        $hasInlineGap = true;
                    }
                    if (str_contains($htmlLine, 'margin') && (str_contains($htmlLine, 'margin:') || str_contains($htmlLine, 'margin-'))) {
                        $hasInlineMargin = true;
                    }
                    break;
                }
                if (str_contains($htmlLine, '<!-- wp:')) {
                    break;
                }
            }

            if ($isGroup) {
                if ($hasInlineGap) {
                    $issueMsg = 'Group with layout:' . $layoutType . ' has inline gap: style — WordPress generates gap via CSS (wp-container-* for flex, layout CSS for others); remove gap: from HTML style attribute';
                    $exists = false;
                    foreach ($violations as $v) {
                        if ($v['message'] === $issueMsg) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $violations[] = $this->violation('blockgap-inline-styles', $issueMsg);
                    }
                }
            } elseif ($isColumns) {
                if ($hasInlineGap) {
                    $issueMsg = 'Columns block has inline gap: style but WordPress 6.6+ handles column gap via block CSS — remove gap: from HTML style attribute';
                    $exists = false;
                    foreach ($violations as $v) {
                        if ($v['message'] === $issueMsg) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $violations[] = $this->violation('blockgap-inline-styles', $issueMsg);
                    }
                }

                $hasInlineMarginColumns = false;
                for ($j = $i + 1; $j <= $i + 3 && $j < $totalLines; $j++) {
                    $htmlLine = $lines[$j];
                    $isColumnsDiv = str_contains($htmlLine, '<div class="wp-block-columns') || str_contains($htmlLine, "<div class='wp-block-columns");
                    if ($isColumnsDiv && str_contains($htmlLine, 'margin') && (str_contains($htmlLine, 'margin:') || str_contains($htmlLine, 'margin-'))) {
                        $hasInlineMarginColumns = true;
                        break;
                    }
                    if (str_contains($htmlLine, '<!-- wp:')) {
                        break;
                    }
                }
                if ($hasInlineMarginColumns) {
                    $issueMsg = 'Columns block has inline margin: style but WordPress 6.6+ handles column margins via layout CSS — remove margin: from HTML style attribute';
                    $exists = false;
                    foreach ($violations as $v) {
                        if ($v['message'] === $issueMsg) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $violations[] = $this->violation('blockgap-inline-styles', $issueMsg);
                    }
                }
            }
        }

        return $violations;
    }

    private function checkUntranslatedStrings(string $content): array
    {
        $textDomain = $this->config->getThemeSlug() ?: 'your-theme';
        $violations = [];
        $hasUntranslatedText = false;
        $hasUntranslatedAlt = false;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (str_contains($line, '<!-- wp:') || str_contains($line, '<!-- /wp:')) {
                continue;
            }
            if (str_starts_with(trim($line), '$')) {
                continue;
            }
            if (str_contains($line, '<?php')) {
                $htmlOnly = preg_replace('/<\?php.*?\?>/s', '', $line);
                $htmlOnly = preg_replace('/&[a-zA-Z#][a-zA-Z0-9]*;/', '', $htmlOnly);
                if (preg_match('/<(?:h[1-6]|p|a|li|button|span|figcaption|cite|blockquote|td|th|dt|dd)[^>]*>[^<{?\n]*[a-zA-Z]{3,}[^<{?\n]*<\//', $htmlOnly)) {
                    $hasUntranslatedText = true;
                }
                continue;
            }
            $cleanLine = preg_replace('/&[a-zA-Z#][a-zA-Z0-9]*;/', '', $line);
            if (preg_match('/<(?:h[1-6]|p|a|li|button|span|figcaption|cite|blockquote|td|th|dt|dd)[^>]*>[^<{?\n]*[a-zA-Z]{3,}[^<{?\n]*<\//', $cleanLine)) {
                $hasUntranslatedText = true;
            }
            if (preg_match('/alt="[A-Za-z][^"]*"/', $line)) {
                $hasUntranslatedAlt = true;
            }
        }

        if ($hasUntranslatedText) {
            $violations[] = $this->violation('untranslated-strings', 'Untranslated text strings in HTML tags — wrap with esc_html_e( \'Text\', \'' . $textDomain . '\' )');
        }
        if ($hasUntranslatedAlt) {
            $violations[] = $this->violation('untranslated-strings', 'Untranslated alt attribute text — use alt="<?php echo esc_attr__( \'Desc\', \'' . $textDomain . '\' ); ?>"');
        }

        return $violations;
    }

    private function checkPatternName(string $content, bool $isTemplate): array
    {
        if ($isTemplate) {
            return [];
        }

        $prefix = $this->config->getPatternPrefix();
        $slugHint = '';
        if (preg_match('/\*\s+Slug:\s+' . preg_quote($prefix, '/') . '([^\s]+)/', $content, $slugM)) {
            $slugHint = ' — expected: "metadata":{"patternName":"' . $prefix . trim($slugM[1]) . '","name":"..."} on outermost block';
        }

        $firstBlockJson = '';
        if (preg_match('/<!--\s*wp:[a-z\/-]+\s+(\{[^\n]+\})\s*(?:\/)?-->/', $content, $fbM)) {
            $firstBlockJson = $fbM[1];
        }

        $hasPatternNameAnywhere = (bool) preg_match('/"patternName"\s*:\s*"' . preg_quote($prefix, '/') . '[^"]*"/', $content);
        $hasMetadataAnywhere = (bool) preg_match('/"metadata"\s*:\s*\{[^}]*"patternName"\s*:\s*"' . preg_quote($prefix, '/') . '[^"]*"/', $content);
        $hasMetadataOnFirstBlock = $firstBlockJson !== ''
            && (bool) preg_match('/"metadata"\s*:\s*\{[^}]*"patternName"\s*:\s*"' . preg_quote($prefix, '/') . '[^"]*"/', $firstBlockJson);

        if (!$hasPatternNameAnywhere) {
            return [$this->violation('pattern-name', 'Missing "patternName" in block metadata' . $slugHint)];
        } elseif ($hasPatternNameAnywhere && !$hasMetadataAnywhere) {
            return [$this->violation('pattern-name', '"patternName" found at root block level but must be inside "metadata":{} — WordPress ignores root-level patternName (block shows as unnamed in editor)' . $slugHint)];
        } elseif ($hasMetadataAnywhere && !$hasMetadataOnFirstBlock) {
            return [$this->violation('pattern-name', '"patternName" found in inner block metadata but must be on the OUTERMOST block — WordPress only reads the name from the first block comment (outer block shows as unnamed in editor)' . $slugHint)];
        }

        return [];
    }

    private function autofixStripInlineGap(string $content, array &$applied): string
    {
        $lines = explode("\n", $content);
        $total = count($lines);
        $count = 0;

        for ($i = 0; $i < $total; $i++) {
            $line = $lines[$i];
            $isGroup = str_contains($line, '<!-- wp:group');
            $isColumns = str_contains($line, '<!-- wp:columns');
            if (str_contains($line, '<!-- wp:column ') || trim($line) === '<!-- wp:column-->') {
                continue;
            }
            if (!$isGroup && !$isColumns) {
                continue;
            }
            $marker = $isColumns ? 'wp-block-columns' : 'wp-block-group';
            $divIdx = $this->findBlockDivLine($lines, $i, $marker);
            if ($divIdx === false) {
                continue;
            }
            $newLine = $this->stripStyleProperty($lines[$divIdx], 'gap');
            if ($newLine !== $lines[$divIdx]) {
                $lines[$divIdx] = $newLine;
                $count++;
            }
        }

        if ($count > 0) {
            $applied[] = 'strip inline gap from all group layouts and columns (' . $count . ')';
            return implode("\n", $lines);
        }
        return $content;
    }

    private function autofixStripInlineMargin(string $content, array &$applied): string
    {
        $lines = explode("\n", $content);
        $total = count($lines);
        $count = 0;

        for ($i = 0; $i < $total; $i++) {
            $line = $lines[$i];
            $isGroup = str_contains($line, '<!-- wp:group');
            $isColumns = str_contains($line, '<!-- wp:columns');
            if (str_contains($line, '<!-- wp:column ') || trim($line) === '<!-- wp:column-->') {
                continue;
            }
            if (!$isGroup && !$isColumns) {
                continue;
            }
            $strip = false;
            if ($isColumns) {
                $strip = true;
            } elseif ($isGroup) {
                $layoutType = 'default';
                if (preg_match('/"layout":\s*\{"type":"([^"]+)"/', $line, $lm)) {
                    $layoutType = $lm[1];
                }
                if ($layoutType === 'flex') {
                    $strip = true;
                }
            }
            if (!$strip) {
                continue;
            }
            $marker = $isColumns ? 'wp-block-columns' : 'wp-block-group';
            $divIdx = $this->findBlockDivLine($lines, $i, $marker);
            if ($divIdx === false) {
                continue;
            }
            $divLine = $lines[$divIdx];
            $hasReset = str_contains($divLine, 'margin-top:0') && str_contains($divLine, 'margin-bottom:0');
            if ($hasReset) {
                continue;
            }
            $newLine = $this->stripStyleProperty($divLine, 'margin');
            $newLine = $this->stripStyleProperty($newLine, 'margin-top');
            $newLine = $this->stripStyleProperty($newLine, 'margin-right');
            $newLine = $this->stripStyleProperty($newLine, 'margin-bottom');
            $newLine = $this->stripStyleProperty($newLine, 'margin-left');
            if ($newLine !== $divLine) {
                $lines[$divIdx] = $newLine;
                $count++;
            }
        }

        if ($count > 0) {
            $applied[] = 'strip inline margin from flex groups and columns (' . $count . ')';
            return implode("\n", $lines);
        }
        return $content;
    }

    private function autofixButtonAttributeOrder(string $content, array &$applied): string
    {
        $count = 0;
        $out = preg_replace_callback(
            '/(<!-- wp:button\s+)(\{[^\n]+\})(\s*(?:\/)?-->)/',
            function ($m) use (&$count) {
                $json = $m[2];
                $data = json_decode($json, true);
                if (!is_array($data) || !isset($data['style'], $data['className'])) {
                    return $m[0];
                }
                $keys = array_keys($data);
                $styleIdx = array_search('style', $keys, true);
                $classIdx = array_search('className', $keys, true);
                if ($styleIdx === false || $classIdx === false || $classIdx < $styleIdx) {
                    return $m[0];
                }
                $rootFirst = ['className', 'backgroundColor', 'textColor', 'fontSize', 'fontFamily', 'gradient'];
                $reordered = [];
                foreach ($rootFirst as $rk) {
                    if (array_key_exists($rk, $data)) {
                        $reordered[$rk] = $data[$rk];
                    }
                }
                foreach ($data as $k => $v) {
                    if ($k === 'style') {
                        continue;
                    }
                    if (!array_key_exists($k, $reordered)) {
                        $reordered[$k] = $v;
                    }
                }
                $reordered['style'] = $data['style'];
                $newJson = json_encode($reordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $count++;
                return $m[1] . $newJson . $m[3];
            },
            $content
        );
        if ($count > 0) {
            $applied[] = 'reorder wp:button JSON keys (' . $count . ')';
            return $out;
        }
        return $content;
    }

    private function autofixButtonFontSize(string $content, array &$applied): string
    {
        $slugs = self::FONT_SIZE_SLUGS;
        $lines = explode("\n", $content);
        $total = count($lines);
        $count = 0;

        for ($i = 0; $i < $total; $i++) {
            $line = $lines[$i];
            if (!str_contains($line, '<!-- wp:button')) {
                continue;
            }

            $slug = null;
            foreach ($slugs as $s) {
                if (str_contains($line, '"fontSize":"' . $s . '"') && !str_contains($line, '"typography"')) {
                    $slug = $s;
                    break;
                }
            }
            if ($slug === null) {
                continue;
            }

            if (!preg_match('/(<!-- wp:button\s+)(\{[^\n]+\})(\s*(?:\/)?-->)/', $line, $m)) {
                continue;
            }
            $data = json_decode($m[2], true);
            if (!is_array($data) || !isset($data['fontSize'])) {
                continue;
            }

            unset($data['fontSize']);

            if (!isset($data['style']['typography']['fontSize'])) {
                $data['style'] ??= [];
                $data['style']['typography'] ??= [];
                $data['style']['typography']['fontSize'] = 'var:preset|font-size|' . $slug;
            }

            $lines[$i] = $m[1] . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $m[3];

            $cssVar = 'var(--wp--preset--font-size--' . $slug . ')';
            $slugClass = 'has-' . $slug . '-font-size';

            for ($j = $i + 1; $j <= $i + 3 && $j < $total; $j++) {
                $htmlLine = $lines[$j];
                if (str_contains($htmlLine, '<!-- wp:')) {
                    break;
                }
                if (!str_contains($htmlLine, 'wp-block-button')) {
                    continue;
                }

                $htmlLine = str_replace(' ' . $slugClass, '', $htmlLine);
                $htmlLine = str_replace($slugClass . ' ', '', $htmlLine);
                $htmlLine = str_replace($slugClass, '', $htmlLine);

                if (!str_contains($htmlLine, 'font-size:')) {
                    if (preg_match('/<a\b[^>]*\bstyle="/', $htmlLine)) {
                        $htmlLine = preg_replace(
                            '/(<a\b[^>]*\bstyle=")([^"]*)"/',
                            '$1$2;font-size:' . $cssVar . '"',
                            $htmlLine,
                            1
                        );
                    } elseif (str_contains($htmlLine, '<a ')) {
                        $htmlLine = preg_replace(
                            '/(<a\b)([^>]*)(>)/',
                            '$1$2 style="font-size:' . $cssVar . '"$3',
                            $htmlLine,
                            1
                        );
                    }
                }

                $lines[$j] = $htmlLine;
                $count++;
                break;
            }
        }

        if ($count > 0) {
            $applied[] = 'migrate wp:button root fontSize to style.typography.fontSize (' . $count . ')';
            return implode("\n", $lines);
        }
        return $content;
    }

    private function autofixInjectFontSizeClass(string $content, array &$applied): string
    {
        $slugs = self::FONT_SIZE_SLUGS;
        $lines = explode("\n", $content);
        $total = count($lines);
        $count = 0;

        for ($i = 0; $i < $total; $i++) {
            $line = $lines[$i];
            if (!str_contains($line, '<!-- wp:heading') && !str_contains($line, '<!-- wp:paragraph')) {
                continue;
            }
            foreach ($slugs as $slug) {
                if (!str_contains($line, '"fontSize":"' . $slug . '"')) {
                    continue;
                }
                if (str_contains($line, '"typography"')) {
                    continue;
                }
                $expected = 'has-' . $slug . '-font-size';
                for ($j = $i + 1; $j <= $i + 3 && $j < $total; $j++) {
                    $htmlLine = $lines[$j];
                    if (str_contains($htmlLine, '<!-- wp:')) {
                        break;
                    }
                    if (!preg_match('/<(?:h[1-6]|p)[^>]*>/', $htmlLine)) {
                        continue;
                    }
                    if (str_contains($htmlLine, $expected)) {
                        break;
                    }
                    if (preg_match('/<((?:h[1-6]|p))([^>]*)class="([^"]*)"/', $htmlLine)) {
                        $lines[$j] = preg_replace(
                            '/<((?:h[1-6]|p))([^>]*)class="([^"]*)"/',
                            '<$1$2class="$3 ' . $expected . '"',
                            $htmlLine,
                            1
                        );
                        $count++;
                    } elseif (preg_match('/<((?:h[1-6]|p))([^>]*)>/', $htmlLine)) {
                        $lines[$j] = preg_replace(
                            '/<((?:h[1-6]|p))([^>]*)>/',
                            '<$1$2 class="' . $expected . '">',
                            $htmlLine,
                            1
                        );
                        $count++;
                    }
                    break;
                }
            }
        }

        if ($count > 0) {
            $applied[] = 'inject has-{slug}-font-size class on heading/paragraph (' . $count . ')';
            return implode("\n", $lines);
        }
        return $content;
    }

    private function findBlockDivLine(array $lines, int $startIdx, string $marker): int|false
    {
        $total = count($lines);
        for ($j = $startIdx + 1; $j <= $startIdx + 3 && $j < $total; $j++) {
            $line = $lines[$j];
            if (str_contains($line, '<div class="' . $marker) || str_contains($line, "<div class='" . $marker)) {
                return $j;
            }
            if (str_contains($line, '<!-- wp:')) {
                return false;
            }
        }
        return false;
    }

    private function stripStyleProperty(string $line, string $property): string
    {
        return preg_replace_callback(
            '/\s?style="([^"]*)"/',
            function ($m) use ($property) {
                $style = $m[1];
                $pattern = '/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:[^;"]*;?/';
                $new = preg_replace($pattern, '', $style);
                $new = ltrim($new, ';');
                $new = rtrim($new, ';');
                if (trim($new) === '') {
                    return '';
                }
                return ' style="' . $new . '"';
            },
            $line,
            1
        );
    }
}
