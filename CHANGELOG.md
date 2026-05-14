# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.1] - 2026-05-14

### Fixed

- `checkButtonWidthClasses`: false positive when `<!-- wp:button` appears on the same line as the outer `<div class="wp-block-buttons">` wrapper. The previous `str_contains` check matched `wp-block-buttons` as a substring of `wp-block-button`, incorrectly reporting missing `has-custom-width`/`wp-block-button__width-N` classes. Replaced with a word-boundary regex that distinguishes the singular `wp-block-button` (inner div) from the plural `wp-block-buttons` (wrapper).

## [2.3.0] - 2026-05-14

### Added

- **`pattern:diff` command** (`src/Commands/PatternDiffCommand.php`) — compares a Gutenberg clipboard paste against an existing PHP pattern file and reports differences (missing translations, editor-only attributes, CSS drift, WooCommerce block issues). Options:
  - `--from-stdin` — read Gutenberg HTML from stdin (pipe from `pbpaste`)
  - `--apply` — merge clipboard changes into the PHP file, preserving all PHP translation wrappers (`esc_html_e`, `esc_attr_e`, `wp_kses_post`, `get_template_directory_uri`)
  - `--dry-run` — with `--apply`: print the merged result to stdout instead of writing to disk
  - `--theme` — theme config to use (default: `elayne`)
  - `--json` — output diff results as JSON
  - `--similarity-threshold` — minimum block similarity score to consider a match (0–1, default: `0.95`)
  - `--show-suggestions` / `-s` — include fix suggestions in the output

- **`BlockNormalizer` class** (`src/PatternDiff/BlockNormalizer.php`) — normalises block HTML for comparison: strips editor-only attributes (`__privatePreviewState`, `isNotStackedOnMobile`, constrained layout flags, product-collection block flags), normalises CSS value tokens (font-size slugs → CSS variables, spacing presets), collapses whitespace, and decodes HTML entities. Exposes `detectEditorAttributes()`, `extractOuterBlockType()`, and `hasWooCommerceBlocks()` as public helpers.

- **`PatternDiffer` class** (`src/PatternDiff/PatternDiffer.php`) — calculates structural similarity between clipboard HTML and a PHP pattern file. Extracts block HTML from PHP, computes a similarity score via `similar_text`, and finds CSS, structural, and text-node differences. WooCommerce plugin paths (`wp-content/plugins/woocommerce/patterns/*`) are exempt via `isExempt()`.

- **`PatternSyncer` class** (`src/PatternDiff/PatternSyncer.php`) — implements the `--apply` merge strategy: extracts the PHP docblock header, builds a `text → PHP call` map from all translation wrappers in the original file, applies structural fixes to the clipboard (strips `__privatePreviewState`, normalises font-size slugs, removes nested `<p>` copy artefacts), then walks every HTML text node replacing raw strings with their original wrappers or generating new `esc_html_e()` calls for new strings.

- **`TranslationDetector` class** (`src/PatternDiff/TranslationDetector.php`) — finds text nodes in clipboard HTML that are not yet wrapped in a translation function in the existing PHP file. Generates `esc_html_e()` fix suggestions. Exposes `isTranslated()`, `generateTranslationWrapper()`, and `extractTranslatableText()` as public helpers.

- **`WooCommerceValidator` class** (`src/PatternDiff/WooCommerceValidator.php`) — validates WooCommerce-specific block structure in the diff context: checks `isDescendentOfQueryLoop` on WC blocks inside `product-template`, validates `<div>` wrappers and layout attributes, and inspects `product-collection` query metadata.

