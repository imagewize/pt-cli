# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Tool Does

`pt-cli` is a standalone PHP CLI that checks WordPress FSE block theme pattern files for structural and naming compliance. It runs on the host machine with no WordPress context — pure PHP regex/string analysis. It is step 3 in a three-step pattern workflow:

| Step | Tool | Where |
|------|------|-------|
| 1 | `elayne scaffold` | Host |
| 2 | `wp pattern validate` | VM |
| 3 | `pt-cli check` | Host |

## Commands

```bash
# Install dependencies
composer install

# Run the checker
php bin/pt-cli check <path/to/patterns> [--theme=elayne] [--autofix]

# Run tests (PHPUnit installed, no test files written yet)
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/path/to/FooTest.php
```

Minimum PHP: 8.1. Depends on `symfony/console ^6.4|^7.0`.

## Architecture

```
bin/pt-cli                  Entry point — registers CheckCommand as default
src/
  Commands/CheckCommand      Parses CLI args (path, --theme, --autofix); delegates to ComplianceChecker
  Compliance/
    ComplianceChecker        Orchestrates rule sets; checkFile() / checkDirectory() return violation arrays
    Config/
      ConfigLoader           Three-tier JSON config lookup (see below)
      ThemeConfig            Immutable wrapper with typed getters around the merged config array
    Rules/
      RuleSetInterface       Contract: getName(), check(filePath): array, isAutofixable(), autofix(content)
      AbstractRuleSet        Defaults: isAutofixable()=false, autofix()=passthrough, violation() helper
      BaseRules              Stub — all FSE theme rules (to migrate from legacy checker)
      WooCommerceRules       Stub — WC-specific rules (enabled per config)
  Utils/                     Empty — shared helpers land here
config/
  base.json                  Default config for any FSE theme
  elayne.json                Elayne theme overrides (extends base)
```

### Config Loading (Three-tier merge)

`ConfigLoader::load(theme, projectDir)` resolves in priority order:

1. `.pt-cli/{theme}.json` — project-level override in the checked theme directory
2. `config/{theme}.json` — tool-bundled theme config (e.g. `config/elayne.json`)
3. `config/base.json` — fallback defaults

Configs are deep-merged; `ThemeConfig` exposes typed getters (`getPatternPrefix()`, `woocommerceEnabled()`, `getRules()`, etc.).

### Rule System

Each rule set implements `RuleSetInterface`. `check(string $filePath): array` returns an array of violation structs built with `AbstractRuleSet::violation()`. `ComplianceChecker` holds a `$ruleSets` array and feeds files through them.

Rules to implement (from README):

**BaseRules** (all FSE themes):
- Balanced block delimiters, no hardcoded font sizes, no spacer blocks, margin reset on alignfull, valid block comment structure, responsive grid for 3+ columns, no hardcoded media IDs, translated strings, proper `patternName` in outermost block

**WooCommerceRules** (when `woocommerce.enabled: true`):
- WC namespace checks, product-collection rules

**Autofixable** (applied with `--autofix`):
- Strip inline `gap:`/`margin:` from groups/columns, reorder button JSON keys (`className` before `style`), migrate button `fontSize` to `style.typography`, inject `has-{slug}-font-size` class

**Exceptions**: template files (`template-*`, `header-*`, `footer-*`) allow `border-radius:5px` and `border-radius:100px`. WooCommerce plugin patterns (`wp-content/plugins/woocommerce/patterns/*`) are exempt from all checks.

## Current State

All rule logic is stubbed — `BaseRules::check()` and `WooCommerceRules::check()` return empty arrays with TODO comments pointing to a legacy `class-patterncompliancechecker.php`. `CheckCommand::execute()` outputs "not yet implemented." No test files exist yet.

## Git Commits

Commits must be atomic — one logical change per commit. Do not mention Claude Code or any AI tool in commit messages.

`Imagewize\PtCli\` → `src/`  
`Imagewize\PtCli\Tests\` → `tests/`
