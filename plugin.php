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
 * Version: 0.1
 * Author: Artios Media
 * Author URI: http://www.artiosmedia.com
 * Developer: Vijaya Shanthi (email : vijayasanthi@stallioni.com).
 * Copyright: © 2018 Artios Media (email : steven@artiosmedia.com).
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: product-code-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.2.0
 * WC tested up to: 3.4.5
 */

namespace Artiosmedia\PcfWooCommerce;

/**
 * Retrieves the plugin singleton.
 *
 * @since 0.1
 *
 * @return null|Plugin
 */
function plugin() {
	static $instance = null;

	$autoload_file = __DIR__ . '/vendor/autoload.php';
	if ( file_exists( $autoload_file ) ) {
		require $autoload_file;
	}

	if ( is_null( $instance ) ) {
		$base_path        = __FILE__;
		$base_dir         = dirname( $base_path );
		$base_url         = plugins_url( '', $base_path );
		$services_factory = require_once "$base_dir/services.php";
		$services         = $services_factory( $base_path, $base_url );
		$container        = new DI_Container( $services );

		$instance = new Plugin( $container );
	}

	return $instance;
}

plugin()->run();
