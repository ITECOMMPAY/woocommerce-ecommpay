<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayNotImplementedException class
 *
 * @class   EcpGatewayNotImplementedException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @abstract
 */
class EcpGatewayNotImplementedException extends EcpGatewayException {
	/**
	 * @var string Default exception message.
	 */
	const DEFAULT_MESSAGE = 'Object is not instance of required interface.';

	/**
	 * @var string
	 */
	private string $interface;

	/**
	 * @var string
	 */
	private string $object;

	/**
	 * Object is not implement required interface exception constructor.
	 *
	 * @param object $object
	 * @param string $interface
	 * @param int $code [optional] Error code. Default: {@see EcpGatewayError::NOT_IMPLEMENTED}.
	 * @param string $message [optional] Base error message.
	 *                                   Default: {@see EcpGatewayNotImplementedException::DEFAULT_MESSAGE}.
	 * @param ?Exception $previous [optional] Previous exception. Default: none.
	 */
	public function __construct(
		$object,
		$interface,
		$code = EcpGatewayError::NOT_IMPLEMENTED,
		string $message = self::DEFAULT_MESSAGE,
		Exception $previous = null
	) {
		$this->object    = get_class( $object );
		$this->interface = $interface;

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @inheritDoc
	 * @return string[][]
	 */
	protected function prepare_message(): array {
		return [
			[
				$this->get_base_message(),
				WC_Log_Levels::ALERT,
			],
			[
				sprintf( __( 'Object name: %s', 'woo-ecommpay' ), $this->get_object() ),
				WC_Log_Levels::ERROR,
			],
			[
				sprintf( __( 'Interface name: %s', 'woo-ecommpay' ), $this->get_interface() ),
				WC_Log_Levels::ERROR,
			],
		];
	}

	/**
	 * Returns not implement required interface object name.
	 *
	 * @return string
	 */
	final public function get_object(): string {
		return $this->object;
	}

	/**
	 * Returns required interface name.
	 *
	 * @return string
	 */
	final public function get_interface(): string {
		return $this->interface;
	}
}
