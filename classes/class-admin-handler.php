<?php
/**
 * Admin_Handler class.
 *
 * @package PcfWooCommerce
 */

namespace XedinUnknown\PcfWooCommerce;

use WC_Product_Composite;
use WC_Product_Variable;
use WP_Post;

/**
 * Handles the back-office of the plugin.
 *
 * @package PcfWooCommerce
 */
class Admin_Handler extends Handler {

	/**
	 * {@inheritdoc}
	 *
	 * @since 0.1
	 */
	protected function hook() {
		add_action(
			'admin_enqueue_scripts',
			function () {
				$post = get_post();
				if ( $post && $this->is_product( $post ) ) {
					$this->enqueue_assets_admin();
				}
			}
		);

		add_filter(
			'plugin_row_meta',
			function ( $links, $plugin_file, $plugin_data ) {
				return $this->plugin_row_filter( $links, $plugin_file, $plugin_data );
			},
			10,
			3
		);

		add_action(
			'woocommerce_product_options_inventory_product_data',
			function () {
				$post = get_post();

				if ( $post instanceof WP_Post ) {
					echo $this->get_inventory_fields_html( $post ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
		);

		add_action(
			'woocommerce_process_product_meta',
			function () {
				$post = get_post();

				$this->process_product_meta( $post );
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
			'woo-admin-add-gtin1',
			$this->get_js_url( 'stl_admin_custom.js' ),
			[ 'jquery' ],
			$this->get_config( 'version' ),
			false
		);
	}

	/**
	 * Enqueues assets necessary for the admin pages.
	 *
	 * @since 0.1
	 */
	protected function enqueue_assets_admin() {
		wp_enqueue_script( 'woo-admin-add-gtin1' );

		$post = get_post();
		if ( $post && $this->is_product( $post ) ) {
			wp_localize_script( 'woo-admin-add-gtin1', 'wooGtinVars', $this->get_product_page_vars( $post ) );
		}
	}

	/**
	 * Modifies the list of links for this plugin, improving it.
	 *
	 * @since 0.1
	 *
	 * @param array  $links List of links for plugin.
	 * @param string $plugin_file The name of the plugin file.
	 * @param array  $plugin_data Info about the plugin. See {@link https://core.trac.wordpress.org/browser/tags/4.9.8/src/wp-admin/includes/class-wp-plugins-list-table.php#L805}.
	 *
	 * @return array A list of links.
	 */
	protected function plugin_row_filter( $links, $plugin_file, $plugin_data ) {
		// Not our plugin.
		if ( plugin_basename( $plugin_file ) !== plugin_basename( $this->get_config( 'base_path' ) ) ) {
			return $links;
		}

		$slug          = basename( $plugin_data['PluginURI'] );
		$link_template = $this->get_template( 'link' );

		$links[2] = $link_template->render(
			[
				'href'    => add_query_arg(
					[
						'tab'       => 'plugin-information',
						'plugin'    => $slug,
						'TB_iframe' => 'true',
						'width'     => 772,
						'height'    => 563,
					],
					self_admin_url( 'plugin-install.php' )
				),
				// translators: Plugin name placeholder.
				'title'   => sprintf( __( 'More information about %s', 'product-code-for-woocommerce' ), $plugin_data['Name'] ),
				'content' => __( 'View Details', 'product-code-for-woocommerce' ),
			]
		);

		$links['donation'] = $link_template->render(
			[
				'href'    => add_query_arg(
					[
						'cmd'              => '_s-xclick',
						'hosted_button_id' => $this->get_config( 'donate_paypal_btn_id' ),
					],
					'https://www.paypal.com/cgi-bin/webscr'
				),
				'target'  => '_blank',
				'content' => __( 'Donation for Homeless', 'product-code-for-woocommerce' ),
			]
		);

		return $links;
	}

	/**
	 * Retrieves the HTML with fields for the "Inventory" tab of the product page.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The post of the product for which to get the HTML.
	 *
	 * @return string The HTML.
	 */
	protected function get_inventory_fields_html( $post ) {
		$field_name = $this->get_config( 'product_code_field_name' );

		return $this->get_template( 'wc-text-input' )->render(
			[
				'id'          => $this->get_config( 'product_code_field_name' ),
				'label'       => __( 'Product Code', 'product-code-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', 'product-code-for-woocommerce' ),
				'value'       => get_post_meta( $post->ID, $field_name, true ),
			]
		);
	}

	/**
	 * Processes meta data for a product.
	 *
	 * Saves custom meta.
	 *
	 * @since 0.1
	 *
	 * @param int WP_Post The post that the meta data is being processed for.
	 */
	protected function process_product_meta( $post ) {
		// Verify nonce.
		if ( ! ( isset( $_POST['woocommerce_meta_nonce'] )
			|| wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) ) {
			return;
		}

		$field_name = $this->get_config( 'product_code_field_name' );
		$post_id    = $post->ID;

		// Save the product code as meta if passed.
		if ( isset( $_POST[ $field_name ] ) ) {
			$gtin_post = sanitize_text_field( $_POST[ $field_name ] );
			update_post_meta( $post_id, $field_name, $gtin_post );
		}

		// Remove product code meta if empty.
		$gtin_meta = get_post_meta( $post_id, $field_name, true );
		if ( empty( $gtin_meta ) ) {
			delete_post_meta( $post_id, $field_name );
		}
	}

	/**
	 * Retrieves variables to be used on a product page.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The post for which to get the vars.
	 *
	 * @return array The map of variable names to values.
	 */
	protected function get_product_page_vars( $post ) {

		$vars = [
			'gtin' => get_post_meta( $post->ID, $this->get_config( 'product_code_field_name' ), true ),
		];

		$wc = $this->get_config( 'woocommerce' );

		// WooCommerce not installed.
		if ( ! ( $wc instanceof WooCommerce ) ) {
			return $vars;
		}

		$variations           = [];
		$composite_variations = [];

		// Handling variations before WooCommerce 3.2.
		if ( version_compare( $wc->version, '3.2', 'lt' ) ) {
			$vars       = new WC_Product_Variable( $post->ID );
			$variations = $vars->get_available_variations();
		} else {
			$product = $wc->product_factory->get_product( $post );

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
			}

			if ( $product->is_type( 'composite' ) ) {
				/* @var $product WC_Product_Composite */
				$vars['is_composite'] = true;

				$components = $product->get_composite_data();
				foreach ( $components as $component_id => $component ) {
					$comp        = $product->get_component( $component_id );
					$product_ids = $comp->get_options();

					foreach ( $product_ids as $id ) {
						$variable_product       = new WC_Product_Variable( $id );
						$composite_variations[] = $variable_product->get_available_variations();
					}
				}
			}
		}

		$variant_field_name = $this->get_config( 'product_code_variant_field_name' );

		foreach ( $variations as $variation ) {
			if ( ! empty( $variation ) && false !== $variation['variation_is_active'] ) {
				$vars['variation_gtins'][ $variation['variation_id'] ] = get_post_meta( $variation['variation_id'], $variant_field_name, true );
			}
		}

		foreach ( $composite_variations as $id => $comp_variation ) {
			foreach ( $comp_variation as $variation ) {
				if ( ! empty( $variation ) && false !== $variation['variation_is_active'] ) {
					$vars['composite_variation_pcm'][ $variation['variation_id'] ] = get_post_meta( $variation['variation_id'], $variant_field_name, true );
				}
			}
		}

		return $vars;
	}

	/**
	 * Checks if a post is a product.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The post to check.
	 *
	 * @return bool True if the post is a product; false otherwise.
	 */
	protected function is_product( $post ) {
		$type = $post->post_type;

		return in_array( $type, [ 'product', 'product_variation' ], true );
	}
}
