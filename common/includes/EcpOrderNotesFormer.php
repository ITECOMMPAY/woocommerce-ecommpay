<?php

namespace common\includes;

use common\models\EcpGatewayInfoCallback;

class EcpOrderNotesFormer {
	private EcpCallbacksHandler $ecp_callbacks_handler;

	public function __construct( EcpCallbacksHandler $ecp_callbacks_handler ) {
		$this->ecp_callbacks_handler = $ecp_callbacks_handler;
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return string
	 */
	public function get_dashboard_append_text_recommendation( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): string {
		return ( $this->ecp_callbacks_handler->is_callback_from_dashboard( $callback, $order )
			? "\nIt is recommended that you apply the corresponding changes in the order, including the order amount and status." : '' );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return string
	 */
	public function get_dashboard_append_text( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): string {
		return ( $this->ecp_callbacks_handler->is_callback_from_dashboard( $callback, $order ) ? ' via Dashboard of ECOMMPAY' : '' );

	}
}
