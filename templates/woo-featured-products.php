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
<!-- TEMPLATE: woo-product-grid — section header with View All button + woocommerce/product-collection 4-col grid ordered by menu_order -->
<!-- RULES CHECKED: margin reset ✓ | no spacers ✓ | translation wrappers ✓ | preset fonts ✓ | woo product-collection ✓ -->
<!-- NOTE: Uses product-collection with menu_order sort — change queryId if multiple product-collection blocks exist on the same page -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|xx-large","bottom":"var:preset|spacing|xx-large"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-tertiary-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--xx-large);padding-bottom:var(--wp--preset--spacing--xx-large)"><!-- wp:columns {"style":{"spacing":{"blockGap":"var:preset|spacing|medium","margin":{"top":"0","bottom":"var:preset|spacing|x-large"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"blockGap":"var:preset|spacing|small"}}} -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"typography":{"fontSize":"small","fontStyle":"normal","fontWeight":"400","letterSpacing":"0.22em","textTransform":"uppercase"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"orange"} -->
<p class="has-orange-color has-text-color" style="margin-top:0;margin-bottom:0;font-size:var(--wp--preset--font-size--small);font-style:normal;font-weight:400;letter-spacing:0.22em;text-transform:uppercase"><?php esc_html_e( 'TODO: Section eyebrow', 'elayne' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"300","lineHeight":"1.1","letterSpacing":"-0.01em"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"primary","fontSize":"x-large","fontFamily":"var:preset|font-family|heading"} -->
<h2 class="wp-block-heading has-primary-color has-text-color has-var-preset-font-family-heading-font-family has-x-large-font-size" style="margin-top:0;margin-bottom:0;font-style:normal;font-weight:300;letter-spacing:-0.01em;line-height:1.1"><?php esc_html_e( 'TODO: Section heading', 'elayne' ); ?><br><em style="color:var(--wp--preset--color--main-accent)"><?php esc_html_e( 'TODO: Italic accent', 'elayne' ); ?></em></h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:buttons {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons" style="margin-top:0;margin-bottom:0"><!-- wp:button {"className":"is-style-outline","textColor":"primary","style":{"border":{"radius":"0","width":"1px"},"typography":{"fontStyle":"normal","fontWeight":"500","letterSpacing":"0.16em","textTransform":"uppercase"}},"borderColor":"primary"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-primary-color has-text-color has-border-color has-primary-border-color wp-element-button" style="border-width:1px;border-radius:0;font-style:normal;font-weight:500;letter-spacing:0.16em;text-transform:uppercase"><?php esc_html_e( 'TODO: View all CTA', 'elayne' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":4,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":4},"queryContextIncludes":["collection"],"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"4/5"} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0","top":"var:preset|spacing|x-small"}}},"fontSize":"base","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
