<?php

namespace common\models;

use common\helpers\EcpGatewayJson;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoStatus
 *
 * @class    EcpGatewayInfoCallback
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoStatus extends EcpGatewayJson {


	/**
	 * Label for payment instrument information.
	 */
	private const FIELD_ACCOUNT = 'account';

	/**
	 * Label for 3-D Secure data information.
	 */
	private const FIELD_ACS = 'acs';

	/**
	 * Label for customer information.
	 */
	private const FIELD_CUSTOMER = 'customer';

	/**
	 * Label for array of errors information.
	 */
	public const FIELD_ERRORS = 'errors';

	/**
	 * Label for operation information.
	 */
	private const FIELD_OPERATIONS = 'operations';

	/**
	 * Label for payment information.
	 */
	private const FIELD_PAYMENT = 'payment';

	/**
	 * Label for identifier of merchant project received from ECOMMPAY.
	 */
	private const FIELD_PROJECT_ID = 'project_id';

	private const FIELD_GENERAL = 'general';

	/**
	 * Label for recurring information.
	 */
	private const FIELD_RECURRING = 'recurring';


	/**
	 * Callback information constructor.
	 *
	 * @param array $data [optional] <p>JSON-data as array.</p>
	 */
	public function __construct( array $data = [] ) {
		$this->register( self::FIELD_ACCOUNT, EcpGatewayInfoAccount::class );
		$this->register( self::FIELD_ACS, EcpGatewayInfoACS::class );
		$this->register( self::FIELD_CUSTOMER, EcpGatewayInfoCustomer::class );
		$this->register( self::FIELD_PAYMENT, EcpGatewayInfoPayment::class );
		$this->register( self::FIELD_RECURRING, EcpGatewayInfoRecurring::class );

		parent::__construct( $data );
	}

	/**
	 * <h2>Returns the payment instrument information.</h2>
	 *
	 * @return ?EcpGatewayJson
	 */
	public function get_account(): ?EcpGatewayJson {
		if ( $this->try_get_json( $account, self::FIELD_ACCOUNT ) ) {
			return $account;
		}

		return null;
	}

	/**
	 * <h2>Returns the 3-D Secure data information.</h2>
	 *
	 * @return ?EcpGatewayJson
	 */
	public function get_acs(): ?EcpGatewayJson {
		if ( $this->try_get_json( $acs, self::FIELD_ACS ) ) {
			return $acs;
		}

		return null;
	}

	/**
	 * <h2>Returns the customer information.</h2>
	 *
	 * @return ?EcpGatewayJson
	 */
	public function get_customer(): ?EcpGatewayJson {
		if ( $this->try_get_json( $customer, self::FIELD_CUSTOMER ) ) {
			return $customer;
		}

		return null;
	}

	/**
	 * <h2>Returns list of errors information.</h2>
	 *
	 * @return EcpGatewayInfoError[]
	 */
	public function get_errors(): array {
		$this->try_get_array( $errors, self::FIELD_ERRORS );

		return $errors;
	}

	/**
	 * <h2>Returns the information about transactions.</h2>
	 * @return EcpGatewayInfoOperation[]
	 */
	public function get_operations(): ?array {
		if ( $this->try_get_array( $operation, self::FIELD_OPERATIONS ) ) {
			return $operation;
		}

		return null;
	}

	/**
	 * <h2>Returns the payment information.</h2>
	 *
	 * @return EcpGatewayInfoPayment
	 */
	public function get_payment(): EcpGatewayInfoPayment {
		$this->try_get_payment( $payment );

		return $payment;
	}

	public function try_get_payment( &$payment ): bool {
		return $this->try_get_json( $payment, self::FIELD_PAYMENT );
	}

	/**
	 * <h2>Returns the project identifier in the payment platform ECOMMPAY.</h2>
	 *
	 * @return int
	 */
	public function get_project_id(): int {
		$this->try_get_int( $id, self::FIELD_PROJECT_ID );

		return $id;
	}

	/**
	 * <h2>Returns the recurring information.</h2>
	 *
	 * @return ?EcpGatewayJson
	 */
	public function get_recurring(): ?EcpGatewayJson {
		if ( $this->try_get_json( $recurring, self::FIELD_RECURRING ) ) {
			return $recurring;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function unpackRules(): array {
		return [
			self::FIELD_PROJECT_ID => function ( $value ) {
				return (int) $value;
			},
			self::FIELD_OPERATIONS => function ( $value ) {
				foreach ( $value as &$item ) {
					$item = new EcpGatewayInfoOperation( $item );
				}

				return $value;
			},
			self::FIELD_ERRORS     => function ( $value ) {
				foreach ( $value as &$item ) {
					$item = new EcpGatewayInfoError( $item );
				}

				return $value;
			}
		];
	}
}
