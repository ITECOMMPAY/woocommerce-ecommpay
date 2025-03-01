<?php

namespace common\modules;

use common\api\EcpGatewayAPIPayment;
use common\exceptions\EcpGatewayAPIException;
use common\exceptions\EcpGatewayException;
use common\exceptions\EcpGatewayLogicException;
use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayRegistry;
use common\includes\EcpGatewayOrder;
use common\includes\EcpGatewayPayment;
use common\includes\EcpGatewayRefund;
use common\models\EcpGatewayInfoCallback;
use DateTime;
use Exception;
use WC_Data_Exception;
use WC_Order;
use WC_Order_Refund;

defined( 'ABSPATH' ) || exit;

/**
 * @class    EcpModuleRefund
 * @version  2.0.0
 * @package  Ecp_Gateway/Modules
 * @category Class
 */
class EcpModuleRefund extends EcpGatewayRegistry {
	private const REFUND_DASHBOARD_REASON = 'The operation was performed via a Ecommpay dashboard';

	/**
	 * <h2>Check refund available before saving WC_Order_Refund.</h2>
	 * <p>This function running for all refunds. Additional verification required for order payment via ECOMMPAY.</p>
	 *
	 * @param WC_Order_Refund $refund
	 * @param array $args
	 *
	 * @return void
	 * @throws EcpGatewayException
	 * @throws EcpGatewayLogicException
	 * @throws EcpGatewayLogicException
	 */
	public function before_create( WC_Order_Refund $refund, array $args ): void {
		ecp_get_log()->debug( __( 'Run prepare refund process.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Refund ID:', 'woo-ecommpay' ), $refund->get_id() );
		ecp_get_log()->debug( __( 'Arguments:', 'woo-ecommpay' ), $args );

		if ( ! $args['refund_payment'] ) {
			ecp_get_log()->debug(
				__( 'Undefined option "refund_payment". We interrupt the preparation.', 'woo-ecommpay' )
			);

			return;
		}

		$refund = ecp_get_refund( $refund );
		$order  = $refund->get_order();

		//  Additional verification required for order payment via ECOMMPAY.
		if ( ! $order || ! $order->is_ecp() ) {
			ecp_get_log()->debug(
				__( 'The order was not paid via ECOMMPAY. We interrupt the preparation.', 'woo-ecommpay' )
			);

			return;
		}

		ecp_get_log()->debug( __( 'Parent order ID:', 'woo-ecommpay' ), $order->get_id() );

		try {
			// Check if the transaction can be refunded
			if ( ! in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
				throw new EcpGatewayLogicException(
					__( 'Inappropriate order status. It should be "processing" or "complete".', 'woo-ecommpay' )
				);
			}

			if ( ! $order->is_action_allowed( EcpGatewayOperationType::REFUND ) ) {
				throw new EcpGatewayLogicException(
					__( 'The payment status does not allow a refund.', 'woo-ecommpay' )
				);
			}

			$refund->create_payment_id();
			ecp_get_log()->info(
				__( 'Refund preparation complete. Refund payment ID:', 'woo-ecommerce' ),
				$refund->get_payment_id()
			);
		} catch ( EcpGatewayException $e ) {
			$e->write_to_logs();
			throw $e;
		}
	}

