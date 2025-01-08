<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayKeyNotFoundException class
 *
 * @class   EcpGatewayKeyNotFoundException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class EcpGatewayKeyNotFoundException extends EcpGatewayException {
	/**
	 * Contain corrupted key name.
	 *
	 * @var int|string
	 */
	private $key;

	/**
	 * Contain list of available key names.
	 *
	 * @var int[]|string[]
	 */
	private array $available;

	/**
	 * Exception constructor.
	 *
	 * @param int|string $key Corrupted key name.
	 * @param int[]|string[] $available List of available key names.
	 * @param int $code [optional] Error code. Default: {@see EcpGatewayError::KEY_NOT_FOUND}.
	 * @param string|null $message [optional] Base error message. Default: none.
	 * @param ?Exception $previous Previous exception. Default: none.
	 */
	public function __construct(
		$key,
		$available,
		$code = EcpGatewayError::KEY_NOT_FOUND,
		string $message = null,
		Exception $previous = null
	) {
		$this->key       = $key;
		$this->available = $available;

		if ( $message === null ) {
			$message = _x( 'Key not found in the current array.', 'Exception message', 'woo-ecommpay' );
		}

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
				sprintf( _x( 'Searched key: %s', 'Exception message', 'woo-ecommpay' ), $this->getKey() ),
				WC_Log_Levels::ERROR,
			],
			[
				sprintf(
					_x( 'Available keys: %s', 'Exception message', 'woo-ecommpay' ),
					implode( ', ', $this->getAvailable() )
				),
				WC_Log_Levels::ERROR,
			]
		];
	}

	/**
	 * Returns corrupted key name.
	 *
	 * @return int|string
	 */
	final public function getKey() {
		return $this->key;
	}

	/**
	 * Returns list of available key names.
	 *
	 * @return int[]|string[]
	 */
	final public function getAvailable(): array {
		return $this->available;
	}
}
