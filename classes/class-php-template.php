<?php
/**
 * DI_Container class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

/**
 * Represents a template.
 *
 * @since 0.1
 *
 * @package PcfWooCommerce
 */
class PHP_Template {

	/**
	 * Path to the template file.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $template_path;

	/**
	 * PHP_Template constructor.
	 *
	 * @param string $template_path The path to the template file.
	 */
	public function __construct( $template_path ) {
		$this->template_path = $template_path;
	}

	/**
	 * Renders the template with given context.
	 *
	 * @since 0.1
	 *
	 * @param array $context The map of context keys to values.
	 *
	 * @return string The rendered template.
	 */
	public function render( $context ) {
		ob_start();

		$c = $this->get_context_function( $context );
		include $this->get_template_path();

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Retrieves the path to the template file.
	 *
	 * @since 0.1
	 *
	 * @return string The path to the template file.
	 */
	protected function get_template_path() {
		return (string) $this->template_path;
	}

	/**
	 * Retrieves a function that will get context variables.
	 *
	 * @since 0.1
	 *
	 * @param array $context The context for which to get the function.
	 *
	 * @return callable The function that will retrieve context variables.
	 */
	protected function get_context_function( $context ) {
		return function ( $key, $default = null ) use ( $context ) {
			if ( ! array_key_exists( $key, $context ) ) {
				return $default;
			}

			return $context[ $key ];
		};
	}
}
