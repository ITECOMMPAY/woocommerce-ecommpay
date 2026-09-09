<?php
/**
 * ECOMMPAY Gateway Brazil Online Banks class.
 *
 * @package Ecp_Gateway/Gateways
 * @since   3.3.0
 */

namespace common\gateways;

use common\enums\EcpWcPaymentMethods;
use common\exceptions\EcpGatewayLogicException;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway Brazil Online Banks.</h2>
 *
 * @class    EcpBrazilOnlineBanks
 * @version  3.3.0
 * @package  Ecp_Gateway/Gateways
 * @category Class
 */
class EcpBrazilOnlineBanks extends EcpGateway {

	/**
	 * <h2>Payment method code for Brazil Online Banks.</h2>
	 *
	 * @var string
	 * @since 3.3.0
	 */
	private const PAYMENT_METHOD_CODE = 'online-brazil-banks';

	/**
	 * Refund endpoint prefix for Brazil Online Banks.
	 *
	 * @var string
	 * @since 3.3.0
	 */
	private const REFUND_ENDPOINT_PREFIX = 'banks/brazil';

	/**
	 * <h2>Supported gateway features.</h2>
	 *
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.3.0
	 */
	public $supports = array(
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_REFUNDS,
	);

	/**
	 * <h2>Returns payment method code.</h2>
	 *
	 * @inheritDoc
	 * @return string
	 * @since 3.3.0
	 */
	protected function get_payment_method_code(): string {
		return self::PAYMENT_METHOD_CODE;
	}

	/**
	 * <h2>Returns refund endpoint prefix.</h2>
	 *
	 * @inheritDoc
	 * @return string
	 * @since 3.3.0
	 */
	protected function get_refund_endpoint_prefix(): string {
		return self::REFUND_ENDPOINT_PREFIX;
	}

	/**
	 * <h2>ECOMMPAY Brazil Online Banks Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id                     = EcpWcPaymentMethods::BRAZIL_ONLINE_BANKS;
		$this->method_title_key       = 'ECOMMPAY Brazil';
		$this->method_description_key = 'Accept payments via Brazil.';

		parent::__construct();
	}

	/**
	 * <h2>Applies payment arguments.</h2>
	 *
	 * @inheritDoc
	 * @override
	 * @param array    $values <p>Payment arguments.</p>
	 * @param WC_Order $order <p>Order object.</p>
	 * @return array
	 * @since 3.3.0
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, $this->get_payment_method_code() );

		return parent::apply_payment_args( $values, $order );
	}

	/**
	 * <h2>Processes payment.</h2>
	 *
	 * @inheritDoc
	 * @override
	 * @param int $order_id <p>Order ID.</p>
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 3.3.0
	 */
	public function process_payment( $order_id ): array {
		return $this->process_standard_payment( $order_id );
	}

	/**
	 * <h2>Processes refund.</h2>
	 *
	 * @inheritDoc
	 * @override
	 * @param int        $order_id <p>Order ID.</p>
	 * @param float|null $amount <p>Refund amount.</p>
	 * @param string     $reason <p>Refund reason.</p>
	 * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
	 * @throws EcpGatewayLogicException If refund process fails.
	 * @since 3.3.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		return EcpModuleRefund::get_instance()->process( $order_id, $amount, $reason );
	}

	/**
	 * <h2>Checks if order can be refunded.</h2>
	 * <p>If false, the automatic refund button is hidden in the UI.</p>
	 *
	 * @inheritDoc
	 * @override
	 * @param WC_Order $order <p>Order object.</p>
	 * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
	 * @throws EcpGatewayLogicException If order validation fails.
	 * @since 3.3.0
	 */
	public function can_refund_order( $order ): bool {
		if ( ! $order ) {
			ecp_get_log()->debug(
				_x( 'Undefined argument order. Hide refund via ECOMMPAY button.', 'Log information', 'woo-ecommpay' )
			);

			return false;
		}

		$order = ecp_get_order( $order );

		if ( ! $order->is_ecp() ) {
			return false;
		}

		return EcpModuleRefund::get_instance()->is_available( $order );
	}
}
