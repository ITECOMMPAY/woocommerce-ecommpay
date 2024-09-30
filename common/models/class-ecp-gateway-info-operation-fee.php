<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Info_Operation_Fee
 *
 * Contains the information about per-operation fee charged by ecommpay.
 *
 * @class    Ecp_Gateway_Info_Operation_Fee
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Operation_Fee extends Ecp_Gateway_Json {


	/**
	 * Label for amount of per-operation fee.
	 */
	const FIELD_AMOUNT = 'amount';

	/**
	 * Label for currency of the operation fee.
	 */
	const FIELD_CURRENCY = 'currency';

	/**
	 * Label for total amount of the operation surcharge and operation amount.
	 */
	const FIELD_SUM_WITH_SURCHARGE = 'sum_with_surcharge';

	/**
	 * Label for amount of the operation surcharge.
	 */
	const FIELD_SURCHARGE_AMOUNT = 'surcharge_amount';

	/**
	 * Label for currency of the operation surcharge.
	 */
	const FIELD_SURCHARGE_CURRENCY = 'surcharge_currency';


	/**
	 * <h2>Returns amount of per-operation fee.</h2>
	 *
	 * @return ?int Amount specified in minor currency units, if available, or <b>NULL</b> otherwise.
	 */
	public function get_amount() {
		if ( $this->try_get_int( $amount, self::FIELD_AMOUNT ) ) {
			return $amount;
		}

		return null;
	}

	/**
	 * <h2>Returns currency of the operation fee.</h2>
	 *
	 * @return ?string Currency in ISO 4217 alpha-3 format, if available, or <b>NULL</b> otherwise.
	 */
	public function get_currency() {
		if ( $this->try_get_string( $currency, self::FIELD_CURRENCY ) ) {
			return $currency;
		}

		return null;
	}

	/**
	 * <h2>Returns total amount of the operation surcharge and operation amount.</h2>
	 *
	 * @return ?int Amount specified in minor currency units, if available, or <b>NULL</b> otherwise.
	 */
	public function get_sum_with_surcharge() {
		if ( $this->try_get_int( $total, self::FIELD_SUM_WITH_SURCHARGE ) ) {
			return $total;
		}

		return null;
	}

	/**
	 * <h2>Returns the amount of the operation surcharge.</h2>
	 *
	 * @return ?int Amount specified in minor currency units, if available, or <b>NULL</b> otherwise.
	 */
	public function get_surcharge_amount() {
		if ( $this->try_get_int( $amount, self::FIELD_SURCHARGE_AMOUNT ) ) {
			return $amount;
		}

		return null;
	}

	/**
	 * <h2>Returns the currency of the operation surcharge.</h2>
	 *
	 * @return ?string Currency in ISO 4217 alpha-3 format, if available, or <b>NULL</b> otherwise.
	 */
	public function get_surcharge_currency() {
		if ( $this->try_get_string( $currency, self::FIELD_SURCHARGE_CURRENCY ) ) {
			return $currency;
		}

		return null;
	}
}
