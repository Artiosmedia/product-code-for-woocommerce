<?php
/**
 * Contains service definitions used by the plugin.
 *
 * @package PcfWooCommerce
 */

use XedinUnknown\PcfWooCommerce\Admin_Handler;
use XedinUnknown\PcfWooCommerce\DI_Container;
use XedinUnknown\PcfWooCommerce\Front_Handler;
use XedinUnknown\PcfWooCommerce\PHP_Template;
use XedinUnknown\PcfWooCommerce\Template_Block;

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
			'version'                         => '0.1',
			'base_path'                       => $base_path,
			'base_dir'                        => dirname( $base_path ),
			'base_url'                        => $base_url,
			'js_path'                         => '/assets/js',
			'templates_dir'                   => '/templates',
			'translations_dir'                => '/languages',
			'text_domain'                     => 'product-code-for-woocommerce',
			'product_code_field_name'         => '_product_code',
			'product_code_variant_field_name' => '_product_code_variant',
			'donate_paypal_btn_id'            => 'E7LS2JGFPLTH2',

			/*
			 * Makes templates.
			 *
			 * @since 0.1
			 */
			'template_factory'                => function ( DI_Container $c ) {
				return function ( $path ) {
					return new PHP_Template( $path );
				};
			},

			/*
			 * Makes blocs.
			 *
			 * @since 0.1
			 */
			'block_factory'                   => function ( DI_Container $c ) {
				return function ( PHP_Template $template, $context ) {
					return new Template_Block( $template, $context );
				};
			},

			/*
			 * WooCommerce singleton, or null if WC not installed.
			 *
			 * @since 0.1
			 */
			'woocommerce'                     => function ( DI_Container $c ) {
				return function_exists( 'wc' )
					? wc()
					: null;
			},

			/*
			 * List of handlers to run.
			 *
			 * @since 0.1
			 */
			'handlers'                        => function ( DI_Container $c ) {
				return [
					$c->get( 'admin_handler' ),
					$c->get( 'front_handler' ),
				];
			},

			/*
			 * Handles the back-office.
			 *
			 * @since 0.1
			 */
			'admin_handler'                   => function ( DI_Container $c ) {
				return new Admin_Handler( $c );
			},

			/*
			 * Handles the front-office.
			 *
			 * @since 0.1
			 */
			'front_handler'                   => function ( DI_Container $c ) {
				return new Front_Handler( $c );
			},
		];
};
