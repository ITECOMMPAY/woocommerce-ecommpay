<?php

namespace common\gateways;

use common\includes\filters\EcpAppendsFilters;
use common\modules\EcpModuleRefund;
use common\settings\EcpSettingsGiropay;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway Giropay.</h2>
 *
 * @class    EcpGiropay
 * @version  3.0.0
 * @package  Ecp_Gateway/Gateways
 * @category Class
 */
class EcpGiropay extends EcpGateway {
	protected const PAYMENT_METHOD = 'giropay';
	protected const REFUND_ENDPOINT = 'online-banking/giropay';
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
	 * <h2>ECOMMPAY Giropay Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id = EcpSettingsGiropay::ID;
		$this->method_title       = __( 'ECOMMPAY Giropay', 'woo-ecommpay' );
		$this->method_description = __( 'Accept payments via Giropay.', 'woo-ecommpay' );

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( $values, $order ): array {
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, self::PAYMENT_METHOD );

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
