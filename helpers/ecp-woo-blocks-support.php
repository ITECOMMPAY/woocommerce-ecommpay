<?php

defined( 'ABSPATH' ) || exit;


// Declare Support For Cart+Checkout Blocks
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use common\enums\EcpWcPaymentMethods;
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
						EcpWcPaymentMethods::CARD                => EcpCard::class,
						EcpWcPaymentMethods::PAYPAL              => EcpPayPal::class,
						EcpWcPaymentMethods::PAYPAL_PAYLATER     => EcpPayPalPayLater::class,
						EcpWcPaymentMethods::KLARNA              => EcpKlarna::class,
						EcpWcPaymentMethods::BLIK                => EcpBlik::class,
						EcpWcPaymentMethods::IDEAL               => EcpIdeal::class,
						EcpWcPaymentMethods::BANKS               => EcpBanks::class,
						EcpWcPaymentMethods::HUMM                => EcpHumm::class,
						EcpWcPaymentMethods::BRAZIL_ONLINE_BANKS => EcpBrazilOnlineBanks::class,
						EcpWcPaymentMethods::GOOGLE_PAY          => EcpGooglepay::class,
						EcpWcPaymentMethods::APPLE_PAY           => EcpApplepay::class,
						EcpWcPaymentMethods::DIRECTDEBIT_BACS    => EcpDirectDebitBACS::class,
						EcpWcPaymentMethods::DIRECTDEBIT_SEPA    => EcpDirectDebitSEPA::class,
						EcpWcPaymentMethods::MORE                => EcpMore::class,
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
