<?php

namespace common\models;

use common\helpers\EcpGatewayJson;
use DateTime;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoProvider
 *
 * Contains external provider information about the result of the operation.
 *
 * @class    EcpGatewayInfoProvider
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoProvider extends EcpGatewayJson {


	/**
	 * Label for the payment provider that has been used to process the operation.
	 */
	const FIELD_ID = 'id';

	/**
	 * Label for unique ID of the payment in the provider system.
	 */
	const FIELD_PAYMENT_ID = 'payment_id';

	/**
	 * Label for authorization code received from an external provider.
	 */
	const FIELD_AUTH_CODE = 'auth_code';

	/**
	 * Label for CRC32 ID of the external provider gate.
	 */
	const FIELD_ENDPOINT_ID = 'endpoint_id';

	/**
	 * Label for the date and time the payment provider finished processing the payment.
	 */
	const FIELD_DATE = 'date';


	/**
	 * <h2>Returns the payment provider that has been used to process the operation.</h2>
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
	 * <h2>Returns the unique ID of the payment in the provider system.</h2>
	 *
	 * @return ?string
	 */
	public function get_payment_id(): ?string {
		if ( $this->try_get_string( $id, self::FIELD_PAYMENT_ID ) ) {
			return $id;
		}

		return null;
	}

	/**
	 * <h2>Returns the authorization code received from an external provider.</h2>
	 *
	 * @return ?string
	 */
	public function get_auth_code(): ?string {
		if ( $this->try_get_string( $code, self::FIELD_AUTH_CODE ) ) {
			return $code;
		}

		return null;
	}

	/**
	 * <h2>Returns CRC32 ID of the external provider gate.</h2>
	 *
	 * @return ?string
	 */
	public function get_endpoint_id(): ?string {
		if ( $this->try_get_string( $id, self::FIELD_ENDPOINT_ID ) ) {
			return $id;
		}

		return null;
	}

	/**
	 * <h2>Returns the date and time the payment provider finished processing the payment.</h2>
	 *
	 * @return ?DateTime
	 */
	public function get_date(): ?object {
		if ( $this->try_get_object( $date, self::FIELD_DATE ) ) {
			return $date;
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	protected function unpackRules(): array {
		return [
			self::FIELD_DATE => function ( $value ) {
				return DateTime::createFromFormat( DateTimeInterface::RFC3339, $value );
			},
		];
	}
}
