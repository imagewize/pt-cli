<?php
/**
 * Title: TODO: Pattern Title
 * Slug: elayne/TODO-slug
 * Description: TODO: One-line description
 * Categories: elayne/TODO-category
 * Keywords: TODO keyword1, keyword2
 * Viewport Width: 1200
 * Block Types: woocommerce/product-collection
 * Inserter: true
 */
?>
<!-- TEMPLATE: woo-product-grid — filter-aware product-collection grid with toolbar (sort + results count) + pagination -->
<!-- RULES CHECKED: margin reset ✓ | no spacers ✓ | preset fonts ✓ | translation wrappers ✓ | product-collection ✓ -->
<!-- NOTE: queryId 0 is a placeholder. Must be unique per page — change to 1 (or next free integer) after inserting. -->
<!-- NOTE: Pair with woo-filters-sidebar in a two-column layout. Both blocks share the same queryId for filter linking. -->
<!-- NOTE: displayLayout columns=3 → adjust to 2 or 4 to match your grid width. -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":3},"queryContextIncludes":["collection"],"isFilterDataSource":true,"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|medium"},"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--medium)"><!-- wp:woocommerce/product-collection-toolbar -->
<div class="wp-block-woocommerce-product-collection-toolbar"><!-- wp:woocommerce/product-collection-sort-by /-->
<!-- wp:woocommerce/product-collection-results-count /--></div>
<!-- /wp:woocommerce/product-collection-toolbar --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":true,"isDescendentOfQueryLoop":true,"aspectRatio":"4/5"} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"var:preset|spacing|x-small","bottom":"0"}}},"fontSize":"base","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"paginationArrow":"arrow","style":{"spacing":{"margin":{"top":"var:preset|spacing|large","bottom":"0"}}}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

</div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->
