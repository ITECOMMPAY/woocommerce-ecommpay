<?php

namespace common\includes\callbackOperations;

use common\includes\EcpCallbacksHandler;
use common\includes\EcpGatewayOrder;
use common\interfaces\EcpOperationHandlerInterface;
use common\models\EcpGatewayInfoCallback;
use WC_Data_Exception;

class EcpConfirmOperationHandler implements EcpOperationHandlerInterface {
	private EcpCallbacksHandler $ecp_callbacks_handler;

	public function __construct( EcpCallbacksHandler $ecp_callbacks_handler ) {
		$this->ecp_callbacks_handler = $ecp_callbacks_handler;
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		ecp_get_log()->info( __( 'Apply payment confirmation callback data.', 'woo-ecommpay' ) );
		$this->ecp_callbacks_handler->order_manager->log_order_data( $order );

		// Set the transaction order ID
		$this->ecp_callbacks_handler->order_manager->update_payment( $order, $callback );
		$order->set_payment_system( $callback->get_payment()->get_method() );
		$this->ecp_callbacks_handler->order_manager->update_subscription( $order, $callback );
		$this->ecp_callbacks_handler->process( $callback, $order );
	}
}
