<?php

namespace common\includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use common\settings\EcpSettings;

class EcpGatewayBlocksSupport extends AbstractPaymentMethodType {

	/**
	 * @var array
	 */
	public array $data;
	protected string $payment_method;

	public function __construct( string $payment_method, $gateway ) {
		$this->payment_method = $payment_method;
		$this->name           = sprintf( 'ecommpay-%s', $this->payment_method );
		$this->data           = [
			'title'                => $gateway->settings['title'],
			'icon'                 => $gateway->get_icon_path(),
			'description' => $gateway->settings['show_description'] === EcpSettings::VALUE_ENABLED ? $gateway->settings['description'] : '',
			'checkout_button_text' => $gateway->settings['checkout_button_text'],
			'enabled'              => $gateway->settings['enabled'],
			'supports'             => $gateway->supports,
		];

		if ( isset ( $gateway->settings['pp_mode'] ) ) {
			$this->data['pp_mode'] = $gateway->settings['pp_mode'];
		}

		if ( isset ( $gateway->settings['pp_close_on_miss_click'] ) ) {
			$this->data['pp_close_on_miss_click'] = $gateway->settings['pp_close_on_miss_click'];
		}
	}

	public function initialize() {
	}

	public function is_active(): bool {
		return $this->data['enabled'] === EcpSettings::VALUE_ENABLED;
	}

	public function get_payment_method_data(): array {
		return $this->data;
	}
}
