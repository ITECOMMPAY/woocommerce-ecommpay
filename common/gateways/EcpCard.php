<?php

namespace common\gateways;

use common\exceptions\EcpGatewayErrorException;
use common\helpers\EcpGatewayPaymentMethods;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use common\settings\EcpSettings;
use common\settings\EcpSettingsCard;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  3.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class EcpCard extends EcpGateway {
	protected const PAYMENT_METHOD = 'card';
	/**
	 * @override
	 * @var string[]
	 * @since 1.0.0
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

		$this->id = EcpSettingsCard::ID;
		$this->method_title       = __( 'ECOMMPAY Cards', 'woo-ecommpay' );
		$this->method_description = __( 'Accept card payments via ECOMMPAY.', 'woo-ecommpay' );

		parent::__construct();

		if ( $this->get_option( EcpSettings::OPTION_MODE ) == EcpSettings::MODE_EMBEDDED ) {
			$this->description = '<div id="ecommpay-loader-embedded"></div><div id="ecommpay-iframe-embedded"></div>';
		}

		$this->init_subscription();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( $values, $order ): array {
		$amount       = ecp_price_multiply( $order->get_total(), $order->get_currency() );
		$display_mode = $this->get_option( EcpSettings::OPTION_MODE, EcpSettings::MODE_REDIRECT );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, $values, $order );
		// Setup Payment Page Operation Mode
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_MODE, $values, $amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY );
		// Setup Payment Page Force Mode
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, self::PAYMENT_METHOD );
		// Setup Payment Page Display Mode
		$values = apply_filters(
			'ecp_append_display_mode',
			$values,
			$display_mode,
			ecp_is_enabled( EcpSettings::OPTION_POPUP_MISS_CLICK, $this->id )
		);
		// Setup Recurring (Subscriptions)
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_RECURRING, $values, $order );

		return parent::apply_payment_args( $values, $order );
	}

	public function get_refund_endpoint( $order ): string {
		return EcpGatewayPaymentMethods::get_code( $order );
	}

	/**
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @throws EcpGatewayErrorException
	 * @since 3.0.0
	 */
	public function process_payment( $order_id ): array {
		$order      = ecp_get_order( $order_id );
		$payment_id = $_POST['payment_id'];

		if ( empty( $order ) ) {
			throw new EcpGatewayErrorException( 'Order is not found.' );
		}

		if ( ! empty ( $payment_id ) ) {
			$order->set_payment_id( $payment_id );
		}

		$options          = ecp_payment_page()->get_request_url( $order, $this );
		$payment_page_url = ecp_payment_page()->get_url() . '/payment?' . http_build_query( $options );

		return [
			'result'      => 'success',
			'optionsJson' => json_encode( $options ),
			'redirect'    => $payment_page_url,
			'order_id'    => $order_id,
		];
	}

	/**
	 * @override
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @since 3.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		return EcpModuleRefund::get_instance()->process( $order_id, $amount, $reason );
	}

	/**
	 * <p>If false, the automatic refund button is hidden in the UI.</p>
	 *
	 * @param WC_Order $order <p>Order object.</p>
	 *
	 * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
	 * @override
	 * @since 2.0.0
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
}
