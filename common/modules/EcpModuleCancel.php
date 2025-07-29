<?php

namespace common\modules;
defined( 'ABSPATH' ) || exit;

use common\api\EcpGatewayAPIPayment;
use common\exceptions\EcpGatewayAPIException;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayRegistry;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpApiFilters;
use common\includes\filters\EcpWCFilters;
use common\settings\EcpSettingsGeneral;
use Exception;
use Throwable;
use WC_Order;

class EcpModuleCancel extends EcpGatewayRegistry {

	protected function init(): void {
		add_action( EcpApiFilters::WP_AJAX_ECP_PROCESS_CANCEL_ORDER, [ $this, 'process' ] );

		add_action( EcpWCFilters::WOOCOMMERCE_ORDER_STATUS_CANCELLED, [ $this, 'try_auto_cancel_payment' ] );
	}

	/**
	 * @throws EcpGatewayAPIException
	 * @throws Exception
	 */
	public function process( $order_id = null, bool $hide_ajax_message = false ): bool {

		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( [ 'message' => 'Access denied.' ], 403 );

			return false;
		}

		$order_id = ! empty( $order_id ) ? $order_id : $_POST['order_id']; // Both used in different cases

		if ( empty( $order_id ) ) {
			ecp_error( 'Order ID is missing.' );
			if ( ! $hide_ajax_message ) {
				wp_send_json_error( [ 'message' => 'Invalid order ID.' ] );
			}
		}

		ecp_debug( 'Processing cancellation for order ID: ' . $order_id );
		$order = ecp_get_order( $order_id );

		try {
			$api = new EcpGatewayAPIPayment();
			$payment = $api->cancel( $order );

			if ( $payment->get_request_id() === '' ) {
				ecp_error( 'Cancellation error: ', $payment );
				if ( ! $hide_ajax_message ) {
					wp_send_json_error( 'Cancellation request declined by ECOMMPAY gateway.', 418 );
				}

				return false;
			}

			if ( ! $hide_ajax_message ) {
				wp_send_json_success( 'Order cancelled successfully. ' . $order_id );
			}
			ecp_info( 'Cancellation completed for order ID: ' . $order_id );

			return true;
		} catch ( Exception $e ) {
			ecp_error( 'Cancellation unexpected error: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Determine if the order should be auto-cancelled via Ecommpay.
	 *
	 * @param $order EcpGatewayOrder|null
	 *
	 * @return bool
	 */
	private function should_auto_cancel_payment( ?EcpGatewayOrder $order ): bool {
		if ( ! $order || ! $order->is_ecp() ) {
			return false;
		}
		if ( ! ecommpay()->get_general_option( EcpSettingsGeneral::AUTOMATIC_CANCELLATION ) ) {
			return false;
		}
		if ( $order->get_ecp_status() !== EcpGatewayPaymentStatus::AWAITING_CAPTURE ) {
			return false;
		}

		return true;
	}

	/**
	 * Automatically cancel Ecommpay payment if order is cancelled and feature is enabled.
	 *
	 * @param int|WC_Order $order_id
	 */
	public function try_auto_cancel_payment( $order_id ) {
		$order = ecp_get_order( $order_id );
		if ( ! $this->should_auto_cancel_payment( $order ) ) {
			return;
		}
		try {
			$api = new EcpGatewayAPIPayment();
			$api->cancel( $order );
		} catch ( Throwable $e ) {
			ecp_error( 'Automatic cancellation failed: ' . $e->getMessage() );
		}
	}
}
