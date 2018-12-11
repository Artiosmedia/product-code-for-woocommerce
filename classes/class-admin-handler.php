<?php
/**
 * Admin_Handler class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

use WC_Product;
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
			'init',
			function () {
				$this->register_assets();
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				$screen = get_current_screen();

				if ( $screen && 'product' === $screen->post_type ) {
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

		add_action(
			'woocommerce_product_after_variable_attributes',
			/**
			 * Outputs something after WooCommerce variable attributes.
			 *
			 * @param int     $loop The index of the field in the loop.
			 * @param array   $variation_data Variation data.
			 * @param WP_Post $variation The post that represents the variation.
			 *
			 * @see https://github.com/woocommerce/woocommerce/blob/1258f242ff2b663dfcd0c11ae2a428cb8af88f17/includes/admin/meta-boxes/views/html-variation-admin.php#L437
			 */
			function ( $loop, $variation_data, $variation ) {
				echo $this->get_variable_fields_html( $loop, $variation_data, $variation ); // phpcs:ignore WordPress.Security.EscapeOutput
			},
			10,
			3
		);

		add_action(
			'woocommerce_save_product_variation',
			/**
			 * Processes a product variation.
			 *
			 * @since 0.1
			 *
			 * @param int $variation_id The ID of the variation that is being processed.
			 * @param int $loop The index of the variation field in the loop.
			 */
			function ( $variation_id, $loop ) {
				$this->process_variations_data( $variation_id, $loop );
			},
			10,
			2
		);

		add_action(
			'woocommerce_product_quick_edit_end',
			function () {
				echo $this->get_quick_edit_fields_html(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		);

		add_action(
			'woocommerce_product_quick_edit_save',
			/**
			 * Processes quick edit data for a product.
			 *
			 * @since 0.1
			 *
			 * @see https://github.com/woocommerce/woocommerce/blob/4fd6dbd880ace84567649d4747f6e812014e609c/includes/admin/class-wc-admin-post-types.php#L437
			 *
			 * @param WC_Product $product The product for which data is being processed.
			 */
			function ( $product ) {
				$this->process_quick_edit_data( $product );
			}
		);

		add_action(
			'manage_product_posts_custom_column',
			/**
			 * Retrieves the HTML for the hidden product list column.
			 *
			 * @since 0.1
			 *
			 * @param string $column The name of the column being managed.
			 * @param int    $post_id The ID of the post for which the row is being generated.
			 *
			 * @return string The HTML.
			 */
			function ( $column, $post_id ) {
				if ( 'name' === $column ) {
					echo $this->get_hidden_product_list_column_html( $column, $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
			},
			10,
			2
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
		$product = wc_get_product( $post->ID );

		if( !$product->is_type( 'variable' ) ):
			return $this->get_template( 'wc-text-input' )->render(
				[
					'id'          => $this->get_config( 'product_code_field_name' ),
					'label'       => __( 'Product Code', 'product-code-for-woocommerce' ),
					'desc_tip'    => true,
					'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', 'product-code-for-woocommerce' ),
					'value'       => get_post_meta( $post->ID, $field_name, true ),
				]
			);
		endif;
	}

	/**
	 * Processes meta data for a product.
	 *
	 * Saves custom meta.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The post that the meta data is being processed for.
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
	 * Retrieves the HTML of variable product fields.
	 *
	 * @param int     $loop The index of the field in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation The post that represents the variation.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/1258f242ff2b663dfcd0c11ae2a428cb8af88f17/includes/admin/meta-boxes/views/html-variation-admin.php#L437
	 *
	 * @return string The fields HTML.
	 */
	protected function get_variable_fields_html( $loop, $variation_data, $variation ) {
		$field_name = $this->get_config( 'product_code_variant_field_name' );

		return $this->get_template( 'variation-field' )->render(
			[
				'input' => $this->create_template_block(
					'wc-text-input',
					[
						'id'          => vsprintf( '%1$s[%2$s]', [ $field_name, $variation->ID ] ),
						'label'       => __( 'Product Code', 'product-code-for-woocommerce' ),
						'desc_tip'    => true,
						'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', 'product-code-for-woocommerce' ),
						'value'       => get_post_meta( $variation->ID, $field_name, true ),
					]
				),
			]
		);
	}

	/**
	 * Process variation data for a product.
	 *
	 * @since 0.1
	 *
	 * @param int $variation_id The ID of the variation post that is being processed.
	 * @param int $loop The index of the current field in the loop.
	 */
	protected function process_variations_data( $variation_id, $loop ) {
		// Verify nonce.
		if ( ! ( isset( $_POST['security'] )
			|| wp_verify_nonce( sanitize_key( $_POST['security'] ), 'woocommerce_load_variations' ) ) ) {
			return;
		}

		$field_name = $this->get_config( 'product_code_variant_field_name' );

		// Save the product code as meta if passed.
		if ( isset( $_POST[ $field_name ] ) && isset( $_POST[ $field_name ][ $variation_id ] ) ) {
			$tn_post = sanitize_text_field( $_POST[ $field_name ][ $variation_id ] );
			update_post_meta( $variation_id, $field_name, $tn_post );
		}

		// Remove product code meta if empty.
		$tn_meta = get_post_meta( $variation_id, $field_name, true );
		if ( empty( $tn_meta ) ) {
			delete_post_meta( $variation_id, $field_name );
		}
	}

	/**
	 * Retrieves the HTML for quick edit fields.
	 *
	 * @since 0.1
	 *
	 * @return string The HTML.
	 */
	protected function get_quick_edit_fields_html() {
		return $this->get_template( 'quick-edit-text-field' )->render(
			[
				'title' => __( 'Product Code', 'product-code-for-woocommerce' ),
				'name'  => $this->get_config( 'product_code_field_name' ),
				'class' => 'product_code',
			]
		);
	}

	/**
	 * Processes quick edit data for a product.
	 *
	 * @since 0.1
	 *
	 * @param WC_Product $product The product for which data is being processed.
	 */
	protected function process_quick_edit_data( $product ) {
		// Verify nonce.
		if ( ! ( isset( $_POST['woocommerce_quick_edit_nonce'] )
			|| wp_verify_nonce( sanitize_key( $_POST['woocommerce_quick_edit_nonce'] ), 'woocommerce_quick_edit_nonce' ) ) ) {
			return;
		}

		$field_name = $this->get_config( 'product_code_field_name' );

		if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {
			$post_id = $product->get_id();

			if ( isset( $_REQUEST[ $field_name ] ) ) {
				$product_code = sanitize_text_field( $_REQUEST[ $field_name ] );
				update_post_meta( $post_id, $field_name, $product_code );
			}
		}
	}

	/**
	 * Retrieves the HTML for the hidden product list column.
	 *
	 * @since 0.1
	 *
	 * @param string $column The name of the column being managed.
	 * @param int    $post_id The ID of the post for which the row is being generated.
	 *
	 * @return string The HTML.
	 */
	protected function get_hidden_product_list_column_html( $column, $post_id ) {
		return $this->get_template( 'hidden-product-list-column' )->render(
			[
				'post_id'      => $post_id,
				'product_code' => get_post_meta( $post_id, $this->get_config( 'product_code_field_name' ), true ),
			]
		);
	}
}
