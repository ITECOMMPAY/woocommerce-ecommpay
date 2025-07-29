<?php

defined( 'ABSPATH' ) || exit;


use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;

/**
 * Get all ECOMMPAY payment statuses.
 *
 * @return array
 * @since  2.0.0
 * @used-by EcpGatewayPayment::set_status
 */
function ecp_get_payment_statuses(): array {
	return EcpGatewayPaymentStatus::get_status_names();
}

/**
 * Get the nice name for a payment status.
 *
 * @param string $status Status.
 *
 * @return string
 * @since  2.0.0
 */
function ecp_get_payment_status_name( string $status ): string {
	return EcpGatewayPaymentStatus::get_status_name( $status );
}

/**
 * See if a string is an ECOMMPAY payment status.
 *
 * @param string $maybe_status
 *
 * @return bool
 * @since  2.0.0
 */
function ecp_is_payment_status( string $maybe_status ): bool {
	return array_key_exists( $maybe_status, ecp_get_payment_statuses() );
}

/**
 * Get the nice name for an operation type.
 *
 * @param string $status Status.
 *
 * @return string
 * @since  2.0.0
 */
function ecp_get_operation_type_name( string $status ): string {
	return EcpGatewayOperationType::get_status_name( $status );
}

function generateNewPaymentId( ?WC_Order $order ): string {
	$paymentId = uniqid( 'wp_' );
	if ( $orderId = $order ? $order->get_id() : null ) {
		$paymentId = $paymentId . '_' . (string)($orderId);
	}
	return $paymentId;
}
