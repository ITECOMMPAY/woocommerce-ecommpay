<?php

namespace common\modules;
defined( 'ABSPATH' ) || exit;

use common\api\EcpGatewayAPIPayment;
use common\exceptions\EcpGatewayAPIException;
use common\helpers\EcpGatewayRegistry;
use Exception;

class EcpModuleCancel extends EcpGatewayRegistry {

	private const WP_AJAX_ECP_PROCESS_CANCEL_ORDER = 'wp_ajax_ecp_process_cancel_order';

	protected function init(): void {
		add_action( self::WP_AJAX_ECP_PROCESS_CANCEL_ORDER, [ $this, 'process' ] );
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
}
