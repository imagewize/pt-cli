# WordPress Pattern Toolkit

Scaffolding, compliance checking, and HTML template validation for WordPress FSE block themes.

Generates block pattern PHP files, layout patterns, and style variations. Checks pattern `.php` files for structural and naming rule violations, and checks HTML template/part files for client-side block validation drift — no WordPress context required. Runs on the host machine as a standalone PHP CLI tool.

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

### Scaffolding Commands

```bash
# List available templates, snippets, categories, and style variations
pt-cli list

# Scaffold a new pattern from a template
pt-cli pattern:create --title="My Hero" --slug=my-hero --template=hero-cover --category=elayne/hero

# Scaffold a layout pattern
pt-cli layout:create --title="Landing Page" --slug=landing --layout=landing-page --category=elayne/pages

# Scaffold a theme style variation
pt-cli style:create --name="Ocean Legal" --vertical=legal

# Full interactive mode (no options = prompts)
pt-cli pattern:create
```

### Compliance Checking

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

### HTML Template Compliance

```bash
# Check all .html files in a templates or parts directory
pt-cli check:templates /path/to/templates/ --theme=elayne
pt-cli check:templates /path/to/parts/ --theme=elayne

# Check a single template file
pt-cli check:templates /path/to/templates/archive-product.html --theme=elayne

# Apply mechanical autofixes (taxQuery:{} → [] and template-part theme attribute)
pt-cli check:templates /path/to/templates/ --theme=elayne --autofix
```

### Pattern Diff & Sync

Compare a Gutenberg clipboard paste against an existing PHP pattern file, or apply
the changes back while preserving all PHP translation wrappers.

```bash
# Report differences only (shows missing translations, editor attrs, CSS issues)
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin

# Include fix suggestions in the diff report
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin --show-suggestions

# Output diff results as JSON (for tooling integration)
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin --json

# Preview the merged result without touching the file
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin --apply --dry-run

# Apply clipboard changes to the PHP file (preserves esc_html_e, esc_attr_e, etc.)
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin --apply

# Lower the similarity threshold for loosely matched blocks (default: 0.95)
pbpaste | pt-cli pattern:diff patterns/woo-signature-pieces.php --from-stdin --similarity-threshold=0.80
```

**What `--apply` does:**

1. Strips editor-only attributes (`__privatePreviewState`) from block JSON.
2. Fixes bare font-size slug values (`font-size:small`) → CSS variable (`font-size:var(--wp--preset--font-size--small)`).
3. Removes nested `<p>` copy artefacts introduced by the Gutenberg clipboard.
4. Re-maps every text node back to its original PHP wrapper (`esc_html_e`, `esc_attr_e`, `wp_kses_post`) from the existing file.
5. Generates a new `esc_html_e()` wrapper for any text that is new in the clipboard.
6. Preserves the PHP docblock header unchanged.

The file is only written when `--apply` is used **without** `--dry-run`.

## Commands Reference

| Command | Description |
|---------|-------------|
| `list` (default) | List available templates, snippets, categories, and style variations |
| `pattern:create` | Scaffold a new Elayne block pattern from a template |
| `layout:create` | Scaffold a new Elayne block layout pattern |
| `style:create` | Scaffold a WordPress theme style variation JSON |
| `check` | Check PHP pattern files for compliance violations |
| `check:templates` | Check HTML template/part files for block validation drift |
| `pattern:diff` | Diff Gutenberg clipboard against a pattern file, or apply changes preserving PHP |

## Workflow

`pt-cli` is an all-in-one tool for WordPress FSE block theme development:

### Scaffolding Workflow

| Step | Tool | Purpose | Where |
|------|------|---------|-------|
| 1 | `pt-cli pattern:create` or `pt-cli layout:create` | Generate pattern/layout scaffolding | Host |
| 2 | Build in WP editor | Create pattern content | VM |
| 3 | Copy blocks | Copy all blocks from editor | VM |
| 4 | `pt-cli pattern:create --shell-only` | Create PHP file with paste marker | Host |
| 5 | Replace marker | Paste blocks into pattern file | Host |