	/**
	 * <h2>Refund process.</h2>
	 */
	public function process( $order_id, $amount = null, $reason = '' ): bool {
		ecp_get_log()->debug( __( 'Running process refund', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order_id );
		ecp_get_log()->debug( __( 'Description:', 'woo-ecommpay' ), $reason );
		ecp_get_log()->debug( __( 'Amount:', 'woo-ecommpay' ), $amount );

		$order = ecp_get_order( $order_id );

		if ( ! $amount ) {
			$amount = $order->get_total() - $order->get_total_refunded();
		}

		try {
			// Create a payment instance and retrieve payment information
			$payment = $order->get_payment( true );

			if ( $payment->get_info()->get_sum()->get_amount() < ecp_price_multiply( $amount ) ) {
				throw new EcpGatewayLogicException(
					sprintf(
						__( 'Refund amount (%1$s) is greater than payment balance (%2$s).', 'woo-ecommpay' ),
						$amount . $order->get_currency(),
						$payment->get_info()->get_sum()->get_amount_float()
						. $payment->get_info()->get_sum()->get_currency()
					)
				);
			}

			// Find unprocessed refund: without ecommpay_request_id.
			$refund = $order->find_unprocessed_refund();

			// Set refund reason
			$refund->set_reason( $reason );

			// Create and run API request
			$api = new EcpGatewayAPIPayment();
			$payment = $api->refund( $refund, $order );

			if ( ! $payment ) {
				return false;
			}

			// If request is corrupted - throw Exception.
			if ( $payment->get_request_id() === '' ) {
				throw new EcpGatewayAPIException( __( 'Request was declined by ECOMMPAY gateway', 'woo-ecommpay' ) );
			}

			// Adding additional data to the refund object and save it.
			$refund->update_status( 'initial' );
			$refund->update_meta_data( '_transaction_id', $payment->get_request_id() );
			$refund->save();

			ecp_get_log()->debug( __( 'Refund ID:', 'woo-ecommpay' ), $refund->get_id() );
			ecp_get_log()->debug( __( 'Refund operation ID:', 'woo-ecommpay' ), $payment->get_payment_id() );
			ecp_get_log()->debug( __( 'Refund request ID:', 'woo-ecommpay' ), $payment->get_request_id() );

			$c = 0;
			// Wait callback handler execute
			while ( $c < 10 ) {
				ecp_get_log()->debug( 'Wait operation response...' );

				// Reload object from database
				$operation = $order->get_payment( true )->get_operation_by_request( $payment->get_request_id() );

				if ( $operation !== null ) {
					ecp_get_log()->debug( 'Operation ID:', $operation->get_id() );
					ecp_get_log()->debug( 'Last updated:', $operation->get_date()->format( 'D, d M Y H:i:s O' ) );
					ecp_get_log()->debug( 'Operation status:', $operation->get_status() );

					switch ( $operation->get_status() ) {
						// If refund is completed - return
						case EcpGatewayOperationStatus::SUCCESS:
							ecp_get_log()->debug( __( 'Refund process complete successful.', 'woo-ecommpay' ) );

							return true;
						// If refund is corrupted - throw Logic Exception.
						case EcpGatewayOperationStatus::DECLINE:
							throw new EcpGatewayLogicException(
								__( 'Refund is not completed. See more info in log.', 'woo-ecommpay' )
							);
					}
				}

				// Status refund is processing. Wait next...
				++ $c;
				sleep( 2 );
			}
		} catch ( EcpGatewayLogicException|EcpGatewayAPIException|WC_Data_Exception|Exception $e ) {
			ecp_get_log()->error( 'Refund error occurred: ' . $e->getMessage() );
			$e->write_to_logs();

			return false;
		}

		return true; // If we sent a request to refund, and waiting it to be processed.
	}

	public function is_available( WC_Order $order ): bool {
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order->get_id() );

		if ( ! $order instanceof EcpGatewayOrder ) {
			return false;
		}

		if ( ! $order->get_payment_id() ) {
			ecp_get_log()->notice(
				__( 'Not available ECOMMPAY payment identifier for order:', 'woo-ecommpay' ),
				$order->get_order_number()
			);

			return false;
		}

		return $order->get_total() > 0 && $order->is_action_allowed( 'refund' );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @throws Exception
	 */
	public function handle( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		ecp_get_log()->info( __( 'Handle refund callback.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order->get_id() );
		ecp_get_log()->debug( __( 'Payment ID:', 'woo-ecommpay' ), $order->get_payment_id() );

		$operation = $callback->get_operation();
		$refund = $order->find_refund_by_request_id( $operation->get_request_id() );

		if ( is_null( $refund ) ) {
			$refund = ecp_get_refund( wc_create_refund( array(
				'amount'         => $callback->get_operation_sum_initial_amount(),
				'reason'         => self::REFUND_DASHBOARD_REASON,
				'order_id'       => $order->get_id(),
				'refund_payment' => false
			) ) );
			$refund->update_status( 'initial' );
			$refund->update_meta_data( '_transaction_id', $callback->get_operation()->get_request_id() );
			$refund->save();
		}

		switch ( $callback->get_payment()->get_status() ) {
			case EcpGatewayPaymentStatus::REVERSED:
			case EcpGatewayPaymentStatus::REFUNDED:
			case EcpGatewayPaymentStatus::PARTIALLY_REVERSED:
			case EcpGatewayPaymentStatus::PARTIALLY_REFUNDED:
				$this->completed( $callback, $order, $refund );
				break;
			case EcpGatewayPaymentStatus::PROCESSING:
			case EcpGatewayPaymentStatus::EXTERNAL_PROCESSING:
				break;
			default:
				$this->failed( $callback, $order, $refund );
				break;
		}
	}

	/**
	 * <h2>Write data on completed refund.</h2>
	 *
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder|null $order
	 * @param EcpGatewayRefund|null $refund
	 *
	 * @return void
	 */
	private function completed( EcpGatewayInfoCallback $callback, ?EcpGatewayOrder $order, ?EcpGatewayRefund $refund ): void {
		ecp_get_log()->debug( __( 'Callback info:', 'woo-commerce' ), json_encode( $callback ) );

		if ( ! is_null( $refund ) ) {
			$this->complete_update_refund( $refund );
		}

		if ( ! is_null( $order ) ) {
			$this->complete_update_order( $order, $callback );
		}

		if ( ! is_null( $refund ) && ! is_null( $order ) ) {
			$order->add_order_note(
				sprintf(
				/* translators: 1: Refunded sum 2: Payment balance */
					_x( 'Refunded %1$s. Payment balance: %2$s', 'Refund note', 'woo-ecommpay' ),
					$refund->get_formatted_refund_amount(),
					$callback->get_payment()->get_sum()->get_formatted()
				)
			);
		}
	}

	private function complete_update_refund( EcpGatewayRefund $refund ) {
		ecp_get_log()->debug( __( 'Start update Refund ID', 'woo-commerce' ), $refund->get_id() );
		$comment = sprintf(
		/* translators: %s: operation datetime */
			__( 'Successfully processed via ECOMMPAY at %s.', 'woo-ecommpay' ),
			( new DateTime() )->format( 'd.m.Y H:i:s' )
		);

		if ( $refund->get_reason() ) {
			/* translators: %s: refund reason */
			$comment .= sprintf(
				_x( 'Reason: %s', 'Refund note', 'woo-ecommpay' ),
				$refund->get_reason()
			);
		}

		$refund->update_status( 'completed', $comment );
		$refund->save();

		ecp_get_log()->info( __( 'Refund update completed:', 'woo-commerce' ), $refund->get_id() );
	}

	private function complete_update_order( EcpGatewayOrder $order, EcpGatewayInfoCallback $callback ) {
		ecp_get_log()->debug( __( 'Start update Order ID:', 'woo-ecommpay' ), $order->get_id() );

		$payment = $order->get_payment();
		if ( ! is_null( $payment ) ) {
			$this->complete_update_payment( $payment, $callback );
		}

		$order->set_ecp_payment_status( $callback->get_payment()->get_status() );

		ecp_get_log()->info( __( 'Order update completed:', 'woo-ecommpay' ), $order->get_id() );
	}

	private function complete_update_payment( EcpGatewayPayment $payment, EcpGatewayInfoCallback $callback ) {
		ecp_get_log()->debug( __( 'Start update Payment ID:', 'woo-ecommpay' ), $payment->get_id() );

		$payment->add_operation( $callback->get_operation() );
		$payment->set_info( $callback->get_payment() );
		$payment->save();

		ecp_get_log()->info( __( 'Payment update completed:', 'woo-ecommpay' ), $payment->get_id() );
	}

	/**
	 * <h2>Write data on failed refund.</h2>
	 *
	 * @param EcpGatewayInfoCallback $info
	 * @param EcpGatewayRefund $refund
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 */
	private function failed( EcpGatewayInfoCallback $info, EcpGatewayOrder $order, EcpGatewayRefund $refund ): void {
		ecp_get_log()->critical( __( 'Write data on completed refund', 'woo-commerce' ) );
		ecp_get_log()->critical( __( 'Cannot refund order:', 'woo-commerce' ), $order->get_id() );
		ecp_get_log()->critical( __( 'Failed refund ID:', 'woo-commerce' ), $refund->get_id() );

		foreach ( $info->get_errors() as $error ) {
			ecp_get_log()->critical(
				sprintf( 'ERROR [%d]: %s', $error->get_code(), $error->get_message() )
			);
		}

		$refund->update_status( 'failed' );
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	protected function init(): void {
		// register hooks for refund operation
		add_action( 'woocommerce_create_refund', [ $this, 'before_create' ], 10, 2 );
	}
}
