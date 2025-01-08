<?php

defined( 'ABSPATH' ) || exit;


// Declare Support For Cart+Checkout Blocks
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use common\gateways\EcpApplepay;
use common\gateways\EcpBanks;
use common\gateways\EcpBlik;
use common\gateways\EcpBrazilOnlineBanks;
use common\gateways\EcpCard;
use common\gateways\EcpDirectDebitBACS;
use common\gateways\EcpDirectDebitSEPA;
use common\gateways\EcpGiropay;
use common\gateways\EcpGooglepay;
use common\gateways\EcpIdeal;
use common\gateways\EcpKlarna;
use common\gateways\EcpMore;
use common\gateways\EcpPayPal;
use common\gateways\EcpPayPalPayLater;
use common\gateways\EcpSofort;
use common\includes\EcpGatewayBlocksSupport;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsBanks;
use common\settings\EcpSettingsBlik;
use common\settings\EcpSettingsBrazilOnline_Banks;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsDirectDebitBACS;
use common\settings\EcpSettingsDirectDebitSEPA;
use common\settings\EcpSettingsGiropay;
use common\settings\EcpSettingsGooglepay;
use common\settings\EcpSettingsIdeal;
use common\settings\EcpSettingsKlarna;
use common\settings\EcpSettingsMore;
use common\settings\EcpSettingsPayPal;
use common\settings\EcpSettingsPayPalPayLater;
use common\settings\EcpSettingsSofort;

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			ECP_PLUGIN_PATH
		);
	}
} );

// Blocks Support
add_action( 'woocommerce_blocks_loaded', function () {

	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$gateways = [
					EcpSettingsApplepay::ID => new EcpApplepay(),
					EcpSettingsBanks::ID              => new EcpBanks(),
					EcpSettingsBlik::ID               => new EcpBlik(),
					EcpSettingsBrazilOnline_Banks::ID => new EcpBrazilOnlineBanks(),
					EcpSettingsDirectDebitBACS::ID    => new EcpDirectDebitBACS(),
					EcpSettingsDirectDebitSEPA::ID    => new EcpDirectDebitSEPA(),
					EcpSettingsCard::ID               => new EcpCard(),
					EcpSettingsGiropay::ID            => new EcpGiropay(),
					EcpSettingsGooglepay::ID          => new EcpGooglepay(),
					EcpSettingsIdeal::ID              => new EcpIdeal(),
					EcpSettingsKlarna::ID             => new EcpKlarna(),
					EcpSettingsMore::ID               => new EcpMore(),
					EcpSettingsPayPal::ID             => new EcpPayPal(),
					EcpSettingsPayPalPayLater::ID     => new EcpPayPalPayLater(),
					EcpSettingsSofort::ID             => new EcpSofort(),
				];

				foreach ( $gateways as $id => $gateway ) {
					$name = str_replace( 'ecommpay-', '', $id );
					$payment_method_registry->register( new EcpGatewayBlocksSupport( $name, $gateway ) );
				}
			}
		);
	}

} );
