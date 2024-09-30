<?php

use Automattic\WooCommerce\Admin\Overrides\OrderRefund;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Refund
 *
 * Extends Woocommerce refund for easy access to internal data.
 *
 * @class    Ecp_Gateway_Refund
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Refund extends OrderRefund {
	use ECP_Gateway_Order_Extension;

	/**
	 * <h2>Creates and returns a new ECOMMPAY refund identifier.</h2>
	 *
	 * @return string
	 * @since 2.0.0
	 * @uses Ecp_Gateway_Refund::set_is_test()
	 * @uses Ecp_Gateway_Refund::get_id()
	 * @uses Ecp_Gateway_Refund::set_payment_id()
	 * @uses Ecp_Gateway_Refund::set_ecp_status()
	 * @uses Ecp_Gateway_Order::get_refund_attempts_count()
	 * @uses Ecp_Gateway_Order::increase_refund_attempts_count()
	 */
	public function create_payment_id(): string {
		$order     = ecp_get_order( $this->get_parent_id() );

		$id = $this->get_id() . '_' . ( $order->get_refund_attempts_count() + 1 );
		$order->increase_refund_attempts_count();
		$order->save();

		$this->set_payment_id( $id );
		$this->set_ecp_status( 'initial' );
		$this->save();

		ecp_get_log()->debug( __( 'New refund payment identifier created:', 'woo-ecommpay' ), $id );

		return $id;
	}

	/**
	 * <h2>Set refund status from the ECOMMPAY payment platform.</h2>
	 *
	 * @param string $status <p>Status to change the refund to.</p>
	 *
	 * @return array Details of change
	 * @since 2.0.0
	 */
	public function set_ecp_status( $status, $note = '' ): array {
		ecp_get_log()->debug( __( 'Transition refund ECOMMPAY status', 'woo-ecommpay' ) );

		$old = $this->get_ecp_status();

		if ( $status === $old ) {
			ecp_get_log()->notice( __( 'Refund statuses from and to identical. Skip process.' ) );
			ecp_get_log()->debug( __( 'Refund status:', 'woo-ecommpay' ), $old );

			return [ 'from' => $old, 'to' => $status ];
		}

		if ( $old !== '' && ! in_array( $old, $this->get_valid_ecp_statuses() ) ) {
			ecp_get_log()->warning( sprintf( __( 'Refund form status "%s" is not supported', 'woo-ecommpay' ), $old ) );
			$old = 'initial';
		}

		if ( ! in_array( $status, $this->get_valid_ecp_statuses() ) ) {
			ecp_get_log()->warning( sprintf( __( 'Refund to status "%s" is not supported', 'woo-ecommpay' ), $old ) );
			$old = 'initial';
		}

		$this->set_ecp_meta( '_refund_status', $status );
		$transition = [ 'from' => $old, 'to' => $status ];

		if ( $note !== '' ) {
			$this->add_comment( $note );
		}

		ecp_get_log()->info( sprintf( __( 'Refund status transitions: [%s] => [%s]', 'woo-ecommpay' ), $old, $status ) );

		return $transition;
	}

	/**
	 * <h2>Returns the refund status from the ECOMMPAY payment platform.</h2>
	 *
	 * @param string $context <p>What the value is for. Valid values are view and edit.</p>
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function get_ecp_status( string $context = 'view' ): string {
		return $this->get_ecp_meta( '_refund_status', true, $context );
	}

	/**
	 * <h2>Returns all valid statuses for this refund.</h2>
	 *
	 * @return array <p>Internal status keys.</p>
	 * @since 2.0.0
	 */
	private function get_valid_ecp_statuses(): array {
		return [ 'initial', 'completed', 'failed' ];
	}

	/**
	 * <h2>Adds the comment in current refund reason.</h2>
	 *
	 * @param string $comment [optional] <p>Comment. Default: blank string.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @uses Ecp_Gateway_Refund::get_reason()
	 * @uses Ecp_Gateway_Refund::set_reason()
	 * @uses Ecp_Gateway_Refund::get_id()
	 * @uses Ecp_Gateway_Refund::get_parent_id()
	 * @uses Ecp_Gateway_Refund::get_order()
	 */
	private function add_comment( string $comment = '' ) {
		// Return if the comment is blank
		if ( $comment === '' ) {
			return;
		}

		ecp_get_log()->debug( __( 'Add comment into refund', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Comment:', 'woo-ecommpay' ), $comment );
		$reason = $this->get_reason();

		if ( empty ( $reason ) ) {
			$reason = $comment;
		} else {
			$reason .= ' | ' . $comment;
		}

		try {
			$this->set_reason( $reason );

			$refund_request_comment_id = $this->get_ecp_meta( '_refund_request_comment_id' );

			if ( $refund_request_comment_id ) {
				$this->get_order()->append_order_comment( $comment, $refund_request_comment_id );
			}

			ecp_get_log()->info( __( 'Comment added to refund', 'woo-ecommpay' ) );
		} catch ( Exception $e ) {
			ecp_get_log()->error( __( '', 'woo-ecommpay' ) );
			ecp_get_log()->error( $e->getMessage() );
		}
	}

	/**
	 * <h2>Returns the parent order from the refund.</h2>
	 *
	 * @param int $id [optional] <i>** Unusable **</i>
	 *
	 * @return ?Ecp_Gateway_Order Parent order if exists or <b>NULL</b> otherwise.
	 * @since 2.0.0
	 * @uses ecp_get_order()
	 */
	public function get_order( $id = 0 ): ?Ecp_Gateway_Order {
		return ecp_get_order( $this->get_parent_id() );
	}

	/**
	 * <h2>Updates status of refund immediately.</h2>
	 *
	 * @param string $new_status <p>Status to change the refund to.</p>
	 * @param string $note [optional] <b>Note to add. Default: blank string.</p>
	 *
	 * @return bool <b>TRUE</b> on status changed or <b>FALSE</b> otherwise.
	 * @since 2.0.0
	 * @uses Ecp_Gateway_Refund::set_ecp_status()
	 * @uses Ecp_Gateway_Refund::add_comment()
	 */
	public function update_status( string $new_status, string $note = '' ): bool {
		ecp_get_log()->debug( __( 'Update refund status.', 'woo-ecommpay' ) );

		if ( ! $this->get_id() ) {
			ecp_get_log()->warning( __( 'Undefined identifier for refund object.', 'woo-ecommpay' ) );

			return false;
		}

		try {
			$this->set_ecp_status( $new_status, $note );
			$this->save();
		} catch ( Exception $e ) {
			ecp_get_log()->error(
				sprintf( __( 'Error updating status for refund #%d', 'woo-ecommpay' ), $this->get_id() )
			);
			ecp_get_log()->error( $e->getMessage() );

			return false;
		}

		ecp_get_log()->debug( __( 'Refund payment status updated.', 'woo-ecommpay' ) );

		return true;
	}
}
