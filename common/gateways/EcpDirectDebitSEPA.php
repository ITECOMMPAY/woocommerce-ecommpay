<?php

namespace common\gateways;

use common\exceptions\EcpGatewayLogicException;
use common\helpers\EcpGatewayPaymentMethods;
use common\includes\EcpGatewayOrder;
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

	/**
	 * @var string Payment method code for Direct Debit SEPA
	 * @since 3.4.3
	 */
	private const PAYMENT_METHOD_CODE = 'directdebit-sepa';

	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.4.3
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
	 * @since 3.4.3
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
		$this->id                     = EcpSettingsDirectDebitSEPA::ID;
		$this->method_title_key       = 'ECOMMPAY Direct debit SEPA';
		$this->method_description_key = 'Accept payments via Direct Debit Sepa.';

		parent::__construct();

		$this->init_subscription();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.4.3
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, $values, $order );
		$values = $this->apply_standard_payment_args( $values, $order );

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

	public function is_available(): bool {
		return parent::is_available() && $this->is_gateway_with_subscription_only();
	}
}
