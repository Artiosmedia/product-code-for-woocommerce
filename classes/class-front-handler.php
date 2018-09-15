<?php
/**
 * Front_Handler class.
 *
 * @package PcfWooCommerce
 */

namespace XedinUnknown\PcfWooCommerce;

/**
 * Handles the customer-facing part of the plugin.
 *
 * @package PcfWooCommerce
 */
class Front_Handler extends Handler {

	/**
	 * {@inheritdoc}
	 *
	 * @since 0.1
	 */
	protected function hook() {
		add_action(
			'init',
			function () {
				$this->register_assets();
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( function_exists( 'is_product' ) && is_product() ) {
					$this->enqueue_assets_product();
				}
			}
		);
	}

	/**
	 * Registers assets used by the front-office.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function register_assets() {
		wp_register_script(
			'woo-add-gtin1',
			$this->get_js_url( 'stl_custom.js' ),
			[ 'wc-add-to-cart-variation', 'jquery' ],
			$this->get_config( 'version' ),
			false
		);
	}

	/**
	 * Enqueues assets necessary for the product page.
	 *
	 * @since 0.1
	 */
	protected function enqueue_assets_product() {
		wp_enqueue_script( 'woo-add-gtin1' );
	}

}
