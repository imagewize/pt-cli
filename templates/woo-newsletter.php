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
<!-- TEMPLATE: woo-newsletter-band — full-bleed primary background with centered heading and subscription form -->
<!-- RULES CHECKED: margin reset ✓ | constrained inner ✓ | no spacers ✓ | alignfull ✓ | translation wrappers ✓ | preset fonts ✓ -->
<!-- NOTE: Uses wp:search as fallback; wraps in CF7 conditional if Contact Form 7 is active -->
<!-- TODO: Update the CF7 shortcode ID if using Contact Form 7, or remove the conditional and keep only wp:search -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xx-large","bottom":"var:preset|spacing|xx-large"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"primary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-primary-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--xx-large);padding-bottom:var(--wp--preset--spacing--xx-large)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|large"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:columns {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"small","fontStyle":"normal","fontWeight":"400","letterSpacing":"0.22em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"gold","fontFamily":"var:preset|font-family|heading"} -->
<p class="has-text-align-center has-gold-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:var(--wp--preset--font-size--small);font-style:normal;font-weight:400;letter-spacing:0.22em;text-transform:uppercase">
<span style="display:inline-block;width:60px;height:1px;background:var(--wp--preset--color--gold)"></span>
<?php esc_html_e( 'TODO: Section eyebrow', 'elayne' ); ?>
<span style="display:inline-block;width:60px;height:1px;background:var(--wp--preset--color--gold)"></span>
</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"300","lineHeight":"1.1","letterSpacing":"-0.01em"},"spacing":{"margin":{"top":"var:preset|spacing|medium","bottom":"0"}}},"textColor":"base","fontSize":"x-large","fontFamily":"var:preset|font-family|heading"} -->
<h2 class="wp-block-heading has-text-align-center has-base-color has-text-color has-var-preset-font-family-heading-font-family has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--medium);margin-bottom:0;font-style:normal;font-weight:300;line-height:1.1;letter-spacing:-0.01em"><?php esc_html_e( 'TODO: Heading line 1', 'elayne' ); ?> <em style="color:var(--wp--preset--color--gold)"><?php esc_html_e( 'TODO: Italic accent', 'elayne' ); ?></em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"300","lineHeight":"1.8"},"spacing":{"margin":{"top":"var:preset|spacing|medium","bottom":"0"}}},"textColor":"main-accent","fontSize":"medium"} -->
<p class="has-text-align-center has-main-accent-color has-text-color" style="margin-top:var(--wp--preset--spacing--medium);margin-bottom:0;font-weight:300;line-height:1.8;font-size:var(--wp--preset--font-size--medium)"><?php esc_html_e( 'TODO: Description text.', 'elayne' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:columns {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column"></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column">
<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<?php if ( function_exists( 'wpcf7_contact_form' ) ) : ?>
<!-- wp:contact-form-7/contact-form-selector -->
<div class="wp-block-contact-form-7-contact-form-selector">[contact-form-7]</div>
<!-- /wp:contact-form-7/contact-form-selector -->
<?php else : ?>
<!-- wp:search {"className":"elayne-woo-newsletter-search","label":"","showLabel":false,"placeholder":"TODO: Email placeholder text","buttonText":"TODO: Button text","buttonPosition":"button-inside","buttonUseIcon":false,"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-search elayne-woo-newsletter-search wp-block-search__button-inside wp-block-search__icon-button">
<label for="wp-block-search__input-1" class="wp-block-search__label screen-reader-text"><?php esc_html_e( 'TODO: Subscribe label', 'elayne' ); ?></label>
<div class="wp-block-search__inside-wrapper">
<input type="search" id="wp-block-search__input-1" class="wp-block-search__input" placeholder="<?php esc_attr_e( 'TODO: Email placeholder text', 'elayne' ); ?>" value="" />
<button type="submit" class="wp-block-search__button wp-element-button"><?php esc_html_e( 'TODO: Button text', 'elayne' ); ?></button>
</div>
</div>
<!-- /wp:search -->
<?php endif; ?>
</div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column"></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
