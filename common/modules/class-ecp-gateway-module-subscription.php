<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Gateway_Ecommpay_Module_Subscription class.
 *
 * @class    WC_Gateway_Ecommpay_Module_Subscription
 * @version  2.0.0
 * @package  WC_Gateway_Ecommpay/Modules
 * @category Class
 */
class WC_Gateway_Ecommpay_Module_Subscription extends Ecp_Gateway_Registry {
	/**
	 * <h2>Runs every time a scheduled renewal of a subscription is required</h2>
	 *
	 * @param float $amount_to_charge
	 * @param WC_Order $renewal_order <p>Subscription renewal order.</p>
	 *
	 * @return void|null
	 */
	public function scheduled_subscription_payment( float $amount_to_charge, WC_Order $renewal_order ) {
		ecp_get_log()->info( 'Start a subscription renewal on a schedule.' );
		ecp_get_log()->debug( __( 'Renewal order ID:', 'woo-ecommpay' ), $renewal_order->get_id() );

		$renewal_order = new Ecp_Gateway_Order( $renewal_order );

		if ( ! $renewal_order->is_ecp() ) {
			ecp_get_log()->debug( __( 'There is no ECOMMPAY payment in the order. Interrupt.', 'woo-ecommpay' ) );

			return null;
		}

		if ( ! $renewal_order->needs_payment() ) {
			ecp_get_log()->debug( __( 'The order does not require payment. Interrupt.', 'woo-ecommpay' ) );

			return null;
		}

		// Get the subscription based on the renewal order
		$subscription = ecp_get_subscriptions_for_renewal_order( $renewal_order, true );
		ecp_get_log()->debug( __( 'Subscription ID:', 'woo-ecommpay' ), $subscription->get_id() );

		// Make new instance to properly get the transaction ID with built-in fallbacks.
		$subscription_order = new Ecp_Gateway_Order( $subscription->get_parent_id() );
		$renewal_order->set_payment_system( $subscription_order->get_payment_system() );
		$renewal_order->create_payment_id();

		// Get the transaction ID from the subscription
		$transaction_id = $subscription->get_recurring_id();

		ecp_get_log()->debug( __( 'Recurring ID:', 'woo-ecommpay' ), $transaction_id );

		try {
			// Create subscription instance
			$api = new Ecp_Gateway_API_Subscription();

			// Create a recurring payment with fixed amount
			$response = $api->recurring( $transaction_id, $renewal_order, $amount_to_charge );

			do_action(
				'ecp_scheduled_subscription_payment_after',
				$renewal_order,
				$response
			);
		} catch ( Ecp_Gateway_Exception $e ) {
			$e->write_to_logs();
		}
	}

	/**
	 * @param Ecp_Gateway_Order $order <p>Subscription renewal order.</p>
	 * @param Ecp_Gateway_Info_Response $response <p>Response information.</p>
	 *
	 * @return void
	 * @throws Ecp_Gateway_API_Exception
	 * @throws Ecp_Gateway_Logic_Exception
	 */
	public function after_create_recurring(
		Ecp_Gateway_Order $order,
		Ecp_Gateway_Info_Response $response
	) {
		if ( $response->get_status() !== 'success' ) {
			throw new Ecp_Gateway_API_Exception( $response->get_message() );
		}

		if ( ecommpay()->get_project_id() !== $response->get_project_id() ) {
			throw new Ecp_Gateway_Logic_Exception( __( 'Wrong project id.', 'woo-ecommpay' ) );
		}

		try {
			$order->set_transaction_id( $response->get_request_id() );
		} catch ( Exception $e ) {
			throw new Ecp_Gateway_Logic_Exception( __( 'Internal exception.', 'woo-ecommpay' ), 0, $e );
		}
	}

	/**
	 * Cancels a transaction when the subscription is cancelled
	 *
	 * @param WC_Subscription $subscription - WC_Order object
	 *
	 * @return void
	 */
	public function subscription_cancellation( WC_Subscription $subscription ) {
		if ( 'cancelled' !== $subscription->get_status() ) {
			return;
		}

		if ( ! ecp_is_subscription( $subscription ) ) {
			return;
		}

		$subscription = new Ecp_Gateway_Subscription( $subscription );

		if (
			! apply_filters(
				'woocommerce_ecommpay_allow_subscription_transaction_cancellation',
				true,
				$subscription,
				$this
			)
		) {
			return;
		}

		$order = new Ecp_Gateway_Order( $subscription );
		$api   = new Ecp_Gateway_API_Subscription();
		$api->cancel( $subscription->get_recurring_id(), $order );
	}

