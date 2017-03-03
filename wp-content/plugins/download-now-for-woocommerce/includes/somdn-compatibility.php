<?php
/**
 * DOWNLOAD NOW - WooCommerce - Compatibility Functions
 * 
 * Functions for compatibility with other plugins.
 * 
 * @version	2.3.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets up the settings and setting pages for WooCommerce Memberships.
 *
 * @since 2.3.6
 */
add_action( 'somdn_settings_subtabs_after_multiple' , 'somdn_settings_subtabs_memberships', 10, 1 );
function somdn_settings_subtabs_memberships( $active_section ) {
	if ( ! somdn_memberships() ) return;
	$nav_active = ( $active_section == 'memberships' ) ? 'current' : '' ;
	echo '<li><a href="' . somdn_get_plugin_link_full() . '&tab=settings&section=memberships" class="' . $nav_active . '">Memberships</a> | </li>';
}

add_action( 'admin_init', 'somdn_settings_memberships' );

function somdn_settings_memberships() {

	if ( ! somdn_memberships() ) return;

	register_setting( 'somdn_memberships_settings', 'somdn_memberships_settings' );

	add_settings_section(
		'somdn_memberships_settings_section', 
		__( 'WooCommerce Membership Settings', 'download-now-for-woocommerce' ), 
		'somdn_memberships_settings_section_callback', 
		'somdn_memberships_settings'
	);

	add_settings_field( 
		'somdn_memberships_global', 
		__( 'Global settings', 'download-now-for-woocommerce' ), 
		'somdn_memberships_global_render', 
		'somdn_memberships_settings', 
		'somdn_memberships_settings_section' 
	);

}

function somdn_memberships_global_render() { 

	$options = get_option( 'somdn_memberships_settings' );
	$optionvalue = ( isset( $options['somdn_memberships_global'] ) && $options['somdn_memberships_global'] ) ? $options['somdn_memberships_global'] : '' ;
	
	if ( ! $optionvalue ) {
		$optionvalue = 1;
	}

	?>

	<div class="somdn-setting-wrapper">
	<label for="somdn_memberships_global_1">

	<input type="radio" id="somdn_memberships_global_1" name="somdn_memberships_settings[somdn_memberships_global]" value="1" <?php checked( 1, $optionvalue, true ); ?>>

	Include Membership restricted items
	</label>
	<p class="description">This setting will enable restricted free products to be purchased by users with the correct membership.</p>
	
	</div>
	
	<div class="somdn-setting-wrapper">

	<label for="somdn_memberships_global_2">

	<input type="radio" id="somdn_memberships_global_2" name="somdn_memberships_settings[somdn_memberships_global]" value="2" <?php checked( 2, $optionvalue, true ); ?>>

	Exclude Membership restricted items
	</label>
	<p class="description">Any items that require a membership, regardless of price, will be excluded.</p>

	</div>

	<div class="somdn-setting-wrapper">

	<label for="somdn_memberships_global_3">

	<input type="radio" id="somdn_memberships_global_3" name="somdn_memberships_settings[somdn_memberships_global]" value="3" <?php checked( 3, $optionvalue, true ); ?>>

	Members only
	</label>
	<p class="description">Excludes all free products except members only items.</p>

	</div>

	<?php

}

add_action( 'somdn_settings_page_content' , 'somdn_settings_content_memberships', 10, 1 );
function somdn_settings_content_memberships( $active_section ) {
	if ( ! somdn_memberships() ) return;
	if ( 'memberships' == $active_section ) {
		somdn_memberships_settings_content();
	}
}

function somdn_memberships_settings_section_callback() { 
	echo __( 'Customise the experience for your WooCommerce Membership site.', 'download-now-for-woocommerce' );
}

function somdn_memberships_settings_content() { ?>

	<div class="somdn-container">
		<div class="somdn-row">
		
			<div class="somdn-col-12">
	
				<form action="options.php" class="somdn-settings-form" method="post">
			
					<div class="somdn-gen-settings-form-wrap">
			
					<?php
					settings_fields( 'somdn_memberships_settings' );
					do_settings_sections( 'somdn_memberships_settings' );
					submit_button();
					?>
			
					</div>
			
				</form>
		
			</div>

		</div>
	</div>

<?php

}

