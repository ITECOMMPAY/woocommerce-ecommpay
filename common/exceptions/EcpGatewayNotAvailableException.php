<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayNotAvailableException class
 *
 * @class   EcpGatewayNotAvailableException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class EcpGatewayNotAvailableException extends EcpGatewayException {
	/**
	 * @param string $message Error message.
	 * @param int $code [optional] Error code. Default: {@see EcpGatewayError::NOT_AVAILABLE}.
	 * @param ?Exception $previous [optional] Previous exception. Default: none.
	 */
	public function __construct(
		$message = '',
		$code = EcpGatewayError::NOT_AVAILABLE,
		Exception $previous = null
	) {
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
				WC_Log_Levels::ERROR,
			],
		];
	}
}
