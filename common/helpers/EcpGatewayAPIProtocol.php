<?php

namespace common\helpers;

use common\EcpCore;
use common\exceptions\EcpGatewaySignatureException;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpAppendsFilters;
use common\includes\filters\EcpFilters;
use common\modules\EcpModuleCapture;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

class EcpGatewayAPIProtocol extends EcpGatewayRegistry {
	/**
	 * @param string $key
	 * @param mixed $value
	 * @param $values
	 *
	 * @return void
	 */
	private function append_argument( string $key, $value, &$values ): void {
		if ( $value === null ) {
			return;
		}

		$values[ $key ] = $value;
	}

	public function append_versions( $data ) {
		$this->append_argument( '_plugin_version', EcpCore::WC_ECP_VERSION, $data );
		$this->append_argument( '_wordpress_version', wp_version(), $data );
		$this->append_argument( '_woocommerce_version', wc_version(), $data );

		return $data;
	}

	public function append_project_id( $data ) {
		// Identifier of merchant project received from ECOMMPAY
		$this->append_argument( 'project_id', ecommpay()->get_project_id(), $data );

		return $data;
	}

	public function append_interface_type( $data, $encode = false ) {
		// ECOMMPAY internal interface type identifier
		$this->append_argument(
			'interface_type',
			$encode ? json_encode( ecommpay()->get_interface_type() ) : ecommpay()->get_interface_type(),
			$data
		);

		return $data;
	}

	/**
	 * <h2>Appends customer data.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 3.0.0
	 */
	public function append_customer_data( array $values, EcpGatewayOrder $order ): array {
		ecp_get_log()->info( __( 'Append customer data to the form data.', 'woo-ecommpay' ) );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_COUNTRY, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_STATE, $values, $order );
		$values = apply_filters( EcpFilters::ECP_APPEND_CUSTOMER_CITY, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ADDRESS, $values, $order );

