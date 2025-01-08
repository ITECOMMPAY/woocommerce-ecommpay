<?php

namespace common\includes\callbackOperations;

use common\includes\EcpCallbacksHandler;
use common\includes\EcpGatewayOrder;
use common\interfaces\EcpOperationHandlerInterface;
use common\models\EcpGatewayInfoCallback;

class EcpCancelOperationHandler implements EcpOperationHandlerInterface {
	private EcpCallbacksHandler $ecp_callbacks_handler;

	public function __construct( EcpCallbacksHandler $ecp_callbacks_handler ) {
		$this->ecp_callbacks_handler = $ecp_callbacks_handler;
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return bool
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): bool {
		ecp_info( ecpTr( 'Handling ' . $callback->get_operation()->get_type() . ' callback for order ID: ' ), $order->get_id() );
		$this->ecp_callbacks_handler->order_manager->log_order_data( $order );
		$this->ecp_callbacks_handler->order_manager->update_payment( $order, $callback );
		$this->ecp_callbacks_handler->order_manager->set_payment_systems( $callback, $order );

		return $this->ecp_callbacks_handler->order_manager->add_order_note( $callback, $order );
	}
}