- **PHPUnit test suite for `PatternDiff`** — 6 new test files covering all public methods in the feature:
  - `tests/PatternDiff/BlockNormalizerTest.php` — 29 tests for all normalisation methods
  - `tests/PatternDiff/PatternDifferTest.php` — 5 tests (diff result shape, identical-file similarity, PHP extraction, exempt paths)
  - `tests/PatternDiff/PatternSyncerTest.php` — 22 tests (header extraction/stripping, PHP call map, structural fixes, translation restore, wrapper generation)
  - `tests/PatternDiff/TranslationDetectorTest.php` — 16 tests (missing translation detection, `isTranslated`, `generateTranslationWrapper`, `extractTranslatableText`)
  - `tests/PatternDiff/WooCommerceValidatorTest.php` — 6 tests (validate, extractWooCommerceBlocks, isDescendantOfProductTemplate)
  - `tests/Commands/PatternDiffCommandTest.php` — 9 tests (command name, path validation, apply/dry-run guards, JSON output, threshold, theme option)

### Fixed

- **`PatternDiffer::isTranslatable()`** — the `if (preg_match(...))` guard around the CSS-value `return false` branch was accidentally omitted, causing the method to always return `false` after the single-character check. All text nodes after that point were silently skipped by the translation detector.

### Changed

- **`.vibe/config.toml`** — `python` and `python3` removed from the vibe tools denylist to allow local script execution during pattern diffing.

## [2.2.3] - 2026-05-13

### Added

- **`button-width-classes` rule** (`BaseRules`) — detects `wp:button` blocks that have `"width":N` in their JSON but whose HTML outer `<div>` is missing the `has-custom-width` and/or `wp-block-button__width-{N}` classes, or where the inner `<a>` still carries a `width:{N}%` inline style. WordPress's `core/button` save function expresses percentage width via CSS classes on the outer div — not inline style — so any mismatch causes a block validation failure in the editor.

## [2.2.2] - 2026-05-12

### Added

- **`border-style-solid-in-html` rule** (`BaseRules`) — detects `border-style:solid` in `<div>` inline styles. WordPress's save function does not emit this property when border color uses `var:preset|color|`, so its presence in the pattern HTML guarantees a block validation mismatch.
- **`border-property-order-in-html` rule** (`BaseRules`) — detects `border-width` appearing before `border-color` in a `<div>` inline style. WordPress always serializes `border-color` first; wrong order causes a block validation mismatch on template reset.

## [2.2.1] - 2026-05-12

### Added

- **`border-color-raw-slug` rule** (`BaseRules`) — detects raw color slugs in `"border"` style objects (e.g. `"color":"border-light"`) that must be `"color":"var:preset|color|{slug}"`. A bare slug causes WordPress to emit `border-color:border-light` instead of the CSS variable, producing a `core/group` block validation mismatch in the site editor.

## [2.2.0] - 2026-05-12

### Added

- **`check:templates` command** (`src/Commands/TemplateCheckCommand.php`) — new CLI command that scans `.html` template and template-part files for block-validation drift that the PHP pattern compliance checker cannot detect:
  - Accepts a path to a single `.html` file or a directory (e.g. `templates/`, `parts/`)
  - `--theme` option (default `base`) to load the matching config
  - `--autofix` flag to apply mechanical repairs in-place
  - Renders per-file pass/fail summary with rule name, line number, and severity

- **`TemplateRules` rule set** (`src/Compliance/Rules/TemplateRules.php`) — five checks for FSE HTML template files:
  - `taxQuery-object` — detects `"taxQuery":{}` (object); must be `[]` (array) to avoid WP-CLI "block structure needs normalization" warnings and editor save loops
  - `woo-filter-missing-wrapper` — detects WooCommerce filter sub-blocks (`woocommerce/product-filter-active`, `-chips`, `-checkbox-list`, `-price-slider`, `-price`, `-attribute`, `-taxonomy`) that are self-closing or lack the required `<div class="wp-block-woocommerce-…">` wrapper introduced in WooCommerce 9.x+
  - `woo-product-filters-css-vars` — detects `woocommerce/product-filters` div wrappers missing `--wc-product-filters-text-color` / `--wc-product-filters-background-color` CSS custom properties (added in WooCommerce 9.x+)
  - `template-part-theme` — detects `wp:template-part` blocks missing the `"theme"` attribute (warning; requires `requireThemeAttribute: true` in config)
  - `unbalanced-html-tags` — mirrors the BaseRules check for `<div>`, `<ul>`, `<ol>`, `<li>`, `<figure>`, `<figcaption>`, `<section>`, `<article>`, `<header>`, `<footer>`, `<nav>`, `<aside>`, `<main>`, `<p>` in HTML template files

