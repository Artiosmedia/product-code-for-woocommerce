<?php
/**
 * DI_Container class.
 *
 * @package PcfWooCommerce
 */

namespace XedinUnknown\PcfWooCommerce;

use Exception;
use OutOfRangeException;

/**
 * A simple Dependency Injection container.
 *
 * @package PcfWooCommerce
 * @since 0.1
 */
class DI_Container {

	/**
	 * Service definitions map.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $definitions = [];

	/**
	 * Map of resolved services.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $services = [];

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 * @param array $definitions The service definitions. Callables will be resolved; everything else will be returned as is.
	 */
	public function __construct( array $definitions = [] ) {
		$this->definitions = $definitions;
	}

	/**
	 * Checks if this instance contains the specified key.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key to check for.
	 * @return bool True if key is found; false otherwise.
	 * @throws Exception If problem checking.
	 */
	public function has( $key ) {
		return isset( $this->definitions[ $key ] );
	}

	/**
	 * Retrieves a value for the specified key.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key to retrieve the value for.
	 * @return mixed The value.
	 * @throws OutOfRangeException If key not found.
	 */
	public function get( $key ) {
		return $this->resolve_service( $key );
	}

	/**
	 * Retrieves a service for the specified key, resolving it if necessary.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key to resolve a service for.
	 * @return mixed The resolved service.
	 * @throws OutOfRangeException If service for specified key does not exist.
	 */
	protected function resolve_service( $key ) {
		if ( ! array_key_exists( $key, $this->services ) ) {
			if ( ! array_key_exists( $key, $this->definitions ) ) {
				throw new OutOfRangeException( vsprintf( 'Could not resolve service for key "%1$s": definition not found', [ $key ] ) );
			}

			$service = is_callable( $this->definitions[ $key ] )
				? $this->definitions[ $key ]( $this )
				: $this->definitions[ $key ];

			$this->services[ $key ] = $service;
		}

		return $this->services[ $key ];
	}
}
