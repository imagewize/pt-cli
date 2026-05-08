<?php
/**
 * Title: TODO: Pattern Title
 * Slug: elayne/TODO-slug
 * Description: TODO: One-line description
 * Categories: elayne/TODO-category
 * Keywords: TODO keyword1, keyword2
 * Viewport Width: 1200
 * Grid Config: 19rem (complex pricing cards: heading + subheading + feature list + pricing + CTA)
 */
?>
<!-- wp:group {"metadata":{"patternName":"elayne/TODO-slug","name":"Pricing Comparison"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|xxx-large","bottom":"var:preset|spacing|xxx-large","left":"var:preset|spacing|medium","right":"var:preset|spacing|medium"},"margin":{"top":"0","bottom":"0"},"blockGap":"var:preset|spacing|x-large"}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--xxx-large);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--xxx-large);padding-left:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","fontSize":"x-large"} -->
		<h2 class="wp-block-heading has-text-align-center has-x-large-font-size"><?php esc_html_e( 'TODO: Section heading', 'elayne' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"base"} -->
		<p class="has-text-align-center has-base-font-size"><?php esc_html_e( 'TODO: Supporting description text.', 'elayne' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|large"}},"layout":{"type":"grid","minimumColumnWidth":"19rem"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large"},"blockGap":"var:preset|spacing|medium"},"border":{"radius":"16px"}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-tertiary-background-color has-background" style="padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large);border-radius:16px">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","fontSize":"large"} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color has-large-font-size"><?php esc_html_e( 'TODO: Plan 1 name', 'elayne' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"textAlign":"center","fontSize":"base"} -->
			<h4 class="wp-block-heading has-text-align-center has-base-font-size"><?php esc_html_e( "What You'll Get", 'elayne' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"is-style-checkmark-list","textColor":"main"} -->
			<ul class="is-style-checkmark-list has-main-color has-text-color">
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature one', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature two', 'elayne' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->

			<!-- wp:separator {"backgroundColor":"border-light","className":"is-style-dots"} -->
			<hr class="wp-block-separator has-text-color has-border-light-color has-alpha-channel-opacity has-border-light-background-color has-background is-style-dots"/>
			<!-- /wp:separator -->

			<!-- wp:heading {"level":4,"textAlign":"center","textColor":"primary","fontSize":"x-large"} -->
			<h4 class="wp-block-heading has-text-align-center has-primary-color has-text-color has-x-large-font-size"><?php esc_html_e( 'Free Forever', 'elayne' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"primary","textColor":"base","style":{"border":{"radius":"4px"},"typography":{"fontSize":"var:preset|font-size|base"}}} -->
				<div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-base-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:4px;font-size:var(--wp--preset--font-size--base)"><?php esc_html_e( 'Get Started', 'elayne' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large"},"margin":{"top":"-2rem","bottom":"0"},"blockGap":"var:preset|spacing|medium"},"border":{"radius":"16px"},"shadow":"var:preset|shadow|large"},"backgroundColor":"primary-alt","layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-primary-alt-background-color has-background" style="margin-top:-2rem;margin-bottom:0;padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large);border-radius:16px;box-shadow:var(--wp--preset--shadow--large)">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"base","fontSize":"large"} -->
			<h3 class="wp-block-heading has-text-align-center has-base-color has-text-color has-large-font-size"><?php esc_html_e( 'TODO: Plan 2 name (recommended)', 'elayne' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"textAlign":"center","textColor":"base","fontSize":"base"} -->
			<h4 class="wp-block-heading has-text-align-center has-base-color has-text-color has-base-font-size"><?php esc_html_e( "What You'll Get", 'elayne' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"is-style-checkmark-list","textColor":"base"} -->
			<ul class="is-style-checkmark-list has-base-color has-text-color">
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature one', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature two', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature three', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature four', 'elayne' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->

			<!-- wp:separator {"className":"is-style-dots","style":{"color":{"background":"rgba(255,255,255,0.3)"}}} -->
			<hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background is-style-dots" style="background-color:rgba(255,255,255,0.3);color:rgba(255,255,255,0.3)"/>
			<!-- /wp:separator -->

			<!-- wp:heading {"level":4,"textAlign":"center","textColor":"base","fontSize":"x-large"} -->
			<h4 class="wp-block-heading has-text-align-center has-base-color has-text-color has-x-large-font-size"><?php esc_html_e( '$XX', 'elayne' ); ?><span style="font-size:50%;"><?php esc_html_e( '/month', 'elayne' ); ?></span></h4>
			<!-- /wp:heading -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"primary","textColor":"base","style":{"border":{"radius":"50px"},"typography":{"fontSize":"var:preset|font-size|base"}}} -->
				<div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-base-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:50px;font-size:var(--wp--preset--font-size--base)"><?php esc_html_e( 'Get Started', 'elayne' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large"},"blockGap":"var:preset|spacing|medium"},"border":{"radius":"16px"}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
		<div class="wp-block-group has-tertiary-background-color has-background" style="padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large);border-radius:16px">
			<!-- wp:heading {"level":3,"textAlign":"center","textColor":"primary","fontSize":"large"} -->
			<h3 class="wp-block-heading has-text-align-center has-primary-color has-text-color has-large-font-size"><?php esc_html_e( 'TODO: Plan 3 name', 'elayne' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":4,"textAlign":"center","fontSize":"base"} -->
			<h4 class="wp-block-heading has-text-align-center has-base-font-size"><?php esc_html_e( "What You'll Get", 'elayne' ); ?></h4>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"is-style-checkmark-list","textColor":"main"} -->
			<ul class="is-style-checkmark-list has-main-color has-text-color">
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature one', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature two', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature three', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature four', 'elayne' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php esc_html_e( 'TODO: Feature five', 'elayne' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->

			<!-- wp:separator {"backgroundColor":"border-light","className":"is-style-dots"} -->
			<hr class="wp-block-separator has-text-color has-border-light-color has-alpha-channel-opacity has-border-light-background-color has-background is-style-dots"/>
			<!-- /wp:separator -->

			<!-- wp:heading {"level":4,"textAlign":"center","textColor":"primary","fontSize":"x-large"} -->
			<h4 class="wp-block-heading has-text-align-center has-primary-color has-text-color has-x-large-font-size"><?php esc_html_e( '$XX', 'elayne' ); ?><span style="font-size:50%;"><?php esc_html_e( '/month', 'elayne' ); ?></span></h4>
			<!-- /wp:heading -->

			<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"primary","textColor":"base","style":{"border":{"radius":"4px"},"typography":{"fontSize":"var:preset|font-size|base"}}} -->
				<div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-base-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:4px;font-size:var(--wp--preset--font-size--base)"><?php esc_html_e( 'Get Started', 'elayne' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
