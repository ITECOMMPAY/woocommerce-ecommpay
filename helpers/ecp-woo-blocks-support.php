<?php

// Declare Support For Cart+Checkout Blocks
add_action('before_woocommerce_init', function () {
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			ECP_PLUGIN_PATH
		);
	}
});

// Blocks Support
add_action('woocommerce_blocks_loaded', function () {

	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$gateways = [
					Ecp_Gateway_Settings_Applepay::ID => new Ecp_Gateway_Applepay(),
					Ecp_Gateway_Settings_Banks::ID => new Ecp_Gateway_Banks(),
					Ecp_Gateway_Settings_Blik::ID => new Ecp_Gateway_Blik(),
					Ecp_Gateway_Settings_Brazil_Online_Banks::ID => new Ecp_Gateway_Brazil_Online_Banks(),
					Ecp_Gateway_Settings_DirectDebit_BACS::ID => new Ecp_Gateway_DirectDebit_BACS(),
					Ecp_Gateway_Settings_DirectDebit_SEPA::ID => new Ecp_Gateway_DirectDebit_SEPA(),
					Ecp_Gateway_Settings_Card::ID => new Ecp_Gateway_Card(),
					Ecp_Gateway_Settings_Giropay::ID => new Ecp_Gateway_Giropay(),
					Ecp_Gateway_Settings_Googlepay::ID => new Ecp_Gateway_Googlepay(),
					Ecp_Gateway_Settings_Ideal::ID => new Ecp_Gateway_Ideal(),
					Ecp_Gateway_Settings_Klarna::ID => new Ecp_Gateway_Klarna(),
					Ecp_Gateway_Settings_More::ID => new Ecp_Gateway_More(),
					Ecp_Gateway_Settings_PayPal::ID => new Ecp_Gateway_PayPal(),
					Ecp_Gateway_Settings_PayPal_PayLater::ID => new Ecp_Gateway_PayPal_PayLater(),
					Ecp_Gateway_Settings_Sofort::ID => new Ecp_Gateway_Sofort(),
				];

				foreach ($gateways as $id => $gateway) {
					$name = str_replace('ecommpay-', '', $id);
					$payment_method_registry->register(new Ecp_Gateway_Blocks_Support($name, $gateway));
				}
			}
		);
	}

});