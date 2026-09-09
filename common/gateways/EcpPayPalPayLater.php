<?php

namespace common\gateways;

use common\enums\EcpWcPaymentMethods;
use common\exceptions\EcpGatewayLogicException;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway PayPal PayLater.</h2>
 *
 * @class    EcpPayPalPayLater
 * @version  3.4.3
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class EcpPayPalPayLater extends EcpGateway {

	/**
	 * @var string Payment method code for PayPal Pay Later
	 * @since 3.4.5
	 */
	private const PAYMENT_METHOD_CODE = 'paypal-wallet';

	/**
	 * @var string Icon file name for PayPal Pay Later
	 * @since 3.4.5
	 */
	private const ICON_FILE_NAME = 'paypal-paylater';

	/**
	 * Refund endpoint prefix for PayPal Pay Later.
	 *
	 * @var string
	 * @since 3.4.5
	 */
	private const REFUND_ENDPOINT_PREFIX = 'wallet/paypal';

	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.4.3
	 */
	public $supports = array(
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_REFUNDS,
	);

	/**
	 * @inheritDoc
	 * @return string
	 * @since 3.4.5
	 */
	protected function get_payment_method_code(): string {
		return self::PAYMENT_METHOD_CODE;
	}

	/**
	 * @inheritDoc
	 * @return string
	 * @since 3.4.5
	 */
	protected function get_icon_file_name(): string {
		return self::ICON_FILE_NAME;
	}

	/**
	 * @inheritDoc
	 * @return string
	 * @since 3.4.5
	 */
	protected function get_refund_endpoint_prefix(): string {
		return self::REFUND_ENDPOINT_PREFIX;
	}

	public function __construct() {
		$this->id                     = EcpWcPaymentMethods::PAYPAL_PAYLATER;
		$this->method_title_key       = 'ECOMMPAY PayPal PayLater';
		$this->method_description_key = 'Accept payments via PayPal Buy Now Pay Later.';

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.4.3
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$values                            = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, $this->get_payment_method_code() );
		$values['payment_methods_options'] = '{"submethod_code": "paylater"}';

		return parent::apply_payment_args( $values, $order );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 3.4.3
	 */
	public function process_payment( $order_id ): array {
		return $this->process_standard_payment( $order_id );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @throws EcpGatewayLogicException
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
	 * @throws EcpGatewayLogicException
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
}
