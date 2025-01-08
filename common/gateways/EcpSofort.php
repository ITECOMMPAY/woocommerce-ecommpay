<?php

namespace common\gateways;

use common\modules\EcpModuleRefund;
use common\settings\EcpSettingsSofort;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Sofort Gateway.</h2>
 *
 * @class    EcpSofort
 * @version  3.0.0
 * @package  Ecp_Gateway/Gateways
 * @category Class
 */
class EcpSofort extends EcpGateway {
	protected const PAYMENT_METHOD = 'sofort';
	protected const REFUND_ENDPOINT = 'online-banking/sofort';
	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 3.0.0
	 */
	public $supports = [
		self::SUPPORT_PRODUCTS,
		self::SUPPORT_REFUNDS
	];

	/**
	 * <h2>ECOMMPAY Sofort Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id = EcpSettingsSofort::ID;
		$this->method_title       = __( 'ECOMMPAY SOFORT', 'woo-ecommpay' );
		$this->method_description = __( 'Accept payments via SOFORT.', 'woo-ecommpay' );

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( $values, $order ): array {
		$values['force_payment_method'] = 'sofort';

		return parent::apply_payment_args( $values, $order );
	}


	/**
	 * @inheritDoc
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 3.0.0
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
