<?php
/**
 * Product Code for WooCommerce plugin.
 *
 * @package PcfWooCommerce
 * @wordpress-plugin
 *
 * Plugin Name: Product Code for WooCommerce
 * Plugin URI: http://wordpress.org/plugins/product-code-for-woocommerce
 * Description: This plugin will allow a user to add a unique internal product identifier in addition to the GTIN, EAN, SKU and UPC throughout the order process.
 * Version: 1.0.0
 * Author: Artios Media
 * Author URI: http://www.artiosmedia.com
 * Developer: James John (email : me@donjajo.com).
 * Copyright: © 2018 Artios Media (email : steven@artiosmedia.com).
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: product-code-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.2.0
 * WC tested up to: 3.5.1
 */

namespace Artiosmedia\WC_Product_Code;
define( 'PRODUCT_CODE_URL', plugins_url( '', __FILE__ ) );
define( 'PRODUCT_CODE_FIELD_NAMES', [
	'variant' => '_product_code_variant',
	'nonvariant' => '_product_code'
]);
define( 'PRODUCT_CODE_TEMPLATE_PATH', __DIR__ . '/templates' );
define( 'PRODUCT_CODE_PAYPAL_ID', 'E7LS2JGFPLTH2' );

require_once( __DIR__ . '/vendor/autoload.php' );

new Main();

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links ) {
	$links[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=products&section=product_code_settings' ), __( 'Settings', 'product-code-for-woocommerce' ) );

	return $links;
});

register_activation_hook( __FILE__, function() {
	$show_product = get_option( 'product_code' );
	if( !$show_product ) 
		add_option( 'product_code', 'yes' );
});

register_deactivation_hook( __FILE__, function() {
	delete_option( 'product_code' );
});