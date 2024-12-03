<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Payment_Methods class
 *
 * Contains a list of fully supported payment methods.
 *
 * @class    Ecp_Gateway_Payment_Methods
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Payment_Methods {
	private static $payment_methods = [
		'card'             => 'card',
		'applepay'         => 'etoken',
		'googlepay'        => 'etoken-google',
		'directdebit/bacs' => 'Direct Debit BACS',
		'directdebit/sepa' => 'Direct Debit SEPA',
	];

	/**
	 * <h2>Returns payment method code by name.</h2>
	 *
	 * @param string $method_name
	 *
	 * @return false|string
	 */
	public static function get_code( string $method_name ) {
		return array_search( $method_name, self::$payment_methods );
	}
}
