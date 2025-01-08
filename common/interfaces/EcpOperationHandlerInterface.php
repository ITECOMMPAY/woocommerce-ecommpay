<?php

namespace common\interfaces;

use common\includes\EcpGatewayOrder;
use common\models\EcpGatewayInfoCallback;
use WC_Data_Exception;

interface EcpOperationHandlerInterface {
	/**
	 * @throws WC_Data_Exception
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order );
}
