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
}
