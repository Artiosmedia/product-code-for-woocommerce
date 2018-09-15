<?php
/**
 * Contains service definitions used by the plugin.
 *
 * @package PcfWooCommerce
 */

use XedinUnknown\PcfWooCommerce\DI_Container;
use XedinUnknown\PcfWooCommerce\PHP_Template;

/**
 * A factory of a service definition map.
 *
 * @since 0.1
 *
 * @param string $base_path Path to the plugin file.
 * @param string $base_url URL of the plugin folder.
 *
 * @return array A map of service names to service definitions.
 */
return function ( $base_path, $base_url ) {
		return [
			'version'                 => '0.1',
			'base_path'               => $base_path,
			'base_dir'                => dirname( $base_path ),
			'base_url'                => $base_url,
			'js_path'                 => '/assets/js',
			'templates_dir'           => '/templates',
			'product_code_field_name' => '_ean_field',

			'template_factory'        => function ( DI_Container $c ) {
				return function ( $path ) {
					return new PHP_Template( $path );
				};
			},
		];
};