- **Two autofixable rules** in `TemplateRules`:
  - `taxQuery:{}` → `taxQuery:[]`
  - Inject missing `"theme":"<slug>"` into `wp:template-part` block comments

- **`ComplianceChecker` template methods** (`src/Compliance/ComplianceChecker.php`):
  - `checkTemplateFile(string $path, bool $autofix): array` — checks a single `.html` file through `templateRuleSets`
  - `checkTemplateDirectory(string $dir, bool $autofix): array` — iterates `*.html` files in a directory and collects per-file results
  - Separate `$templateRuleSets` property keeps template rules isolated from PHP pattern rule sets

- **`woocommerce/product-filter-taxonomy`** added to `WOO_FILTER_BLOCKS` in `TemplateRules` — covers taxonomy-based filter blocks missing wrappers

- **`tests/Compliance/Rules/TemplateRulesTest.php`** — PHPUnit tests for all five `TemplateRules` checks and both autofixes

### Changed

- `README.md` — updated to document the `check:templates` command, three-pass validation workflow (WP-CLI → `pt-cli check` → `pt-cli check:templates`), and new TemplateRules rule reference

## [2.1.0] - 2026-05-08

### Added

- **Unbalanced HTML tags check** in `BaseRules` (`src/Compliance/Rules/BaseRules.php`) — Detects unbalanced HTML tags (`<div>`, `<ul>`, `<ol>`, `<li>`, `<figure>`, `<figcaption>`, `<section>`, `<article>`, `<header>`, `<footer>`, `<nav>`, `<aside>`, `<main>`, `<p>`) in pattern files. Reports violations with opening/closing tag counts to help authors identify mismatches. Addresses issue #2 where missing `</div>` tags caused DOM nesting issues but went undetected by both pt-cli and `wp pattern validate`.
- **PHPUnit test suite** (`tests/`) — First test suite for the project with comprehensive tests for the unbalanced HTML tags rule:
  - `tests/bootstrap.php` — Test bootstrap file
  - `tests/Compliance/Rules/BaseRulesTest.php` — Tests for BaseRules including unbalanced HTML tags detection

### Changed

- `.gitignore` — Added `.phpunit.cache/` directory
- `README.md` — Added "Balanced HTML tags" rule to the Base rules table

## [2.0.0] - 2026-05-08

### Added

**Scaffolding Commands (Phase 5 - absorbed from elayne-cli):**

- `pattern:create` command (`src/Commands/Scaffold/PatternCreateCommand.php`) — Scaffold new Elayne block patterns from templates with interactive prompts:
  - Supports 23 pattern templates (see below)
  - Supports 18 pattern categories (header, footer, elayne/hero, elayne/features, etc.)
  - Options: `--title`, `--slug`, `--template`, `--category`, `--keywords`, `--output-dir`, `--with-style`, `--style-dir`, `--shell-only`
  - Automatically generates CSS file when `--with-style` flag is used
  - Shell-only mode for editor-first workflow (generates PHP header + paste marker)

- `layout:create` command (`src/Commands/Scaffold/LayoutCreateCommand.php`) — Scaffold new Elayne block layout patterns:
  - Supports 8 layout skeletons (full-width, two-column, three-column, sidebar-left, sidebar-right, hero-image-left, hero-image-right, landing-page)
  - Options: `--title`, `--slug`, `--layout`, `--category`, `--keywords`, `--output-dir`, `--shell-only`

- `style:create` command (`src/Commands/Scaffold/StyleCreateCommand.php`) — Scaffold WordPress theme style variation JSON:
  - Supports 5 preset color palettes (custom, legal, plumbing, spa, food-beverage)
  - Generates full theme.json with color palette, gradients, and duotone presets
  - Interactive color picker with validation
  - Options: `--name`, `--vertical`, `--output-dir`

