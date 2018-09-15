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
class Plugin extends Handler {

	/**
	 * Runs the plugin.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function run() {
		$result   = parent::run();
		$handlers = (array) $this->get_config( 'handlers' );

		foreach ( $handlers as $_handler ) {
			/* @var $_handler Handler */
			$_handler->run();
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 0.1
	 */
	protected function hook() {
		add_action(
			'plugins_loaded',
			function () {
				$this->load_translations();
			}
		);
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
}