function somdn_memberships() {
	if ( function_exists( 'wc_memberships' ) ) {
		return true;
	}
}

function somdn_is_product_valid_compat( $productID ) {

	if ( function_exists( 'wc_memberships' ) ) {
	
		$postype = get_post_type( $productID );

		/**
		 * Membership option values
		 * 
		 * 1 = Include Membership restricted items (default)
		 * 2 = Exclude Membership restricted items
		 * 3 = Members only
		 * 
		 */
		$membership_options = get_option( 'somdn_memberships_settings' );
		$option = ( isset( $membership_options['somdn_memberships_global'] ) && $membership_options['somdn_memberships_global'] ) ? $membership_options['somdn_memberships_global'] : 1 ;
		
		$has_access = somdn_is_user_member_purchase( $productID );

		/**
		 * If product is a membership plan, prevent.
		 */
		if ( 'wc_membership_plan' == $postype ) {
			return false;
		}

		/**
		 * If product is restricted and restricted items are excluded, prevent.
		 */
		if ( somdn_is_member_restricted( $productID ) && $option == 2 ) {
			return false;
		}

		/**
		 * If product is not restricted and only restricted items allowed, prevent.
		 * This prevents free download of any other other products entirely.
		 */
		if ( ! somdn_is_member_restricted( $productID ) && $option == 3 ) {
			return false;
		}

		/**
		 * If product is restricted and the user has access, and only restricted items allowed, allow.
		 */	
		if ( ( $has_access && $option == 3 ) ) {
			return true;
		}

		/**
		 * Default behaviour. If restricted and user does not have access, prevent.
		 */	
		if ( ! $has_access ) {
			return false;
		}

	}

	if ( class_exists( 'WC_Subscriptions_Product' ) ) {
	
		$subscriptions = array( 'subscription', 'variable-subscription' );
	
		if ( has_term( $subscriptions, 'product_type', $productID ) ) {
			return false;
		}
		
	}

	return true;

}

function somdn_is_user_member_purchase( $productID ) {

	$has_access = current_user_can( 'wc_memberships_purchase_restricted_product', $productID );

	if ( $has_access ) {
		return true;
	} else {
		return false;
	}

	return true;

}

function somdn_is_member_restricted( $productID ) {

	$post_id = $productID;

	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$rules = wc_memberships()->rules->get_the_product_restriction_rules( $post_id );

	$is_resticted = false;

	if ( ! empty( $rules ) ) {

		foreach ( $rules as $rule ) {

			if ( 'purchase' == $rule->get_access_type() ) {
				$is_resticted = true;
			}
		}
	}

	return $is_resticted;

}

function somdn_is_purchasable_compat( $purchasable ) {

	if ( somdn_ti_wishlist_exists() && is_product() ) {
		$purchasable = true;
	}

	return $purchasable;

}

add_action('wp_head', 'somdn_purchasable_compat_head');

function somdn_purchasable_compat_head() {

	if ( ! is_product() ) {
		return;
	}

	$product = wc_get_product();

	if ( ! $product ) {
		global $product;
	}

	if ( ! $product ) {
		return;
	}

	$productID = $product->id;
	$downloadable = $product->downloadable;

	if ( somdn_is_product_valid( $productID, $downloadable ) ) {

		if ( somdn_ti_wishlist_exists() ) {
			somdn_ti_wishlist_header();
		}

	}

}

function somdn_ti_wishlist_exists() {
	if ( class_exists( 'TInvWL_Public_AddToWishlist' ) ) {
		return true;
	}		
}

function somdn_ti_wishlist_add_to_cart() {
	if ( somdn_ti_wishlist_exists() ) {
		$position = tinv_get_option( 'add_to_wishlist', 'position' );
			if ( 'shortcode' != $position ) {
				return true;
			}
	}	
}

function somdn_ti_wishlist_show_link() {
	//echo do_shortcode( '[ti_wishlists_addtowishlist]' );
}

function somdn_ti_wishlist_header() { ?>
<style>
	.single-product div.product form.cart { display: none!important; }
	.tinv-wraper.woocommerce.tinv-wishlist.tinvwl-shortcode-add-to-cart { padding-bottom: 15px; }
</style>
<?php
}

function somdn_hide_cart_style() { ?>
<style>
	.single-product div.product form.cart { display: none!important; }
</style>
<?php
}