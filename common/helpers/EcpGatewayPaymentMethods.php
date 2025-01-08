<?php

namespace common\helpers;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayPaymentMethods class
 *
 * Contains a list of fully supported payment methods.
 *
 * @class    EcpGatewayPaymentMethods
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayPaymentMethods {
	private static array $payment_methods = [
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
