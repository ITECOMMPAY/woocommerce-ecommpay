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

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				ECP_PLUGIN_PATH
			);
		}
	}
);


// Blocks Support
add_action(
	'woocommerce_blocks_loaded',
	function () {

		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {

					$gateway_classes = array(
						EcpSettingsCard::ID               => EcpCard::class,
						EcpSettingsPayPal::ID             => EcpPayPal::class,
						EcpSettingsPayPalPayLater::ID     => EcpPayPalPayLater::class,
						EcpSettingsKlarna::ID             => EcpKlarna::class,
						EcpSettingsBlik::ID               => EcpBlik::class,
						EcpSettingsIdeal::ID              => EcpIdeal::class,
						EcpSettingsBanks::ID              => EcpBanks::class,
						EcpSettingsHumm::ID               => EcpHumm::class,
						EcpSettingsBrazilOnline_Banks::ID => EcpBrazilOnlineBanks::class,
						EcpSettingsGooglepay::ID          => EcpGooglepay::class,
						EcpSettingsApplepay::ID           => EcpApplepay::class,
						EcpSettingsDirectDebitBACS::ID    => EcpDirectDebitBACS::class,
						EcpSettingsDirectDebitSEPA::ID    => EcpDirectDebitSEPA::class,
						EcpSettingsMore::ID               => EcpMore::class,
					);

					foreach ( $gateway_classes as $id => $gateway_class ) {
						// Prevent duplicate registration.
						if ( ! $payment_method_registry->is_registered( $id ) ) {
							$payment_method_registry->register( new EcpGatewayBlocksSupport( $id, $gateway_class ) );
						}
					}
				}
			);
		}
	}
);
