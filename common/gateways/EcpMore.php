<?php

namespace common\gateways;

use common\includes\filters\EcpAppendsFilterList;
use common\settings\EcpSettings;
use common\settings\EcpSettingsMore;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  2.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class EcpMore extends EcpGateway {
	/**
	 * @inheritDoc
	 * @override
	 * @var string[]
	 * @since 1.0.0
	 */
	public $supports = [
		self::SUPPORT_PRODUCTS,
	];


	/**
	 * <h2>ECOMMPAY Gateway constructor.</h2>
	 */
	public function __construct() {
		$this->id = EcpSettingsMore::ID;
		$this->method_title       = __( 'ECOMMPAY More payment methods', 'woo-ecommpay' );
		$this->method_description = __( 'Open the payment page with all payment methods or select an additional alternative payment method.', 'woo-ecommpay' );
		$this->has_fields         = false;
		$this->title             = $this->get_option( EcpSettings::OPTION_TITLE );
		$this->order_button_text = $this->get_option( EcpSettings::OPTION_CHECKOUT_BUTTON_TEXT );
		$this->enabled           = $this->get_option( EcpSettings::OPTION_ENABLED );
		$this->icon               = '';

		if ( $this->is_enabled( EcpSettings::OPTION_SHOW_DESCRIPTION ) ) {
			$this->description = $this->get_option( EcpSettings::OPTION_DESCRIPTION );
		}

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array
	 * @since 3.0.0
	 */
	public function apply_payment_args( $values, $order ): array {
		$force = $this->get_option( EcpSettings::OPTION_FORCE_CODE );

		if ( $force !== null && $force !== '' ) {
			$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_FORCE_MODE, $values, $force );
		}

		return parent::apply_payment_args( $values, $order );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
	 * @since 2.0.0
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
