<?php

namespace common\helpers;

use ArrayAccess;
use Countable;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Array object.</h2>
 *
 * @class    EcpGatewayArray
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayArray implements ArrayAccess, Countable {
	/**
	 * Internal container.
	 *
	 * @var array
	 * @since 2.0.0
	 */
	private array $array;

	/**
	 * Array object constructor.
	 *
	 * @param array $array
	 *
	 * @since 2.0.0
	 */
	public function __construct( array $array = [] ) {
		$this->array = $array;
	}


	/**
	 * @param int|string $offset
	 *
	 * @return bool <p><b>TRUE</b> if offset exists or <b>FALSE</b> otherwise.</p>
	 * * @since 2.0.0
	 */
	public function offsetExists( $offset ): bool {
		return array_key_exists( $offset, $this->array );
	}

	/**
	 * @inheritDoc
	 * @return mixed <p>Array offset value.</p>
	 * @since 2.0.0
	 */
	public function offsetGet( $offset ) {
		return $this->array[ $offset ];
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	public function offsetSet( $offset, $value ): void {
		if ( null === $offset ) {
			$this->array[] = $value;
		} else {
			$this->array[ $offset ] = $value;
		}
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->array[ $offset ] );
	}


	/**
	 * <h2>Returns current object as a native array.</h2>
	 *
	 * @return array <p>Native array.</p>
	 * @since 2.0.0
	 */
	public function to_array(): array {
		return $this->array;
	}


	/**
	 * <h2>Returns all array values as array.</h2>
	 *
	 * @return array <p>All array values.</p>
	 * @since 2.0.0
	 */
	public function values(): array {
		return array_values( $this->array );
	}

	/**
	 * <h2>Returns the first value from array.</h2>
	 *
	 * @return int|string|null <p>First value from array.</p>
	 * @since 2.0.0
	 */
	public function first() {
		return array_key_first( $this->array );
	}

	/**
	 * @inheritDoc
	 * @return int <p>The number of elements in the array.</p>
	 * @since 2.0.0
	 */
	public function count(): int {
		return count( $this->array );
	}

	/**
	 * <h2>Returns all array keys as array.</h2>
	 *
	 * @return int[]|string[] <p>All array keys.</p>
	 * @since 2.0.0
	 */
	public function keys(): array {
		return array_keys( $this->array );
	}

	/**
	 * <h2>Returns the last value from array.</h2>
	 *
	 * @return int|string|null <p>Last value from array.</p>
	 * @since 2.0.0
	 */
	public function last() {
		return array_key_last( $this->array );
	}
}
