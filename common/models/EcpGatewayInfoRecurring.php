<?php

namespace common\models;

use common\helpers\EcpGatewayJson;
use common\helpers\EcpGatewayRecurringStatus;
use common\helpers\EcpGatewayRecurringTypes;
use DateTime;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoRecurring
 *
 * @class    EcpGatewayInfoRecurring
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoRecurring extends EcpGatewayJson {


	/**
	 * Label for COF payment ID.
	 */
	const FIELD_ID = 'id';

	/**
	 * Label for COF purchase currency.
	 */
	const FIELD_CURRENCY = 'currency';

	/**
	 * Label for COF ECOMMPAY payment ID.
	 */
	const FIELD_REGISTER_PAYMENT_ID = 'register_payment_id';

	/**
	 * Label for COF purchase status.
	 */
	const FIELD_STATUS = 'status';

	/**
	 * Label for COF purchase type.
	 */
	const FIELD_TYPE = 'type';

	/**
	 * Label for expiration date of the COF purchase ID.
	 */
	const FIELD_VALID_THRU = 'valid_thru';


	/**
	 * <h2>Returns COF payment identifier.</h2>
	 *
	 * @return ?int
	 */
	public function get_id(): ?int {
		if ( $this->try_get_int( $id, self::FIELD_ID ) ) {
			return $id;
		}

		return null;
	}

	/**
	 * <h2>Returns COF purchase currency.</h2>
	 *
	 * @return ?string Currency in ISO 4217 alpha-3 format, if available, or <b>NULL</b> otherwise.
	 */
	public function get_currency(): ?string {
		if ( $this->try_get_string( $currency, self::FIELD_CURRENCY ) ) {
			return $currency;
		}

		return null;
	}

	/**
	 * <h2>Returns COF ECOMMPAY payment ID.</h2>
	 *
	 * @return ?string
	 */
	public function get_register_payment_id(): ?string {
		if ( $this->try_get_string( $id, self::FIELD_REGISTER_PAYMENT_ID ) ) {
			return $id;
		}

		return null;
	}

	/**
	 * <h2>Returns COF purchase status.</h2>
	 *
	 * @return ?string <p>
	 * Possible values:<br/>
	 *      - {@see EcpGatewayRecurringStatus::ACTIVE} COF is active.<br/>
	 *      - {@see EcpGatewayRecurringStatus::CANCELLED} COF is cancelled.<br/>
	 *      - <b>NULL</b> if not defined.<br/>
	 * </p>
	 */
	public function get_status(): ?string {
		if ( $this->try_get_string( $status, self::FIELD_STATUS ) ) {
			if ( array_key_exists( $status, EcpGatewayRecurringStatus::get_status_names() ) ) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * <h2>Returns COF purchase type.</h2>
	 *
	 * @return ?string
	 */
	public function get_type(): ?string {
		if ( $this->try_get_string( $type, self::FIELD_TYPE ) ) {
			if ( array_key_exists( $type, EcpGatewayRecurringTypes::get_status_names() ) ) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * <h2>Returns expiration date of the COF purchase ID.</h2>
	 *
	 * @return ?object
	 */
	public function get_valid_thru(): ?object {
		if ( $this->try_get_object( $date, self::FIELD_VALID_THRU ) ) {
			return $date;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function unpackRules(): array {
		return [
			self::FIELD_VALID_THRU => function ( $value ) {
				return DateTime::createFromFormat( DateTimeInterface::RFC3339, $value );
			}
		];
	}
}
