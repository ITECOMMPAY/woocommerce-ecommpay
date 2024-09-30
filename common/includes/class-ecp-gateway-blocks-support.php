<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Ecp_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	protected string $payment_method;
	/**
	 * @var array
	 */
	public array $data;

	public function __construct( string $payment_method, $gateway ) {
		$this->payment_method = $payment_method;
		$this->name           = sprintf( 'ecommpay-%s', $this->payment_method );
		$this->data           = [
			'title'                => $gateway->settings['title'],
			'icon'                 => $gateway->get_icon_path(),
			'description' => $gateway->settings['show_description'] === Ecp_Gateway_Settings::YES ? $gateway->settings['description'] : '',
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
		return $this->data['enabled'] === 'yes';
	}

	public function get_payment_method_data(): array {
		return $this->data;
	}
}
