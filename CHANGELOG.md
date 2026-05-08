# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — v2.0.0

### Planned

- Absorb all `elayne-cli` scaffold commands into pt-cli (Phase 5)
  - `pattern:create` — scaffold patterns from templates
  - `layout:create` — scaffold layout patterns
  - `style:create` — scaffold WordPress theme style variation JSON
  - `pattern:list` — list available templates, categories, and layouts
- Namespace migration: `Imagewize\ElaynePatternCli\Commands` → `Imagewize\PtCli\Commands\Scaffold`
- Deprecate and archive `imagewize/elayne-cli` (Phase 6)

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
