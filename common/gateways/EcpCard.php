<?php

namespace common\gateways;

use common\enums\EcpWcPaymentMethods;
use common\exceptions\EcpGatewayErrorException;
use common\exceptions\EcpGatewayLogicException;
use common\helpers\EcpGatewayPaymentMethods;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use common\settings\EcpSettings;
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

	/**
	 * @var string Payment method code for Card
	 * @since 3.0.0
	 */
	private const PAYMENT_METHOD_CODE = 'card';

	/**
	 * @override
	 * @var string[]
	 * @since 1.0.0
	 */
	public $supports = array(
		self::SUPPORT_SUBSCRIPTIONS,
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_SUBSCRIPTION_CANCELLATION,
		self::SUPPORT_SUBSCRIPTION_REACTIVATION,
		self::SUPPORT_SUBSCRIPTION_SUSPENSION,
		self::SUPPORT_SUBSCRIPTION_AMOUNT_CHANGES,
		self::SUPPORT_SUBSCRIPTION_DATE_CHANGES,
		self::SUPPORT_REFUNDS,
		self::SUPPORT_MULTIPLE_SUBSCRIPTIONS,
	);


	/**
	 * @inheritDoc
	 * @return string
	 * @since 3.0.0
	 */
	protected function get_payment_method_code(): string {
		return self::PAYMENT_METHOD_CODE;
	}

	/**
	 * <h2>ECOMMPAY Gateway constructor.</h2>
	 *
	 * @throws EcpGatewayLogicException
	 */
	public function __construct() {
		$this->id                     = EcpWcPaymentMethods::CARD;
		$this->method_title_key       = 'ECOMMPAY Cards';
		$this->method_description_key = 'Accept card payments via ECOMMPAY.';

		parent::__construct();

		if ( $this->get_option( EcpSettings::OPTION_MODE, EcpSettings::MODE_REDIRECT ) === EcpSettings::MODE_EMBEDDED ) {
			$this->description = '<div id="ecommpay-loader-embedded"><div class="lds-ecommpay"><div></div><div></div><div></div></div></div><div id="ecommpay-iframe-embedded"></div>';
		}

		$this->init_subscription();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$display_mode = $this->get_option( EcpSettings::OPTION_MODE, EcpSettings::MODE_REDIRECT );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, $values, $order );
		$values = $this->apply_standard_payment_args( $values, $order );
		// Setup Payment Page Display Mode
		$values = apply_filters(
			EcpAppendsFilters::ECP_APPEND_DISPLAY_MODE,
			$values,
			$display_mode,
			ecp_is_enabled( EcpSettings::OPTION_POPUP_MISS_CLICK, $this->id )
		);

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
		$order = ecp_get_order( $order_id );

		if ( empty( $order ) ) {
			throw new EcpGatewayErrorException( 'Order is not found.' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$payment_id = isset( $_POST['payment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_id'] ) ) : null;

		if ( ! empty( $payment_id ) ) {
			$order->set_payment_id( $payment_id );
		}

		$options          = ecp_payment_page()->get_request_url( $order, $this );
		$payment_page_url = ecp_payment_page()->get_payment_url( $order, $this );

		return array(
			'result'      => 'success',
			'optionsJson' => wp_json_encode( $options ),
			'redirect'    => $payment_page_url,
			'order_id'    => $order_id,
		);
	}

	/**
	 * @override
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @throws EcpGatewayLogicException
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
	 * @throws EcpGatewayLogicException
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
