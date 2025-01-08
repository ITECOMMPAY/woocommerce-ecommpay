<?php

namespace common\models;

use common\helpers\EcpGatewayJson;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoSum
 *
 * Contains the currency of the payment provider account and the amount denominated in this currency.
 *
 * @class    EcpGatewayInfoSum
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoSum extends EcpGatewayJson {


	/**
	 * Label for the amount in minor units of the payment provider currency.
	 */
	const FIELD_AMOUNT = 'amount';

	/**
	 * Label for the currency of the payment provider account in ISO 4217 alpha-3 format.
	 */
	const FIELD_CURRENCY = 'currency';


	/**
	 * <h2>Returns formatted price value.</h2>
	 * <p>Returns value of amount and currency as html-formatted value.</p>
	 *
	 * @return string
	 */
	public function get_formatted(): string {
		return wc_price( $this->get_amount_float(), [ 'currency' => $this->get_currency() ] );
	}

	/**
	 * <h2>Returns the price value as float.</h2>
	 *
	 * @return float
	 */
	public function get_amount_float(): float {
		return ecp_price_multiplied_to_float( $this->get_amount(), $this->get_currency() );
	}

	/**
	 * <h2>Returns the amount as int.</h2>
	 * <p>Returns the value of the amount in lower units of the currency of the payment system.</p>
	 *
	 * @return int
	 */
	public function get_amount(): int {
		$this->try_get_int( $amount, self::FIELD_AMOUNT );

		return $amount;
	}

	/**
	 * <h2>Returns currency.</h2>
	 * <p>Returns the value of the currency of the payment provider account in ISO 4217 alpha-3 format.</p>
	 *
	 * @return string
	 */
	public function get_currency(): string {
		$this->try_get_string( $currency, self::FIELD_CURRENCY );

		return $currency;
	}
}
