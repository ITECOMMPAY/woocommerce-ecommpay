<?php

namespace common\modules;

use common\exceptions\EcpGatewayInvalidArgumentException;
use common\exceptions\EcpGatewaySignatureException;
use common\gateways\EcpGateway;
use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayRegistry;
use common\helpers\EcpLoader;
use common\helpers\WCOrderStatus;
use common\includes\EcpGatewayFormHandler;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpApiFilters;
use common\includes\filters\EcpAppendsFilters;
use common\includes\filters\EcpFilters;
use common\includes\filters\EcpWCFilters;
use common\includes\filters\EcpWPFilters;
use common\settings\EcpSettings;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsGeneral;
use Exception;
use WC_Log_Levels;
use WC_Payment_Gateway;
use WC_Payment_Gateways;
use WC_Subscriptions_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Request generator to open ECOMMPAY Payment Page.</h2>
 *
 * @class    EcpModulePaymentPage
 * @version  2.0.0
 * @package  Ecp_Gateway/Modules
 * @category Class
 */
class EcpModulePaymentPage extends EcpGatewayRegistry {

	private const PROTOCOL = 'https';
	private const HOST     = 'paymentpage.ecommpay.com';

	private const SCRIPTS_VERSION            = null;  // must be null
	private const ORDER_RECEIVED_SCRIPT_NAME = 'ecommpay-order-received-script';


	private const FAILED_URI = '/checkout?payment_failed=1';

	private const FRAME_MODE_IFRAME   = 'iframe';
	private const FRAME_MODE_EMBEDDED = 'embedded';

	private const FORCE_PAYMENT_METHOD_CARD = 'card';
	private const TARGET_ELEMENT_EMBEDDED   = 'ecommpay-iframe-embedded';

	/**
	 * <h2>Stores line items to send to ECOMMPAY.</h2>
	 *
	 * @var array
	 * @since 2.0.0
	 */
	protected array $line_items = array();

