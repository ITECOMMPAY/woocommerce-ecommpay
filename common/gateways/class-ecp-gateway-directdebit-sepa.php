<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    Ecp_Gateway_DirectDebit_SEPA
 * @version  3.4.3
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class Ecp_Gateway_DirectDebit_SEPA extends Ecp_Gateway {
	protected const PAYMENT_METHOD = 'directdebit-sepa';

	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.4.3
	 */
	public $supports = [
		self::SUPPORT_SUBSCRIPTIONS,
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_SUBSCRIPTION_CANCELLATION,
		self::SUPPORT_SUBSCRIPTION_REACTIVATION,
		self::SUPPORT_SUBSCRIPTION_SUSPENSION,
		self::SUPPORT_SUBSCRIPTION_AMOUNT_CHANGES,
		self::SUPPORT_SUBSCRIPTION_DATE_CHANGES,
		self::SUPPORT_REFUNDS,
		self::SUPPORT_MULTIPLE_SUBSCRIPTIONS,
	];


	/**
	 * <h2>ECOMMPAY Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id                 = Ecp_Gateway_Settings_DirectDebit_SEPA::ID;
		$this->method_title       = __( 'ECOMMPAY Direct debit SEPA', 'woo-ecommpay' );
		$this->method_description = __( 'Accept payments via Direct Debit Sepa.', 'woo-ecommpay' );
		$this->has_fields         = false;
		$this->title              = $this->get_option( Ecp_Gateway_Settings::OPTION_TITLE );
		$this->order_button_text  = $this->get_option( Ecp_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT );
		$this->enabled            = $this->get_option( Ecp_Gateway_Settings::OPTION_ENABLED );
		$this->icon               = $this->get_icon();

		if ( $this->is_enabled( Ecp_Gateway_Settings::OPTION_SHOW_DESCRIPTION ) ) {
			$this->description = $this->get_option( Ecp_Gateway_Settings::OPTION_DESCRIPTION );
		}

		parent::__construct();

		$this->init_subscription();
	}

	private function init_subscription() {
		// WooCommerce Subscriptions hooks/filters
		if ( ! ecp_subscription_is_active() ) {
			return;
		}

		// On scheduled subscription
		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			[ WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'scheduled_subscription_payment' ],
			10,
			2
		);

		// On cancelled subscription
		add_action(
			'woocommerce_subscription_cancelled_' . $this->id,
			[ WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'subscription_cancellation' ]
		);

		// On updated subscription
		add_action(
			'woocommerce_subscription_payment_method_updated_to_' . $this->id,
			[
				WC_Gateway_Ecommpay_Module_Subscription::get_instance(),
				'on_subscription_payment_method_updated_to_ecommpay'
			],
			10,
			2
		);

		add_action(
			'woocommerce_subscription_validate_payment_meta_' . $this->id,
			[
				WC_Gateway_Ecommpay_Module_Subscription::get_instance(),
				'woocommerce_subscription_validate_payment_meta'
			],
			10,
			2
		);
	}

	/**
	 * <h2>Returns a new instance of self, if it does not already exist.</h2>
	 *
	 * @return static
	 * @since 3.4.3
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.4.3
	 */
	public function apply_payment_args( $values, $order ): array {
		$amount = ecp_price_multiply( $order->get_total(), $order->get_currency() );

		$values = apply_filters( 'ecp_append_card_operation_type', $values, $order );
		// Setup Payment Page Operation Mode
		$values = apply_filters( 'ecp_append_operation_mode', $values, $amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY  );
		// Setup Payment Page Force Mode
		$values = apply_filters( 'ecp_append_force_mode', $values, self::PAYMENT_METHOD );
		// Setup Recurring (Subscriptions)
		$values = apply_filters( 'ecp_append_recurring', $values, $order );

		return parent::apply_payment_args( $values, $order );
	}

	public function get_refund_endpoint( $order ): string {
		return Ecp_Gateway_Payment_Methods::get_code( $order );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 3.4.3
	 */
	public function process_payment( $order_id ): array {
		$order            = ecp_get_order( $order_id );
		$options          = ecp_payment_page()->get_request_url( $order, $this );
		$payment_page_url = ecp_payment_page()->get_url() . '/payment?' . http_build_query( $options );

		return [
			'result' => self::PROCESS_RESULT_SUCCESS,
			'redirect' => $payment_page_url,
			'order_id' => $order_id,
		];
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @since 3.4.3
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		return Ecp_Gateway_Module_Refund::get_instance()->process( $order_id, $amount, $reason );
	}

	/**
	 * @inheritDoc
	 * <p>If false, the automatic refund button is hidden in the UI.</p>
	 *
	 * @param WC_Order $order <p>Order object.</p>
	 *
	 * @override
	 * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
	 * @since 3.4.3
	 */
	public function can_refund_order( $order ): bool {
		if ( ! $order ) {
			ecp_get_log()->debug(
				_x( 'Undefined argument order. Hide refund via ECOMMPAY button.', 'Log information', 'woo-ecommpay' )
			);

			return false;
		}

		$order = ecp_get_order( $order );

		// Check if there is a ECOMMPAY payment
		if ( ! $order->is_ecp() ) {
			return false;
		}

		return Ecp_Gateway_Module_Refund::get_instance()->is_available( $order );
	}

	public function is_available(): bool {
		return $this->is_gateway_with_subscription_only();
	}
}
