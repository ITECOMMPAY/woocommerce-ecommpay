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
use common\gateways\EcpGooglepay;
use common\gateways\EcpHumm;
use common\gateways\EcpIdeal;
use common\gateways\EcpKlarna;
use common\gateways\EcpMore;
use common\gateways\EcpPayPal;
use common\gateways\EcpPayPalPayLater;
use common\includes\EcpGatewayBlocksSupport;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsBanks;
use common\settings\EcpSettingsBlik;
use common\settings\EcpSettingsBrazilOnline_Banks;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsDirectDebitBACS;
use common\settings\EcpSettingsDirectDebitSEPA;
use common\settings\EcpSettingsGooglepay;
use common\settings\EcpSettingsHumm;
use common\settings\EcpSettingsIdeal;
use common\settings\EcpSettingsKlarna;
use common\settings\EcpSettingsMore;
use common\settings\EcpSettingsPayPal;
use common\settings\EcpSettingsPayPalPayLater;

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
					EcpSettingsCard::ID           => new EcpCard(),
					EcpSettingsPayPal::ID         => new EcpPayPal(),
					EcpSettingsPayPalPayLater::ID => new EcpPayPalPayLater(),
					EcpSettingsKlarna::ID         => new EcpKlarna(),
					EcpSettingsBlik::ID               => new EcpBlik(),
					EcpSettingsIdeal::ID          => new EcpIdeal(),
					EcpSettingsBanks::ID          => new EcpBanks(),
					EcpSettingsHumm::ID           => new EcpHumm(),
					EcpSettingsBrazilOnline_Banks::ID => new EcpBrazilOnlineBanks(),
					EcpSettingsGooglepay::ID      => new EcpGooglepay(),
					EcpSettingsApplepay::ID       => new EcpApplepay(),
					EcpSettingsDirectDebitBACS::ID    => new EcpDirectDebitBACS(),
					EcpSettingsDirectDebitSEPA::ID    => new EcpDirectDebitSEPA(),
					EcpSettingsMore::ID               => new EcpMore(),
				];

				foreach ( $gateways as $id => $gateway ) {
					$name = str_replace( 'ecommpay-', '', $id );
					$payment_method_registry->register( new EcpGatewayBlocksSupport( $name, $gateway ) );
				}
			}
		);
	}

} );
