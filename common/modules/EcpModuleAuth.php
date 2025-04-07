<?php

namespace common\modules;

defined( 'ABSPATH' ) || exit;

use common\helpers\EcpGatewayRegistry;
use common\includes\filters\EcpWCFilters;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsGeneral;
use common\settings\EcpSettingsGooglepay;


class EcpModuleAuth extends EcpGatewayRegistry {

	public function filterPaymentMethodsAuthOnly( $available_gateways ): array {
		$mode = ecommpay()->get_general_option( EcpSettingsGeneral::PURCHASE_TYPE,
			EcpSettingsGeneral::PURCHASE_TYPE_SALE );

		$auth_mode_enabled = ( $mode === EcpSettingsGeneral::PURCHASE_TYPE_AUTH );

		if ( $auth_mode_enabled ) {
			$supported_gateways = array(
				EcpSettingsCard::ID,
				EcpSettingsGooglepay::ID,
				EcpSettingsApplepay::ID
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

	protected function init(): void {
		add_filter( EcpWCFilters::WOOCOMMERCE_AVAILABLE_PAYMENT_GATEWAYS, [
			$this,
			'filterPaymentMethodsAuthOnly'
		] );
	}
}
