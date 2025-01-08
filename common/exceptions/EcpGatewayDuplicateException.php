<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayDuplicateException class
 *
 * @class   EcpGatewayDuplicateException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @internal
 */
class EcpGatewayDuplicateException extends EcpGatewayException {
	/**
	 * @var string
	 * @since 2.0.0
	 */
	private $key;

	/**
	 * Exception constructor.
	 *
	 * @param mixed $key The duplicate key name.
	 * @param int $errorCode [optional] Error code. Default: {@see EcpGatewayError::DUPLICATE}.
	 * @param string $message [optional] Exception message. Default: none.
	 * @param ?Exception $previous [optional] Previous exception. Default: none.
	 *
	 * @since 2.0.0
	 */
	public function __construct(
		$key,
		$errorCode = EcpGatewayError::DUPLICATE,
		$message = null,
		Exception $previous = null
	) {
		$this->key = $key;

		if ( $message === null ) {
			$message = _x( 'Key is already exists in the current array', 'Exception message', 'woo-ecommpay' );
		}

		parent::__construct( $message, $errorCode, $previous );
	}

	/**
	 * @inheritDoc
	 * @return string[][]
	 * @since 2.0.0
	 */
	protected function prepare_message(): array {
		return [
			[
				$this->get_base_message(),
				WC_Log_Levels::ALERT
			],
			[
				sprintf(
				/* translators: %s: Duplicate key name */
					_x( 'Duplicated key: %s', 'Exception message', 'woo-ecommpay' ),
					$this->getKey()
				),
				WC_Log_Levels::ERROR
			]
		];
	}

	/**
	 * Returns duplicate key name.
	 *
	 * @return mixed
	 * @since 2.0.0
	 */
	final public function getKey() {
		return $this->key;
	}
}
