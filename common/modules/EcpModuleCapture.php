<?php

namespace common\modules;

use Ecp_Gateway_API_Exception;
use Ecp_Gateway_API_Payment;
use Ecp_Gateway_Order;
use Ecp_Gateway_Registry;
use EcpGatewaySettingsProducts;
use EcpGatewaySettingsSubscriptions;
use Exception;

class EcpModuleCapture extends Ecp_Gateway_Registry {

	private const SUBSCRIPTION_TYPE = 'subscription';

	protected function init() {
		add_action( 'wp_ajax_ecp_process_capture_order', [ $this, 'process' ] );
	}

	/**
	 * @throws Ecp_Gateway_API_Exception
	 * @throws Exception
	 */
	public function process( $order_id = null ): bool {

		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json_error( [ 'message' => 'Access denied.' ], 403 );

			return false;
		}

		$order_id = ! empty( $order_id ) ? $order_id : $_POST['order_id']; // Both used in different cases

		if ( empty( $order_id ) ) {
			ecp_error( 'Order ID is missing.' );
			wp_send_json_error( [ 'message' => 'Invalid order ID.' ] );
		}

		ecp_debug( 'Processing capture for order ID: ' . $order_id );
		$order = ecp_get_order( $order_id );

		try {
			$api     = new Ecp_Gateway_API_Payment();
			$payment = $api->capture( $order );

			if ( $payment->get_request_id() === '' ) {
				ecp_error( 'Capture error: ', $payment );
				wp_send_json_error( 'Capture request declined by ECOMMPAY gateway.', 418 );
				return false;
			}

			wp_send_json_success( 'Order captured successfully. ' . $order_id );
			ecp_info( 'Capture completed for order ID: ' . $order_id );

			return true;
		} catch ( Exception $e ) {
			ecp_error( 'Capture unexpected error: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Validates if order contains only virtual or downloadable items
	 *
	 * @param Ecp_Gateway_Order|null $order
	 *
	 * @return bool
	 */
	public static function is_auto_capture_needed( Ecp_Gateway_Order $order = null ): bool {
		$products_section_id      = EcpGatewaySettingsProducts::ID;
		$subscriptions_section_id = EcpGatewaySettingsSubscriptions::ID;

		$virtualProductConfirmation           = ecommpay()->get_pm_option( $products_section_id, EcpGatewaySettingsProducts::OPTION_ID_VIRTUAL_PRODUCTS_CONFIRMATION, false );
		$downloadableProductConfirmation      = ecommpay()->get_pm_option( $products_section_id, EcpGatewaySettingsProducts::OPTION_ID_DOWNLOADABLE_PRODUCTS_CONFIRMATION, false );
		$virtualSubscriptionConfirmation      = ecommpay()->get_pm_option( $subscriptions_section_id, EcpGatewaySettingsSubscriptions::OPTION_ID_VIRTUAL_SUBSCRIPTIONS_CONFIRMATION, false );
		$downloadableSubscriptionConfirmation = ecommpay()->get_pm_option( $subscriptions_section_id, EcpGatewaySettingsSubscriptions::OPTION_ID_DOWNLOADABLE_SUBSCRIPTIONS_CONFIRMATION, false );
		$otherSubscriptionConfirmation        = ecommpay()->get_pm_option( $subscriptions_section_id, EcpGatewaySettingsSubscriptions::OPTION_ID_OTHER_SUBSCRIPTIONS_CONFIRMATION, false );

		$iterable = $order ? $order->get_items() : WC()->cart->get_cart();

		$is_auto_capture_needed = true;

		foreach ( $iterable as $item ) {
			$product = $order ? $item->get_product() : $item['data'];

			if ( ! $product ) {
				continue;
			}

			if ( $product->is_type( self::SUBSCRIPTION_TYPE ) ) {
				if ( !(
					( $product->is_virtual() && $virtualSubscriptionConfirmation ) ||
					( $product->is_downloadable() && $downloadableSubscriptionConfirmation ) ||
					( self::is_other_subscription($product) && $otherSubscriptionConfirmation )
				) ){
					$is_auto_capture_needed = false;
				}
			} else {
				if ( !(
					( $product->is_virtual() && $virtualProductConfirmation ) ||
					( $product->is_downloadable() && $downloadableProductConfirmation )
				) ){
					$is_auto_capture_needed = false;
				}
			}
		}

		return $is_auto_capture_needed;
	}

	private static function is_other_subscription($product): bool
	{
		return !( $product->is_virtual() | $product->is_downloadable() );
	}
}