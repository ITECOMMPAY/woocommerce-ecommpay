<?php

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Subscription ECOMMPAY Gate2025 API.</h2>
 *
 * @class    Ecp_Gateway_API_Subscription
 * @since    2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class Ecp_Gateway_API_Subscription extends Ecp_Gateway_API {
	/**
	 * <h2>Subscription Gate2025 API constructor.</h2>
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct( 'payment' );
	}

	/**
	 * <h2>Sends data and return created subscription transaction data.</h2>
	 *
	 * @param int $subscription_id <p>Subscription identifier.</p>
	 * @param Ecp_Gateway_Order $order <p>Renew subscription order.</p>
	 * @param int $amount <p>Amount of renewal subscription.</p>
	 *
	 * @return Ecp_Gateway_Info_Response
	 * @throws Ecp_Gateway_API_Exception <p>
	 * If subscriptions is not enabled or payment_method not supported subscriptions.
	 * </p>
	 */
	public function recurring( $subscription_id, Ecp_Gateway_Order $order, $amount = null ) {
		ecp_get_log()->info( __( 'Run recurring API process.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Subscription ID:', 'woo-ecommpay' ), $subscription_id );
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order->get_id() );
		ecp_get_log()->debug( __( 'Payment status:', 'woo-ecommpay' ), $order->get_ecp_status() );

		if ( ! class_exists( 'WC_Subscriptions_Order' ) ) {
			ecp_get_log()->alert(
				__(
					'Woocommerce Subscription plugin is not available. Interrupt process.',
					'woo-ecommpay'
				)
			);
			throw new Ecp_Gateway_API_Exception( __( 'Woocommerce Subscription plugin is not available.', 'woo-ecommpay' ) );
		}

		// Check if a custom amount has been set
		if ( $amount === null ) {
			// No custom amount set. Default to the order total
			$amount = WC_Subscriptions_Order::get_recurring_total( $order );
		}

		ecp_get_log()->debug( __( 'Amount:', 'woo-ecommpay' ), $amount );

		$payment_method = Ecp_Gateway_Payment_Methods::get_code( $order->get_payment_system() );

		if ( ! $payment_method ) {
			throw new Ecp_Gateway_API_Exception( __( 'Payment method is not supported subscription.', 'woo-ecommpay' ) );
		}

		ecp_get_log()->debug( __( 'Payment method:', 'woo-ecommpay' ), $payment_method );

		// Create form data
		$data = apply_filters( 'ecp_api_recurring_form_data', $subscription_id, $order );

		// Run request
		$response = new Ecp_Gateway_Info_Response(
			$this->post(
				sprintf( '%s/%s', $payment_method, 'recurring' ),
				apply_filters( 'ecp_append_signature', $data )
			)
		);

		ecp_get_log()->info( __( 'Recurring process completed.', 'woo-ecommpay' ) );

		return $response;
	}

	/**
	 * <h2>Sends a request and returns the information about the transaction.</h2>
	 *
	 * @param string $request_id <p>Request identifier.</p>
	 *
	 * @return Ecp_Gateway_Info_Response <p>Transaction information data.</p>
	 * @since 2.0.0
	 */
	public function operation_status( $request_id ) {
		ecp_get_log()->info( __( 'Run check transaction status API process.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Request ID:', 'woo-ecommpay' ), $request_id );

		// Create form data
		$data = apply_filters( 'ecp_create_general_data', $request_id );

		// Run request
		$response = new Ecp_Gateway_Info_Response(
			$this->post(
				'status/request',
				apply_filters( 'ecp_append_signature', $data )
			)
		);

		ecp_get_log()->info( __( 'Check transaction status process completed.', 'woo-ecommpay' ) );

		return $response;
	}

	/**
	 * <h2>Sends data and return subscription cancellation data.</h2>
	 *
	 * @param int $subscription_id <p>Recurring identifier.</p>
	 * @param Ecp_Gateway_Order $order <p>Cancellation order.</p>
	 *
	 * @return bool
	 * @since 2.0.0
	 */
	public function cancel( $subscription_id, Ecp_Gateway_Order $order ) {
		ecp_get_log()->info( __( 'Run recurring cancel API process.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Subscription ID:', 'woo-ecommpay' ), $subscription_id );
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order->get_id() );
		ecp_get_log()->debug( __( 'Payment status:', 'woo-ecommpay' ), $order->get_ecp_status() );

		return true;
	}

	/**
	 * <h2>Returns the underlying form data for the recurring request.</h2>
	 *
	 * @param int $subscription_id <p>ECOMMPAY recurring identifier.</p>
	 * @param Ecp_Gateway_Order $order <p>Renewal subscription order.</p>
	 *
	 * @return array[] <p>Form data for the recurring request.</p>
	 * @since 2.0.0
	 */
	final public function create_recurring_request_form_data( $subscription_id, Ecp_Gateway_Order $order ) {
		ecp_get_log()->info( __( 'Create form data for recurring request.', 'woo-ecommpay' ) );

		$data = $this->create_general_section(
			apply_filters(
				'ecp_append_merchant_callback_url',
				apply_filters( 'ecp_create_general_data', $order )
			)
		);
		$data = apply_filters( 'ecp_api_append_recurring_data', $data, $subscription_id );
		$data = apply_filters( 'ecp_append_payment_section', $data, $order );

		$ip_address       = $order->get_ecp_meta( '_customer_ip_address' );
		$data['customer'] = [
			'id'         => (string) $order->get_customer_id(),
			"ip_address" => $ip_address ?: wc_get_var( $_SERVER['REMOTE_ADDR'] )
		];

		return apply_filters( 'ecp_append_interface_type', $data );
	}

	/**
	 * <h2>Returns the underlying form data for the recurring cancel request.</h2>
	 *
	 * @param int $subscription_id <p>ECOMMPAY recurring identifier.</p>
	 * @param Ecp_Gateway_Order $order <p>Renewal subscription order.</p>
	 *
	 * @return array <p>Form data for the cancel recurring request.</p>
	 * @since 2.0.0
	 */
	final public function create_cancel_request_form_data( $subscription_id, Ecp_Gateway_Order $order ) {
		ecp_get_log()->info( __( 'Create form data for recurring cancel request.', 'woo-ecommpay' ) );

		return apply_filters(
			'ecp_append_interface_type',
			$this->create_general_section(
				apply_filters(
					'ecp_api_append_recurring_data',
					apply_filters(
						'ecp_append_merchant_callback_url',
						apply_filters( 'ecp_create_general_data', $order )
					),
					$subscription_id
				)
			)
		);
	}

	/**
	 * <h2>Append recurring information to the form data.</h2>
	 *
	 * @param array $data <p>Form data as array.</p>
	 * @param string $subscription_id <p>Identifier of the subscription.</p>
	 *
	 * @return array <p>Form data with recurring information.</p>
	 * @since 3.0.0
	 */
	public function append_recurring_data( $data, $subscription_id ) {
		$data['recurring']    = [ 'id' => $subscription_id ];
		$data['recurring_id'] = $subscription_id;

		return $data;
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	protected function hooks() {
		parent::hooks();

		add_filter( 'ecp_api_recurring_form_data', [ $this, 'create_recurring_request_form_data' ], 10, 2 );
		add_filter( 'ecp_api_append_recurring_data', [ $this, 'append_recurring_data' ], 10, 2 );
		add_filter( 'ecp_api_recurring_cancel_form_data', [ $this, 'create_cancel_request_form_data' ], 10, 2 );
	}
}
