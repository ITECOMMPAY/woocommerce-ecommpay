<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Recurring_Types class
 *
 * @class    Ecp_Gateway_Recurring_Types
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Recurring_Types {
	/**
	 * One-click payment
	 */
	public const PAYMENT = 'C';

	/**
	 * COF-purchase (Regular)
	 */
	public const REGULAR = 'R';

	/**
	 * Auto-payment
	 */
	public const AUTO = 'U';

	private static $names;


	public static function get_status_names(): array {
		if ( ! self::$names ) {
			self::$names = self::compile_names();
		}

		return self::$names;
	}

	private static function compile_names(): array {
		return [
			self::PAYMENT => _x( 'One-click', 'Recurring type', 'woo-ecommpay' ),
			self::REGULAR => _x( 'Regular', 'Recurring type', 'woo-ecommpay' ),
			self::AUTO    => _x( 'Auto-payment', 'Recurring type', 'woo-ecommpay' ),
		];
	}
}
