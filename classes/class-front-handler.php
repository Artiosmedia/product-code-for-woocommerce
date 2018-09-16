<?php
/**
 * Front_Handler class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

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

		add_action(
			'woocommerce_product_meta_start',
			function () {
				echo $this->get_product_meta_before_html(); // phpcs:ignore WordPress.Security.EscapeOutput
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

	protected function get_product_meta_before_html()	{
		$post = get_post();
		$value = get_post_meta( $post->ID, $this->get_config( 'product_code_field_name' ), true );

		if ( empty( $value ) ) {
			return '';
		}

		return $this->get_template('product-meta-row' )->render(
			[
				'title' => __( 'Product Code', 'product-code-for-woocommerce' ),
				'value' => $value
			]
		);
	}
}
