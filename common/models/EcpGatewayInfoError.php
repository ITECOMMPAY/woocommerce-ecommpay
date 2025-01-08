<?php

namespace common\models;

use common\helpers\EcpGatewayJson;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoError
 *
 * Contains information about a single error.
 *
 * @class    EcpGatewayInfoError
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoError extends EcpGatewayJson {


	/**
	 * Label for unified error code
	 */
	const FIELD_CODE = 'code';

	/**
	 * Label for message that explains the cause of the error.
	 */
	const FIELD_MESSAGE = 'message';

	/**
	 * Label for parameter or object name in the request that is missing or where the error is found
	 */
	const FIELD_NAME = 'field';

	/**
	 * Label for description of the error cause.
	 */
	const FIELD_DESCRIPTION = 'description';

	//endregion

	/**
	 * <h2>Returns the unified error code.</h2>
	 *
	 * @return ?int
	 */
	public function get_code(): ?int {
		if ( $this->try_get_int( $code, self::FIELD_CODE ) ) {
			return $code;
		}

		return null;
	}

	/**
	 * <h2>Returns the message that explains the cause of the error.</h2>
	 *
	 * @return ?string
	 */
	public function get_message(): ?string {
		if ( $this->try_get_string( $message, self::FIELD_MESSAGE ) ) {
			return $message;
		}

		return null;
	}

	/**
	 * <h2>Returns the parameter or object name in the request that is missing or where the error is found.</h2>
	 *
	 * @return ?string
	 */
	public function get_field(): ?string {
		if ( $this->try_get_string( $field, self::FIELD_NAME ) ) {
			return $field;
		}

		return null;
	}

	/**
	 * <h2>Returns description of the error cause.</h2>
	 *
	 * @return ?string
	 */
	public function get_description(): ?string {
		if ( $this->try_get_string( $constraint, self::FIELD_DESCRIPTION ) ) {
			return $constraint;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function unpackRules(): array {
		return [
			self::FIELD_CODE => function ( $value ) {
				return (int) $value;
			},
		];
	}
}
