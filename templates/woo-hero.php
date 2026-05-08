<?php
/**
 * Title: TODO: Pattern Title
 * Slug: elayne/TODO-slug
 * Description: TODO: One-line description
 * Categories: elayne/TODO-category
 * Keywords: TODO keyword1, keyword2
 * Viewport Width: 1280
 * Block Types: core/columns
 * Inserter: true
 */
?>
<!-- TEMPLATE: woo-hero-split — two-column hero: left text + CTA buttons, right decorative cover with watermark -->
<!-- RULES CHECKED: margin reset ✓ | no spacers ✓ | alignfull ✓ | translation wrappers ✓ | preset fonts ✓ | button attr order ✓ -->

<!-- wp:columns {"align":"full","style":{"spacing":{"blockGap":"0","margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns alignfull"><!-- wp:column {"verticalAlignment":"stretch","style":{"spacing":{"padding":{"top":"var:preset|spacing|xx-large","right":"var:preset|spacing|x-large","bottom":"var:preset|spacing|xx-large","left":"var:preset|spacing|x-large"}}},"backgroundColor":"primary"} -->
<div class="wp-block-column is-vertically-aligned-stretch has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--xx-large);padding-right:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--xx-large);padding-left:var(--wp--preset--spacing--x-large)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|medium"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"small","fontStyle":"normal","fontWeight":"400","letterSpacing":"0.22em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"gold"} -->
<p class="has-gold-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:var(--wp--preset--font-size--small);font-style:normal;font-weight:400;letter-spacing:0.22em;text-transform:uppercase"><?php esc_html_e( 'TODO: Eyebrow text', 'elayne' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","lineHeight":"1.05","letterSpacing":"-0.01em"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"base","fontSize":"xx-large","fontFamily":"var:preset|font-family|heading"} -->
<h2 class="wp-block-heading has-base-color has-text-color has-var-preset-font-family-heading-font-family has-xx-large-font-size" style="margin-top:0;margin-bottom:0;font-style:normal;font-weight:300;letter-spacing:-0.01em;line-height:1.05"><?php esc_html_e( 'TODO: Hero', 'elayne' ); ?><br><em style="color:var(--wp--preset--color--orange-light)"><?php esc_html_e( 'TODO: Italic', 'elayne' ); ?></em><br><?php esc_html_e( 'TODO: Headline', 'elayne' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"300","lineHeight":"1.8"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"base","fontSize":"medium"} -->
<p class="has-base-color has-text-color has-medium-font-size" style="margin-top:0;margin-bottom:0;font-weight:300;line-height:1.8"><?php esc_html_e( 'TODO: Hero description paragraph.', 'elayne' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|small","margin":{"top":"var:preset|spacing|large"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--large)"><!-- wp:button {"className":"is-style-fill","backgroundColor":"orange","textColor":"tertiary","style":{"border":{"radius":"0"},"typography":{"fontStyle":"normal","fontWeight":"500","letterSpacing":"0.16em","textTransform":"uppercase"}}} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-tertiary-color has-orange-background-color has-text-color has-background wp-element-button" style="border-radius:0;font-style:normal;font-weight:500;letter-spacing:0.16em;text-transform:uppercase"><?php esc_html_e( 'TODO: Primary CTA', 'elayne' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline","textColor":"base","style":{"border":{"radius":"0","width":"1px"},"typography":{"fontStyle":"normal","fontWeight":"500","letterSpacing":"0.16em","textTransform":"uppercase"}},"borderColor":"base"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-base-color has-text-color has-border-color has-base-border-color wp-element-button" style="border-width:1px;border-radius:0;font-style:normal;font-weight:500;letter-spacing:0.16em;text-transform:uppercase"><?php esc_html_e( 'TODO: Secondary CTA', 'elayne' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"stretch","style":{"spacing":{"padding":"0"}},"backgroundColor":"base-accent"} -->
<div class="wp-block-column is-vertically-aligned-stretch has-base-accent-background-color has-background" style="padding:0"><!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"space-between","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:cover {"dimRatio":0,"minHeight":600,"minHeightUnit":"px","style":{"dimensions":{"minHeight":"600px"},"spacing":{"padding":{"top":"var:preset|spacing|xx-large","bottom":"var:preset|spacing|xx-large"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="min-height:600px;padding-top:var(--wp--preset--spacing--xx-large);padding-bottom:var(--wp--preset--spacing--xx-large)"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","className":"elayne-hero-watermark","style":{"typography":{"fontStyle":"normal","fontWeight":"300","letterSpacing":"-0.05em"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"gold","fontSize":"x-large"} -->
<p class="has-text-align-center has-gold-color has-text-color elayne-hero-watermark has-x-large-font-size" style="margin-top:0;margin-bottom:0;font-style:normal;font-weight:300;letter-spacing:-0.05em"><?php esc_html_e( 'TODO: BRAND', 'elayne' ); ?><br><?php esc_html_e( 'TODO: TAGLINE', 'elayne' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|x-large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|x-large"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--x-large)"><!-- wp:columns {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic","fontWeight":"400"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"main-accent","fontSize":"medium"} -->
<p class="has-main-accent-color has-text-color has-medium-font-size" style="margin-top:0;margin-bottom:0;font-style:italic;font-weight:400"><?php esc_html_e( 'TODO: Bottom-left tagline', 'elayne' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"right","style":{"typography":{"fontSize":"small","fontStyle":"normal","fontWeight":"400","letterSpacing":"0.18em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"main-accent"} -->
<p class="has-text-align-right has-main-accent-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:var(--wp--preset--font-size--small);font-style:normal;font-weight:400;letter-spacing:0.18em;text-transform:uppercase"><?php esc_html_e( 'TODO: Bottom-right hint', 'elayne' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
