<?php

namespace common\gateways;

use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpApiFilters;
use common\includes\filters\EcpAppendsFilters;
use common\includes\filters\EcpWCFilters;
use common\modules\EcpModuleSubscription;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;
use common\settings\forms\EcpForm;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  2.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
abstract class EcpGateway extends WC_Payment_Gateway {

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
	private static ?EcpGateway $_instance = null;

	public $id = EcpSettingsGeneral::ID;

	public $supports = '';

	/**
	 * <h2>Returns a new instance, if it does not already exist.</h2>
	 *
	 * @return static
	 * @since 3.0.1
	 */
	public static function get_instance(): EcpGateway {
		if ( null === self::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	public function __construct() {
		$this->has_fields        = false;
		$this->title = $this->get_option( EcpSettings::OPTION_TITLE );
		$this->order_button_text = $this->get_option( EcpSettings::OPTION_CHECKOUT_BUTTON_TEXT );
		$this->enabled = $this->get_option( EcpSettings::OPTION_ENABLED );
		$this->icon              = $this->get_icon();

		if ( $this->is_enabled( EcpSettings::OPTION_SHOW_DESCRIPTION ) ) {
			$this->description = $this->get_option( EcpSettings::OPTION_DESCRIPTION );
		}

		add_action( EcpWCFilters::WOOCOMMERCE_UPDATE_OPTIONS_PAYMENT_GATEWAYS . $this->id, [
			EcpForm::get_instance(),
			'save'
		] );
		add_filter( EcpAppendsFilters::ECP_APPEND_GATEWAY_ARGUMENTS . $this->id, [
			$this,
			'apply_payment_args'
		], 10, 2 );
		add_filter( EcpApiFilters::ECP_API_REFUND_ENDPOINT_PREFIX . $this->id, [ $this, 'get_refund_endpoint' ] );
	}


	/**
	 * @inheritDoc
	 * @override
	 * @return string | null DOM element img as a string
	 * @since 3.0.0
	 */
	public function get_icon(): ?string {
		if ( ! $icon_path = $this->get_icon_path() ) {
			return null;
		}

		$icon_str = sprintf(
			'<img src="%s" style="max-width: 50px" alt="%s" />',
			$icon_path,
			static::PAYMENT_METHOD
		);

		return apply_filters( 'woocommerce_gateway_icon', $icon_str, $this->id );
	}

	public function get_icon_path(): ?string {
		if ( defined( static::class . '::ICON_NAME' ) ) {
			$image_file_name = static::ICON_NAME . '.svg';
		} elseif ( defined( static::class . '::PAYMENT_METHOD' ) ) {
			$image_file_name = static::PAYMENT_METHOD . '.svg';
		} else {
			return null;
		}

		return ecp_img_url( $image_file_name );
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
		return $this->get_option( $value, EcpSettings::VALUE_DISABLED ) === EcpSettings::VALUE_ENABLED;
	}

	/**
	 * <h2>Init settings for gateways.</h2>
	 *
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	public function init_settings(): void {
		$this->settings = ecommpay()->get_option( $this->id );
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param array $values
	 *
	 * @return array
	 */
	public function apply_payment_args( array $values, EcpGatewayOrder $order ): array {
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
	final public function init_form_fields(): void {
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
	final public function generate_settings_html( $form_fields = [], $echo = true ): void {
	}

	/**
	 * <h2>Output the admin options table.</h2>
	 * <p>Overrides the base function and renders an HTML-page.</p>
	 *
	 * @override
	 * @return void
	 * @since 3.0.0
	 */
	public function admin_options(): void {
		echo '<img src="' . ecp_img_url( 'ecommpay.svg' ) . '" alt="" class="ecp_logo right">';
		echo '<h2>' . esc_html( $this->get_method_title() );
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';
		EcpForm::get_instance()->output();
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

	protected function init_subscription() {
		// WooCommerce Subscriptions hooks/filters
		if ( ! ecp_subscription_is_active() ) {
			return;
		}

		// On scheduled subscription
		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			[ EcpModuleSubscription::get_instance(), 'scheduled_subscription_payment' ],
			10,
			2
		);

		// On cancelled subscription
		add_action(
			'woocommerce_subscription_cancelled_' . $this->id,
			[ EcpModuleSubscription::get_instance(), 'subscription_cancellation' ]
		);

		// On updated subscription
		add_action(
			'woocommerce_subscription_payment_method_updated_to_' . $this->id,
			[
				EcpModuleSubscription::get_instance(),
				'on_subscription_payment_method_updated_to_ecommpay'
			],
			10,
			2
		);

		add_action(
			'woocommerce_subscription_validate_payment_meta_' . $this->id,
			[
				EcpModuleSubscription::get_instance(),
				'woocommerce_subscription_validate_payment_meta'
			],
			10,
			2
		);
	}
}