### Pattern Sync Workflow (updating existing patterns)

When an existing PHP pattern needs updating from the Site Editor, use `pattern:diff --apply`
instead of pasting manually — it keeps all `esc_html_e()` and other PHP wrappers intact.

| Step | Command | Purpose |
|------|---------|---------|
| 1 | Edit pattern in Site Editor | Make structural/layout changes |
| 2 | Copy all blocks (Cmd+A, Cmd+C) | Copy updated block HTML to clipboard |
| 3 | `pbpaste \| pt-cli pattern:diff <file> --from-stdin --apply --dry-run` | Preview merged result |
| 4 | `pbpaste \| pt-cli pattern:diff <file> --from-stdin --apply` | Write merged result to file |
| 5 | `pt-cli check <file> --theme=elayne` | Verify compliance passes |

### Compliance Workflow (three-pass)

| Pass | Tool | Purpose | Where |
|------|------|---------|-------|
| 1 | `wp pattern validate --fix` | Structural validation (WordPress parser — unbalanced delimiters, malformed JSON, bad nesting) | VM |
| 2 | `pt-cli check --theme=elayne` | PHP pattern compliance (hardcoded values, naming rules, WooCommerce block structure) | Host |
| 3 | `pt-cli check:templates --theme=elayne` | HTML template compliance (WooCommerce save() drift, taxQuery, template-part theme attribute) | Host |

Pass 1 requires WordPress (database connection) and runs in the Trellis VM. Passes 2 and 3 are standalone and run on the host machine.

## Templates

**23 pre-built pattern templates** covering common use cases:

| Template | Description |
|----------|-------------|
| `blank` | Empty pattern with header only |
| `hero-cover` | Full-bleed wp:cover with bottom-center content |
| `cta-fullwidth` | Full-width call-to-action band |
| `feature-grid-3col` | Full-width section with 3 feature cards |
| `stats-bar-fullwidth` | Dark full-width stats/numbers bar |
| `two-column-text-image` | Text left, image right two-column layout |
| `header-standard` | Standard header — logo, navigation, social links |
| `footer-standard` | Standard footer — brand blurb, nav columns, subnav |
| `testimonials-grid` | Responsive testimonial card grid with reviewer info |
| `pricing-comparison` | Three-tier pricing table with elevated recommended card |
| `blog-post-columns` | wp:query-driven 3-column post grid (portrait images) |
| `team-grid` | Team member profile grid — photo, name, title, bio |

**WooCommerce templates:**

| Template | Description |
|----------|-------------|
| `woo-hero` | Two-column hero: text + CTA left, decorative cover right |
| `woo-ticker` | Server-rendered marquee ticker bar (needs render_block filter) |
| `woo-shop-categories` | CSS bento grid: one large featured card + four smaller cards |
| `woo-featured-products` | Section header with View All + product-collection 4-col grid |
| `woo-our-story` | Two-column brand story: monogram watermark left, text + stats right |
| `woo-testimonials` | Three-column testimonial cards with star ratings and avatar circles |
| `woo-newsletter` | Full-bleed newsletter signup with decorative eyebrow |
| `woo-shop-landing` | Store homepage shell that composes sub-patterns in sequence |
| `woo-cart` | Full-width cart page wrapper (Inserter: false) |
| `woo-checkout` | Full-width checkout page wrapper (Inserter: false) |
| `woo-filters-sidebar` | Sticky sidebar: price slider + colour-chip attribute + two checkbox-list attributes |
| `woo-product-grid` | Filter-aware product-collection grid with sort toolbar + pagination |

## Layouts

**8 layout skeletons** for rapid page construction:

