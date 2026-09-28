<?php

namespace common\helpers;

use common\EcpCore;
use common\enums\EcpWcPaymentMethods;
use common\exceptions\EcpGatewaySignatureException;
use common\includes\EcpGatewayOrder;
use common\modules\EcpModuleCapture;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;

class EcpRequestBuildHelper {

	private static function append_field( string $key, $value, array $data ): array {
		if ( $value !== null ) {
			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * <h2>Appends version information to the payment data.</h2>
	 *
	 * @param array $data <p>Payment data array.</p>
	 *
	 * @return array <p>Payment data with version information.</p>
	 * @since 5.0.6
	 */
	public static function append_versions( array $data ): array {
		$data = self::append_field( '_plugin_version', EcpCore::WC_ECP_VERSION, $data );
		$data = self::append_field( '_wordpress_version', wp_version(), $data );
		return self::append_field( '_woocommerce_version', wc_version(), $data );
	}

	/**
	 * <h2>Appends project ID to the payment data.</h2>
	 *
	 * @param array $data <p>Payment data array.</p>
	 *
	 * @return array <p>Payment data with project ID.</p>
	 * @since 5.0.6
	 */
	public static function append_project_id( array $data ): array {
		return self::append_field( 'project_id', ecommpay()->get_project_id(), $data );
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Mode settings.</h2>
	 *
	 * @param ?string $value <p>Order for payment.</p>
	 * @param array $values
	 *
	 * @return array <p>Payment Page mode settings.</p>
	 * @since 5.0.6
	 */
	public static function append_operation_mode( array $values, ?string $value ): array {
		return self::append_field( 'mode', $value, $values );
	}

	/**
	 * <h2>Appends interface type to the payment data.</h2>
	 *
	 * @param array $data <p>Payment data array.</p>
	 * @param bool $encode <p>Whether to JSON encode the interface type.</p>
	 *
	 * @return array <p>Payment data with interface type.</p>
	 * @since 5.0.6
	 */
	public static function append_interface_type( array $data, bool $encode = false ): array {
		return self::append_field(
			'interface_type',
			$encode ? wp_json_encode( ecommpay()->get_interface_type() ) : ecommpay()->get_interface_type(),
			$data
		);
	}

	/**
	 * <h2>Appends customer data.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 * @param array $data <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 5.0.6
	 */
	public static function append_customer_data( array $data, EcpGatewayOrder $order ): array {
		ecp_get_log()->info( __( 'Append customer data to the form data.', 'woo-ecommpay' ) );

		$data = self::append_customer_country( $data, $order );
		$data = self::append_field( 'customer_state', $order->get_billing_state(), $data );
		$data = self::append_field( 'customer_city', $order->get_billing_city(), $data );
		$data = self::append_field( 'customer_address', $order->get_billing_address(), $data );
		return self::append_field(
			'customer_zip',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$data
		);
	}

	/**
	 * используется в Кларна как отдельный аппенд, поэтому в отдельной функции
	 *
	 * @param EcpGatewayOrder $order
	 * @param array $data
	 *
	 * @return array
	 * @since 5.0.6
	 */
	public static function append_customer_country( array $data, EcpGatewayOrder $order ): array {
		return self::append_field( 'customer_country', $order->get_billing_country(), $data );
	}

	/**
	 * используется в Кларна как отдельный аппенд, поэтому в отдельной функции
	 *
	 * @param EcpGatewayOrder $order
	 * @param array $data
	 *
	 * @return array
	 * @since 5.0.6
	 */
	public static function append_billing_country( array $data, EcpGatewayOrder $order ): array {
		return self::append_field( 'billing_country', $order->get_billing_country(), $data );
	}

	public static function append_custom_variables( array $data, EcpGatewayOrder $order ): array {
		$data = self::append_customer_id( $data, $order );
		$data = self::append_field(
			'customer_phone',
			wc_format_phone_number( $order->get_billing_phone() ),
			$data
		);
		$data = self::append_field( 'customer_email', $order->get_billing_email(), $data );
		$data = self::append_field( 'customer_first_name', $order->get_billing_first_name(), $data );
		$data = self::append_field( 'customer_last_name', $order->get_billing_last_name(), $data );
		$data = self::append_customer_data( $data, $order );
		$data = self::append_billing_data( $data, $order );
		$data = self::append_shipping_data( $data, $order );
		$data = self::append_receipt_data( $data, $order, true );

		return self::append_avs_data( $data, $order );
	}

	public static function append_customer_id( array $data, EcpGatewayOrder $order ): array {
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$data = self::append_field( 'customer_id', $customer_id, $data );
		}

		return $data;
	}

	/**
	 * @param array $data
	 * @param EcpGatewayOrder|null $order
	 *
	 * @return array
	 * @since 5.0.6
	 */
	public static function append_operation_type( array $data, EcpGatewayOrder $order = null ): array {
		$operation_type = ecommpay()->get_general_option(
			EcpSettingsGeneral::PURCHASE_TYPE,
			EcpSettingsGeneral::PURCHASE_TYPE_SALE
		);

		if ( $operation_type === EcpSettingsGeneral::PURCHASE_TYPE_AUTH && EcpModuleCapture::is_auto_capture_needed( $order ) ) {
			$operation_type = EcpSettingsGeneral::PURCHASE_TYPE_SALE;
		}

		return self::append_field( 'operation_type', $operation_type, $data );
	}

	/**
	 * <h2>Appends billing information.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 * @param array $data <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 2.0.0
	 */
	public static function append_billing_data( array $data, EcpGatewayOrder $order ): array {
		$billing_state = $order->get_billing_state();

		if ( ! empty( $billing_state ) ) {
			$data['billing_region'] = $billing_state;
		}

		$normalized_code = EcpAddressHelper::normalizeRegionCode( $billing_state, $order->get_billing_country() );

		$data = self::append_field( 'billing_region_code', $normalized_code, $data );
		$data = self::append_billing_country( $data, $order );
		$data = self::append_field( 'billing_address', $order->get_billing_address(), $data );
		$data = self::append_field( 'billing_city', $order->get_billing_city(), $data );
		return self::append_field(
			'billing_postal',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$data
		);
	}

	public static function append_shipping_data( array $data, EcpGatewayOrder $order ): array {
		if ( ! $order->needs_shipping() ) {
			return $data;
		}

		$region       = $order->get_shipping_state();
		$country_code = $order->get_shipping_country();

		$address = EcpAddressHelper::extractShippingAddressFromOrder( $order );

		$shipping_info = array(
			'address'     => $address,
			'city'        => $order->get_shipping_city(),
			'country'     => $order->get_shipping_country(),
			'postal'      => $order->get_shipping_postcode(),
			'region'      => $region,
			'region_code' => EcpAddressHelper::normalizeRegionCode( $region, $country_code ),
		);

		$shipping_info = array_filter(
			$shipping_info,
			static function ( ?string $val ): bool {
				return null !== $val && '' !== $val;
			}
		);

		$is_card_payment  = $order->get_payment_method() === EcpWcPaymentMethods::CARD;
		$is_embedded_mode = ecommpay()->get_option( EcpSettings::OPTION_MODE, EcpSettings::MODE_REDIRECT ) === EcpSettings::MODE_EMBEDDED;

		if ( ! empty( $shipping_info ) ) {
			if ( $is_card_payment && $is_embedded_mode ) {
				$data['shipping'] = $shipping_info;
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- required for customer_shipping payload.
				$data['customer_shipping'] = base64_encode(
					wp_json_encode(
						array(
							'customer' => array(
								'shipping' => $shipping_info,
							),
						)
					)
				);
			}
		}

		return $data;
	}

	public static function append_receipt_data( array $data, EcpGatewayOrder $order, bool $encode = false ): array {
		$receipt_data = EcpReceiptDataExtractor::extract( $order );

		$receipt_data = self::filter_clean( $receipt_data );

		if ( count( $receipt_data ) <= 0 ) {
			return $data;
		}

		$receipt['receipt_data'] = $encode
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			? base64_encode( wp_json_encode( $receipt_data ) )
			: $receipt_data;

		return array_merge( $data, $receipt );
	}

	/**
	 * <h2>Cleans and returns form data.</h2>
	 * <p>The process of removing blank or empty arguments from form data.</p>
	 *
	 * @param array $data <p>ECOMMPAY Payment Page form data.</p>
	 *
	 * @return array <p>Cleaned up list of form data.</p>
	 * @since 2.0.0
	 */
	public static function filter_clean( array $data ): array {
		foreach ( $data as $key => $value ) {
			switch ( true ) {
				case $value === null:
				case is_string( $value ) && strlen( trim( $value ) ) <= 0:
					unset( $data[ $key ] );
					break;
			}
		}

		return $data;
	}

	/**
	 * <h2>Appends ECOMMPAY Payment Page AVS data (post code and street address).</h2>
	 * <p>Both AVS fields are added only if both values are present.
	 * If either field is missing, neither is sent to allow Payment Page to collect both.</p>
	 *
	 * @param array $data <p>Base array for appending data</p>
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public static function append_avs_data( array $data, EcpGatewayOrder $order ): array {
		$postcode = wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() );
		$address  = $order->get_billing_address();

		if ( $postcode && $address ) {
			$data = self::append_field( 'avs_post_code', $postcode, $data );
			$data = self::append_field( 'avs_street_address', $address, $data );
		}

		return $data;
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Display mode settings.</h2>
	 *
	 * @param ?string $value <p></p>
	 * @param array $data <p></p>
	 * @param boolean $missclick [optional] <p></p>
	 *
	 * @return array <p>Payment page display mode settings.</p>
	 * @since 5.0.6
	 */
	public static function append_display_mode( array $data, ?string $value, bool $missclick = false ): array {
		if ( $value !== EcpSettings::MODE_REDIRECT ) {
			$data = self::append_field( 'frame_mode', $value, $data );

			if ( $value === EcpSettings::MODE_IFRAME ) {
				$data = self::append_field( 'target_element', 'ecommpay-iframe', $data );
			} elseif ( $value === EcpSettings::MODE_EMBEDDED ) {
				$data = self::append_field( 'target_element', 'ecommpay-iframe-embedded', $data );
			} elseif ( $value === EcpSettings::MODE_POPUP && $missclick ) {
				$data = self::append_field( 'close_on_missclick', 1, $data );
			}
		}

		return $data;
	}

	/**
	 * <h2>Returns force payment mode if the order contains subscription.</h2>
	 *
	 * @param ?string $value
	 * @param array $data
	 *
	 * @return string[] <p>Payment page force mode settings.</p>
	 * @since 5.0.6
	 */
	public static function append_force_mode( array $data, ?string $value ): array {
		return self::append_field( 'force_payment_method', $value, $data );
	}

	/**
	 * <h2>Returns language code settings.</h2>
	 *
	 * @param array $data
	 *
	 * @return array <p>Payment page language settings.</p>
	 * @since 5.0.6
	 */
	public static function append_language( array $data ): array {
		switch ( ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_LANGUAGE, 'by_customer_browser' ) ) {
			case EcpSettingsGeneral::LANG_BY_CUSTOMER:
				return $data;
			case EcpSettingsGeneral::LANG_BY_WORDPRESS:
				$language_code = get_bloginfo( 'language' );
				if ( strpos( $language_code, '-' ) !== false ) {
					list( $language_code, ) = explode( '-', $language_code, 2 );
				}
				break;
			default:
				$language_code = ecommpay()->get_general_option(
					EcpSettingsGeneral::OPTION_LANGUAGE,
					EcpSettingsGeneral::LANG_ENGLISH
				);
		}

		return self::append_field( 'language_code', strtoupper( $language_code ), $data );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public static function append_merchant_callback_url( array $data ): array {
		return self::append_field( 'merchant_callback_url', ecp_callback_url(), $data );
	}

	/**
	 * @param ?string $value
	 * @param array $data
	 *
	 * @return array
	 */
	public static function append_merchant_success_url( array $data, ?string $value ): array {
		$data = self::append_field( 'merchant_success_enabled', 2, $data );
		$data = self::append_field( 'merchant_success_url', $value, $data );
		return self::append_field( 'merchant_success_redirect_mode', 'parent_page', $data );
	}

	/**
	 * @param ?string $value
	 * @param array $data
	 *
	 * @return array
	 */
	public static function append_merchant_fail_url( array $data, ?string $value ): array {
		$data = self::append_field( 'merchant_fail_enabled', 2, $data );
		$data = self::append_field( 'merchant_fail_url', $value, $data );
		return self::append_field( 'merchant_fail_redirect_mode', 'parent_page', $data );
	}

	/**
	 * @param ?string $value
	 * @param array $data
	 *
	 * @return array
	 */
	public static function append_merchant_return_url( array $data, ?string $value ): array {
		$data = self::append_field( 'merchant_return_enabled', 2, $data );
		$data = self::append_field( 'merchant_return_url', $value, $data );
		return self::append_field( 'merchant_return_redirect_mode', 'parent_page', $data );
	}

	/**
	 * @param array $data
	 * @param string|null $value
	 *
	 * @return array
	 */
	public static function append_redirect_return_url( array $data, ?string $value ): array {
		return self::append_field( 'redirect_return_url', $value, $data );
	}

	/**
	 * @param array $data
	 * @param string $url
	 *
	 * @return array
	 */
	public static function append_redirect_success_url( array $data, string $url ): array {
		$data = self::append_field( 'redirect_success_enabled', 2, $data );
		$data = self::append_field( 'redirect_success_mode', 'parent_page', $data );
		return self::append_field( 'redirect_success_url', $url, $data );
	}

	/**
	 * @param array $data
	 * @param $url
	 *
	 * @return array
	 */
	public static function append_redirect_fail_url( array $data, string $url ): array {
		$data = self::append_field( 'redirect_fail_enabled', 2, $data );
		$data = self::append_field( 'redirect_fail_mode', 'parent_page', $data );
		return self::append_field( 'redirect_fail_url', $url, $data );
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Subscription information.</h2>
	 *
	 * @param array $data
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 *
	 * @return array <p>An array of the recurring data if available, or an empty array.</p>
	 * @since 5.0.6
	 */
	public static function append_recurring( array $data, EcpGatewayOrder $order ): array {
		if ( ! ecp_subscription_is_active() ) {
			return $data;
		}

		switch ( true ) {
			case ecp_subscription_is_resubscribe( $order ):
				$subscriptions = ecp_get_subscriptions_for_resubscribe_order( $order );
				break;
			case $order->contains_subscription():
				$subscriptions = ecp_get_subscriptions_for_order( $order );
				break;
			default:
				return $data;
		}

		if ( empty( $subscriptions ) ) {
			return $data;
		}

		$amount = 0;

		foreach ( $subscriptions as $subscription ) {
			$amount += $subscription->get_total();
		}

		$recurring = array(
			'register' => true,
			'type'     => EcpGatewayRecurringTypes::AUTO,
			'amount'   => ecp_price_multiply( $amount, $order->get_currency() ),
		);

		$recurring = self::filter_clean( $recurring );

		$data = self::append_field( 'recurring', wp_json_encode( $recurring ), $data );
		return self::append_field( 'recurring_register', 1, $data );
	}

	/**
	 * <h2>Form data filter to add signature parameter.</h2>
	 *
	 * @param array $data <p>Incoming form data.</p>
	 *
	 * @return array <p>Filtered form data.</p>
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 5.0.6
	 */
	public static function append_signature( array $data ): array {
		return ecp_get_signer()->sign( $data );
	}
}
