<?php

namespace common\modules;

use Ecp_Gateway_API_Exception;
use Ecp_Gateway_API_Payment;
use Ecp_Gateway_Registry;
use Ecp_Gateway_Settings_General;
use Exception;
use Ecp_Gateway_Payment_Status;

class EcpModuleCancel extends Ecp_Gateway_Registry {
	protected function init() {
		add_action( 'wp_ajax_ecp_process_cancel_order', [ $this, 'process' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'auto_cancel_on_status_change' ] );
	}

	/**
	 * @throws Ecp_Gateway_API_Exception
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
			$api     = new Ecp_Gateway_API_Payment();
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
	 * @throws Ecp_Gateway_API_Exception
	 */
	public function auto_cancel_on_status_change( $order_id = null ): bool {

		if ( ! ecommpay()->get_general_option( Ecp_Gateway_Settings_General::AUTOMATIC_CANCELLATION, false ) ) {
			return false;
		}

		$order = ecp_get_order($order_id);

		if ( $order->get_ecp_status() !== Ecp_Gateway_Payment_Status::AWAITING_CAPTURE) {
			return false;
		}

		return $this->process( $order_id, true );
	}
}