| Layout | Description |
|--------|-------------|
| `full-width` | Single column, constrained — simplest starting point |
| `two-column` | 50/50 columns block |
| `three-column` | Grid with 3 equal groups |
| `sidebar-left` | Narrow left sidebar (33%) + wide content area (66%) |
| `sidebar-right` | Wide content area (66%) + narrow right sidebar (33%) |
| `hero-image-left` | Cover image left + heading, text, CTA right |
| `hero-image-right` | Heading, text, CTA left + cover image right |
| `landing-page` | Hero + 3-column features + CTA — no header/footer wrapper |

## Style Variations

**5 preset color palettes** for common business verticals:

| Vertical | Color Scheme |
|----------|--------------|
| `custom` | Enter your own hex color values |
| `legal` | Navy blue + gold |
| `plumbing` | Dark blue + orange |
| `spa` | Sage green + sand |
| `food-beverage` | Burgundy + gold |

## Snippets

**13 reusable code snippets** for common pattern components:

| Snippet | Description |
|---------|-------------|
| `eyebrow-heading-body.txt` | Eyebrow label + heading + body paragraph |
| `3col-grid-wrapper.txt` | Responsive 3-column grid wrapper |
| `stat-item.txt` | Number + label stat card (dark background) |
| `testimonial-card.txt` | Testimonial with stars, quote, author |
| `two-button-group.txt` | Primary + outline button pair |
| `overlay-grid-cover-card.txt` | Portrait cover image card + floating badge (use wp:cover, NOT wp:image) |
| `valid-cover.txt` | wp:cover with all required attrs: dimRatio, backgroundColor/customGradient, minHeight (root integer) + minHeightUnit |
| `valid-columns-wp66.txt` | wp:columns without inline gap/margin; isStackedOnMobile:false → is-not-stacked-on-mobile class |
| `responsive-grid-min-width.txt` | wp:group grid layout with minimumColumnWidth — preferred over wp:columns for 3+ columns |
| `valid-button-attr-order.txt` | wp:button with className/colors before style; font size via style.typography.fontSize |
| `valid-fullwidth-section.txt` | alignfull outer group + margin reset (top/bottom:"0" no units) + constrained inner group |
| `valid-heading-with-preset.txt` | wp:heading with fontSize slug in JSON and matching has-{slug}-font-size utility class in HTML |

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
| Balanced HTML tags (`<div>`, `<ul>`, `<ol>`, `<li>`, `<figure>`, etc.) | No | Error |
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

### Template rules (`check:templates`)

Applied to `.html` files in `templates/` and `parts/` directories. These checks catch client-side JavaScript `save()` mismatches that the PHP compliance checker and WP-CLI structural validator cannot detect.

| Rule | Autofixable | Severity |
|------|-------------|----------|
| `taxQuery:{}` must be `taxQuery:[]` (object → array) | Yes | Error |
| WooCommerce filter sub-blocks must have an HTML `<div>` wrapper | No | Error |
| `woocommerce/product-filters` `<div>` must include WooCommerce CSS custom properties | No | Error |
| `wp:template-part` must declare `"theme":"<slug>"` | Yes | Warning |
| Balanced HTML tags (`<div>`, `<ul>`, `<ol>`, `<li>`, etc.) | No | Error |

**Why separate from `check`?**

The `check` command processes PHP pattern files. HTML template files use a different structure — raw block markup without PHP wrappers — and require different checks. In particular, WooCommerce 9.x+ changed the `save()` output for filter blocks to include empty `<div>` wrappers; templates written against older versions lack those wrappers and trigger client-side block validation errors that neither WP-CLI nor the PHP checker can catch.

## Demo Rebuild Script

For rebuilding demo pages from pattern PHP files, see the [Demo Rebuild Script guide](docs/demo-rebuild-script.md).

## Development

```bash
git clone https://github.com/imagewize/pt-cli
cd pt-cli
composer install
bin/pt-cli list
bin/pt-cli check --help
```

## License

MIT — see [LICENSE](LICENSE).
