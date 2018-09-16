<?php
/**
 * DI_Container class.
 *
 * @package PcfWooCommerce
 */

namespace Artiosmedia\PcfWooCommerce;

use Exception;

/**
 * Represents a block that wraps a template.
 *
 * Useful for associating a template with a context, to avoid having to specify data for rendering at render time.
 *
 * @since 0.1
 *
 * @package PcfWooCommerce
 */
class Template_Block {

	/**
	 * The template for this block to render.
	 *
	 * @since 0.1
	 *
	 * @var PHP_Template
	 */
	protected $template;

	/**
	 * The context to render the template with.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $context;

	/**
	 * Template_Block constructor.
	 *
	 * @since 0.1
	 *
	 * @param PHP_Template $template The template that this block will render.
	 * @param array        $context The context for the template.
	 */
	public function __construct( PHP_Template $template, array $context ) {
		$this->template = $template;
		$this->context  = $context;
	}

	/**
	 * Renders the internal block with pre-determined context.
	 *
	 * @since 0.1
	 *
	 * @return string The rendered block.
	 */
	public function __toString() {
		try {
			return $this->template->render( $this->context );
		} catch ( Exception $e ) {
			return $e->getMessage() . PHP_EOL . $e->getTraceAsString();
		}
	}
}