- `pattern:list` command (`src/Commands/Scaffold/PatternListCommand.php`) — List all available resources:
  - Lists all 23 templates with descriptions
  - Lists all 13 snippets with descriptions
  - Lists all 5 style variations with descriptions
  - Lists all 18 pattern categories
  - Set as default command (replaces `check` as default)

**Template Files (23 patterns in `templates/` directory):**

- `blank.php` — Empty pattern with header only
- `hero-cover.php` — Full-bleed wp:cover with bottom-center content
- `cta-fullwidth.php` — Full-width call-to-action band
- `feature-grid-3col.php` — Full-width section with 3 feature cards
- `stats-bar-fullwidth.php` — Dark full-width stats/numbers bar
- `two-column-text-image.php` — Text left, image right two-column layout
- `header-standard.php` — Standard header with logo, navigation, social links
- `footer-standard.php` — Standard footer with brand blurb, nav columns, subnav
- `testimonials-grid.php` — Responsive testimonial card grid with reviewer info
- `pricing-comparison.php` — Three-tier pricing table with elevated recommended card
- `blog-post-columns.php` — wp:query-driven 3-column post grid (portrait images)
- `team-grid.php` — Team member profile grid with photo, name, title, bio
- `woo-hero.php` — WooCommerce two-column hero
- `woo-ticker.php` — WooCommerce server-rendered marquee ticker bar
- `woo-shop-categories.php` — WooCommerce CSS bento grid layout
- `woo-featured-products.php` — WooCommerce section header with View All + product-collection grid
- `woo-our-story.php` — WooCommerce two-column brand story
- `woo-testimonials.php` — WooCommerce three-column testimonial cards with ratings
- `woo-newsletter.php` — WooCommerce full-bleed newsletter signup
- `woo-shop-landing.php` — WooCommerce store homepage shell
- `woo-cart.php` — WooCommerce cart page wrapper
- `woo-checkout.php` — WooCommerce checkout page wrapper
- `woo-filters-sidebar.php` — WooCommerce sticky sidebar with filters
- `woo-product-grid.php` — WooCommerce filter-aware product-collection grid

**Layout Files (8 layouts in `layouts/` directory):**

- `full-width.php` — Single column, constrained
- `two-column.php` — 50/50 columns block
- `three-column.php` — Grid with 3 equal groups
- `sidebar-left.php` — Narrow left sidebar (33%) + wide content area (66%)
- `sidebar-right.php` — Wide content area (66%) + narrow right sidebar (33%)
- `hero-image-left.php` — Cover image left + heading, text, CTA right
- `hero-image-right.php` — Heading, text, CTA left + cover image right
- `landing-page.php` — Hero + 3-column features + CTA

**CSS Stubs (8 files in `css/` directory):**

- `cta-fullwidth.css` — Styles for CTA full-width pattern
- `feature-grid-3col.css` — Styles for 3-column feature grid
- `generic.css` — Generic fallback CSS stub
- `hero-cover.css` — Styles for hero cover pattern
- `stats-bar-fullwidth.css` — Styles for stats bar pattern
- `team-grid.css` — Styles for team grid pattern
- `testimonials-grid.css` — Styles for testimonials grid pattern
- `woo-filters-sidebar.css` — Comprehensive styles for WooCommerce filters sidebar

**Code Snippets (13 files in `snippets/` directory):**

- `eyebrow-heading-body.txt` — Eyebrow label + heading + body paragraph
- `3col-grid-wrapper.txt` — Responsive 3-column grid wrapper
- `stat-item.txt` — Number + label stat card (dark background)
- `testimonial-card.txt` — Testimonial with stars, quote, author
- `two-button-group.txt` — Primary + outline button pair
- `overlay-grid-cover-card.txt` — Portrait cover image card + floating badge
- `valid-cover.txt` — wp:cover with all required attributes
- `valid-columns-wp66.txt` — wp:columns without inline gap/margin
- `responsive-grid-min-width.txt` — wp:group grid layout with minimumColumnWidth
- `valid-button-attr-order.txt` — wp:button with proper attribute order
- `valid-fullwidth-section.txt` — alignfull outer group + margin reset
- `valid-heading-with-preset.txt` — wp:heading with fontSize slug and matching utility class

