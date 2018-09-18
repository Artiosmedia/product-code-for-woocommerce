<?php
/**
 * Front_Handler class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product_Variable;
use WooCommerce;
use WP_Post;

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

		add_filter(
			'woocommerce_add_cart_item_data',
			/**
			 * Handles extra item data to be added to cart.
			 *
			 * @since 0.1
			 *
			 * @param array $cart_item_data Extra cart item data to be passed into the item.
			 * @param int   $product_id     The ID of the product post that is being added to cart.
			 * @param int   $variation_id   The ID of teh variation that is being added to cart.
			 * @param int   $quantity       The quantity of the item that is being added to cart.
			 *
			 * @return array Possibly modified cart item data.
			 */
			function ( $cart_item_data, $product_id, $variation_id, $quantity ) {
				return $this->process_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity );
			},
			10,
			4
		);

		add_filter(
			'woocommerce_get_item_data',
			/**
			 * Adds our data to cart item data.
			 *
			 * @since 0.1
			 *
			 * @param array[]   $cart_item_data A list of "key" and "value" pairs representing the data.
			 * @param array     $cart_item      The "cart item object", as described by WooCommerce.
			 *
			 * @see https://github.com/woocommerce/woocommerce/blob/fe1acc0e8ec7651b574c419bbb4d02e2770ff584/includes/wc-template-functions.php#L3295
			 *
			 * @return array[] The new cart item data.
			 */
			function ( $cart_item_data, $cart_item ) {
				return $this->retrieve_cart_item_data( $cart_item_data, $cart_item );
			},
			20,
			2
		);

		add_action(
			'woocommerce_checkout_create_order_line_item',
			/**
			 * Adds metadata to the order item.
			 *
			 * @since 0.1
			 *
			 * @param WC_Order_Item_Product $item           The order item.
			 * @param string                $cart_item_key  The key of the cart item from which the order item is being created.
			 * @param array                 $values         The cart item values that correspond to the item key.
			 * @param WC_Order              $order          The order for which the items are being created.
			 */
			function ( $item, $cart_item_key, $values, $order ) {
				$this->process_order_item( $item, $cart_item_key, $values, $order );
			},
			10,
			4
		);

		add_action(
			'woocommerce_order_item_get_formatted_meta_data',
			/**
			 * Modifies the meta value as it appears on the order item.
			 *
			 * @since 0.1
			 *
			 * @param array[] $formatted_meta A map of meta data IDs to their data maps.
			 * Each one has the following keys:
			 * `key` - The metadata key.
			 * `value` - The metadata value.
			 * `display_key` - A filtered attribute label.
			 * `disaplay_value` - A filtered meta value.
			 * @param WC_Order_Item $item The order item for which the meta data is being retrieved.
			 *
			 * @return array[] The new meta data map.
			 */
			function ( $formatted_meta, WC_Order_Item $item ) {
				return $this->get_formatted_order_item_meta_data( $formatted_meta, $item );
			},
			10,
			2
		);

		add_action(
			'woocommerce_order_item_display_meta_key',
			/**
			 * Modifies the display label of an order item's meta piece.
			 *
			 * @since 0.1
			 *
			 * @param string $display_key The current display key.
			 * @param object $meta The meta data object with the following properties
			 * `key` - The metadata key.
			 * `value` - The metadata value.
			 * `display_key` - A filtered attribute label.
			 * `disaplay_value` - A filtered meta value.
			 * @param WC_Order_Item $item The order item for which the meta data is being retrieved.
			 *
			 * @return string The new meta label.
			 */
			function ( $display_key, $meta, WC_Order_Item $item ) {
				return $this->get_order_item_meta_display_key( $display_key, $meta, $item );
			},
			10,
			3
		);

		add_action(
			'woocommerce_product_meta_start',
			/**
			 * Runs before product meta is output on a single product page.
			 *
			 * @since 0.1
			 */
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

		$post = get_post();
		if ( $post && $this->is_product( $post ) ) {
			wp_localize_script( 'woo-add-gtin1', 'wooGtinVars', $this->get_product_page_vars( $post ) );
		}
	}

	/**
	 * Retrieves HTMl to output before product meta on a single product page.
	 *
	 * @since 0.1
	 *
	 * @return string The HTML to output before product meta.
	 */
	protected function get_product_meta_before_html() {
		$post  = get_post();
		$value = get_post_meta( $post->ID, $this->get_config( 'product_code_field_name' ), true );

		if ( empty( $value ) ) {
			return '';
		}

		return $this->get_template( 'product-meta-row' )->render(
			[
				'title' => __( 'Product Code', 'product-code-for-woocommerce' ),
				'value' => $value,
			]
		);
	}

	/**
	 * Handles extra item data to be added to cart.
	 *
	 * @since 0.1
	 *
	 * @param array $cart_item_data Extra cart item data to be passed into the item.
	 * @param int   $product_id     The ID of the product post that is being added to cart.
	 * @param int   $variation_id   The ID of teh variation that is being added to cart.
	 * @param int   $quantity       The quantity of the item that is being added to cart.
	 *
	 * @return array Possibly modified cart item data.
	 */
	protected function process_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
		// Get the correct post ID to be used.
		$the_id = $variation_id > 0
			? $variation_id
			: $product_id;

		$variant_field_name = $this->get_config( 'product_code_variant_field_name' );
		$simple_field_name  = $this->get_config( 'product_code_field_name' );

		$variant_value = get_post_meta( $the_id, $variant_field_name, true );
		if ( $variant_value ) {
			$cart_item_data[ $variant_field_name ] = sanitize_text_field( $variant_value );
		}

		$simple_value = get_post_meta( $the_id, $simple_field_name, true );
		if ( $simple_value ) {
			$cart_item_data[ $simple_field_name ] = sanitize_text_field( $simple_value );
		}

		return $cart_item_data;
	}

	/**
	 * Adds our data to cart item data.
	 *
	 * This is for display only.
	 *
	 * @since 0.1
	 *
	 * @param array[] $cart_item_data   A list of "key" and "value" pairs representing the data.
	 * @param array   $cart_item        The "cart item object", as described by WooCommerce.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/fe1acc0e8ec7651b574c419bbb4d02e2770ff584/includes/wc-template-functions.php#L3295
	 *
	 * @return array[] The new cart item data.
	 */
	protected function retrieve_cart_item_data( $cart_item_data, $cart_item ) {
		$variant_field_name = $this->get_config( 'product_code_variant_field_name' );
		$simple_field_name  = $this->get_config( 'product_code_field_name' );

		if ( isset( $cart_item[ $variant_field_name ] ) ) {
			$cart_data[] = array(
				'name'  => __( 'Product Code', 'product-code-for-woocommerce' ),
				'value' => $cart_item[ $variant_field_name ],
			);
		}
		if ( isset( $cart_item[ $simple_field_name ] ) ) {
			$cart_data[] = array(
				'name'  => __( 'Product Code', 'product-code-for-woocommerce' ),
				'value' => $cart_item[ $simple_field_name ],
			);
		}

		return $cart_data;
	}

	/**
	 * Adds metadata to the order item.
	 *
	 * @since 0.1
	 *
	 * @param WC_Order_Item_Product $item           The order item.
	 * @param string                $cart_item_key  The key of the cart item from which the order item is being created.
	 * @param array                 $values         The cart item values that correspond to the item key.
	 * @param WC_Order              $order          The order for which the items are being created.
	 */
	protected function process_order_item( $item, $cart_item_key, $values, $order ) {
		$variant_field_name = $this->get_config( 'product_code_variant_field_name' );
		$simple_field_name  = $this->get_config( 'product_code_field_name' );

		if ( isset( $values[ $variant_field_name ] ) ) {
			$item->add_meta_data( $variant_field_name, $values[ $variant_field_name ], false );
		}

		if ( isset( $values[ $simple_field_name ] ) ) {
			$item->add_meta_data( $simple_field_name, $values[ $simple_field_name ], false );
		}
	}

	/**
	 * Modifies the meta value as it appears on the order item.
	 *
	 * @since 0.1
	 *
	 * @param array[]       $formatted_meta A map of meta data IDs to their data maps.
	 *                                      Each one has the following keys:
	 *                                      `key` - The metadata key.
	 *                                      `value` - The metadata value.
	 *                                      `display_key` - A filtered attribute label.
	 *                                      `disaplay_value` - A filtered meta value.
	 * @param WC_Order_Item $item           The order item for which the meta data is being retrieved.
	 *
	 * @return array[] The new meta data map.
	 */
	protected function get_formatted_order_item_meta_data( $formatted_meta, $item ) {
		$field_name = $this->get_config( 'product_code_field_name' );

		foreach ( $formatted_meta as $idx => $meta ) {
			if ( $meta->key === $field_name ) {
				return $formatted_meta;
			}
		}

		$value = $item->get_meta( $field_name );
		if ( empty( $value ) ) {
			return $formatted_meta;
		}

		$formatted_meta[ $field_name ] = (object) [
			'key'           => $field_name,
			'value'         => $value,
			'display_key'   => __( 'Product Code', 'product-code-for-woocommerce' ),
			'display_value' => wpautop( make_clickable( $value ) ),
		];

		return $formatted_meta;
	}

	/**
	 * Modifies the display label of an order item's meta piece.
	 *
	 * @since 0.1
	 *
	 * @param string        $display_key    The current display key.
	 * @param object        $meta           The meta data object with the following properties
	 *                                      `key` - The metadata key.
	 *                                      `value` - The metadata value.
	 *                                      `display_key` - A filtered attribute label.
	 *                                      `disaplay_value` - A filtered meta value.
	 * @param WC_Order_Item $item The order item for which the meta data is being retrieved.
	 *
	 * @return string The new meta label.
	 */
	protected function get_order_item_meta_display_key( $display_key, $meta, WC_Order_Item $item ) {
		if ( $meta->key === $this->get_config( 'product_code_field_name' ) ) {
			return __( 'Product Code', 'product-code-for-woocommerce' );
		}

		return $display_key;
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
