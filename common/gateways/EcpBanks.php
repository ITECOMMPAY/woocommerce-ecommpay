<?php

namespace common\gateways;

use common\enums\EcpWcPaymentMethods;
use common\includes\EcpGatewayOrder;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Banks Gateway.</h2>
 *
 * @class    EcpBanks
 * @version  2.0.0
 * @package  Ecp_Gateway/Gateways
 * @category Class
 */
class EcpBanks extends EcpGateway {

	/**
	 * @var string Payment method code for Banks
	 * @since 3.0.0
	 */
	private const PAYMENT_METHOD_CODE = 'banks';

	/**
	 * @var string Force payment group for open banking
	 * @since 3.0.0
	 */
	private const FORCE_PAYMENT_GROUP = 'openbanking';

	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.0.0
	 */
	public $supports = array(
		self::SUPPORT_PRODUCTS,
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
	 * <h2>ECOMMPAY Banks Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id                     = EcpWcPaymentMethods::BANKS;
		$this->method_title_key       = 'ECOMMPAY Open banking';
		$this->method_description_key = 'Accept payments via Open Banking.';

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
		$values['force_payment_group'] = self::FORCE_PAYMENT_GROUP;

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
	 * @since 3.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 * <p>If false, the automatic refund button is hidden in the UI.</p>
	 *
	 * @param WC_Order $order <p>Order object.</p>
	 *
	 * @override
	 * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
	 * @since 3.0.0
	 */
	public function can_refund_order( $order ): bool {
		return false;
	}
}
