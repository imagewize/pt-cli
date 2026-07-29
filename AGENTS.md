# Repository Guidelines

## Primary Directive
Always follow the rules and conventions in the project's `CLAUDE.md` file. This is the authoritative source for all project-specific guidance.

## Project Overview
**pt-cli** is a standalone PHP CLI tool that checks WordPress FSE block theme pattern files for structural and naming compliance. It runs on the host machine with no WordPress context — pure PHP regex/string analysis.

- **Type**: PHP CLI application (Symfony Console)
- **Minimum PHP**: 8.1
- **Namespace**: `Imagewize\PtCli\` (`src/`), `Imagewize\PtCli\Tests\` (`tests/`)
- **Package**: `imagewize/pt-cli`

## Workflow Context
`pt-cli` is step 3 in a three-step pattern workflow:

| Step | Tool | Where | Purpose |
|------|------|-------|---------|
| 1 | `elayne scaffold` | Host | Generate pattern scaffolding |
| 2 | `wp pattern validate` | VM | Structural validation (WordPress parser) |
| 3 | `pt-cli check` | Host | Compliance checking |

## Project Structure & Module Organization
```
bin/pt-cli                  Entry point — registers CheckCommand as default
src/
  Commands/CheckCommand      Parses CLI args (path, --theme, --autofix); delegates to ComplianceChecker
  Compliance/
    ComplianceChecker        Orchestrates rule sets; checkFile() / checkDirectory() return violation arrays
    Config/
      ConfigLoader           Three-tier JSON config lookup
      ThemeConfig            Immutable wrapper with typed getters around merged config
    Rules/
      RuleSetInterface       Contract: getName(), check(filePath): array, isAutofixable(), autofix(content)
      AbstractRuleSet        Defaults: isAutofixable()=false, autofix()=passthrough, violation() helper
      BaseRules              All FSE theme rules (see CLAUDE.md for the rule catalog)
      WooCommerceRules       WC-specific rules (enabled per config)
  Utils/                     Shared helpers
config/
  base.json                  Default config for any FSE theme
  elayne.json                Elayne theme overrides (extends base)
```

## Build, Test, and Development Commands
```bash
# Install dependencies
composer install

# Run the checker
php bin/pt-cli check <path/to/patterns> [--theme=elayne] [--autofix]

# Run tests (PHPUnit)
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/path/to/FooTest.php
```

## Configuration
Three-tier config merge (priority order):
1. `.pt-cli/{theme}.json` — project-level override in the checked theme directory
2. `config/{theme}.json` — tool-bundled theme config (e.g., `config/elayne.json`)
3. `config/base.json` — fallback defaults

Configs are deep-merged; `ThemeConfig` exposes typed getters (`getPatternPrefix()`, `woocommerceEnabled()`, `getRules()`, etc.).

## Key Principles
- **No WordPress context**: pure PHP regex/string analysis on pattern `.php` files
- **Autofix flag**: `--autofix` applies mechanical fixes (strip inline gap/margin, reorder button JSON keys, migrate fontSize, inject font-size classes)
- **Exceptions**: template files (`template-*`, `header-*`, `footer-*`) allow specific border-radius values; WooCommerce plugin patterns are exempt
- **Rule sets**: `BaseRules` (all FSE themes) and `WooCommerceRules` (when `woocommerce.enabled: true`)

## Before Responding
1. **Review `CLAUDE.md`** for the full rule catalog and severity levels, architecture details and stub locations, and the current implementation state.
2. **Check current context**: verify the working directory, confirm the branch and git status, and review recent commits for relevant changes.
3. **Adhere to conventions**: all commands run from the host machine (not a VM); follow PSR-4 autoloading under the `Imagewize\PtCli\` namespace; dev-only files (`tests/`, `docs/`, `.vibe/`, `AGENTS.md`, `CLAUDE.md`) are `export-ignore`d in `.gitattributes` and must stay out of Composer distributions.

## Response Guidelines
- Be concise and technical
- Reference specific files, line numbers, and rule IDs
- Use markdown formatting for code and structure
- Prioritise verification over assumptions
- When unsure, ask for clarification before acting

## Commit Guidelines
- **Atomic commits**: add files individually or in logical groups with specific, descriptive messages
- **No AI attribution**: never mention any AI tool or co-authoring attribution in commit messages. This applies to all files and directories in the repository.
- Keep all commits professional and attribution-free
- Follow standard git commit message format
