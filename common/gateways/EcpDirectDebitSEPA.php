<?php

namespace common\gateways;

use common\helpers\EcpGatewayPaymentMethods;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use common\settings\EcpSettingsDirectDebitSEPA;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    EcpDirectDebitSEPA
 * @version  3.4.3
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class EcpDirectDebitSEPA extends EcpGateway {
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
		$this->id = EcpSettingsDirectDebitSEPA::ID;
		$this->method_title       = __( 'ECOMMPAY Direct debit SEPA', 'woo-ecommpay' );
		$this->method_description = __( 'Accept payments via Direct Debit Sepa.', 'woo-ecommpay' );

		parent::__construct();

		$this->init_subscription();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.4.3
	 */
	public function apply_payment_args( $values, $order ): array {
		$amount = ecp_price_multiply( $order->get_total(), $order->get_currency() );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, $values, $order );
		// Setup Payment Page Operation Mode
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_MODE, $values, $amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY );
		// Setup Payment Page Force Mode
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, self::PAYMENT_METHOD );
		// Setup Recurring (Subscriptions)
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_RECURRING, $values, $order );

		return parent::apply_payment_args( $values, $order );
	}

	public function get_refund_endpoint( $order ): string {
		return EcpGatewayPaymentMethods::get_code( $order );
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
		return EcpModuleRefund::get_instance()->process( $order_id, $amount, $reason );
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

		return EcpModuleRefund::get_instance()->is_available( $order );
	}

	public function is_available(): bool {
		return parent::is_available() && $this->is_gateway_with_subscription_only();
	}
}
