<?php

namespace common\modules;

use common\exceptions\EcpGatewaySignatureException;
use common\gateways\EcpGateway;
use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayRegistry;
use common\includes\EcpGatewayFormHandler;
use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpApiFilterList;
use common\includes\filters\EcpAppendsFilterList;
use common\includes\filters\EcpFiltersList;
use common\includes\filters\EcpWCFilterList;
use Exception;
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


	/**
	 * <h2>ECOMMPAY Payment Page URL protocol.</h2>
	 *
	 * @const
	 * @var string
	 * @since 2.0.0
	 */
	private const PROTOCOL = 'https';

	/**
	 * <h2>ECOMMPAY Payment Page URL host name.</h2>
	 *
	 * @const
	 * @var string
	 * @since 2.0.0
	 */
	private const HOST = 'paymentpage.ecommpay.com';


	/**
	 * <h2>Stores line items to send to ECOMMPAY.</h2>
	 *
	 * @var array
	 * @since 2.0.0
	 */
	protected array $line_items = [];

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
		switch ( wc_get_var( $_REQUEST['action'] ) ) {
			case 'ecommpay_process':
				if ( wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'] ) !== null ) {
					ecp_get_log()->debug( __( 'Ecommpay checkout process', 'woo-ecommpay' ) );
					// Checkout page
					WC()->checkout()->process_checkout();
				} elseif ( wc_get_var( $_REQUEST['woocommerce-pay-nonce'] ) !== null ) {
					// Checkout pay page
					global $wp;
					$wp->set_query_var( 'order-pay', wc_get_var( $_REQUEST['order_id'], 0 ) );
					$_POST['payment_method'] = wc_get_post_data_by_key( 'payment_method', null );
					EcpGatewayFormHandler::pay_action();
				}
				break;
			case 'ecommpay_break':
				ecp_get_log()->debug( __( 'Ecommpay break process', 'woo-ecommpay' ) );
				$order_id = intval( wc_get_post_data_by_key( 'order_id', 0 ) );

				if ( $order_id > 0 ) {
					$order = ecp_get_order( $order_id );

					$result = [
						'redirect' => $order->get_checkout_payment_url(),
					];
					wp_send_json( $result );
				}
				break;
			case 'get_data_for_payment_form':
				$this->get_data_for_payment_form();
				break;
			case 'get_payment_status':
				$this->get_payment_status();
				break;
			case 'check_cart_amount':
				$this->check_cart_amount( wc_get_var( $_REQUEST['amount'], '0' ) );
				break;
		}
	}

	/**
	 * @throws EcpGatewaySignatureException
	 */
	private function get_data_for_payment_form() {
		if ( wc_get_var( $_GET['pay_for_order'], '' ) != "" && wc_get_var( $_GET['key'], '' ) != "" ) {
			$order_key        = wc_get_var( $_GET['key'], '' );
			$order_id         = wc_get_order_id_by_order_key( $order_key );
			$order            = ecp_get_order( $order_id );
			$payment_currency = $order->get_currency();
			$payment_amount   = ecp_price_multiply( $order->get_total(), $order->get_currency() );
			$order->set_payment_system( EcpGatewayOperationStatus::AWAITING_CUSTOMER );
		} else {
			$payment_currency = get_woocommerce_currency();
			$payment_amount   = ecp_price_multiply( WC()->cart->total, $payment_currency );
		}

		$data = [
			'mode' => $payment_amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY,
			'payment_amount'          => $payment_amount,
			'payment_currency'        => $payment_currency,
			'project_id'              => ecommpay()->get_project_id(),
			'payment_id'              => uniqid( 'wp_' ),
			'force_payment_method'    => 'card',
			'target_element'          => 'ecommpay-iframe-embedded',
			'frame_mode'              => 'iframe',
			'merchant_callback_url'   => ecp_callback_url(),
			'payment_methods_options' => "{\"additional_data\":{\"embedded_mode\":true}}",
		];

		$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_INTERFACE_TYPE, $data, true );

		$data = $this->append_recurring_total_form_cart( $data );

		if ( isset ( $order ) ) {
			$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_CARD_OPERATION_TYPE, $data, $order );
			$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_RECEIPT_DATA, $data, $order, true );
			$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_CUSTOMER_ID, $data, $order );
		} else {
			$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_CARD_OPERATION_TYPE, $data );
			$data = $this->append_receipt_data_from_cart( $data );
			if ( WC()->cart->get_customer()->id ) {
				$data['customer_id'] = WC()->cart->get_customer()->id;
			}
		}

		$data = apply_filters( EcpAppendsFilterList::ECP_APPEND_LANGUAGE_CODE, $data );

		ecp_debug( 'Payment page data: ', $data );

		$data = EcpSigner::get_instance()->sign( $data );
		wp_send_json( $data );
	}

	private function append_recurring_total_form_cart( $data ) {
		if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$data['recurring']          = '{"register":true,"type":"U"}';
			$data['recurring_register'] = 1;
		}

		return $data;
	}

	private function append_receipt_data_from_cart( $data ) {
		$cart                 = WC()->cart;
		$totalTax             = abs( $cart->get_totals()['total_tax'] );
		$totalPrice           = abs( floatval( $cart->get_totals()['total'] ) );
		$receipt              = $totalTax > 0
			? [
				// Item positions.
				'positions'        => $this->get_positions( $cart ),
				// Total tax amount per payment.
				'total_tax_amount' => ecp_price_multiply( $totalTax, get_woocommerce_currency() ),
				'common_tax'       => $totalPrice !== $totalTax ? round( $totalTax * 100 / ( $totalPrice - $totalTax ), 2 ) : 0,
			]
			: [
				// Item positions.
				'positions' => $this->get_positions( $cart )
			];
		$data['receipt_data'] = base64_encode( json_encode( $receipt ) );

		return $data;
	}

	private function get_positions( $cart ): array {
		$positions = [];
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$positions[] = $this->get_receipt_position( $cart_item, get_woocommerce_currency() );
		}

		return $positions;
	}

	private function get_receipt_position( $item, $currency ): array {
		$product  = $item['data'];
		$quantity = abs( $item['quantity'] );

		$price       = abs( (float) $product->get_price() * (float) $item['quantity'] );
		$description = esc_attr( $product->name );
		$data        = [
			// Required. Amount of the positions.
			'amount' => ecp_price_multiply( $price, $currency ),
		];
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
			$data['tax'] = $price !== 0 ? round( $totalTax * 100 / $price, 2 ) : 0;
			// Tax amount for the position.
			$data['tax_amount'] = ecp_price_multiply( $totalTax, $currency );
		}

		return $data;
	}

	private function limit_length( $string, $limit = 127 ): string {
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

	private function get_payment_status() {
		$order_key = wc_get_var( $_GET['key'], '' );
		$order_id  = wc_get_order_id_by_order_key( $order_key );
		$order     = ecp_get_order( $order_id );
		$status    = $order->get_ecp_status();
		$statuses  = [
			EcpGatewayPaymentStatus::SUCCESS,
			EcpGatewayPaymentStatus::DECLINE,
			EcpGatewayPaymentStatus::EXPIRED,
			EcpGatewayPaymentStatus::INTERNAL_ERROR,
			EcpGatewayPaymentStatus::EXTERNAL_ERROR,
			EcpGatewayPaymentStatus::AWAITING_CONFIRMATION,
			EcpGatewayPaymentStatus::AWAITING_CUSTOMER,
			EcpGatewayPaymentStatus::AWAITING_CAPTURE
		];
		$data      = [
			'callback_received' => in_array( $status, $statuses ),
			'status'            => in_array( $status, [
				EcpGatewayPaymentStatus::SUCCESS,
				EcpGatewayPaymentStatus::AWAITING_CONFIRMATION,
				EcpGatewayPaymentStatus::AWAITING_CAPTURE
			] ),
		];
		wp_send_json( $data );
	}

	private function check_cart_amount( $query_amount ) {
		$query_amount = (int) $query_amount;
		$cart_amount  = ecp_price_multiply( WC()->cart->total, get_woocommerce_currency() );
		wp_send_json( [ 'amount_is_equal' => ( $query_amount === $cart_amount ) ] );
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

	public function include_new_checkout_scripts() {
		global $wp;

		try {
			if ( isset ( $wp->query_vars['order-pay'] ) && absint( $wp->query_vars['order-pay'] ) > 0 ) {
				$order_id = absint( $wp->query_vars['order-pay'] ); // The order ID
			} else {
				$order_id = is_wc_endpoint_url( 'order-pay' );
			}
		} catch ( Exception $e ) {
			$order_id = 0;
		}

		$url = ecp_payment_page()->get_url();

		// Ecommpay merchant bundle.
		wp_enqueue_script(
			'ecommpay_merchant_js',
			sprintf( '%s/shared/merchant.js', $url ),
			[],
			null
		);
		wp_enqueue_style(
			'ecommpay_merchant_css',
			sprintf( '%s/shared/merchant.css', $url ),
			[],
			null
		);

		$script_name = 'wc-ecommpay-blocks-integration';
		wp_register_script(
			$script_name,
			plugins_url( 'build/index.js', ECP_PLUGIN_PATH ),
			[
				'jquery',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			ecp_version(),
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $script_name );
		}
		wp_enqueue_script( $script_name );

		$gateways          = ( new WC_Payment_Gateways() )->get_available_payment_gateways();
		$filtered_gateways = array_keys( array_filter( $gateways, function ( $gateway ) {
			return strpos( $gateway->id, 'ecommpay-' ) === 0 && $gateway->enabled === "yes";
		} ) );

		wp_localize_script(
			$script_name,
			'ECP',
			[
				'ajax_url'      => admin_url( "admin-ajax.php" ),
				'origin_url'    => $url,
				'order_id'      => $order_id,
				'gateways'      => $filtered_gateways,
				'ecp_pay_nonce' => wp_create_nonce( 'woocommerce-process_checkout' ),
			]
		);
	}

	/**
	 * <h2></h2>
	 *
	 * @param string $content
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function append_iframe_container( string $content ): string {
		if ( ! is_checkout() ) {
			return $content;
		}

		return '<div id="ecommpay-loader"><div class="lds-ecommpay"><div></div><div></div><div></div></div></div>'
			   . '<div id="ecommpay-iframe"></div><div id="woocommerce_ecommpay_checkout_page">'
			   . '<div id="ecommpay-overlay-loader" class="blockUI blockOverlay ecommpay-loader-overlay" style="display: none;"></div>'
			   . $content . "</div>";
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
		return apply_filters( EcpAppendsFilterList::ECP_APPEND_SIGNATURE, $this->get_form_data( $order, $gateway ) );
	}

	/**
	 * <h2>Returns form data for ECOMMPAY Payment Page.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 * @param EcpGateway $gateway
	 *
	 * @return array <p>Form data.</p>
	 * @since 2.0.0
	 */
	private function get_form_data( EcpGatewayOrder $order, EcpGateway $gateway ): array {
		$return_url = esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) ) );
		$info = apply_filters( EcpFiltersList::ECP_CREATE_PAYMENT_INFO, $order );

		// General options
		$values = apply_filters( EcpFiltersList::ECP_CREATE_PAYMENT_DATA, $order );
		$values['baseUrl'] = $this->endpoint;

		// Set payment information
		foreach ( $info as $key => $value ) {
			$values[ 'payment_' . $key ] = $value;
		}

		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_LANGUAGE_CODE, $values );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_ADDITIONAL_VARIABLES, $values, $order );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_MERCHANT_SUCCESS_URL, $values, $return_url );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_MERCHANT_FAIL_URL, $values, $return_url );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_MERCHANT_RETURN_URL, $values, esc_url_raw( $order->get_checkout_payment_url() ) );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_MERCHANT_CALLBACK_URL, $values );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_REDIRECT_URL, $values, $return_url );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_GATEWAY_ARGUMENTS . $gateway->id, $values, $order );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_VERSIONS, $values );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_INTERFACE_TYPE, $values, true );
		$values = apply_filters( EcpAppendsFilterList::ECP_APPEND_CARD_OPERATION_TYPE, $values, $order );

		// Clean arguments and return
		return apply_filters( EcpFiltersList::ECP_PAYMENT_PAGE_CLEAN_PARAMETERS, $values );
	}

	public function wc_custom_redirect_after_purchase() {
		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		global $wp;
		// If order_id is defined
		if ( isset ( $wp->query_vars['order-received'] ) && absint( $wp->query_vars['order-received'] ) > 0 ):
			$order_key = wc_get_var( $_GET['key'], '' );
			$order_id  = wc_get_order_id_by_order_key( $order_key );
			$order     = ecp_get_order( $order_id );

			?>
			<script type="text/javascript">
				// order-receive page status (ty page or failed)
				const order_is_failed = <?= ( $order->get_status() == 'failed' ) ? 'true' : 'false' ?>;

				let result = {}

				function get_status() {
					jQuery.ajax({
						type: 'POST',
						url: '<?= admin_url( "admin-ajax.php" ) ?>' + window.location.search,
						data: [{ 'name': 'action', 'value': 'get_payment_status' }],
						dataType: 'json',
						success: function(response) {
							result = response
						},
						error: function() {
							console.log('Error while getting order complete status')
						},
					})
					if (result['callback_received']) {
						if (!(result['status'] ^ order_is_failed)) {
							location.reload()
						} else {
							let loader = jQuery('.blockUI')
							if (loader.length > 0) {
								loader[0].remove()
							}
							return
						}
					} else if ((jQuery('.blockUI').length < 1) && (document.getElementsByTagName('body').length > 0)) {
						document.getElementsByTagName('body')[0].innerHTML += '<div class="blockUI blockOverlay ecommpay-loader-overlay"></div>'
					}
					setTimeout(get_status, 400)
				}

				get_status()

			</script>
		<?php
		endif;
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	protected function init(): void {
		$this->endpoint = sprintf( '%s://%s', $this->get_protocol(), $this->get_host() );

		// register hooks for AJAX requests
		add_action( EcpApiFilterList::WP_AJAX_ECOMMPAY_PROCESS, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_ECOMMPAY_BREAK, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_ECOMMPAY_PROCESS, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_ECOMMPAY_BREAK, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access
		add_action( EcpApiFilterList::WP_AJAX_GET_DATA_FOR_PAYMENT_FORM, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_GET_DATA_FOR_PAYMENT_FORM, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access
		add_action( EcpApiFilterList::WP_AJAX_GET_PAYMENT_STATUS, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_GET_PAYMENT_STATUS, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access
		add_action( EcpApiFilterList::WP_AJAX_CHECK_CART_AMOUNT, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_CHECK_CART_AMOUNT, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access
		add_action( EcpApiFilterList::WP_AJAX_ADD_PAYMENT_ID_TO_ORDER, [ $this, 'ajax_process' ] ); // Authorised user
		add_action( EcpApiFilterList::WP_AJAX_NOPRIV_ADD_PAYMENT_ID_TO_ORDER, [
			$this,
			'ajax_process'
		] ); // Non-authorised user: Guest access


		// register hooks for display payment form on block-based checkout page
		add_action( EcpWCFilterList::WOOCOMMERCE_BLOCKS_ENQUEUE_CHECKOUT_BLOCK_SCRIPTS_BEFORE, [
			$this,
			'include_new_checkout_scripts'
		] );
		add_action( EcpFiltersList::ENQUEUE_BLOCK_EDITOR_ASSETS, [ $this, 'include_new_checkout_scripts' ] );

		// register hooks for additional container on checkout pages
		add_filter( EcpFiltersList::THE_CONTENT, [ $this, 'append_iframe_container' ] );

		add_action( EcpFiltersList::WP_HEAD, [ $this, 'wc_custom_redirect_after_purchase' ] );
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
