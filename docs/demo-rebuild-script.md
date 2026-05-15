# Demo Rebuild Script

A WP-CLI script is included to rebuild demo pages from the latest pattern PHP files. It renders each pattern file in the live WordPress context and overwrites the page's `post_content` — identical to a fresh block-editor insert.

## Location

`scripts/rebuild-demo.php`

## Usage

Copy the script to your theme directory (e.g., `web/app/themes/your-theme/scripts/rebuild-demo.php`) and customize the `$page_map`, `$templates`, and `$template_parts` arrays.

```bash
# Single-site usage (run from your WordPress root):
wp --path=web/wp eval-file web/app/themes/your-theme/scripts/rebuild-demo.php

# Multisite usage — pass --url= to target the correct subsite AND pass the
# subsite slug as a positional argument:
wp --path=web/wp --url=example.com/store/ \
  eval-file web/app/themes/your-theme/scripts/rebuild-demo.php store

# Dry-run (shows what would be updated, no writes):
WP_REBUILD_DRY_RUN=1 wp --path=web/wp \
  eval-file web/app/themes/your-theme/scripts/rebuild-demo.php
```

## Configuration

Edit the script's arrays to map your page IDs to pattern files:

```php
// Single-site
$page_map = array(
    17 => array( 'patterns/hero-with-cta.php', 'patterns/features.php' ),
    20 => array( 'patterns/contact-with-form.php' ),
);

// Multisite (nested by subsite slug)
$page_map = array(
    'main'  => array( 17 => array( 'patterns/hero-with-cta.php' ) ),
    'store' => array( 92 => array( 'patterns/woocommerce/woo-hero.php' ) ),
);

// Optional: Push custom .html templates to WP database
$templates = array(
    'archive-product' => 'templates/archive-product-custom.html',
);

// Optional: Push custom template parts
$template_parts = array(
    'header' => 'patterns/my-custom-header.php',
    'footer' => 'patterns/my-custom-footer.php',
);
```

## Important for multisite

Page IDs are per-subsite (not global). Always pass `--url=` so WP-CLI switches to the right blog context. Without it, `get_template_directory()` and page IDs may resolve against the wrong subsite.
