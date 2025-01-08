<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * Invalid argument exception in plugin.
 *
 * EcpGatewayInvalidArgumentException class
 *
 * @class   EcpGatewayInvalidArgumentException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class EcpGatewayInvalidArgumentException extends EcpGatewayException {
	/**
	 * Argument name.
	 *
	 * @var string
	 */
	private string $arg;

	/**
	 * Expected argument type.
	 *
	 * @var string
	 */
	private string $expected;

	/**
	 * Received argument type.
	 *
	 * @var string
	 */
	private string $received;

	/**
	 * Exception constructor.
	 *
	 * @param string $arg Wrong argument name.
	 * @param string $value Expected argument type.
	 * @param string $pattern Received argument type.
	 * @param int $errorCode [optional] Error code. Default: {@see EcpGatewayError::INVALID_ARGUMENT}.
	 * @param ?string $message [optional] Base exception message. Default: none.
	 * @param ?Exception $previous [optional] Previous exception. Default: none.
	 */
	public function __construct(
		$arg,
		$value,
		$pattern,
		int $errorCode = EcpGatewayError::INVALID_ARGUMENT,
		string $message = null,
		Exception $previous = null
	) {
		$this->arg      = $arg;
		$this->expected = $value;
		$this->received = $pattern;

		if ( $message === null ) {
			$message = _x( 'Invalid argument type', 'Exception message', 'woo-ecommpay' );
		}

		parent::__construct( $message, $errorCode, $previous );
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
				sprintf( _x( 'Argument name: %s', 'Exception message', 'woo-ecommpay' ), $this->get_arg() ),
				WC_Log_Levels::ERROR,
			],
			[
				sprintf( _x( 'Expected type: %s', 'Exception message', 'woo-ecommpay' ), $this->get_expected() ),
				WC_Log_Levels::ERROR,
			],
			[
				sprintf( _x( 'Received type: %s', 'Exception message', 'woo-ecommpay' ), $this->get_received() ),
				WC_Log_Levels::ERROR,
			],
		];
	}

	/**
	 * Returns argument name.
	 *
	 * @return string
	 */
	final public function get_arg(): string {
		return $this->arg;
	}

	/**
	 * Returns expected argument type.
	 *
	 * @return string
	 */
	final public function get_expected(): string {
		return $this->expected;
	}

	/**
	 * Returns received argument type.
	 *
	 * @return string
	 */
	final public function get_received(): string {
		return $this->received;
	}
}
