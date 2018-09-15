<?php
/**
 * Plugin class.
 *
 * @package PcfWooCommerce
 */

namespace XedinUnknown\PcfWooCommerce;

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
			'woo-add-gtin1',
			$this->get_js_url( 'stl_admin_custom.js' ),
			[ 'jquery' ],
			$this->get_config( 'version' ),
			false
		);
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
}
