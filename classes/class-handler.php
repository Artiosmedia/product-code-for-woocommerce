<?php
/**
 * Handler class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

/**
 * A base class for all handlers.
 *
 * @package PcfWooCommerce
 */
abstract class Handler {

	/**
	 * The container of services and configuration used by the plugin.
	 *
	 * @since 0.1
	 *
	 * @var DI_Container
	 */
	protected $config;

	/**
	 * Handler constructor.
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
	 * @return mixed
	 */
	public function run() {
		$this->hook();

		return null;
	}

	/**
	 * Procedural way to run the handler.
	 *
	 * @since 0.1
	 *
	 * @return mixed The result of handling.
	 */
	public function __invoke() {
		return $this->run();
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
	 * Retrieves a URL to the JS directory of the handler.
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
	 * Creates a new template block.
	 *
	 * @since 0.1
	 *
	 * @param PHP_Template|string $template The template or template key.
	 * @param array               $context The context for the template.
	 *
	 * @return Template_Block The new block.
	 */
	protected function create_template_block( $template, $context ) {
		if ( ! ( $template instanceof PHP_Template ) ) {
			$template = $this->get_template( (string) $template );
		}

		$factory = $this->get_config( 'block_factory' );

		return $factory( $template, $context );
	}

	/**
	 * Adds handler hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	abstract protected function hook();
}
