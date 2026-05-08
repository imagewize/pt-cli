<?php
/**
 * Title: TODO: Pattern Title
 * Slug: elayne/TODO-slug
 * Description: TODO: One-line description
 * Categories: elayne/TODO-category
 * Keywords: TODO keyword1, keyword2
 * Viewport Width: 1280
 * Block Types: core/group
 * Inserter: true
 */
?>
<!-- TEMPLATE: woo-ticker-band — server-rendered marquee ticker bar with elayne-ticker CSS class -->
<!-- RULES CHECKED: margin reset ✓ | no spacers ✓ | alignfull ✓ | translation wrappers ✓ | preset fonts ✓ -->
<!-- NOTE: Content is rendered server-side via the elayne_render_ticker render_block filter in inc/block-extensions.php -->
<!-- TODO: Register a render_block filter that targets className "elayne-ticker" and injects the marquee HTML -->
<!-- TODO: Update the ticker items array in the render_block callback to match the new vertical's categories/labels -->

<!-- wp:group {"align":"full","className":"elayne-ticker","style":{"spacing":{"padding":{"top":"var:preset|spacing|small","bottom":"var:preset|spacing|small"}}},"backgroundColor":"primary","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull elayne-ticker has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--small);padding-bottom:var(--wp--preset--spacing--small)"></div>
<!-- /wp:group -->
