<?php

class Ecp_Gateway_API_Protocol extends Ecp_Gateway_Registry {
	/**
	 * @param Ecp_Gateway_Order|Ecp_Gateway_Refund $order
	 *
	 * @return array
	 */
	public function create_payment_data( $order ): array {
		$data = apply_filters( 'ecp_append_project_id', [] );

		if ( $order instanceof Ecp_Gateway_Order ) {
			// Identifier of the payment, must be unique within the project.
			$this->append_argument( 'payment_id', $order->create_payment_id(), $data );
		} else if ( $order instanceof Ecp_Gateway_Refund ) {
			// Identifier of the payment, must be unique within the project.
			$this->append_argument( 'payment_id', $order->get_order()->get_payment_id(), $data );
		}

		return $data;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param $values
	 *
	 * @return void
	 */
	private function append_argument( string $key, $value, &$values ) {
		if ( $value === null ) {
			return;
		}

		$values[ $key ] = $value;
	}

	public function create_general_info( $order ) {
		$data = apply_filters( 'ecp_append_project_id', [] );

		switch ( true ) {
			case is_string( $order ):
				// Identifier of the request from ECOMMPAY payment platform.
				$this->append_argument( 'request_id', $order, $data );
				break;
			case $order instanceof Ecp_Gateway_Refund:
				// Identifier of the payment, must be unique within the project.
				$this->append_argument( 'payment_id', $order->get_order()->get_payment_id(), $data );
				break;
			case $order instanceof Ecp_Gateway_Order:
				// Identifier of the payment, must be unique within the project.
				$this->append_argument( 'payment_id', $order->get_payment_id(), $data );
				break;
		}

		return $data;
	}

	public function append_payment_section( $data, $order ) {
		$this->append_argument( 'payment', $this->create_payment_info( $order ), $data );

		return $data;
	}

	public function create_payment_info( $order ) {
		$payment = [];

		$this->append_argument(
			'amount',
			ecp_price_multiply( abs( $order->get_total() ), $order->get_currency() ),
			$payment
		);
		$this->append_argument( 'currency', $order->get_currency(), $payment );

		if ( $order instanceof Ecp_Gateway_Refund ) {
			// Refund comment. REQUIRED!!!
			$this->append_argument(
				'description',
				(string) $order->get_reason() !== ''
					? $order->get_reason()
					: sprintf( 'User %s create refund', wp_get_current_user()->ID ),
				$payment
			);
			// Refund ECOMMPAY identifier in WooCommerce.
			$this->append_argument( 'merchant_refund_id', $order->get_payment_id(), $payment );
		}

		return $payment;
	}

	public function append_versions( $data ) {
		$this->append_argument( '_plugin_version', Ecp_Core::WC_ECP_VERSION, $data );
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
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 3.0.0
	 */
	public function append_customer_data( $values, $order ) {
		ecp_get_log()->info( __( 'Append customer data to the form data.', 'woo-ecommpay' ) );

		$values = apply_filters( 'ecp_append_customer_country', $values, $order );
		$values = apply_filters( 'ecp_append_customer_state', $values, $order );
		$values = apply_filters( 'ecp_append_customer_city', $values, $order );
		$values = apply_filters( 'ecp_append_customer_address', $values, $order );

		return apply_filters( 'ecp_append_customer_zip', $values, $order );
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_id( $values, $order ) {
		$customer_id = $order->get_customer_id();
		if ( $customer_id ) {
			$this->append_argument( 'customer_id', $customer_id, $values );
		}

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_phone( $values, $order ) {
		$this->append_argument(
			'customer_phone',
			wc_format_phone_number( $order->get_billing_phone() ),
			$values
		);

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_email( $values, $order ) {
		$this->append_argument( 'customer_email', $order->get_billing_email(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_first_name( $values, $order ) {
		$this->append_argument( 'customer_first_name', $order->get_billing_first_name(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_last_name( $values, $order ) {
		$this->append_argument( 'customer_last_name', $order->get_billing_last_name(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_country( $values, $order ) {
		$this->append_argument( 'customer_country', $order->get_billing_country(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_state( $values, $order ) {
		$this->append_argument( 'customer_state', $order->get_billing_state(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_city( $values, $order ) {
		$this->append_argument( 'customer_city', $order->get_billing_city(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_address( $values, $order ) {
		$this->append_argument( 'customer_address', $order->get_billing_address(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_customer_zip( $values, $order ) {
		$this->append_argument(
			'customer_zip',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$values
		);

		return $values;
	}


	/**
	 * <h2>Appends ECOMMPAY Payment Page Avs zip.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function append_avs_post_code( $values, $order ) {
		$this->append_argument(
			'avs_post_code',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$values
		);

		return $values;
	}

	/**
	 * <h2>Appends ECOMMPAY Payment Page Avs address.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function append_avs_street_address( $values, $order ) {
		$this->append_argument( 'avs_street_address', $order->get_billing_address(), $values );

		return $values;
	}

	/**
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_card_operation_type( array $values ): array {
		$this->append_argument( 'card_operation_type', 'sale', $values );

		return $values;
	}

	/**
	 * <h2>Appends billing information.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array <p>Result of appending data as new array.</p>
	 * @since 2.0.0
	 */
	public function append_billing_data( array $values, Ecp_Gateway_Order $order ): array {
		$values = apply_filters( 'ecp_append_billing_address', $values, $order );
		$values = apply_filters( 'ecp_append_billing_city', $values, $order );
		$values = apply_filters( 'ecp_append_billing_country', $values, $order );

		return apply_filters( 'ecp_append_billing_postal', $values, $order );
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_address( $values, $order ) {
		$this->append_argument( 'billing_address', $order->get_billing_address(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_city( $values, $order ) {
		$this->append_argument( 'billing_city', $order->get_billing_city(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_country( $values, $order ) {
		$this->append_argument( 'billing_country', $order->get_billing_country(), $values );

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_postal( $values, $order ) {
		$this->append_argument(
			'billing_postal',
			wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ),
			$values
		);

		return $values;
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_region( $values, $order ) {
		$this->append_argument( 'billing_region', $order->get_billing_state(), $values );

		return $values;
	}


	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function append_billing_region_code( $values, $order ) {
		$this->append_argument(
			'billing_region_code',
			ecp_region_code( $order->get_billing_country(), $order->get_billing_state() ),
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
	public function append_merchant_fail_url( $values, $value ) {
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
	public function append_merchant_return_url( $values, $value ) {
		$this->append_argument( 'merchant_return_enabled', 2, $values );
		$this->append_argument( 'merchant_return_url', $value, $values );
		$this->append_argument( 'merchant_return_redirect_mode', 'parent_page', $values );

		return $values;
	}

	/**
	 * @param ?string $value
	 * @param array $values
	 *
	 * @return array
	 */
	public function append_redirect_url( $values, $url ) {
		$this->append_argument( 'redirect_success_enabled', 2, $values );
		$this->append_argument( 'redirect_success_mode', 'parent_page', $values );
		$this->append_argument( 'redirect_success_url', $url, $values );
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
	public function append_operation_mode( $values, $value ) {
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
	public function append_display_mode( $values, $value, $missclick = false ) {
		if ( $value !== Ecp_Gateway_Settings::MODE_REDIRECT ) {
			$this->append_argument( 'frame_mode', $value, $values );

			if ( $value === Ecp_Gateway_Settings::MODE_IFRAME ) {
				$this->append_argument( 'target_element', 'ecommpay-iframe', $values );
			} elseif ( $value === Ecp_Gateway_Settings::MODE_EMBEDDED ) {
				$this->append_argument( 'target_element', 'ecommpay-iframe-embedded', $values );
			} elseif ( $value === Ecp_Gateway_Settings::MODE_POPUP && $missclick ) {
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
	public function append_force_mode( $values, $value ) {
		$this->append_argument( 'force_payment_method', $value, $values );

		return $values;
	}

	/**
	 * <h2>Returns language code settings.</h2>
	 *
	 * @return string[] <p>Payment page language settings.</p>
	 * @since 2.0.0
	 */
	public function append_language( $values ) {
		switch ( ecommpay()->get_general_option( Ecp_Gateway_Settings_General::OPTION_LANGUAGE, 'by_customer_browser' ) ) {
			case Ecp_Gateway_Settings_General::LANG_BY_CUSTOMER:
				return $values;
			case Ecp_Gateway_Settings_General::LANG_BY_WORDPRESS:
				$lang = get_bloginfo( "language" );
				if ( strpos( $lang, '-' ) !== false ) {
					list( $lang, ) = explode( '-', $lang, 2 );
				}
				break;
			default:
				$lang = ecommpay()->get_general_option(
					Ecp_Gateway_Settings_General::OPTION_LANGUAGE,
					Ecp_Gateway_Settings_General::LANG_ENGLISH
				);
		}

		$this->append_argument( 'language_code', strtoupper( $lang ), $values );

		return $values;
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page custom variables.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
	 *
	 * @return array <p>Payment page custom settings.</p>
	 * @since 2.0.0
	 */
	public function append_custom_variables( $values, $order ) {
		$values = apply_filters( 'ecp_append_customer_id', $values, $order );

		$values = apply_filters( 'ecp_append_customer_phone', $values, $order );
		$values = apply_filters( 'ecp_append_customer_email', $values, $order );
		$values = apply_filters( 'ecp_append_customer_first_name', $values, $order );
		$values = apply_filters( 'ecp_append_customer_last_name', $values, $order );
		$values = apply_filters( 'ecp_append_customer_data', $values, $order );
		$values = apply_filters( 'ecp_append_billing_data', $values, $order );
		$values = apply_filters( 'ecp_append_receipt_data', $values, $order, true );
		$values = apply_filters( 'ecp_append_avs_post_code', $values, $order );

		return apply_filters( 'ecp_append_avs_street_address', $values, $order );
	}

	/**
	 * <h2>Returns ECOMMPAY Payment Page Subscription information.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
	 *
	 * @return array <p>An array of the recurring data if available, or an empty array.</p>
	 * @since 2.0.0
	 */
	public function append_recurring( $values, $order ) {
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
			'type'     => Ecp_Gateway_Recurring_Types::AUTO,
			'amount'   => ecp_price_multiply( $amount, $order->get_currency() ),
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
	public function filter_clean( array $data ) {
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
	 * @throws Ecp_Gateway_Signature_Exception <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see Ecp_Gateway_Signer::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	public function append_signature( array $data ) {
		return ecp_get_signer()->sign( $data );
	}

	/**
	 * <h2>Appends ECOMMPAY Payment Page Receipt data.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 * @param bool $encode [optional] <p>Encode JSON-object as base64 string. By default - none.</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function filter_receipt_data( $values, $order, $encode = false ) {
		$data = $this->receipt_data( $order );

		apply_filters( 'ecp_payment_page_clean_parameters', $data );

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
	public function receipt_data( $order ) {
		$totalTax   = abs( $order->get_total_tax() );
		$totalPrice = abs( $order->get_total() );

		return $totalTax > 0
			? [
				// Item positions.
				'positions'        => $this->get_positions( $order ),
				// Total tax amount per payment.
				'total_tax_amount' => ecp_price_multiply( $totalTax, $order->get_currency() ),
				'common_tax'       => round( $totalTax / ( $totalPrice - $totalTax ), 2 ),
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
	private function get_positions( $order ) {
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
			$data['tax'] = round( $totalTax / $price, 2 );
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
	private function limit_length( $string, $limit = 127 ) {
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
	 * <h2>Appends shipping information.</h2>
	 *
	 * @param Ecp_Gateway_Order $order <p>Order object.</p>
	 * @param array $values <p>Base array for appending data</p>
	 *
	 * @return array Result of appending data as new array.
	 */
	public function filter_shipping_data( $order, $values ) {
		if ( ! $order->needs_shipping_address() ) {
			return $values;
		}

		$shipping_args = [
			'type'           => $order->get_shipping_type(),
			'delivery_email' => $this->limit_length( $order->get_billing_email(), 255 ),
			'city'           => $this->limit_length( $order->get_shipping_city(), 50 ),
			'country'        => $order->get_shipping_country(),
			'address'        => $this->limit_length( $order->get_shipping_address(), 150 ),
			'postal'         => wc_format_postcode( $order->get_shipping_postcode(), $order->get_shipping_country() ),
			'region_code'    => ecp_region_code( $order->get_shipping_country(), $order->get_shipping_state() ),
			'name_indicator' => $order->get_shipping_name_indicator(),
		];

		apply_filters( 'ecp_payment_page_clean_parameters', $shipping_args );

		if ( count( $shipping_args ) <= 0 ) {
			return $values;
		}

		$values['customer_shipping'] = base64_encode( json_encode( [ 'customer' => [ 'shipping' => $shipping_args ] ] ) );

		return $values;
	}

	public function filter_cash_voucher_data( $values, $order ) {
		return $values;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	protected function init() {
		add_filter( 'ecp_create_general_data', [ $this, 'create_general_info' ] );
		add_filter( 'ecp_create_payment_data', [ $this, 'create_payment_data' ] );
		add_filter( 'ecp_create_payment_info', [ $this, 'create_payment_info' ] );

		// register filters for appending payment arguments
		add_filter( 'ecp_append_project_id', [ $this, 'append_project_id' ] );
		add_filter( 'ecp_append_interface_type', [ $this, 'append_interface_type' ], 10, 2 );
		add_filter( 'ecp_append_card_operation_type', [ $this, 'append_card_operation_type' ], 10, 2 );
		add_filter( 'ecp_append_operation_mode', [ $this, 'append_operation_mode' ], 10, 2 );
		add_filter( 'ecp_append_force_mode', [ $this, 'append_force_mode' ], 10, 2 );
		add_filter( 'ecp_append_display_mode', [ $this, 'append_display_mode' ], 10, 3 );
		add_filter( 'ecp_append_payment_section', [ $this, 'append_payment_section' ], 10, 2 );
		add_filter( 'ecp_append_language_code', [ $this, 'append_language' ] );
		add_filter( 'ecp_append_merchant_success_url', [ $this, 'append_merchant_success_url' ], 10, 2 );
		add_filter( 'ecp_append_merchant_fail_url', [ $this, 'append_merchant_fail_url' ], 10, 2 );
		add_filter( 'ecp_append_merchant_return_url', [ $this, 'append_merchant_return_url' ], 10, 2 );
		add_filter( 'ecp_append_redirect_url', [ $this, 'append_redirect_url' ], 10, 2 );
		add_filter( 'ecp_append_merchant_callback_url', [ $this, 'append_merchant_callback_url' ] );
		add_filter( 'ecp_append_customer_data', [ $this, 'append_customer_data' ], 10, 2 );
		add_filter( 'ecp_append_customer_id', [ $this, 'append_customer_id' ], 10, 2 );
		add_filter( 'ecp_append_customer_phone', [ $this, 'append_customer_phone' ], 10, 2 );
		add_filter( 'ecp_append_customer_email', [ $this, 'append_customer_email' ], 10, 2 );
		add_filter( 'ecp_append_customer_last_name', [ $this, 'append_customer_last_name' ], 10, 2 );
		add_filter( 'ecp_append_customer_first_name', [ $this, 'append_customer_first_name' ], 10, 2 );
		add_filter( 'ecp_append_customer_country', [ $this, 'append_customer_country' ], 10, 2 );
		add_filter( 'ecp_append_customer_state', [ $this, 'append_customer_state' ], 10, 2 );
		add_filter( 'ecp_append_customer_city', [ $this, 'append_customer_city' ], 10, 2 );
		add_filter( 'ecp_append_customer_address', [ $this, 'append_customer_address' ], 10, 2 );
		add_filter( 'ecp_append_customer_zip', [ $this, 'append_customer_zip' ], 10, 2 );
		add_filter( 'ecp_append_avs_post_code', [ $this, 'append_avs_post_code' ], 10, 2 );
		add_filter( 'ecp_append_avs_street_address', [ $this, 'append_avs_street_address' ], 10, 2 );
		add_filter( 'ecp_append_billing_data', [ $this, 'append_billing_data' ], 10, 2 );
		add_filter( 'ecp_append_billing_address', [ $this, 'append_billing_address' ], 10, 2 );
		add_filter( 'ecp_append_billing_city', [ $this, 'append_billing_city' ], 10, 2 );
		add_filter( 'ecp_append_billing_country', [ $this, 'append_billing_country' ], 10, 2 );
		add_filter( 'ecp_append_billing_postal', [ $this, 'append_billing_postal' ], 10, 2 );
		add_filter( 'ecp_append_billing_region', [ $this, 'append_billing_region' ], 10, 2 );
		add_filter( 'ecp_append_billing_region_code', [ $this, 'append_billing_region_code' ], 10, 2 );
		add_filter( 'ecp_append_additional_variables', [ $this, 'append_custom_variables' ], 10, 2 );
		add_filter( 'ecp_payment_page_clean_parameters', [ $this, 'filter_clean' ] );
		add_filter( 'ecp_append_recurring', [ $this, 'append_recurring' ], 10, 2 );
		add_filter( 'ecp_append_shipping_data', [ $this, 'filter_shipping_data' ], 10, 2 );
		add_filter( 'ecp_append_receipt_data', [ $this, 'filter_receipt_data' ], 10, 3 );
		add_filter( 'ecp_append_cash_voucher_data', [ $this, 'filter_cash_voucher_data' ], 10, 2 );
		add_filter( 'ecp_append_versions', [ $this, 'append_versions' ] );
		add_filter( 'ecp_append_signature', [ $this, 'append_signature' ] );
	}

}
