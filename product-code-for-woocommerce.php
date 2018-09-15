<?php
/*
* Plugin Name: Product Code for WooCommerce
* Plugin URI: http://wordpress.org/plugins/product-code-for-woocommerce
* Description: This plugin will allow a user to add a unique internal product identifier in addition to the GTIN, EAN, SKU and UPC throughout the order process.
* Version: 1.0.0
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	define( 'PCFW_TEXT_DOMAIN', 'product-for-woocommerce' );
	define( 'PCFW_FIELD_NAME', '_ean_field' );


	// Plugin path
	define( 'Woo_PCFW_DIR', plugin_dir_path( __FILE__ ) );

	// Plugin URL
	define( 'Woo_PCFW_URL', plugin_dir_url( __FILE__ ) );

	// Plugin version
			define( 'Woo_PCFW_VER', '0.3.1' );



	add_action( 'wp_enqueue_scripts', 'stl_add_scripts_styles' );

	add_action( 'woocommerce_product_options_inventory_product_data', 'product_pcfw_field' );
	add_action( 'woocommerce_process_product_meta', 'save_product_pcfw' );

	add_action( 'woocommerce_product_after_variable_attributes', 'variation_pcfw_field', 10, 3 );
	add_action( 'woocommerce_save_product_variation', 'save_variations_pcfw', 10, 2 );

	 add_action( 'woocommerce_product_meta_end', 'frontend_display_productcode' );



	add_filter( 'plugin_row_meta', 'pcfw_add_details_link', 10, 3 );

	function pcfw_add_details_link( $links, $plugin_file, $plugin_data ) {
		if ( basename( $plugin_file ) === basename( __FILE__ ) ) {
			$slug = basename( $plugin_data['PluginURI'] );
			unset( $links[2] );
			$links[] = sprintf( '<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=772&amp;height=563' ), esc_attr( sprintf( __( 'More information about %s', PCFW_TEXT_DOMAIN ), $plugin_data['Name'] ) ), __( 'View Details', PCFW_TEXT_DOMAIN ) );
		}

		return $links;
	}


	add_filter( 'plugin_row_meta', 'pcfw_add_description_link', 10, 2 );

	function pcfw_add_description_link( $links, $file ) {
		if ( plugin_basename( __FILE__ ) == $file ) {
			$row_meta = array(
				'donation' => '<a href="' . esc_url( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=E7LS2JGFPLTH2' ) . '" target="_blank">' . esc_html__( 'Donation for Homeless', PCFW_TEXT_DOMAIN ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	add_action( 'plugins_loaded', 'pcfw_add_translations' );

	function pcfw_add_translations() {
		load_plugin_textdomain( PCFW_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}


	function stl_add_scripts_styles( $hook ) {

		if ( ! is_product() ) {
			return;
		}

		  global $post,$woocommerce;

		  // Use minified libraries if SCRIPT_DEBUG is turned off
		  // $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		  wp_enqueue_script( 'woo-add-gtin1', Woo_PCFW_URL . 'js/stl_custom.js', array( 'wc-add-to-cart-variation', 'jquery' ), Woo_PCFW_VER, true );

		  $localized = array(
			  'gtin' => get_post_meta( $post->ID, '_product_code', 1 ),
		  );

		  $variations;

		  // handle variations pre 3.2
		if ( floatval( $woocommerce->version ) < 3.2 ) {

			$product = new WC_Product( $post->ID );

			$vars       = new WC_Product_Variable( $post->ID );
			$variations = $vars->get_available_variations();

		} else {

			// 3.2 + variations
			global $product;

			if ( ! is_object( $product ) ) {
				$product = wc_get_product( $post->ID );
			}

			if ( is_object( $product ) && $product->is_type( 'variable' ) ) {

				$variations = $product->get_available_variations();

			}

			if ( function_exists( 'is_composite_product' ) && is_composite_product() || is_object( $product ) && $product->is_type( 'composite' ) ) {

				$localized['is_composite'] = true;

				$components = $product->get_composite_data();
				foreach ( $components as $component_id => $component ) {
					$comp = $product->get_component( $component_id );

					$product_ids = $comp->get_options();

					foreach ( $product_ids as $id ) {

						$variable_product = new WC_Product_Variable( $id );

						$composite_variations[] = $variable_product->get_available_variations();

					}
				}
			}
		}

		if ( ! empty( $variations ) ) {

			foreach ( $variations as $variation ) {
				if ( ! empty( $variation ) && $variation['variation_is_active'] != false ) {

					$localized['variation_gtins'][ $variation['variation_id'] ] = get_post_meta( $variation['variation_id'], '_product_code_variant', 1 );

				}
			}
		}

		if ( ! empty( $composite_variations ) ) {
			foreach ( $composite_variations as $id => $comp_variation ) {

				foreach ( $comp_variation as $variation ) {
					if ( ! empty( $variation ) && $variation['variation_is_active'] != false ) {

						$localized['composite_variation_pcm'][ $variation['variation_id'] ] = get_post_meta( $variation['variation_id'], '_product_code_variant', 1 );

					}
				}
			}
		}

			wp_localize_script( 'woo-add-gtin1', 'wooGtinVars', $localized );

	}


	function product_pcfw_field() {

			global $post;

			// $label = ( !empty( get_option( 'hwp_gtin_text' ) ) ? get_option( 'hwp_gtin_text' ) : 'GTIN' );
			// add GTIN field for variations
			woocommerce_wp_text_input(
				array(
					'id'          => '_product_code',
					'label'       => 'Product Code',
					'desc_tip'    => 'true',
					'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', PCFW_TEXT_DOMAIN ),
					'value'       => get_post_meta( $post->ID, '_product_code', true ),
				)
			);

	}


	function variation_pcfw_field( $loop, $variation_data, $variation ) {
		echo '<div class="form-row form-row-first" style="    margin-top: -13px;">';
		  // $label = ( !empty( get_option( 'hwp_gtin_text' ) ) ? get_option( 'hwp_gtin_text' ) : 'GTIN' );
		   // add GTIN field for variations
		woocommerce_wp_text_input(
			array(
				'id'          => '_product_code_variant[' . $variation->ID . ']',
				'label'       => 'Product Code',
				'desc_tip'    => 'true',
				'class'       => 'form-row-first',
				'description' => __( 'Product code refers to a company’s unique internal product identifier, needed for online product fulfillment', PCFW_TEXT_DOMAIN ),
				'value'       => get_post_meta( $variation->ID, '_product_code_variant', true ),
			)
		);
		   echo '</div><div style="clear:both;"></div>';

	}

		/**
		 * Save variation settings
		 *
		 * @since       0.1.0
		 * @return      void
		 */
	function save_variations_pcfw( $post_id ) {

		$tn_post = $_POST['_product_code_variant'][ $post_id ];

		// save
		if ( isset( $tn_post ) ) {
			  update_post_meta( $post_id, '_product_code_variant', esc_attr( $tn_post ) );
		}

				// remove if meta is empty
				$tn_meta = get_post_meta( $post_id, '_product_code_variant', true );

		if ( empty( $tn_meta ) ) {
			delete_post_meta( $post_id, '_product_code_variant', '' );
		}

	}

		/**
		 * Save simple product GTIN settings
		 *
		 * @since       0.1.0
		 * @return      void
		 */
	function save_product_pcfw( $post_id ) {

		$gtin_post = $_POST['_product_code'];

		// save the gtin
		if ( isset( $gtin_post ) ) {
				update_post_meta( $post_id, '_product_code', esc_attr( $gtin_post ) );
		}

				// remove if GTIN meta is empty
				$gtin_meta = get_post_meta( $post_id, '_product_code', true );

		if ( empty( $gtin_meta ) ) {
			delete_post_meta( $post_id, '_product_code', '' );
		}

	}




	// Save custom field value in cart item
	add_filter( 'woocommerce_add_cart_item_data', 'save_custom_field_in_cart_object', 30, 3 );
	function save_custom_field_in_cart_object( $cart_item_data, $product_id, $variation_id ) {

		// Get the correct Id to be used
		$the_id = $variation_id > 0 ? $variation_id : $product_id;

		if ( $value = get_post_meta( $the_id, '_product_code_variant', true ) ) {
			$cart_item_data['product_code_variant'] = sanitize_text_field( $value );
		}

		if ( $value = get_post_meta( $the_id, '_product_code', true ) ) {
			$cart_item_data['product_code'] = sanitize_text_field( $value );
		}

		if ( $value = get_post_meta( $the_id, '_product_code', true ) ) {
			$cart_item_data['product_code'] = sanitize_text_field( $value );
		}

		return $cart_item_data;
	}

	// Display on cart and checkout pages
	add_filter( 'woocommerce_get_item_data', 'display_custom_field_as_item_data', 20, 2 );
	function display_custom_field_as_item_data( $cart_data, $cart_item ) {
		if ( isset( $cart_item['product_code_variant'] ) ) {
			$cart_data[] = array(
				'name'  => __( 'Product Code', 'woocommerce' ),
				'value' => $cart_item['product_code_variant'],
			);
		}
		if ( isset( $cart_item['product_code'] ) ) {
			$cart_data[] = array(
				'name'  => __( 'Product Code', 'woocommerce' ),
				'value' => $cart_item['product_code'],
			);
		}
		return $cart_data;
	}


	function kia_add_order_item_meta( $item_id, $values ) {

		if ( ! empty( $values['product_code'] ) ) {
			woocommerce_add_order_item_meta( $item_id, 'Product Code', $values['product_code'] );
		}
		if ( ! empty( $values['product_code_variant'] ) ) {
			woocommerce_add_order_item_meta( $item_id, 'Product Code', $values['product_code_variant'] );
		}

	}
	add_action( 'woocommerce_add_order_item_meta', 'kia_add_order_item_meta', 10, 2 );






	add_action( 'woocommerce_before_single_product', 'cspl_change_single_product_layout' );
	function cspl_change_single_product_layout() {

		// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		// add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta_remove_category', 40 );

		function woocommerce_template_single_meta_remove_category() {

			global $post, $product;

			$cat_count = sizeof( get_the_terms( $post->ID, 'product_cat' ) );
			$tag_count = sizeof( get_the_terms( $post->ID, 'product_tag' ) );

			?>
<div class="product_meta">

			<?php do_action( 'woocommerce_product_meta_start' ); ?>

			<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

	<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'woocommerce' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' ); ?></span></span>

	<?php endif; ?>


			<?php
			global $post;
			   $gtin = get_post_meta( $post->ID, '_product_code', 1 );
			   // $display = get_option( 'hwp_display_gtin' );
			// $label = ( !empty( get_option( 'hwp_gtin_text' ) ) ? get_option( 'hwp_gtin_text' ) : 'GTIN' );
			   // if( !empty( $display ) && 'yes' === $display )
			// return;
			if ( ! empty( $gtin ) ) {

				echo '<span class="wo_productcode"><span>' . esc_html__( 'Product Code : ', PCFW_TEXT_DOMAIN ) . '</span><span class="stl_codenum">' . get_post_meta( $post->ID, '_product_code', 1 ) . '</span></span>';

			}
			?>

			<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

			<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

			<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>

			<?php
		}

	}







	function load_custom_wp_admin_style() {
		wp_enqueue_script( 'woo-admin-add-gtin1', Woo_PCFW_URL . 'js/stl_admin_custom.js', array( 'jquery' ), Woo_PCFW_VER, true );
	}
	add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

	add_action(
		'woocommerce_product_quick_edit_end',
		function() {

			?>

	<label class="product_code" style="clear:both;">
		<span class="title"><?php _e( 'Product Code', 'woocommerce' ); ?></span>
		<span class="input-text-wrap">
		  <input type="text" name="_product_code" class="text" placeholder="<?php _e( 'Product Code', 'woocommerce' ); ?>" value="">
		</span>
	  </label>


<!--     <div class="product_code">
		<label class="alignleft">
			<div class="title"><?php _e( 'Product Code', 'woocommerce' ); ?></div>
			<input type="text" name="_product_code" class="text" placeholder="<?php _e( 'Product Code', 'woocommerce' ); ?>" value="">
		</label>
	</div> -->
			<?php

		}
	);

	add_action(
		'woocommerce_product_quick_edit_save',
		function( $product ) {

			if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {

				// echo "ifffffFFF";
				$post_id = $product->id;

				if ( isset( $_REQUEST['_product_code'] ) ) {

					// echo "ggggg";
					$customFieldDemo = trim( esc_attr( $_REQUEST['_product_code'] ) );

					// Do sanitation and Validation here
					update_post_meta( $post_id, '_product_code', wc_clean( $customFieldDemo ) );
				}
			}

		},
		10,
		1
	);


	add_action(
		'manage_product_posts_custom_column',
		function( $column, $post_id ) {

			?>
<div class="hidden product_code" id="product_code_inline_<?php echo $post_id; ?>">
			<div id="product_codeddd"><?php echo get_post_meta( $post_id, '_product_code', true ); ?></div>
		</div>
			<?php

		},
		99,
		2
	);




	add_action( 'woocommerce_email_before_order_table', 'bbloomer_add_content_specific_email', 20, 4 );

	function bbloomer_add_content_specific_email( $order, $sent_to_admin, $plain_text, $email ) {

		add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'mobilefolk_order_item_get_formatted_meta_data', 10, 1 );

	}



	function mobilefolk_order_item_get_formatted_meta_data( $formatted_meta ) {
		$temp_metas = [];
		foreach ( $formatted_meta as $key => $meta ) {
			if ( isset( $meta->key ) && ! in_array(
				$meta->key,
				[
					'Product Code',
				]
			) ) {
				$temp_metas[ $key ] = $meta;
			}
		}
		return $temp_metas;
	}
}


