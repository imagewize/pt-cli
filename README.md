# pt-cli

Pattern compliance checker for WordPress FSE block themes.

Checks pattern `.php` files for structural and naming rule violations — no WordPress context required. Runs on the host machine as a standalone PHP CLI tool.

## Installation

As a global tool:
```bash
composer global require imagewize/pt-cli
```

Or as a project dev dependency (recommended for theme development):
```bash
composer require --dev imagewize/pt-cli
```

Then use `./vendor/bin/pt-cli` or add a Composer script.

Requires PHP 8.1+.

## Usage

```bash
# Check all patterns in a directory
pt-cli check /path/to/patterns

# Check with a specific theme config
pt-cli check /path/to/patterns --theme=elayne

# Check a single file
pt-cli check /path/to/patterns/header-default.php --theme=elayne

# Apply mechanical autofixes
pt-cli check /path/to/patterns --theme=elayne --autofix
```

## Workflow

`pt-cli` is one tool in a three-step pattern workflow:

| Step | Tool | Purpose | Where |
|------|------|---------|-------|
| 1 | `elayne scaffold` | Generate pattern scaffolding | Host |
| 2 | `wp pattern validate` | Structural validation (WordPress parser) | VM |
| 3 | `pt-cli check` | Compliance checking | Host |

## Configuration

Built-in configs ship with the tool:

- `base` — default rules for any FSE theme
- `elayne` — Elayne-specific rules (extends base)

### Project-level override

Create `.pt-cli/{theme}.json` in your project root to override or extend the built-in config without needing a `pt-cli` release:

```
my-project/
├── .pt-cli/
│   └── elayne.json
└── web/app/themes/elayne/
    └── patterns/
```

Config lookup order:
1. `.pt-cli/{theme}.json` (project directory)
2. `config/{theme}.json` (tool directory)
3. `config/base.json` (fallback)

## Rules

### Base rules (all FSE themes)

| Rule | Autofixable | Severity |
|------|-------------|----------|
| Balanced block delimiters | No | Error |
| No hardcoded font sizes | No | Error |
| No spacer blocks | No | Error |
| Margin reset on alignfull | No | Error |
| Valid block comment structure | No | Error |
| Responsive grid for 3+ columns | No | Warning |
| No hardcoded media IDs | No | Error |
| Translated strings | No | Error |
| Proper patternName in outermost block | No | Error |

### Elayne-specific rules

| Rule | Autofixable | Severity |
|------|-------------|----------|
| Theme attribute on template-part | No | Error |
| PatternName prefix starts with `elayne/` | No | Error |
| No emoji icons | No | Warning |
| No HTML comments between tags | No | Error |
| WooCommerce namespace checks | No | Error |
| WC product-collection rules | No | Error |

### Autofixable rules (`--autofix`)

| Rule |
|------|
| Strip inline `gap:` from groups/columns |
| Strip inline `margin:` from flex groups/columns |
| Reorder button JSON keys (`className` before `style`) |
| Migrate button root `fontSize` to `style.typography` |
| Inject `has-{slug}-font-size` class on heading/paragraph |

### Exceptions

- Templates (`template-*`, `header-*`, `footer-*`) allow: `border-radius:5px`, `border-radius:100px`
- WooCommerce plugin patterns (`wp-content/plugins/woocommerce/patterns/*`) are exempt from all checks

## Development

```bash
git clone https://github.com/imagewize/pt-cli
cd pt-cli
composer install
bin/pt-cli check --help
```

## License

MIT — see [LICENSE](LICENSE).
