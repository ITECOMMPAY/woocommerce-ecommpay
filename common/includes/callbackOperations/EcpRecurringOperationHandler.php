<?php

namespace common\includes\callbackOperations;

use common\includes\EcpCallbacksHandler;
use common\includes\EcpGatewayOrder;
use common\includes\EcpOrderManager;
use common\interfaces\EcpOperationHandlerInterface;
use common\models\EcpGatewayInfoCallback;
use WC_Data_Exception;

class EcpRecurringOperationHandler implements EcpOperationHandlerInterface {
	private EcpCallbacksHandler $ecp_callbacks_handler;
	private EcpOrderManager $order_manager;

	public function __construct( EcpCallbacksHandler $ecp_callbacks_handler, EcpOrderManager $ecp_order_manager ) {
		$this->ecp_callbacks_handler = $ecp_callbacks_handler;
		$this->order_manager         = $ecp_order_manager;
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @throws WC_Data_Exception
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		ecp_get_log()->info( __( 'Apply recurring callback data.', 'woo-ecommpay' ) );
		$this->order_manager->log_order_data( $order );

		// Set the transaction order ID
		$this->order_manager->update_payment( $order, $callback );
		$order->set_payment_system( $callback->get_payment()->get_method() );
		$this->ecp_callbacks_handler->process( $callback, $order );
	}
}
