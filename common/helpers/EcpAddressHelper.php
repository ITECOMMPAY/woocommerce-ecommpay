<?php

namespace common\helpers;

use common\includes\EcpGatewayOrder;

/**
 * <h2>Address helper.</h2>
 *
 * @class    EcpAddressHelper
 * @package  common\helpers
 */
class EcpAddressHelper {

	/**
	 * Normalizes country state/region code into a clean local ISO 3166-2 code.
	 *
	 * @param string|null $region_code Raw state/region code from WooCommerce (e.g. 'US-CA', 'CA', 'ny').
	 * @param string|null $country_code Two-letter ISO country code (e.g. 'US', 'RU').
	 *
	 * @return string|null Normalized local code (1-3 uppercase characters) or null if invalid/empty.
	 */
	public static function normalizeRegionCode( ?string $region_code, ?string $country_code = null ): ?string {
		if ( null === $region_code || null === $country_code ) {
			return null;
		}

		$wc_states    = WC()->countries->get_states();
		$region_code  = strtoupper( trim( $region_code ) );
		$country_code = strtoupper( trim( $country_code ) );
		if ( '' === $region_code ||
			'' === $country_code ||
			! array_key_exists( $country_code, $wc_states )
		) {
			return null;
		}

		// if region has prefix like "XX-":
		if ( 1 === preg_match( '/^([A-Z]{2})-(.+)$/', $region_code, $matches ) ) {
			// prefix must be equal to country code
			if ( $matches[1] !== $country_code ) {
				return null;
			}

			$region_code = $matches[2];
		} elseif ( 0 === strpos( $region_code, $country_code ) && strlen( $region_code ) > strlen( $country_code ) ) {
			// prefix without dash "XXYYY" ("RS": "RSKM", "JP": "JP13")
			// cut country code from beginning
			$region_code = substr( $region_code, strlen( $country_code ) );
		}

		// check that region has max 3 symbols
		if ( 1 === preg_match( '/^[0-9A-Z]{1,3}$/', $region_code ) ) {
			return $region_code;
		}

		return null;
	}

	public static function extractShippingAddressFromOrder( EcpGatewayOrder $order ): ?string {
		$addressLines = array_filter(
			array(
				$order->get_shipping_address_1(),
				$order->get_shipping_address_2(),
			)
		);

		$address = ! empty( $addressLines ) ? implode( ', ', $addressLines ) : null;

		return $address;
	}
}
