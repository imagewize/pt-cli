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
| No hardcoded font sizes (CSS px/rem/em) | No | Error |
| No spacer blocks | No | Error |
| Margin reset on alignfull patterns | No | Error |
| Responsive grid for 3+ columns (warn on `wp:columns`) | No | Warning |
| No hardcoded media IDs | No | Error |
| Translated strings (HTML tags + alt attributes) | No | Error |
| Proper `patternName` in outermost block metadata | No | Error |
| No HTML comments between opening tags and block comments | No | Error |
| No custom domain emails (use `example@example.com`) | No | Error |
| No hardcoded external URLs in `src` attributes | No | Error |
| `wp:button` root `fontSize` must use `style.typography` | No | Error |
| `wp:button` `className` must come before `style` in JSON | No | Error |
| Cover block `minHeight` must have root-level units | No | Error |
| No empty border side objects `{}` in block JSON | No | Error |
| `wp:buttons` must have `<div class="wp-block-buttons">` wrapper | No | Error |
| No `overflow:hidden` as inline style on group blocks | No | Error |
| No `opacity` as inline style on HTML elements | No | Error |
| Font preset classes match root-level `fontSize`/`fontFamily` | No | Error |
| No stale inline `blockGap`/`gap`/`margin` on group/column wrappers | No | Error |

### Elayne-specific rules (`--theme=elayne`)

| Rule | Autofixable | Severity |
|------|-------------|----------|
| `wp:template-part` must have `"theme":"elayne"` attribute | No | Error |
| `patternName` prefix must start with `elayne/` | No | Error |
| No emoji icons | No | Warning |
| `woocommerce/product-title` inside `product-template` must use `post-title` + `__woocommerceNamespace` | No | Error |
| WC native blocks must not have `__woocommerceNamespace` | No | Error |
| `woocommerce/product-collection` must have `query` metadata | No | Error |
| `woocommerce/product-collection` must have `<div class="wp-block-woocommerce-product-collection">` wrapper | No | Error |
| `woocommerce/product-collection` must not have both `layout` and `displayLayout` | No | Error |

### Autofixable rules (`--autofix`)

| Rule |
|------|
| Strip inline `gap:` from groups/columns |
| Strip inline `margin:` from flex groups/columns |
| Reorder button JSON keys (`className` before `style`) |
| Migrate button root `fontSize` to `style.typography` |
| Inject `has-{slug}-font-size` class on heading/paragraph |

### Exceptions

- Templates (`template-*`, `header-*`, `footer-*`) allow: `border-radius:5px`, `border-radius:100px`, `blockGap:0.5rem`, `blockGap:10px`
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
