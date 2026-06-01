<?php

namespace common\gateways;

use common\exceptions\EcpGatewayLogicException;
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


	private const TRANSLATIONS_HOOK = 'plugins_loaded';

	protected const PROCESS_RESULT_SUCCESS = 'success';
	protected const MODE_PURCHASE          = 'purchase';
	protected const MODE_CARD_VERIFY       = 'card_verify';

	protected const SUPPORT_PRODUCTS                    = 'products';
	protected const SUPPORT_REFUNDS                     = 'refunds';
	protected const SUPPORT_SUBSCRIPTIONS               = 'subscriptions';
	protected const SUPPORT_SUBSCRIPTION_CANCELLATION   = 'subscription_cancellation';
	protected const SUPPORT_SUBSCRIPTION_REACTIVATION   = 'subscription_reactivation';
	protected const SUPPORT_SUBSCRIPTION_SUSPENSION     = 'subscription_suspension';
	protected const SUPPORT_SUBSCRIPTION_AMOUNT_CHANGES = 'subscription_amount_changes';
	protected const SUPPORT_SUBSCRIPTION_DATE_CHANGES   = 'subscription_date_changes';
	protected const SUPPORT_MULTIPLE_SUBSCRIPTIONS      = 'multiple_subscriptions';
	private const EMPTY_REFUND_ENDPOINT_PREFIX          = '';

	public string $payment_method = '';

	/**
	 * Singleton instance.
	 *
	 * @var EcpGateway|null
	 */
	private static ?EcpGateway $instance = null;

	public $id = EcpSettingsGeneral::ID;

	public $supports = array();

	/**
	 * Untranslated method title key for lazy translation.
	 *
	 * @var string|null
	 */
	protected ?string $method_title_key = null;

	/**
	 * Untranslated method description key for lazy translation.
	 *
	 * @var string|null
	 */
	protected ?string $method_description_key = null;

	/**
	 * Returns payment method code for API.
	 *
	 * @return string|null Payment method code or null for gateways without fixed method
	 * @since 3.0.0
	 */
	protected function get_payment_method_code(): ?string {
		return null;
	}

	/**
	 * Returns icon file name (without extension).
	 * By default, equals to payment_method_code.
	 *
	 * @return string|null Icon file name or null if no icon
	 * @since 3.0.0
	 */
	protected function get_icon_file_name(): ?string {
		return $this->get_payment_method_code();
	}

	/**
	 * Returns refund endpoint prefix for API.
	 *
	 * @return string Endpoint prefix or empty string for default endpoint
	 * @since 3.0.0
	 */
	protected function get_refund_endpoint_prefix(): string {
		return self::EMPTY_REFUND_ENDPOINT_PREFIX;
	}

	/**
	 * <h2>Returns a new instance, if it does not already exist.</h2>
	 *
	 * @return static
	 * @since 3.0.1
	 */
	public static function get_instance(): EcpGateway {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Get the method title with a lazy translation.
	 *
	 * @return string
	 */
	public function get_method_title(): string {
		// In WP-CLI context or before init, return untranslated value to avoid early translation loading
		if ( null !== $this->method_title_key && ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! did_action( self::TRANSLATIONS_HOOK ) ) ) {
			return $this->method_title_key;
		}

		// After init in web context, return translated value
		if ( null !== $this->method_title_key && did_action( self::TRANSLATIONS_HOOK ) ) {
			return __( $this->method_title_key, 'woo-ecommpay' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

		return parent::get_method_title();
	}

	/**
	 * Get the method description with lazy translation.
	 *
	 * @return string
	 */
	public function get_method_description(): string {
		// In WP-CLI context or before init, return untranslated value to avoid early translation loading
		if ( null !== $this->method_description_key && ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! did_action( self::TRANSLATIONS_HOOK ) ) ) {
			return $this->method_description_key;
		}

		// After init in web context, return translated value
		if ( null !== $this->method_description_key && did_action( self::TRANSLATIONS_HOOK ) ) {
			return __( $this->method_description_key, 'woo-ecommpay' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

		return parent::get_method_description();
	}

	/**
	* @throws EcpGatewayLogicException
	*/
	public function __construct() {

		$payment_method_code = $this->get_payment_method_code();
		if ( null !== $payment_method_code ) {
			$this->payment_method = $payment_method_code;
		}

		// Set untranslated values for method_title and method_description
		// They will be translated lazily via get_method_title() and get_method_description()
		if ( null !== $this->method_title_key ) {
			$this->method_title = $this->method_title_key;
		}
		if ( null !== $this->method_description_key ) {
			$this->method_description = $this->method_description_key;
		}

		$this->has_fields        = false;
		$this->title             = $this->get_option( EcpSettings::OPTION_TITLE, '' );
		$this->order_button_text = $this->get_option( EcpSettings::OPTION_CHECKOUT_BUTTON_TEXT, '' );
		$this->enabled           = $this->get_option( EcpSettings::OPTION_ENABLED, EcpSettings::VALUE_DISABLED );
		$this->icon              = $this->get_icon();

		if ( $this->is_enabled( EcpSettings::OPTION_SHOW_DESCRIPTION ) ) {
			$this->description = $this->get_option( EcpSettings::OPTION_DESCRIPTION, '' );
		}

		add_action(
			EcpWCFilters::WOOCOMMERCE_UPDATE_OPTIONS_PAYMENT_GATEWAYS . $this->id,
			array(
				EcpForm::get_instance(),
				'save',
			)
		);
		add_filter(
			EcpAppendsFilters::ECP_APPEND_GATEWAY_ARGUMENTS . $this->id,
			array(
				$this,
				'apply_payment_args',
			),
			10,
			2
		);

		add_filter( EcpApiFilters::ECP_API_REFUND_ENDPOINT_PREFIX . $this->id, array( $this, 'get_refund_endpoint' ) );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return string | null DOM element img as a string
	 * @since 3.0.0
	 */
	public function get_icon(): ?string {
		$icon_path = $this->get_icon_path();
		if ( ! $icon_path ) {
			return null;
		}

		$alt_text = $this->payment_method ? $this->payment_method : ( $this->get_payment_method_code() ?? '' );

		$icon_str = sprintf(
			'<img src="%s" style="max-width: 50px" alt="%s" />',
			$icon_path,
			$alt_text
		);

		return apply_filters( 'woocommerce_gateway_icon', $icon_str, $this->id );
	}

	public function get_icon_path(): ?string {
		$icon_file_name = $this->get_icon_file_name();
		if ( null === $icon_file_name ) {
			return null;
		}

		return ecp_img_url( $icon_file_name . '.svg' );
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
	 * Applies standard payment arguments common to all gateways.
	 *
	 * Handles operation mode selection (purchase vs card_verify based on amount),
	 * force mode (payment method code), and recurring settings.
	 *
	 * @param array           $values Payment arguments array.
	 * @param EcpGatewayOrder $order  Order object.
	 *
	 * @return array Modified payment arguments.
	 * @since 3.0.0
	 */
	protected function apply_standard_payment_args( array $values, EcpGatewayOrder $order ): array {
		$amount = ecp_price_multiply( $order->get_total(), $order->get_currency() );

		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_OPERATION_MODE, $values, $amount > 0 ? self::MODE_PURCHASE : self::MODE_CARD_VERIFY );
		$values = apply_filters( EcpAppendsFilters::ECP_APPEND_FORCE_MODE, $values, $this->get_payment_method_code() );
		return apply_filters( EcpAppendsFilters::ECP_APPEND_RECURRING, $values, $order );
	}

	/**
	 * @param array           $values
	 * @param EcpGatewayOrder $order
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
		return $this->get_refund_endpoint_prefix();
	}

	/**
	 * Common process_payment implementation for standard payment gateways
	 * that redirect to the ECOMMPAY payment page without a pre-existing payment_id.
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return array Result array for WooCommerce checkout.
	 */
	protected function process_standard_payment( int $order_id ): array {
		$order            = ecp_get_order( $order_id );
		$payment_page_url = ecp_payment_page()->get_payment_url( $order, $this );

		return array(
			'result'   => self::PROCESS_RESULT_SUCCESS,
			'redirect' => $payment_page_url,
			'order_id' => $order_id,
		);
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
	 * @param array $form_fields
	 * @param bool $echo
	 * @return void
	 * @since 3.0.0
	 */
	// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
	final public function generate_settings_html( $form_fields = array(), $echo = true ): void {
	}

	/**
	 * <h2>Output the admin options table.</h2>
	 * <p>Overrides the base function and renders an HTML-page.</p>
	 *
	 * @override
	 * @return void
	 * @throws EcpGatewayLogicException
	 * @since 3.0.0
	 */
	public function admin_options(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<img src="' . esc_url( ecp_img_url( 'ecommpay.svg' ) ) . '" alt="" class="ecp_logo right">';
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
		$cart = WC()->cart;

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
	 * @throws EcpGatewayLogicException
	 */
	protected function init_subscription(): void {
		// WooCommerce Subscriptions hooks/filters
		if ( ! ecp_subscription_is_active() ) {
			return;
		}

		// On scheduled subscription
		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			array( EcpModuleSubscription::get_instance(), 'scheduled_subscription_payment' ),
			10,
			2
		);

		// On cancelled subscription
		add_action(
			'woocommerce_subscription_cancelled_' . $this->id,
			array( EcpModuleSubscription::get_instance(), 'subscription_cancellation' )
		);

		// On updated subscription
		add_action(
			'woocommerce_subscription_payment_method_updated_to_' . $this->id,
			array(
				EcpModuleSubscription::get_instance(),
				'on_subscription_payment_method_updated_to_ecommpay',
			),
			10,
			2
		);

		add_action(
			'woocommerce_subscription_validate_payment_meta_' . $this->id,
			array(
				EcpModuleSubscription::get_instance(),
				'woocommerce_subscription_validate_payment_meta',
			),
			10,
			2
		);
	}
}
