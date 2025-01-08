<?php

namespace common\exceptions;

use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayLogicException class
 *
 * @class   EcpGatewayLogicException
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @category Class
 */
class EcpGatewayLogicException extends EcpGatewayException {

	protected function prepare_message(): array {
		return [
			[
				$this->get_base_message(),
				WC_Log_Levels::ERROR
			]
		];
	}
}