		return apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ZIP, $values, $order );
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_id( array $values, EcpGatewayOrder $order ): array {
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$this->append_argument( 'customer_id', $customer_id, $values );
		}

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_phone( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument(
			'customer_phone',
			wc_format_phone_number( $order->get_billing_phone() ),
			$values
		);

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_email( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_email', $order->get_billing_email(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_first_name( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_first_name', $order->get_billing_first_name(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_last_name( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_last_name', $order->get_billing_last_name(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_country( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_country', $order->get_billing_country(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_state( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_state', $order->get_billing_state(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_city( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_city', $order->get_billing_city(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_address( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'customer_address', $order->get_billing_address(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_zip( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument(
			'customer_zip',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$values
		);

		return $values;
	}

	/**
	 * <h2>Appends ECOMMPAY Payment Page AVS data (post code and street address).</h2>
	 * <p>Both AVS fields are added only if both values are present.
	 * If either field is missing, neither is sent to allow Payment Page to collect both.</p>
	 *
	 * @param array $values <p>Base array for appending data</p>
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function append_avs_data( array $values, EcpGatewayOrder $order ): array {
		$postcode = wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() );
		$address  = $order->get_billing_address();

		if ( $postcode && $address ) {
			$this->append_argument( 'avs_post_code', $postcode, $values );
			$this->append_argument( 'avs_street_address', $address, $values );
		}

		return $values;
	}

	/**
	 * @param array $values
	 * @param EcpGatewayOrder|null $order
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_card_operation_type( array $values, EcpGatewayOrder $order = null ): array {
		$mode = ecommpay()->get_general_option(
			EcpSettingsGeneral::PURCHASE_TYPE,
			EcpSettingsGeneral::PURCHASE_TYPE_SALE
		);

		if ( $mode === EcpSettingsGeneral::PURCHASE_TYPE_AUTH && EcpModuleCapture::is_auto_capture_needed( $order ) ) {
			$mode = EcpSettingsGeneral::PURCHASE_TYPE_SALE;
		}

		$this->append_argument( 'card_operation_type', $mode, $values );

		return $values;
	}

	/**
	 * <h2>Appends billing information.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 2.0.0
	 */
	public function append_billing_data( array $values, EcpGatewayOrder $order ): array {
		$values = apply_filters( EcpFilters::ECP_APPEND_BILLING_ADDRESS, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_BILLING_CITY, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_BILLING_COUNTRY, $values, $order );

		return apply_filters( EcpAppendsFilters::ECP_APPEND_BILLING_POSTAL, $values, $order );
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_address( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'billing_address', $order->get_billing_address(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_city( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'billing_city', $order->get_billing_city(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_country( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument( 'billing_country', $order->get_billing_country(), $values );

		return $values;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_postal( array $values, EcpGatewayOrder $order ): array {
		$this->append_argument(
			'billing_postal',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$values
		);

		return $values;
	}

	/**
	 * @param array $values
	 *
	 * @return array
	 */
	public function append_merchant_callback_url( array $values ): array {
		$values['merchant_callback_url'] = ecp_callback_url();

		return $values;
	}

	/**
	 * @param ?string $value
	 * @param array $values
	 *
	 * @return array
	 */
	public function append_merchant_success_url( array $values, ?string $value ): array {
		$this->append_argument( 'merchant_success_enabled', 2, $values );
		$this->append_argument( 'merchant_success_url', $value, $values );
		$this->append_argument( 'merchant_success_redirect_mode', 'parent_page', $values );

		return $values;
	}

	/**
	 * @param ?string $value
	 * @param array $values
	 *
	 * @return array
	 */
	public function append_merchant_fail_url( array $values, ?string $value ): array {
		$this->append_argument( 'merchant_fail_enabled', 2, $values );
		$this->append_argument( 'merchant_fail_url', $value, $values );
		$this->append_argument( 'merchant_fail_redirect_mode', 'parent_page', $values );

		return $values;
	}

	/**
	 * @param ?string $value
	 * @param array $values
	 *
	 * @return array
	 */
	public function append_merchant_return_url( array $values, ?string $value ): array {
		$this->append_argument( 'merchant_return_enabled', 2, $values );
		$this->append_argument( 'merchant_return_url', $value, $values );
		$this->append_argument( 'merchant_return_redirect_mode', 'parent_page', $values );

		return $values;
	}

	/**
	 * @param array $values
	 * @param string|null $value
	 *
	 * @return array
	 */
	public function append_redirect_return_url( array $values, ?string $value ): array {
		$this->append_argument( 'redirect_return_url', $value, $values );

		return $values;
	}

	/**
	 * @param array $values
	 * @param $url
	 *
	 * @return array
	 */
	public function append_redirect_success_url( array $values, string $url ): array {
		$this->append_argument( 'redirect_success_enabled', 2, $values );
		$this->append_argument( 'redirect_success_mode', 'parent_page', $values );
		$this->append_argument( 'redirect_success_url', $url, $values );
		return $values;
	}

	/**
	 * @param array $values
	 * @param $url
	 *
	 * @return array
	 */
	public function append_redirect_fail_url( array $values, string $url ): array {
		$this->append_argument( 'redirect_fail_enabled', 2, $values );
		$this->append_argument( 'redirect_fail_mode', 'parent_page', $values );
		$this->append_argument( 'redirect_fail_url', $url, $values );
		return $values;
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Mode settings.</h2>
	 *
	 * @param ?string $value <p>Order for payment.</p>
	 * @param array $values
	 *
	 * @return array <p>Payment Page mode settings.</p>
	 * @since 3.0.0
	 */
	public function append_operation_mode( array $values, ?string $value ): array {
		$this->append_argument( 'mode', $value, $values );

		return $values;
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Display mode settings.</h2>
	 *
	 * @param ?string $value <p></p>
	 * @param array $values <p></p>
	 * @param boolean $missclick [optional] <p></p>
	 *
	 * @return array <p>Payment page display mode settings.</p>
	 * @since 3.0.0
	 */
	public function append_display_mode( array $values, ?string $value, bool $missclick = false ): array {
		if ( $value !== EcpSettings::MODE_REDIRECT ) {
			$this->append_argument( 'frame_mode', $value, $values );

			if ( $value === EcpSettings::MODE_IFRAME ) {
				$this->append_argument( 'target_element', 'ecommpay-iframe', $values );
			} elseif ( $value === EcpSettings::MODE_EMBEDDED ) {
				$this->append_argument( 'target_element', 'ecommpay-iframe-embedded', $values );
			} elseif ( $value === EcpSettings::MODE_POPUP && $missclick ) {
				$this->append_argument( 'close_on_missclick', 1, $values );
			}
		}

		return $values;
	}

	/**
	 * <h2>Returns force payment mode if the order contains subscription.</h2>
	 *
	 * @param ?string $value
	 * @param array $values
	 *
	 * @return string[] <p>Payment page force mode settings.</p>
	 * @since 3.0.0
	 */
	public function append_force_mode( array $values, ?string $value ): array {
		$this->append_argument( 'force_payment_method', $value, $values );

		return $values;
	}

	/**
	 * <h2>Returns language code settings.</h2>
	 *
	 * @return string[] <p>Payment page language settings.</p>
	 * @since 2.0.0
	 */
	public function append_language( $values ): array {
		switch ( ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_LANGUAGE, 'by_customer_browser' ) ) {
			case EcpSettingsGeneral::LANG_BY_CUSTOMER:
				return $values;
			case EcpSettingsGeneral::LANG_BY_WORDPRESS:
				$lang = get_bloginfo( "language" );
				if ( strpos( $lang, '-' ) !== false ) {
					list( $lang, ) = explode( '-', $lang, 2 );
				}
				break;
			default:
				$lang = ecommpay()->get_general_option(
					EcpSettingsGeneral::OPTION_LANGUAGE,
					EcpSettingsGeneral::LANG_ENGLISH
				);
		}

		$this->append_argument( 'language_code', strtoupper( $lang ), $values );

		return $values;
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page custom variables.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 *
	 * @return array <p>Payment page custom settings.</p>
	 * @since 2.0.0
	 */
	public function append_custom_variables( $values, EcpGatewayOrder $order ): array {
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ID, $values, $order );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_PHONE, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_EMAIL, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_FIRST_NAME, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_LAST_NAME, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_DATA, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_BILLING_DATA, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_RECEIPT_DATA, $values, $order, true );

		return apply_filters( EcpAppendsFilters::ECP_APPEND_AVS_DATA, $values, $order );
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Subscription information.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 *
	 * @return array <p>An array of the recurring data if available, or an empty array.</p>
	 * @since 2.0.0
	 */
	public function append_recurring( $values, EcpGatewayOrder $order ): array {
		if ( ! ecp_subscription_is_active() ) {
			return $values;
		}

		switch ( true ) {
			case ecp_subscription_is_resubscribe( $order ):
				$subscriptions = ecp_get_subscriptions_for_resubscribe_order( $order );
				break;
			case $order->contains_subscription():
				$subscriptions = ecp_get_subscriptions_for_order( $order );
				break;
			default:
				return $values;
		}

		if ( count( $subscriptions ) <= 0 ) {
			return $values;
		}

		$amount = 0;

		foreach ( $subscriptions as $subscription ) {
			$amount += $subscription->get_total();
		}

		$recurring = [
			'register' => true,
			'type' => EcpGatewayRecurringTypes::AUTO,
			'amount' => ecp_price_multiply( $amount, $order->get_currency() ),
		];

		$this->filter_clean( $recurring );

		$this->append_argument( 'recurring', json_encode( $recurring ), $values );
		$this->append_argument( 'recurring_register', 1, $values );

		return $values;
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
	public function filter_clean( array $data ): array {
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
	 * <h2>Form data filter to add signature parameter.</h2>
	 *
	 * @param array $data <p>Incoming form data.</p>
	 *
	 * @return array <p>Filtered form data.</p>
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	public function append_signature( array $data ): array {
		return ecp_get_signer()->sign( $data );
	}

	/**
	 * <h2>Appends ECOMMPAY Payment Page Receipt data.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 * @param bool $encode [optional] <p>Encode JSON-object as base64 string. By default - none.</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function filter_receipt_data( array $values, EcpGatewayOrder $order, bool $encode = false ): array {
		$data = $this->receipt_data( $order );

		apply_filters( EcpFilters::ECP_PAYMENT_PAGE_CLEAN_PARAMETERS, $data );

		if ( count( $data ) <= 0 ) {
			return $values;
		}

		$receipt['receipt_data'] = $encode
			? base64_encode( json_encode( $data ) )
			: $data;

		return array_merge( $values, $receipt );
	}

	/**
	 * <h2>Returns receipt data by abstract order.</h2>
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function receipt_data( WC_Order $order ): array {
		$totalTax   = abs( $order->get_total_tax() );
		$totalPrice = abs( $order->get_total() );

		return $totalTax > 0
			? [
				// Item positions.
				'positions'        => $this->get_positions( $order ),
				// Total tax amount per payment.
				'total_tax_amount' => ecp_price_multiply( $totalTax, $order->get_currency() ),
				'common_tax'       => $totalPrice !== $totalTax ? round( $totalTax / ( $totalPrice - $totalTax ), 2 ) : 0,
			]
			: [
				// Item positions.
				'positions' => $this->get_positions( $order )
			];
	}

	/**
	 * <h2>Returns order positions for receipt.</h2>
	 *
	 * @param WC_Order $order <p>Order for payment.</p>
	 *
	 * @return array
	 */
	private function get_positions( WC_Order $order ): array {
		$positions = [];

		foreach ( $order->get_items() as $item ) {
			$positions[] = $this->get_receipt_position( $item, $order->get_currency() );
		}

		return $positions;
	}

	/**
	 * <h2>Returns position for receipt.</h2>
	 *
	 * @param string $currency <p>Current currency.</p>
	 * @param WC_Order_Item $item <p>Order item - product, subscription etc.</p>
	 *
	 * @return array
	 */
	private function get_receipt_position( WC_Order_Item $item, string $currency ): array {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return [];
		}

		$quantity    = abs( $item->get_quantity() );
		$price       = abs( $item->get_total() );
		$description = esc_attr( $item->get_name() );

		$data = [
			// Required. Amount of the positions.
			'amount' => ecp_price_multiply( $quantity > 0 ? $price / $quantity : $price, $currency ),
		];

		if ( $quantity > 0 ) {
			// Quantity of the goods or services. Multiple of: 0.000001.
			$data['quantity'] = $quantity;
		}

		if ( strlen( $description ) > 0 ) {
			// Goods or services description. >= 1 characters<= 255 characters.
			$data['description'] = $this->limit_length( $description, 255 );
		}

		$totalTax = abs( $item->get_total_tax() );

		if ( $totalTax > 0 ) {
			// Tax percentage for the position. Multiple of: 0.01.
			$data['tax'] = $price !== 0 ? round( $totalTax / $price, 2 ) : 0;
			// Tax amount for the position.
			$data['tax_amount'] = ecp_price_multiply( $totalTax / $quantity, $currency );
		}

		return $data;
	}

	/**
	 * <h2>Crops and returns string.</h2>
	 *
	 * @param string $string <p>Original string.</p>
	 * @param integer $limit <p>Limit size in characters.</p>
	 *
	 * @return string <p>Cropped string.</p>
	 */
	private function limit_length( string $string, int $limit = 127 ): string {
		$str_limit = $limit - 3;

		if ( function_exists( 'mb_strimwidth' ) ) {
			return mb_strlen( $string ) > $limit
				? mb_strimwidth( $string, 0, $str_limit ) . '...'
				: $string;
		}

		return strlen( $string ) > $limit
			? substr( $string, 0, $str_limit ) . '...'
			: $string;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	protected function init(): void {
		// register filters for appending payment arguments
		add_filter( EcpAppendsFilters::ECP_APPEND_PROJECT_ID, [ $this, 'append_project_id' ] );
		add_filter( EcpAppendsFilters::ECP_APPEND_INTERFACE_TYPE, [ $this, 'append_interface_type' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, [
			$this,
			'append_card_operation_type'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_OPERATION_MODE, [ $this, 'append_operation_mode' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, [ $this, 'append_force_mode' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_DISPLAY_MODE, [ $this, 'append_display_mode' ], 10, 3 );
		add_filter( EcpAppendsFilters::ECP_APPEND_LANGUAGE_CODE, [ $this, 'append_language' ] );
		add_filter( EcpAppendsFilters::ECP_APPEND_MERCHANT_SUCCESS_URL, [
			$this,
			'append_merchant_success_url'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_MERCHANT_FAIL_URL, [ $this, 'append_merchant_fail_url' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_REDIRECT_RETURN_URL, [
			$this,
			'append_redirect_return_url'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_MERCHANT_RETURN_URL, [
			$this,
			'append_merchant_return_url'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_REDIRECT_SUCCESS_URL, [
			$this,
			'append_redirect_success_url'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_REDIRECT_FAIL_URL, [ $this, 'append_redirect_fail_url' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_MERCHANT_CALLBACK_URL, [ $this, 'append_merchant_callback_url' ] );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_DATA, [ $this, 'append_customer_data' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ID, [ $this, 'append_customer_id' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_PHONE, [ $this, 'append_customer_phone' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_EMAIL, [ $this, 'append_customer_email' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_LAST_NAME, [
			$this,
			'append_customer_last_name'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_FIRST_NAME, [
			$this,
			'append_customer_first_name'
		], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_COUNTRY, [ $this, 'append_customer_country' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_STATE, [ $this, 'append_customer_state' ], 10, 2 );
		add_filter( EcpFilters::ECP_APPEND_CUSTOMER_CITY, [ $this, 'append_customer_city' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ADDRESS, [ $this, 'append_customer_address' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ZIP, [ $this, 'append_customer_zip' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_AVS_DATA, [ $this, 'append_avs_data' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_BILLING_DATA, [ $this, 'append_billing_data' ], 10, 2 );
		add_filter( EcpFilters::ECP_APPEND_BILLING_ADDRESS, [ $this, 'append_billing_address' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_BILLING_CITY, [ $this, 'append_billing_city' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_BILLING_COUNTRY, [ $this, 'append_billing_country' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_BILLING_POSTAL, [ $this, 'append_billing_postal' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_ADDITIONAL_VARIABLES, [
			$this,
			'append_custom_variables'
		], 10, 2 );
		add_filter( EcpFilters::ECP_PAYMENT_PAGE_CLEAN_PARAMETERS, [ $this, 'filter_clean' ] );
		add_filter( EcpAppendsFilters::ECP_APPEND_RECURRING, [ $this, 'append_recurring' ], 10, 2 );
		add_filter( EcpAppendsFilters::ECP_APPEND_RECEIPT_DATA, [ $this, 'filter_receipt_data' ], 10, 3 );
		add_filter( EcpAppendsFilters::ECP_APPEND_VERSIONS, [ $this, 'append_versions' ] );
		add_filter( EcpAppendsFilters::ECP_APPEND_SIGNATURE, [ $this, 'append_signature' ] );
	}

}