### Changed

- **Application version**: Bumped from `1.0.0` to `2.0.0` in `bin/pt-cli`
- **Package description**: Updated from "Pattern compliance checker" to "Pattern scaffolding and compliance checker for WordPress FSE block themes" in `composer.json`
- **Default command**: Changed from `check` to `list` in `bin/pt-cli`
- **Namespace migration**: All scaffold commands now under `Imagewize\PtCli\Commands\Scaffold\` namespace (previously `Imagewize\ElaynePatternCli\Commands` in elayne-cli)

### Deprecated

- `imagewize/elayne-cli` package — All scaffold functionality has been absorbed into pt-cli v2.0.0 (Phase 6)

---

## [1.0.0] - 2026-05-08

Initial release — pattern compliance checker for WordPress FSE block themes.

### Added

- `bin/pt-cli` entry point with Symfony Console application
- `check` command with `--theme` and `--autofix` flags
- `src/Commands/CheckCommand.php` — CLI entry point for compliance checking
- `src/Compliance/ComplianceChecker.php` — orchestrates rule sets per file and directory
- `src/Compliance/Rules/RuleSetInterface.php` and `AbstractRuleSet.php` — rule set contract
- `src/Compliance/Rules/BaseRules.php` — 19 compliance checks for any FSE theme:
  - No hardcoded CSS font sizes (px/rem/em)
  - No spacer blocks (use blockGap instead)
  - Margin reset required on alignfull patterns
  - Warn on `wp:columns` for 3+ columns (use responsive grid with `minimumColumnWidth`)
  - No hardcoded media IDs
  - Translated strings required (HTML tags + alt attributes)
  - Proper `patternName` in outermost block metadata
  - No HTML comments between opening tags and block comments
  - No custom-domain emails (use `example@example.com`)
  - No hardcoded external URLs in `src` attributes
  - `wp:button` root `fontSize` must use `style.typography`
  - `wp:button` `className` must come before `style` in JSON
  - Cover block `minHeight` must have root-level units
  - No empty border side objects `{}` in block JSON
  - `wp:buttons` must have `<div class="wp-block-buttons">` wrapper
  - No `overflow:hidden` as inline style on group blocks
  - No `opacity` as inline style on HTML elements
  - Font preset classes must match root-level `fontSize`/`fontFamily`
  - No stale inline `blockGap`/`gap`/`margin` on group/column wrappers
- `src/Compliance/Rules/WooCommerceRules.php` — 5 WooCommerce-specific checks:
  - `product-title` inside `product-template` must use `post-title` + `__woocommerceNamespace`
  - WC native blocks must not have `__woocommerceNamespace`
  - `product-collection` must have `query` metadata
  - `product-collection` must have `<div class="wp-block-woocommerce-product-collection">` wrapper
  - `product-collection` must not have both `layout` and `displayLayout`
- `src/Compliance/Config/ConfigLoader.php` — loads and merges JSON config with project-level override support
- `src/Compliance/Config/ThemeConfig.php` — typed config accessor
- `config/base.json` — default rules for any FSE theme
- `config/elayne.json` — Elayne-specific rules (extends base): theme attribute, `elayne/` prefix, emoji check, WooCommerce rules, template exceptions
- Five autofixable rules applied by `--autofix`:
  - Strip inline `gap:` from group/column wrappers
  - Strip inline `margin:` from flex group/column wrappers
  - Reorder `wp:button` JSON keys (`className` before `style`)
  - Migrate `wp:button` root `fontSize` to `style.typography`
  - Inject `has-{slug}-font-size` class on heading/paragraph elements
- Project-level config override: `.pt-cli/{theme}.json` in project root
- `README.md` with full usage, configuration, and rule reference
- `LICENSE` (MIT)
