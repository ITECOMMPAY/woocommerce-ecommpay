<?php

namespace common\includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;

class EcpGatewayBlocksSupport extends AbstractPaymentMethodType {

	/**
	 * @var array
	 */
	public array $data;
	protected string $gateway_class;
	protected ?object $gateway_instance = null;

	public function __construct( string $name, string $gateway_class ) {
		$this->name          = $name;
		$this->gateway_class = $gateway_class;
	}

	public function initialize() {
		// Create gateway instance only when needed (after init hook).
		$this->gateway_instance = new $this->gateway_class();

		$this->data = array(
			'title'                => $this->gateway_instance->settings['title'],
			'icon'                 => $this->gateway_instance->get_icon_path(),
			'description'          => $this->gateway_instance->settings['show_description'] === EcpSettings::VALUE_ENABLED ? $this->gateway_instance->settings['description'] : '',
			'checkout_button_text' => $this->gateway_instance->settings['checkout_button_text'],
			'enabled'              => $this->gateway_instance->settings['enabled'],
			'supports'             => $this->gateway_instance->supports,
		);

		if ( isset( $this->gateway_instance->settings['pp_mode'] ) ) {
			$this->data['pp_mode'] = $this->gateway_instance->settings['pp_mode'];
		}

		if ( isset( $this->gateway_instance->settings['pp_close_on_miss_click'] ) ) {
			$this->data['pp_close_on_miss_click'] = $this->gateway_instance->settings['pp_close_on_miss_click'];
		}

		$this->data['pp_version'] = ecommpay()->get_general_option(
			EcpSettingsGeneral::OPTION_PAYMENT_PAGE_VERSION,
			EcpSettingsGeneral::PP_VERSION_LEGACY
		);
	}

	public function is_active(): bool {
		if ( null === $this->gateway_instance ) {
			$this->initialize();
		}
		return $this->data['enabled'] === EcpSettings::VALUE_ENABLED;
	}

	public function get_payment_method_data(): array {
		if ( null === $this->gateway_instance ) {
			$this->initialize();
		}
		return $this->data;
	}
}
