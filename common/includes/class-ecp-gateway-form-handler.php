<?php

/**
 * Ecp_Gateway_Form_Handler class
 *
 * Wrapper Handle frontend forms.
 *
 * @class    Ecp_Gateway_Form_Handler
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Form_Handler extends WC_Form_Handler {
	/**
	 * Process the pay form.
	 */
	public static function pay_action() {
		global $wp;

		if ( isset ( $_POST['woocommerce_pay'], $_GET['key'] ) ) {
			wc_nocache_headers();

			$nonce_value = wc_get_var( $_REQUEST['woocommerce-pay-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-pay' ) ) {
				return;
			}

			ob_start();

			// Pay for existing order.
			$order_key = wp_unslash( $_GET['key'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order_id  = absint( $wp->query_vars['order-pay'] );
			$order     = ecp_get_order( $order_id );

			if ( $order_id === $order->get_id() && hash_equals( $order->get_order_key(), $order_key ) && $order->needs_payment() ) {

				do_action( 'woocommerce_before_pay_action', $order );

				WC()->customer->set_props(
					[
						'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
						'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
						'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
						'billing_city'     => $order->get_billing_city() ? $order->get_billing_city() : null,
					]
				);
				WC()->customer->save();

				// Terms
				if ( ! empty ( $_POST['terms-field'] ) && empty ( $_POST['terms'] ) ) {
					wc_add_notice( __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ), 'error' );

					return;
				}

				// Update payment method
				if ( $order->needs_payment() ) {
					try {
						$payment_method_id = isset ( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : false;

						if ( ! $payment_method_id ) {
							throw new Exception( __( 'Invalid payment method.', 'woocommerce' ) );
						}

						$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
						$payment_method = $available_gateways[ $payment_method_id ] ?? false;

						if ( ! $payment_method ) {
							throw new Exception( __( 'Invalid payment method.', 'woocommerce' ) );
						}

						// Update meta
						$order->set_payment_method( $payment_method );
						$order->set_ecp_meta( '_payment_method', $payment_method_id );
						$order->set_ecp_meta( '_payment_method_title', $payment_method->get_title() );
						$order->save();

						// Validate
						$payment_method->validate_fields();

						// Process
						if ( 0 === wc_notice_count( 'error' ) ) {
							$result = $payment_method->process_payment( $order_id );

							// Redirect to success/confirmation/payment page
							if ( ! is_ajax() ) {
								wp_redirect( $result['redirect'] );
								exit;
							}
							wc_clear_cart_after_payment();
							wp_send_json( $result );
						}
					} catch ( Exception $e ) {
						wc_add_notice( $e->getMessage(), 'error' );
					}
				} else {
					// No payment was required for order.
					$order->payment_complete();
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}

				do_action( 'woocommerce_after_pay_action', $order );
			}
		}
	}
}
