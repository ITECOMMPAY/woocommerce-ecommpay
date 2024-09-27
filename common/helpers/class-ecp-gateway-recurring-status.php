<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Recurring_Status class
 *
 * @class    Ecp_Gateway_Recurring_Status
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Recurring_Status {
	/**
	 * COF-purchase is active
	 */
	const ACTIVE = 'active';

	/**
	 * COF-purchase is cancelled
	 */
	const CANCELLED = 'cancelled';

	private static $names;

	private static function compile_codes(): array {
		$data = [];

		foreach ( self::get_status_names() as $key => $value ) {
			$data[ $key ] = str_replace( ' ', '-', $key );
		}

		return $data;
	}

	public static function get_status_names(): array {
		if ( ! self::$names ) {
			self::$names = self::compile_names();
		}

		return self::$names;
	}

	private static function compile_names(): array {
		return [
			self::ACTIVE    => _x( 'Active', 'Recurring status', 'woo-ecommpay' ),
			self::CANCELLED => _x( 'Cancelled', 'Recurring status', 'woo-ecommpay' ),
		];
	}
}