	/**
	 * Triggered when customers are changing payment method to ECOMMPAY.
	 *
	 * @param WC_Subscription $subscription
	 */
	public function on_subscription_payment_method_updated_to_ecommpay( $subscription ) {
		$order = new Ecp_Gateway_Order( $subscription->get_id() );
		$order->increase_payment_method_change_count();
	}

	/**
	 * Prevents the failed attempts count to be copied to renewal orders
	 *
	 * @param $order_meta_query
	 *
	 * @return string
	 */
	public function remove_failed_ecommpay_attempts_meta_query( $order_meta_query ) {
		$order_meta_query .= " AND `meta_key` NOT IN ('" . Ecp_Gateway_Order::META_FAILED_PAYMENT_COUNT . "')";
		$order_meta_query .= " AND `meta_key` NOT IN ('" . Ecp_Gateway_Order::META_TRANSACTION_ID . "')";

		return $order_meta_query;
	}

	/**
	 * Prevents the legacy transaction ID from being copied to renewal orders
	 *
	 * @param $order_meta_query
	 *
	 * @return string
	 */
	public function remove_legacy_transaction_id_meta_query( $order_meta_query ) {
		$order_meta_query .= " AND `meta_key` NOT IN ('" . Ecp_Gateway_Order::META_TRANSACTION_ID . "')";

		return $order_meta_query;
	}

	/**
	 * Declare gateway's metadata requirements in case of manual payment gateway changes performed by admins.
	 *
	 * @param array $payment_meta
	 * @param Ecp_Gateway_Subscription $subscription
	 *
	 * @return array
	 */
	public function woocommerce_subscription_payment_meta( $payment_meta, $subscription ) {
		$order                    = new Ecp_Gateway_Order( $subscription->get_id() );
		$payment_meta['ecommpay'] = [
			'post_meta' => [
				'_ecp_recurring_id' => [
					'value' => $order->get_payment_id(),
					'label' => __( 'ECOMMPAY Payment ID', 'woo-ecommpay' ),
				],
			],
		];

		return $payment_meta;
	}

	/**
	 * Check if the transaction ID actually exists as a subscription transaction in the manager.
	 * If not, an exception will be thrown resulting in a validation error.
	 *
	 * @param array $payment_meta
	 * @param WC_Subscription $subscription
	 */
	public function woocommerce_subscription_validate_payment_meta( $payment_meta, $subscription ) {
		if ( ! isset ( $payment_meta['post_meta'][ Ecp_Gateway_Order::META_TRANSACTION_ID ]['value'] ) ) {
			return;
		}

		$transaction_id = $payment_meta['post_meta'][ Ecp_Gateway_Order::META_TRANSACTION_ID ]['value'];
		$order          = ecp_get_order( $subscription->get_id() );

		// Validate only if the transaction ID has changed
		if ( $transaction_id === $order->get_payment_id() ) {
			return;
		}

		$transaction = new Ecp_Gateway_API_Subscription();
		$transaction->operation_status( $transaction_id );

		// If transaction could be found, add a note on the order for history and debugging reasons.
		$subscription->add_order_note(
			sprintf(
				__( 'ECOMMERCE Payment ID updated from #%d to #%d', 'woo-ecommpay' ),
				$order->get_payment_id(),
				$transaction_id
			),
			0,
			true
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function init() {
		// WooCommerce Subscriptions hooks/filters
		if ( ! ecp_subscription_is_active() ) {
			return;
		}

		// On renewal subscription
		add_filter(
			'wcs_renewal_order_meta_query',
			[ $this, 'remove_failed_ecommpay_attempts_meta_query' ]
		);

		// On renewal subscription
		add_filter(
			'wcs_renewal_order_meta_query',
			[ $this, 'remove_legacy_transaction_id_meta_query' ]
		);

		add_filter(
			'woocommerce_subscription_payment_meta',
			[ $this, 'woocommerce_subscription_payment_meta' ],
			10,
			2
		);

		add_action(
			'ecp_scheduled_subscription_payment_after',
			[ $this, 'after_create_recurring' ],
			10,
			2
		);
	}
}
