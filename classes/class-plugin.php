<?php
/**
 * Plugin class.
 *
 * @package PcfWooCommerce
 */

namespace XedinUnknown\PcfWooCommerce;

use WC_Product_Composite;
use WC_Product_Variable;
use WooCommerce;
use WP_Post;

/**
 * Plugin's main class.
 *
 * @since 0.1
 *
 * @package PcfWooCommerce
 */
class Plugin {
	/**
	 * The container of services and configuration used by the plugin.
	 *
	 * @since 0.1
	 *
	 * @var DI_Container
	 */
	protected $config;

	/**
	 * Plugin constructor.
	 *
	 * @since 0.1
	 *
	 * @param DI_Container $config The configuration of this plugin.
	 */
	public function __construct( DI_Container $config ) {
		$this->config = $config;
	}

	/**
	 * Runs the plugin.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function run() {
		$this->hook();
	}

	/**
	 * Retrieves a config value.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key of the config value to retrieve.
	 *
	 * @return mixed The config value.
	 */
	public function get_config( $key ) {
		return $this->config->get( $key );
	}

	/**
	 * Adds plugin hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	protected function hook() {
		add_action(
			'plugins_loaded',
			function () {
				$this->load_translations();
			}
		);

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
	}

	/**
	 * Registers assets used by the plugin.
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

		wp_register_script(
			'woo-admin-add-gtin1',
			$this->get_js_url( 'stl_admin_custom.js' ),
			[ 'jquery' ],
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
	 * Loads the plugin translations.
	 *
	 * @since 0.1
	 */
	protected function load_translations() {
		$base_dir         = $this->get_config( 'base_dir' );
		$translations_dir = trim( $this->get_config( 'translations_dir' ), '/' );
		$rel_path         = basename( $base_dir );

		load_plugin_textdomain( 'product-code-for-woocommerce', false, "$rel_path/$translations_dir" );
	}

	/**
	 * Retrieves a URL to the JS directory of the plugin.
	 *
	 * @since 0.1
	 *
	 * @param string $path The path relative to the JS directory.
	 *
	 * @return string The absolute URL to the JS directory.
	 */
	protected function get_js_url( $path = '' ) {
		$base_url = $this->get_config( 'base_url' );

		return "$base_url/assets/js/$path";
	}

	/**
	 * Gets the template for the specified key.
	 *
	 * @since 0.1
	 *
	 * @param string $template The template key.
	 *
	 * @return PHP_Template The template for the key.
	 */
	protected function get_template( $template ) {
		$factory       = $this->get_config( 'template_factory' );
		$base_dir      = $this->get_config( 'base_dir' );
		$templates_dir = $this->get_config( 'templates_dir' );

		$path = "$base_dir/$templates_dir/$template.php";

		return $factory( $path );
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
