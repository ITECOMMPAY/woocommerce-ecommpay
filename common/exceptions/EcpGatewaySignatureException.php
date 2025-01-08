<?php

namespace common\exceptions;

use common\modules\EcpSigner;
use Exception;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewaySignatureException class
 *
 * @class   EcpGatewaySignatureException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @category Class
 */
class EcpGatewaySignatureException extends EcpGatewayException {
	/**
	 * <p>The value or key of an invalid parameter</p>
	 * @var string
	 * @since 2.0.0
	 */
	private string $parameter;

	/**
	 * <h2>Signature exception constructor.</h2>
	 *
	 * @param string $parameter <p>The value or key of an invalid parameter.</p>
	 * @param string $message <p>Exception message.</p>
	 * @param int $code [optional] <p>Error code. Default: {@see EcpGatewayError::UNKNOWN_ERROR}
	 * @param ?Exception $previous [optional] <p>Previous exception.</p>
	 */
	public function __construct(
		$message,
		$parameter,
		$code = EcpGatewayError::UNKNOWN_ERROR,
		Exception $previous = null
	) {
		$this->parameter = $parameter;
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @inheritDoc
	 * @return array[]
	 */
	protected function prepare_message(): array {
		return [
			[
				$this->get_base_message(),
				WC_Log_Levels::ERROR
			],
			[
				sprintf( _x( 'Invalid parameter: %s', 'Exception message', 'woo-ecommpay' ), $this->getParameter() ),
				WC_Log_Levels::ERROR,
			],
			[
				sprintf(
					_x( 'Prohibited symbol: %s', 'Exception message', 'woo-ecommpay' ),
					EcpSigner::VALUE_SEPARATOR
				),
				WC_Log_Levels::ERROR,
			],
		];
	}

	/**
	 * <h2>Returns the value or key of an invalid parameter.</h2>
	 * @return string
	 */
	final public function getParameter(): string {
		return $this->parameter;
	}
}
