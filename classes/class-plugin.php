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
			'init',
			function () {
				$this->register_assets();
			}
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
