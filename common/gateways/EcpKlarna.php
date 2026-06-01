<?php

namespace common\gateways;

use common\exceptions\EcpGatewayLogicException;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use common\settings\EcpSettingsKlarna;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway Klarna.</h2>
 *
 * @class    EcpKlarna
 * @version  3.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class EcpKlarna extends EcpGateway {

	/**
	 * @var string Payment method code for Klarna
	 * @since 3.0.0
	 */
	private const PAYMENT_METHOD_CODE = 'klarna';

	/**
	 * Refund endpoint prefix for Klarna.
	 *
	 * @var string
	 * @since 3.0.0
	 */
	private const REFUND_ENDPOINT_PREFIX = 'bank-transfer/klarna';

	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 1.0.0
	 */
	public $supports = array(
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_REFUNDS,
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
	 * @inheritDoc
	 * @return string
	 * @since 3.0.0
	 */
	protected function get_refund_endpoint_prefix(): string {
		return self::REFUND_ENDPOINT_PREFIX;
	}

	/**
	 * <h2>ECOMMPAY Klarna Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id                     = EcpSettingsKlarna::ID;
		$this->method_title_key       = 'ECOMMPAY Klarna';
		$this->method_description_key = 'Accept payments via Klarna.';

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_COUNTRY, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_BILLING_COUNTRY, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_RECEIPT_DATA, $values, $order, true );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, $this->get_payment_method_code() );

		return parent::apply_payment_args( $values, $order );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 3.0.0
	 */
	public function process_payment( $order_id ): array {
		return $this->process_standard_payment( $order_id );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @throws EcpGatewayLogicException
	 * @since 3.0.0
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
	 * @since 3.0.0
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
