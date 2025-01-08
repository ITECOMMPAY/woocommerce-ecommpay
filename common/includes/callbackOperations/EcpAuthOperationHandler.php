<?php

namespace common\includes\callbackOperations;

use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayOperationType;
use common\includes\EcpGatewayOrder;
use common\includes\EcpOrderManager;
use common\interfaces\EcpOperationHandlerInterface;
use common\models\EcpGatewayInfoCallback;
use WC_Data_Exception;

class EcpAuthOperationHandler implements EcpOperationHandlerInterface {
	private EcpOrderManager $order_manager;

	public function __construct( EcpOrderManager $ecp_order_manager ) {
		$this->order_manager = $ecp_order_manager;
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		ecp_info( ecpTr( 'Apply auth callback data.' ) );
		$this->order_manager->log_order_data( $order );

		// Set the transaction order ID
		$this->order_manager->update_payment( $order, $callback );
		$this->order_manager->update_subscription( $order, $callback );
		$order->set_payment_system( $callback->get_payment()->get_method() );

		$operation        = $callback->get_operation();
		$operation_status = $operation->get_status();
		$operation_type   = $operation->get_type();

		if ( $operation_type === EcpGatewayOperationType::AUTH ) {
			switch ( $operation_status ) {
				case EcpGatewayOperationStatus::SUCCESS:
					$order->add_order_note( ecpTr( sprintf( 'The payment of %s was authorized.', $order->get_formatted_order_total() ) ) );
					$this->order_manager->hold_order( $callback, $order );
					break;
				case EcpGatewayOperationStatus::DECLINE:
				case EcpGatewayOperationStatus::EXPIRED:
				case EcpGatewayOperationStatus::INTERNAL_ERROR:
				case EcpGatewayOperationStatus::EXTERNAL_ERROR:
					$order->add_order_note( ecpTr( sprintf( 'An authorization of %s was declined.', $order->get_formatted_order_total() ) ) );
					$this->order_manager->decline_order( $callback, $order );
					break;
			}
		}
	}
}