	/**
	 * <h2>Endpoint for ECOMMPAY Payment Page.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	protected string $endpoint;


	/**
	 * <h2>Return payment page options for AJAX request.</h2>
	 *
	 * @throws Exception
	 * @throws EcpGatewaySignatureException
	 * @since 2.0.0
	 */
	public function ajax_process() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce nonce is checked via WC functions (woocommerce-process-checkout-nonce, woocommerce-pay-nonce)
		switch ( wc_get_var( $_REQUEST['action'] ) ) {
			case 'ecommpay_process':
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce nonce is checked via WC functions
				if ( wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'] ) !== null ) {
					ecp_get_log()->debug( __( 'Ecommpay checkout process', 'woo-ecommpay' ) );
					// Checkout page
					WC()->checkout()->process_checkout();
				} elseif (
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce nonce is checked via WC functions
					wc_get_var( $_REQUEST['woocommerce-pay-nonce'] ) !== null
				) {
					// Checkout pay page
					global $wp;
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce nonce is checked via WC functions
					$wp->set_query_var( 'order-pay', wc_get_var( $_REQUEST['order_id'], 0 ) );
					$_POST['payment_method'] = wc_get_post_data_by_key( 'payment_method', null );
					EcpGatewayFormHandler::pay_action();
				}
				break;
			case 'ecommpay_break':
				ecp_get_log()->debug( __( 'Ecommpay break process', 'woo-ecommpay' ) );
				$order_id = intval( wc_get_post_data_by_key( 'order_id', 0 ) );

				if ( $order_id <= 0 ) {
					break;
				}

				$order = ecp_get_order( $order_id );

				if ( ! $order ) {
					ecp_get_log()->warning( __( 'Order not found', 'woo-ecommpay' ), array( 'order_id' => $order_id ) );
					wp_send_json_error( array( 'message' => __( 'Order not found', 'woo-ecommpay' ) ), 404 );
					break;
				}

				if ( ! $this->verify_order_ownership( $order ) ) {
					wp_send_json_error( array( 'message' => __( 'Access denied', 'woo-ecommpay' ) ), 403 );
					break;
				}

				$result = array(
					'redirect' => $order->get_checkout_payment_url(),
				);
				wp_send_json( $result );
				break;
			case 'get_data_for_payment_form':
				if ( self::is_modern_embedded_mode() ) {
					$this->get_data_for_payment_form();
				} else {
					$this->get_legacy_data_for_payment_form();
				}
				break;
			case 'get_payment_status':
				$this->get_payment_status();
				break;
			case 'check_cart_amount':
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX endpoint with WooCommerce security
				$this->check_cart_amount( wc_get_var( $_REQUEST['amount'], '0' ) );
				break;
		}
	}

	/**
	 * Verify order ownership to prevent IDOR vulnerability.
	 *
	 * @param EcpGatewayOrder $order Order to verify.
	 * @return bool True if user has access, false otherwise.
	 * @since 3.x.x
	 */
	private function verify_order_ownership( EcpGatewayOrder $order ): bool {
		$order_id          = $order->get_id();
		$current_user_id   = get_current_user_id();
		$order_customer_id = $order->get_customer_id();

		if ( $current_user_id > 0 ) {
			// Logged-in user: verify order belongs to them.
			if ( $order_customer_id !== $current_user_id ) {
				ecp_get_log()->warning(
					__( 'Access denied: user tried to access another user\'s order', 'woo-ecommpay' ),
					array(
						'user_id'     => $current_user_id,
						'order_id'    => $order_id,
						'customer_id' => $order_customer_id,
					)
				);
				return false;
			}
		} else {
			// Guest: verify order belongs to current session.
			$session_order_id = WC()->session ? WC()->session->get( 'order_awaiting_payment' ) : null;
			if ( $order_id !== $session_order_id ) {
				ecp_get_log()->warning(
					__( 'Access denied: guest tried to access order not in current session', 'woo-ecommpay' ),
					array(
						'order_id'         => $order_id,
						'session_order_id' => $session_order_id,
					)
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * <h2>Check if payment page v5 embedded mode is configured.</h2>
	 *
	 * @return bool True if card display mode is embedded and PP version is v5.
	 * @since 3.2.0
	 */
	public static function is_modern_embedded_mode(): bool {
		$card_settings        = ecommpay()->get_option( EcpSettingsCard::ID );
		$card_display_mode    = $card_settings[ EcpSettings::OPTION_MODE ] ?? EcpSettings::MODE_EMBEDDED;
		$payment_page_version = ecommpay()->get_general_option(
			EcpSettingsGeneral::OPTION_PAYMENT_PAGE_VERSION,
			EcpSettingsGeneral::PP_VERSION_LEGACY
		);

		return (
			$card_display_mode === EcpSettings::MODE_EMBEDDED &&
			$payment_page_version === EcpSettingsGeneral::PP_VERSION_MODERN
		);
	}

	/**
	 * <h2>Get base embedded form data shared by both embedded widget variants.</h2>
	 *
	 * @return array Base payment data with common parameters.
	 * @throws Exception
	 * @since 3.2.0
	 */
	private function get_base_embedded_data(): array {
		$order = $this->resolveOrderFromPaymentPage();
		if ( $order ) {
			$payment_currency = $order->get_currency();
			$payment_amount   = ecp_price_multiply( $order->get_total(), $order->get_currency() );
			$order->set_payment_system( EcpGatewayOperationStatus::AWAITING_CUSTOMER );
		} else {
			$payment_currency = get_woocommerce_currency();
			$payment_amount   = ecp_price_multiply( WC()->cart->total, $payment_currency );
		}

		$payment_id = generateNewPaymentId( $order ? $order : $this->getCreatedOrderForRePayment() );

		// Build base data array.
		$data = array(
			'mode'                  => $payment_amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY,
			'payment_amount'        => $payment_amount,
			'payment_currency'      => $payment_currency,
			'project_id'            => ecommpay()->get_project_id(),
			'payment_id'            => $payment_id,
			'force_payment_method'  => self::FORCE_PAYMENT_METHOD_CARD,
			'target_element'        => self::TARGET_ELEMENT_EMBEDDED,
			'merchant_callback_url' => ecp_callback_url(),
			// Note: _referrer is not needed here — merchant.js sets it automatically via config._referrer.
		);

		$data = apply_filters( EcpAppendsFilters::ECP_APPEND_INTERFACE_TYPE, $data, true );
		$data = $this->append_recurring_total_form_cart( $data );

		if ( isset( $order ) ) {
			$data = apply_filters( EcpAppendsFilters::ECP_APPEND_CARD_OPERATION_TYPE, $data, $order );
			$data = apply_filters( EcpAppendsFilters::ECP_APPEND_RECEIPT_DATA, $data, $order, true );
			$data = apply_filters( EcpAppendsFilters::ECP_APPEND_CUSTOMER_ID, $data, $order );
		} else {
			$data     = $this->append_receipt_data_from_cart( $data );
			$customer = WC()->cart->get_customer();
			if ( $customer->get_id() ) {
				$data['customer_id'] = $customer->get_id();
			}
		}

		return apply_filters( EcpAppendsFilters::ECP_APPEND_LANGUAGE_CODE, $data );
	}

	/**
	 * <h2>Returns payment page data for the legacy-embedded card form.</h2>
	 *
	 * @throws EcpGatewaySignatureException
	 * @throws Exception
	 * @since 3.2.0
	 */
	private function get_legacy_data_for_payment_form() {
		$data = $this->get_base_embedded_data();

		// Add legacy-specific parameters.
		$data['frame_mode']              = self::FRAME_MODE_IFRAME;
		$data['payment_methods_options'] = wp_json_encode(
			array(
				'additional_data' => array(
					'embedded_mode' => true,
				),
			)
		);
		$data                            = $this->append_operation_type( $data );

		$order = $this->resolveOrderFromPaymentPage();
		if ( $order ) {
			$data = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_TYPE, $data, $order );
		} else {
			$data = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_TYPE, $data );
		}

		ecp_debug( 'Payment page data (legacy): ', $data );

		$data = EcpSigner::get_instance()->sign( $data );
		wp_send_json( $data );
	}

	/**
	 * <h2>Returns payment page data for the embedded card form.</h2>
	 *
	 * @throws EcpGatewaySignatureException
	 * @throws Exception
	 * @since 3.2.0
	 */
	private function get_data_for_payment_form() {
		$data = $this->get_base_embedded_data();

		// Add embedded-specific parameters.
		$data['frame_mode']      = self::FRAME_MODE_EMBEDDED;
		$data['baseUrl']         = $this->endpoint;
		$data['merchant_domain'] = $this->get_merchant_domain();
		$data                    = $this->append_operation_type( $data );

		$data = apply_filters( EcpAppendsFilters::ECP_APPEND_VERSIONS, $data );

		ecp_debug( 'Payment page data (embedded): ', $data );

		$data = EcpSigner::get_instance()->sign( $data );
		wp_send_json( $data );
	}

	private function resolveOrderFromPaymentPage(): ?EcpGatewayOrder {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a public order page with safe GET parameters
		$order_key = wc_get_var( $_GET['key'], '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a public order page with safe GET parameters
		$pay_for_order = wc_get_var( $_GET['pay_for_order'], '' );
		if ( '' === $pay_for_order || '' === $order_key ) {
			return null;
		}
		$order_id = wc_get_order_id_by_order_key( $order_key );

		return ecp_get_order( $order_id );
	}

	private function getCreatedOrderForRePayment(): ?EcpGatewayOrder {
		if ( ! WC()->session->has_session() ) {
			return null;
		}
		$orderId = WC()->session->get( 'order_awaiting_payment' );
		if ( ! $orderId ) {
			return null;
		}

		return ecp_get_order( $orderId );
	}

	/**
	 * <h2>Append operation_type parameter for embedded mode.</h2>
	 *
	 * Supported by both embedded widget variants.
	 *
	 * @param array $data Payment data array.
	 *
	 * @return array Modified payment data.
	 * @since 3.2.0
	 */
	private function append_operation_type( array $data ): array {
		$purchase_type = ecommpay()->get_general_option(
			EcpSettingsGeneral::PURCHASE_TYPE,
			EcpSettingsGeneral::PURCHASE_TYPE_SALE
		);

		$data['operation_type'] = $purchase_type === EcpSettingsGeneral::PURCHASE_TYPE_AUTH
			? EcpSettingsGeneral::PURCHASE_TYPE_AUTH
			: EcpSettingsGeneral::PURCHASE_TYPE_SALE;

		return $data;
	}

	/**
	 * <h2>Get merchant domain for embedded mode.</h2>
	 *
	 * Extracts the domain name from the site URL.
	 *
	 * @return string Merchant domain name.
	 * @since 3.2.0
	 */
	private function get_merchant_domain(): string {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		return $domain ? $domain : '';
	}

	private function append_recurring_total_form_cart( $data ) {
		if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$data['recurring']          = '{"register":true,"type":"U"}';
			$data['recurring_register'] = 1;
		}

		return $data;
	}

	private function append_receipt_data_from_cart( $data ) {
		$cart       = WC()->cart;
		$totalTax   = abs( $cart->get_totals()['total_tax'] );
		$totalPrice = abs( floatval( $cart->get_totals()['total'] ) );
		$receipt    = $totalTax > 0
			? array(
				// Item positions.
				'positions'        => $this->get_positions( $cart ),
				// Total tax amount per payment.
				'total_tax_amount' => ecp_price_multiply( $totalTax, get_woocommerce_currency() ),
				'common_tax'       => $totalTax !== $totalPrice ? round( $totalTax * 100 / ( $totalPrice - $totalTax ), 2 ) : 0,
			)
			: array(
				// Item positions.
				'positions' => $this->get_positions( $cart ),
			);
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- base64_encode is required for receipt_data format
		$data['receipt_data'] = base64_encode( wp_json_encode( $receipt ) );

		return $data;
	}

	private function get_positions( $cart ): array {
		$positions = array();
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$positions[] = $this->get_receipt_position( $cart_item, get_woocommerce_currency() );
		}

		return $positions;
	}

	private function get_receipt_position( $item, $currency ): array {
		$product  = $item['data'];
		$quantity = abs( $item['quantity'] );

		$price       = abs( (float) $product->get_price() * (float) $item['quantity'] );
		$description = esc_attr( $product->get_name() );
		$data        = array(
			// Required. Amount of the positions.
			'amount' => ecp_price_multiply( $price, $currency ),
		);
		if ( $quantity > 0 ) {
			// Quantity of the goods or services. Multiple of: 0.000001.
			$data['quantity'] = $quantity;
		}
		if ( strlen( $description ) > 0 ) {
			// Goods or services description. >= 1 characters<= 255 characters.
			$data['description'] = $this->limit_length( $description, 255 );
		}

		$totalTax = abs( $item['line_tax'] );

		if ( $totalTax > 0 ) {
			// Tax percentage for the position. Multiple of: 0.01.
			$data['tax'] = 0 !== $price ? round( $totalTax * 100 / $price, 2 ) : 0;
			// Tax amount for the position.
			$data['tax_amount'] = ecp_price_multiply( $totalTax, $currency );
		}

		return $data;
	}

	private function limit_length( $text, $limit = 127 ): string {
		$str_limit = $limit - 3;

		if ( function_exists( 'mb_strimwidth' ) ) {
			return mb_strlen( $text ) > $limit
				? mb_strimwidth( $text, 0, $str_limit ) . '...'
				: $text;
		}

		return strlen( $text ) > $limit
			? substr( $text, 0, $str_limit ) . '...'
			: $text;
	}

	private function get_payment_status() {
		check_ajax_referer( 'ecommpay_manual_action', 'nonce' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a public order page with safe GET parameters
		$order_key = wc_get_var( $_GET['key'], '' );
		$order_id  = wc_get_order_id_by_order_key( $order_key );
		$order     = ecp_get_order( $order_id );
		$status    = $order->get_ecp_status();
		$statuses  = array(
			EcpGatewayPaymentStatus::SUCCESS,
			EcpGatewayPaymentStatus::DECLINE,
			EcpGatewayPaymentStatus::EXPIRED,
			EcpGatewayPaymentStatus::INTERNAL_ERROR,
			EcpGatewayPaymentStatus::EXTERNAL_ERROR,
			EcpGatewayPaymentStatus::AWAITING_CONFIRMATION,
			EcpGatewayPaymentStatus::AWAITING_CUSTOMER,
			EcpGatewayPaymentStatus::AWAITING_CAPTURE,
		);
		$data      = array(
			'callbackReceived' => in_array( $status, $statuses, true ),
			'isSuccessStatus'  => in_array(
				$status,
				array(
					EcpGatewayPaymentStatus::SUCCESS,
					EcpGatewayPaymentStatus::AWAITING_CONFIRMATION,
					EcpGatewayPaymentStatus::AWAITING_CAPTURE,
				),
				true
			),
		);
		wp_send_json( $data );
	}

	private function check_cart_amount( $query_amount ) {
		$query_amount = (int) $query_amount;
		$cart_amount  = ecp_price_multiply( WC()->cart->total, get_woocommerce_currency() );
		wp_send_json( array( 'amount_is_equal' => ( $query_amount === $cart_amount ) ) );
	}

	/**
	 * <h2>Returns the ECOMMPAY Payment page URL.</h2>
	 *
	 * @return string <p>Payment Page URL.</p>
	 * @since 2.0.0
	 */
	public function get_url(): string {
		return $this->endpoint;
	}

	/**
	 * <h2>Builds the full ECOMMPAY payment redirect URL.</h2>
	 *
	 * @param EcpGatewayOrder $order   <p>WooCommerce order.</p>
	 * @param mixed           $gateway <p>Gateway instance.</p>
	 *
	 * @return string <p>Full payment page URL with query string.</p>
	 */
	public function get_payment_url( EcpGatewayOrder $order, $gateway ): string {
		return $this->get_url() . '/payment?' . http_build_query( $this->get_request_url( $order, $gateway ) );
	}

	public function include_new_checkout_scripts() {
		global $wp;

		try {
			if ( isset( $wp->query_vars['order-pay'] ) && absint( $wp->query_vars['order-pay'] ) > 0 ) {
				$order_id = absint( $wp->query_vars['order-pay'] ); // The order ID
			} else {
				$order_id = is_wc_endpoint_url( 'order-pay' );
			}
		} catch ( Exception $e ) {
			$order_id = 0;
		}

		$url = ecp_payment_page()->get_url();

		// Ecommpay merchant bundle.
		// Version must be null: merchant.js detects its own base URL by scanning <script> tags
		// for a src ending in /merchant.js. A query string (?ver=...) breaks that detection.
		// Expected invalid behaviour: http://wordpress.test/checkout/null/payment 404 not found
		wp_enqueue_script(
			'ecommpay_merchant_js',
			sprintf( '%s/shared/merchant.js', $url ),
			array(),
			self::SCRIPTS_VERSION,
			true
		);
		wp_enqueue_style(
			'ecommpay_merchant_css',
			sprintf( '%s/shared/merchant.css', $url ),
			array(),
			self::SCRIPTS_VERSION
		);

		$script_name = 'wc-ecommpay-blocks-integration';
		wp_register_script(
			$script_name,
			plugins_url( 'build/index.js', ECP_PLUGIN_PATH ),
			array(
				'jquery',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			ecp_version(),
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $script_name );
		}
		wp_enqueue_script( $script_name );

		$gateways          = ( new WC_Payment_Gateways() )->get_available_payment_gateways();
		$filtered_gateways = array_keys(
			array_filter(
				$gateways,
				static function ( WC_Payment_Gateway $gateway ) {
					return strpos( $gateway->id, 'ecommpay-' ) === 0 && EcpSettings::VALUE_ENABLED === $gateway->enabled;
				}
			)
		);

		wp_localize_script(
			$script_name,
			'ECP',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'origin_url'    => $url,
				'order_id'      => $order_id,
				'gateways'      => $filtered_gateways,
				'ecp_pay_nonce' => wp_create_nonce( 'woocommerce-process_checkout' ),
				'log_level'     => ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_LOG_LEVEL, WC_Log_Levels::DEBUG ),
			)
		);
	}

	/**
	 * <h2>Returns ECOMMPAY request form data for an order.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 *
	 * @return array <p>Settings for the ECOMMPAY payment page.</p>
	 * </p>
	 * @since 2.0.0
	 */
	public function get_request_url( EcpGatewayOrder $order, $gateway ): array {
		return apply_filters( EcpAppendsFilters::ECP_APPEND_SIGNATURE, $this->get_form_data( $order, $gateway ) );
	}

	/**
	 * <h2>Returns form data for ECOMMPAY Payment Page.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 * @param EcpGateway      $gateway
	 *
	 * @return array <p>Form data.</p>
	 * @throws EcpGatewayInvalidArgumentException
	 * @since 2.0.0
	 */
	private function get_form_data( EcpGatewayOrder $order, EcpGateway $gateway ): array {
		$total = $order->get_total();
		if ( ! is_numeric( $total ) ) {
			throw new EcpGatewayInvalidArgumentException( 'order total', 'numeric', esc_html( gettype( $total ) ) );
		}

		$currency = $order->get_currency();
		if ( ! is_string( $currency ) || '' === $currency ) {
			throw new EcpGatewayInvalidArgumentException( 'order currency', 'non-empty string', esc_html( gettype( $currency ) ) );
		}

		$values = array(
			'baseUrl'          => $this->endpoint,
			'payment_id'       => $order->create_payment_id(),
			'payment_amount'   => ecp_price_multiply( abs( $total ), $currency ),
			'payment_currency' => $currency,
		);

		$return_url = esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) ) );
		$fail_url   = home_url( self::FAILED_URI );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_PROJECT_ID, $values );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_LANGUAGE_CODE, $values );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_ADDITIONAL_VARIABLES, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_MERCHANT_SUCCESS_URL, $values, $return_url );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_MERCHANT_FAIL_URL, $values, $fail_url );
		// todo: uncomment after Humm is fixed
		// $values = apply_filters( EcpAppendsFilters::ECP_APPEND_REDIRECT_RETURN_URL, $values, home_url( '/checkout' ) );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_MERCHANT_RETURN_URL, $values, esc_url_raw( $order->get_checkout_payment_url() ) );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_MERCHANT_CALLBACK_URL, $values );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_REDIRECT_SUCCESS_URL, $values, $return_url );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_REDIRECT_FAIL_URL, $values, $fail_url );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_GATEWAY_ARGUMENTS . $gateway->id, $values, $order );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_VERSIONS, $values );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_INTERFACE_TYPE, $values, true );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_TYPE, $values, $order );

		// Clean arguments and return
		return apply_filters( EcpFilters::ECP_PAYMENT_PAGE_CLEAN_PARAMETERS, $values );
	}

	public function wc_custom_redirect_after_purchase() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		global $wp;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_key = wc_get_var( $_GET['key'], '' );
		$order_id  = wc_get_order_id_by_order_key( $order_key );
		$order     = ecp_get_order( $order_id );
		if ( ! $order || ! $order->is_ecp() ) {
			return;
		}

		wp_enqueue_script(
			self::ORDER_RECEIVED_SCRIPT_NAME,
			ecp_js_url( 'order-received.js' ),
			array(),
			self::SCRIPTS_VERSION,
			true
		);

		wp_localize_script(
			self::ORDER_RECEIVED_SCRIPT_NAME,
			'ecpOrderReceivedData',
			array(
				'adminAjaxUrl'              => esc_url( admin_url( 'admin-ajax.php' ) ),
				'isCurrentPageFailedStatus' => WCOrderStatus::FAILED === $order->get_status(),
				'nonce'                     => wp_create_nonce( 'ecommpay_manual_action' ),
			)
		);

		$loader = new EcpLoader();
		$loader->append_loader_on_page();
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	protected function init(): void {
		$this->endpoint = sprintf( '%s://%s', $this->get_protocol(), $this->get_host() );

		// register hooks for AJAX requests
		add_action( EcpApiFilters::WP_AJAX_ECOMMPAY_PROCESS, array( $this, 'ajax_process' ) ); // Authorised user
		add_action( EcpApiFilters::WP_AJAX_ECOMMPAY_BREAK, array( $this, 'ajax_process' ) ); // Authorised user
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_ECOMMPAY_PROCESS,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_ECOMMPAY_BREAK,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access
		add_action( EcpApiFilters::WP_AJAX_GET_DATA_FOR_PAYMENT_FORM, array( $this, 'ajax_process' ) ); // Authorised user
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_GET_DATA_FOR_PAYMENT_FORM,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access
		add_action( EcpApiFilters::WP_AJAX_GET_PAYMENT_STATUS, array( $this, 'ajax_process' ) ); // Authorised user
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_GET_PAYMENT_STATUS,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access
		add_action( EcpApiFilters::WP_AJAX_CHECK_CART_AMOUNT, array( $this, 'ajax_process' ) ); // Authorised user
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_CHECK_CART_AMOUNT,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access
		add_action( EcpApiFilters::WP_AJAX_ADD_PAYMENT_ID_TO_ORDER, array( $this, 'ajax_process' ) ); // Authorised user
		add_action(
			EcpApiFilters::WP_AJAX_NOPRIV_ADD_PAYMENT_ID_TO_ORDER,
			array(
				$this,
				'ajax_process',
			)
		); // Non-authorised user: Guest access

		// register hooks for display payment form on block-based checkout page
		add_action(
			EcpWCFilters::WOOCOMMERCE_BLOCKS_ENQUEUE_CHECKOUT_BLOCK_SCRIPTS_BEFORE,
			array(
				$this,
				'include_new_checkout_scripts',
			)
		);
		add_action( EcpWPFilters::ENQUEUE_BLOCK_EDITOR_ASSETS, array( $this, 'include_new_checkout_scripts' ) );

		add_action( EcpWPFilters::WP_HEAD, array( $this, 'wc_custom_redirect_after_purchase' ) );

		add_action( EcpWPFilters::TEMPLATE_REDIRECT, array( $this, 'payment_fail_notice' ) );
	}

	/**
	 * @return void
	 */
	public function payment_fail_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a public checkout page with safe GET parameters
		if ( is_checkout() && isset( $_GET['payment_failed'] ) ) {
			wc_add_notice( __( 'Payment was declined. You can try another payment method.', 'woo-ecommpay' ), 'error' );
		}
	}

	/**
	 * <h2>Returns the ECOMMPAY Payment Page protocol name.</h2>
	 *
	 * @return string <p>ECOMMPAY Payment Page protocol name.</b>
	 * @since 2.0.0
	 */
	private function get_protocol(): string {
		$proto = getenv( 'ECP_PROTO' );

		return is_string( $proto ) ? $proto : self::PROTOCOL;
	}

	/**
	 * <h2>Returns the ECOMMPAY Payment Page host name.</h2>
	 *
	 * @return string <p>ECOMMPAY Payment Page host name.</p>
	 * @since 2.0.0
	 */
	private function get_host(): string {
		$host = getenv( 'ECP_PAYMENTPAGE_HOST' );

		return is_string( $host ) ? $host : self::HOST;
	}
}
