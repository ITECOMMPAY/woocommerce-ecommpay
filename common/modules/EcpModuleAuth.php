<?php

namespace common\modules;

use Ecp_Gateway_Registry;
use Ecp_Gateway_Settings_Applepay;
use Ecp_Gateway_Settings_Card;
use Ecp_Gateway_Settings_General;
use Ecp_Gateway_Settings_Googlepay;

class EcpModuleAuth extends Ecp_Gateway_Registry {
	protected function init() {
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterPaymentMethodsAuthOnly' ] );
	}

	public function filterPaymentMethodsAuthOnly( $available_gateways ): array {
		$mode = ecommpay()->get_general_option( Ecp_Gateway_Settings_General::PURCHASE_TYPE,
			Ecp_Gateway_Settings_General::PURCHASE_TYPE_SALE );

		$auth_mode_enabled = ( $mode === Ecp_Gateway_Settings_General::PURCHASE_TYPE_AUTH );

		if ( $auth_mode_enabled ) {
			$supported_gateways = array(
				Ecp_Gateway_Settings_Card::ID,
				Ecp_Gateway_Settings_Googlepay::ID,
				Ecp_Gateway_Settings_Applepay::ID
			);

			$ecp_methods = ecp_payment_methods();

			foreach ( $available_gateways as $id => $gateway ) {
				if ( isset( $ecp_methods[ $id ] ) && ! in_array( $id, $supported_gateways ) ) {
					unset( $available_gateways[ $id ] );
				}
			}
		}

		return $available_gateways;
	}
}