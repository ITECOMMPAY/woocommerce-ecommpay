<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayException abstract class
 *
 * @class   EcpGatewayException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @abstract
 * @internal
 */
abstract class EcpGatewayException extends Exception {
	/**
	 * <h2>Base exception message.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private string $base_message;

	/**
	 * <h2>Base exception constructor.</h2>
	 *
	 * @param string $message <p>Error message.</p>
	 * @param int $code [optional] <p>Error code. Default: {@see EcpGatewayError::UNKNOWN_ERROR}.</p>
	 * @param ?Exception $previous [optional] <p>Previous exception. Default: null.</p>
	 *
	 * @since 2.0.0
	 */
	public function __construct( $message, $code = EcpGatewayError::UNKNOWN_ERROR, Exception $previous = null ) {
		$this->set_base_message( $message );

		parent::__construct( $this->get_formatted_message(), $code, $previous );
	}

	/**
	 * <h2>Returns formatted exception message.</h2>
	 *
	 * @return string <p>Formatted exception message.</p>
	 * @since 2.0.0
	 */
	private function get_formatted_message(): string {
		return $this->get_base_message();
	}

	/**
	 * <h2>Returns the base error message.</h2>
	 *
	 * @return string <p>Base error message.</p>
	 * @since 2.0.0
	 */
	final protected function get_base_message(): string {
		return $this->base_message;
	}

	/**
	 * <h2>Sets the base error message.</h2>
	 *
	 * @param string $message <p>Base error message</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	final protected function set_base_message( string $message ): void {
		$this->base_message = $message;
	}

	/**
	 * <h2>Writes exception to log.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	final public function write_to_logs(): void {
		foreach ( $this->prepare_message() as $value ) {
			if ( is_array( $value ) ) {
				list( $value, $level ) = $value;
			} else {
				$level = WC_Log_Levels::ERROR;
			}

			ecp_get_log()->add( $value, $level );
		}
	}

	/**
	 * <h2>Returns prepared error message as string array.</h2>
	 *
	 * @return string[]
	 * @since 2.0.0
	 */
	abstract protected function prepare_message(): array;
}
