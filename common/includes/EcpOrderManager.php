<?php

namespace common\includes;

use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\WCOrderStatus;
use common\models\EcpGatewayInfoCallback;
use WC_Data_Exception;

class EcpOrderManager {

	private EcpOrderNotesFormer $ecp_order_notes_former;

	/**
	 * @param EcpOrderNotesFormer $ecp_order_notes_former
	 */
	public function __construct( EcpOrderNotesFormer $ecp_order_notes_former ) {
		$this->ecp_order_notes_former = $ecp_order_notes_former;
	}


	/**
	 * Decline order
	 *
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function decline_order( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		ecp_get_log()->debug( __( 'Run failed process.', 'woo-ecommpay' ), $order->get_id() );
		$order->set_transaction_id( $callback->get_operation()->get_request_id() );
		$order->update_status( WCOrderStatus::FAILED );
		$order->increase_failed_ecommpay_payment_count();
		$this->append_order_errors( $callback, $order );
		ecp_debug( ecpTr( 'Failed process completed.' ), $order->get_id() );
	}

	public function append_order_errors( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		if ( ! empty( $callback->get_errors() ) ) {
			$errors_text = '';
			foreach ( $callback->get_errors() as $error ) {
				$errors_text .= sprintf(
					'An error with code %s (%s) occurred. ',
					$error['code'], $error['message']
				);
			}
			$order->add_order_note( $errors_text
			                        . 'You can refer <a href="https://developers.ecommpay.com/en/en_platform_payment_info_codes.html" target="_blank">to the ECOMMPAY article</a> for more information.' );
		}
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return bool
	 */
	public function add_order_note( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): bool {
		if ( $callback->get_operation()->get_status() !== EcpGatewayOperationStatus::SUCCESS ) {
			$this->add_decline_order_note( $callback, $order, $callback->get_operation()->get_type() );

			return true;
		}

		$callback_amount   = $callback->get_payment_amount_minor();
		$callback_currency = $callback->get_payment_currency();
		$total_amount      = $order->get_total_minor();
		$sum_less          = $callback->get_payment_amount_minor() < $total_amount;
		$sum_equal         = $callback->get_payment_amount_minor() === $total_amount;

		switch ( $callback->get_operation()->get_type() ) {
			case EcpGatewayOperationType::CAPTURE:
				$order->add_order_note( $sum_equal
					? sprintf(
						'The payment of %s was captured%s.',
						$order->get_formatted_order_total(),
						$this->ecp_order_notes_former->get_dashboard_append_text( $callback, $order )
					)
					: sprintf(
						'The payment of %s was captured%s. The rest is returned to the payer.%s',
						$callback->get_payment()->get_sum()->get_formatted(),
						$this->ecp_order_notes_former->get_dashboard_append_text( $callback, $order ),
						$this->ecp_order_notes_former->get_dashboard_append_text_recommendation( $callback, $order )
					) );
				$this->complete_order( $callback, $order, $sum_less );
				break;
			case EcpGatewayOperationType::CANCEL:
				if ( $sum_equal ) {
					$order->add_order_note( sprintf(
						'Payment authorization of %s was canceled%s.',
						$order->get_formatted_order_total(),
						$this->ecp_order_notes_former->get_dashboard_append_text( $callback, $order )
					) );
					$this->cancel_order( $order );
				} else if ( $sum_less ) {
					$remaining_amount = ecp_price_multiplied_to_float( $total_amount - $callback_amount, $callback_currency );
					$order->add_order_note( sprintf(
						'Payment authorization of %s  was canceled%s. The rest (%s %s) can be either captured or canceled. %s',
						$order->get_formatted_order_total(),
						$this->ecp_order_notes_former->get_dashboard_append_text( $callback, $order ),
						$remaining_amount,
						$callback_currency,
						$this->ecp_order_notes_former->get_dashboard_append_text_recommendation( $callback, $order )
					) );
				}
				break;
			default:
				ecp_error( 'Unknown operation type: ' . $callback->get_operation()->get_type() );
				break;
		}

		return true;
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 * @param string $operation
	 *
	 * @return void
	 */
	public function add_decline_order_note( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order, string $operation ): void {
		$order->add_order_note( sprintf(
			'%s operation%s was declined: %s',
			ucfirst( $operation ),
			$this->ecp_order_notes_former->get_dashboard_append_text( $callback, $order ),
			$callback->get_operation()->get_message()
		) );
	}

	/**
	 * Complete order
	 *
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 * @param bool $skip_amount_check
	 *
	 * @return void
	 */
	public function complete_order( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order, bool $skip_amount_check = false ): void {
		$order_currency   = $order->get_currency_uppercase();
		$payment_currency = $callback->get_payment_currency();

		$is_amount_equal   = $order->get_total_minor() === $callback->get_payment_amount_minor();
		$is_currency_equal = $order_currency === $payment_currency;

		ecp_get_log()->debug( __( 'Run success process.', 'woo-ecommpay' ), $order->get_id() );
		$order->payment_complete( $callback->get_operation()->get_request_id() );
		WC()->cart->empty_cart();
		ecp_get_log()->debug( __( 'Success process completed.', 'woo-ecommpay' ), $order->get_id() );

		if ( ! $skip_amount_check && ( ! $is_amount_equal || ! $is_currency_equal ) ) {
			$message = sprintf(
				'The payment amount does not match the order amount. The order has %s %s. The payment has %s %s',
				$order->get_total(), $order_currency, $callback->get_payment_amount(), $payment_currency
			);
			$order->add_order_note( __( $message, 'woo-ecommpay' ) );
		}
	}

	/**
	 * Cancel order
	 *
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 */
	public function cancel_order( EcpGatewayOrder $order ): void {
		ecp_get_log()->debug( __( 'Run cancel process.', 'woo-ecommpay' ), $order->get_id() );
		$order->update_status( WCOrderStatus::CANCELLED );
		$order->set_ecp_payment_status( EcpGatewayPaymentStatus::CANCELLED );
		ecp_get_log()->debug( __( 'Cancel process completed.', 'woo-ecommpay' ), $order->get_id() );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function hold_order( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		ecp_get_log()->debug( __( 'Run awaiting confirmation process.', 'woo-ecommpay' ), $order->get_id() );
		$order->set_transaction_id( $callback->get_operation()->get_request_id() );
		$order->update_status( WCOrderStatus::ON_HOLD );
		ecp_get_log()->debug( __( 'Awaiting confirmation process completed.', 'woo-ecommpay' ), $order->get_id() );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 */
	public function set_payment_systems( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		$transaction_order_id = $order->get_transaction_order_id( 'view', $callback->get_operation()->get_type() );

		if ( empty( $transaction_order_id ) ) {
			if ( ! empty( $order->get_payment() ) ) {
				$order->set_operation_status( $callback->get_operation()->get_status(), $callback->get_operation()->get_type() );
			}
		} else {
			$order->set_operation_status( $callback->get_operation()->get_status(), $callback->get_operation()->get_type() );
		}

		$order->set_payment_system( $callback->get_payment()->get_method() );
	}

	public function log_order_data( EcpGatewayOrder $order ) {
		ecp_debug( ecpTr( 'Order info: ' ), [
			'ID'             => $order->get_id(),
			'Payment ID'     => $order->get_payment_id(),
			'Transaction ID' => $order->get_ecp_transaction_id()
		] );
	}

	/**
	 * <h2>Update payment data.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Payment order.</p>
	 * @param EcpGatewayInfoCallback $callback <p>Callback information.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function update_payment( EcpGatewayOrder $order, EcpGatewayInfoCallback $callback ): void {
		$payment = $order->get_payment();
		$payment->add_operation( $callback->get_operation() );
		$payment->set_info( $callback->get_payment() );
		$payment->save();
	}

	/**
	 * <h2>Sets to subscriptions recurring information.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Parent payment order.</p>
	 * @param EcpGatewayInfoCallback $callback <p>Callback information.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function update_subscription( EcpGatewayOrder $order, EcpGatewayInfoCallback $callback ): void {
		if ( ! $order->contains_subscription() ) {
			return;
		}

		if ( ! $callback->try_get_recurring( $recurring ) ) {
			ecp_get_log()->warning(
				__( 'No recurring information found in callback data. The Subscription cannot be renewed.', 'woo-ecommpay' )
			);

			return;
		}

		ecp_get_log()->debug( __( 'Order has subscriptions', 'woo-ecommpay' ) );
		$subscriptions = $order->get_subscriptions();

		if ( $subscriptions === null ) {
			return;
		}

		ecp_get_log()->debug( __( 'Recurring ID:', 'woo-ecommpay' ), $recurring->get_id() );

		foreach ( $subscriptions as $subscription ) {
			ecp_get_log()->debug( __( 'Subscription ID:', 'woo-ecommpay' ), $subscription->get_id() );
			$subscription->set_recurring_id( $callback->get_recurring()->get_id() );
			$subscription->save();
		}
	}
}
