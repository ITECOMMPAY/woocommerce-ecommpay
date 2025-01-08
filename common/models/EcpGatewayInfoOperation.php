<?php

namespace common\models;

use common\helpers\EcpGatewayJson;
use DateTime;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoOperation
 *
 * Contains information about the operation
 *
 * @class    EcpGatewayInfoOperation
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoOperation extends EcpGatewayJson {


	/**
	 * Label for unique ID of the operation
	 */
	const FIELD_ID = 'id';

	/**
	 * Label for unique ID of the last request related to the operation
	 */
	const FIELD_REQUEST_ID = 'request_id';

	/**
	 * Label for operation type
	 */
	const FIELD_TYPE = 'type';

	/**
	 * Label for operation status
	 */
	const FIELD_STATUS = 'status';

	/**
	 * Label for date and time the payment status was last updated
	 */
	const FIELD_DATE = 'date';

	/**
	 * Label for date and time the operation was created.
	 */
	const FIELD_CREATED_DATE = 'created_date';

	/**
	 * Label for unified payment provider response code
	 */
	const FIELD_CODE = 'code';

	/**
	 * Label for unified message from the payment provider.
	 */
	const FIELD_MESSAGE = 'message';

	/**
	 * Label for the indicator that shows the result of the 3‑D Secure customer authentication.
	 */
	const FIELD_ECI = 'eci';

	/**
	 * Label for object that contains the amount and currency of the operation as specified in the initial request.
	 */
	const FIELD_SUM_INITIAL = 'sum_initial';

	/**
	 * Label for object that contains the currency of the payment provider account and the initial amount denominated
	 * in this currency
	 */
	const FIELD_SUM_CONVERTED = 'sum_converted';

	/**
	 * Label for object that contains external provider information about the result of the operation.
	 */
	const FIELD_PROVIDER = 'provider';


	/**
	 * <h2>Information constructor.</h2>
	 *
	 * @param array $data [optional] Json-data as array.
	 */
	public function __construct( array $data = [] ) {
		$this->register( self::FIELD_SUM_INITIAL, EcpGatewayInfoSum::class );
		$this->register( self::FIELD_SUM_CONVERTED, EcpGatewayInfoSum::class );
		$this->register( self::FIELD_PROVIDER, EcpGatewayInfoProvider::class );

		parent::__construct( $data );
	}

	/**
	 * <h2>Returns the unique ID of the operation.</h2>
	 *
	 * @return int
	 */
	public function get_id(): int {
		$this->try_get_int( $id, self::FIELD_ID );

		return $id;
	}

	/**
	 * <h2>Returns the operation type.</h2>
	 * Example: capture/cancel
	 *
	 * @return string
	 */
	public function get_type(): string {
		$this->try_get_string( $type, self::FIELD_TYPE );

		return $type;
	}

	/**
	 * <h2>Returns the unique ID of the last request related to the operation.</h2>
	 *
	 * @return string
	 */
	public function get_request_id(): string {
		$this->try_get_string( $id, self::FIELD_REQUEST_ID );

		return $id;
	}

	/**
	 * <h2>Returns the date and time the payment status was last updated.</h2>
	 *
	 * @return object|null
	 */
	public function get_date(): ?object {
		$this->try_get_object( $date, self::FIELD_DATE );

		return $date;
	}

	/**
	 * <h2>Returns the results of check date exists and put value to container.</h2>
	 *
	 * @return bool <p><b>TRUE</b> if Date exists or <b>FALSE</b> otherwise</p>
	 */
	public function try_get_date( &$date ): bool {
		return $this->try_get_object( $date, self::FIELD_DATE );
	}

	/**
	 * <h2>Returns the date and time the operation was created.</h2>
	 *
	 * @return object
	 */
	public function get_created_date(): object {
		$this->try_get_object( $date, self::FIELD_CREATED_DATE );

		return $date;
	}

	/**
	 * <h2>Returns the operation status.</h2>
	 *
	 * @return string
	 */
	public function get_status(): string {
		$this->try_get_string( $status, self::FIELD_STATUS );

		return $status;
	}

	/**
	 * <h2>Returns the unified payment provider response code.</h2>
	 *
	 * @return int
	 */
	public function get_code(): int {
		$this->try_get_int( $code, self::FIELD_CODE );

		return $code;
	}

	/**
	 * <h2>Returns the unified message from the payment provider.</h2>
	 *
	 * @return string
	 */
	public function get_message(): string {
		$this->try_get_string( $message, self::FIELD_MESSAGE );

		return $message;
	}

	/**
	 * <h2>Returns the initial price information.</h2>
	 * <p>Object that contains the amount and currency of the operation as specified in the initial request.</p>
	 *
	 * @return EcpGatewayJson|null
	 */
	public function get_sum_initial(): ?EcpGatewayJson {
		$this->try_get_json( $sum, self::FIELD_SUM_INITIAL, new EcpGatewayInfoSum() );

		return $sum;
	}

	/**
	 * <h2>Returns the converts price information.</h2>
	 * <p>Price contains the currency of the payment provider account and the initial amount denominated
	 * in this currency.</p>
	 *
	 * @return EcpGatewayJson|null
	 */
	public function get_sum_converts(): ?EcpGatewayJson {
		$this->try_get_json( $sum, self::FIELD_SUM_CONVERTED, new EcpGatewayInfoSum() );

		return $sum;
	}

	/**
	 * <h2>Returns the indicator that shows the result of the 3‑D Secure customer authentication.</h2>
	 *
	 * @return string
	 */
	public function get_eci(): string {
		$this->try_get_string( $eci, self::FIELD_ECI );

		return $eci;
	}

	/**
	 * <h2>Returns the provider information.</h2>
	 * <p>Provider information contains external provider information about the result of the operation.</p>
	 *
	 * @return EcpGatewayJson
	 */
	public function get_provider(): EcpGatewayJson {
		$this->try_get_json( $provider, self::FIELD_PROVIDER, new EcpGatewayInfoProvider() );

		return $provider;
	}

	protected function unpackRules(): array {
		return [
			self::FIELD_DATE         => function ( $value ) {
				return DateTime::createFromFormat( DateTimeInterface::RFC3339, $value );
			},
			self::FIELD_CREATED_DATE => function ( $value ) {
				return DateTime::createFromFormat( DateTimeInterface::RFC3339, $value );
			}
		];
	}
}
