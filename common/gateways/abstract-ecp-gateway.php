<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  2.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
abstract class Ecp_Gateway extends WC_Payment_Gateway {

	protected const PROCESS_RESULT_SUCCESS = 'success';
	protected const MODE_PURCHASE = 'purchase';
	protected const MODE_CARD_VERIFY = 'card_verify';

	protected const SUPPORT_PRODUCTS = 'products';
	protected const SUPPORT_REFUNDS = 'refunds';
	protected const SUPPORT_SUBSCRIPTIONS = 'subscriptions';
	protected const SUPPORT_SUBSCRIPTION_CANCELLATION = 'subscription_cancellation';
	protected const SUPPORT_SUBSCRIPTION_REACTIVATION = 'subscription_reactivation';
	protected const SUPPORT_SUBSCRIPTION_SUSPENSION = 'subscription_suspension';
	protected const SUPPORT_SUBSCRIPTION_AMOUNT_CHANGES = 'subscription_amount_changes';
	protected const SUPPORT_SUBSCRIPTION_DATE_CHANGES = 'subscription_date_changes';
	protected const SUPPORT_MULTIPLE_SUBSCRIPTIONS = 'multiple_subscriptions';


	/**
	 * <h2>Instance of ECOMMPAY Gateway.</h2>
	 *
	 * @var ?Ecp_Gateway
	 * @since 3.0.1
	 */
	protected static ?Ecp_Gateway $_instance = null;

	public $id = Ecp_Gateway_Settings_General::ID;

	public $supports = '';

	public function __construct() {
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ Ecp_Form::get_instance(), 'save' ] );
		add_filter( 'ecp_append_gateway_arguments_' . $this->id, [ $this, 'apply_payment_args' ], 10, 2 );
		add_filter( 'ecp_api_refund_endpoint_' . $this->id, [ $this, 'get_refund_endpoint' ] );
	}

	/**
	 * <h2>Init settings for gateways.</h2>
	 *
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	public function init_settings() {
		$this->settings = ecommpay()->get_option( $this->id );
	}

	/**
	 * @param Ecp_Gateway_Order $order
	 * @param array $values
	 *
	 * @return array
	 */
	public function apply_payment_args( array $values, Ecp_Gateway_Order $order ): array {
		return $values;
	}

	/**
	 * @param $order
	 *
	 * @return string
	 * @since 3.0.0
	 */
	public function get_refund_endpoint( $order ): string {
		return static::REFUND_ENDPOINT ?? '';
	}

	/**
	 * <h2>Processes and saves options.</h2>
	 * <p>Overrides the base function and always return true.</p>
	 *
	 * @override
	 * @return bool
	 * @since 2.0.0
	 */
	public function process_admin_options(): bool {
		return true;
	}

	/**
	 * Checks if a setting options is enabled by checking on yes/no data.
	 *
	 * @param string $value
	 *
	 * @return bool
	 * @since 3.0.0
	 */
	final public function is_enabled( string $value ): bool {
		return $this->get_option( $value, Ecp_Gateway_Settings::NO ) === Ecp_Gateway_Settings::YES;
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return bool
	 * @since 3.0.0
	 */
	final public function update_option( $key, $value = '' ): bool {
		return ecommpay()->update_pm_option( $this->id, $key, $value );
	}

	/**
	 * @return string
	 * @since 3.0.0
	 */
	final public function get_option_key(): string {
		return ecommpay()->get_option_key();
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	final public function init_form_fields() {
		foreach ( ecommpay()->settings()->get_form_fields( $this->id ) as $field ) {
			$this->form_fields[ $field['id'] ] = $field;
		}
	}


	final public function get_form_fields(): array {
		if ( count( $this->form_fields ) <= 0 ) {
			$this->init_form_fields();
		}

		return $this->form_fields;
	}

	/**
	 * <h2>Generate Settings HTML.</h2>
	 * <p>Overrides the base function and does nothing.</p>
	 *
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	final public function generate_settings_html( $form_fields = [], $echo = true ) {
	}

	/**
	 * <h2>Output the admin options table.</h2>
	 * <p>Overrides the base function and renders an HTML-page.</p>
	 *
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	public function admin_options() {
		echo '<img src="' . ecp_img_url( 'ecommpay.svg' ) . '" alt="" class="ecp_logo right">';
		echo '<h2>' . esc_html( $this->get_method_title() );
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';
		Ecp_Form::get_instance()->output( $this->id );
	}

	/**
	 * Checks if the current cart contains only one subscription item that is not a renewal.
	 *
	 * @return bool
	 */
	protected function is_gateway_with_subscription_only(): bool {
		global $woocommerce;

		$cart = $woocommerce->cart;

		if ( ! isset( $cart ) || $cart->get_cart_contents_count() !== 1 ) {
			return false;
		}

		$cart_items = $cart->get_cart();
		$first_item = array_shift( $cart_items );

		$is_first_item_subscription = is_a( $first_item['data'], 'WC_Product_Subscription' )
		                              || is_a( $first_item['data'], 'WC_Product_Subscription_Variation' );

		return ( $is_first_item_subscription && ! isset( $first_item['subscription_renewal']['renewal_order_id'] ) );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return string | bool DOM element img as a string
	 * @since 3.0.0
	 */
	public function get_icon()
	{
		if ( !$icon_path = $this->get_icon_path() ) {
			return false;
		}

		$icon_str = sprintf(
			'<img src="%s" style="max-width: 50px" alt="%s" />',
			$icon_path,
			static::PAYMENT_METHOD
		);

		return apply_filters( 'woocommerce_gateway_icon', $icon_str, $this->id );
	}

	public function get_icon_path(): ?string
    {
		if (defined(static::class . '::ICON_NAME')) {
			$image_file_name = static::ICON_NAME . '.svg';
		} elseif (defined(static::class . '::PAYMENT_METHOD')){
			$image_file_name = static::PAYMENT_METHOD . '.svg';
		} else {
			return null;
		}
		return ecp_img_url($image_file_name);
    }
}
