<?php

namespace common\exceptions;

use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayError class
 *
 * @class   EcpGatewayError
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class EcpGatewayErrorException extends EcpGatewayException {
	private $severity;

	public function __construct(
		$message = '',
		$code = 0,
		$severity = E_ERROR,
		$filename = null,
		$line = null,
		Exception $previous = null
	) {
		parent::__construct( $message, $code, $previous );
		$this->severity = $severity;
		$this->file     = $filename;
		$this->line     = $line;
	}

	final public function getSeverity() {
		return $this->severity;
	}


	protected function prepare_message(): array {
		return [
			[
				$this->get_base_message(),
				WC_Log_Levels::ERROR,
			],
		];
	}
}
