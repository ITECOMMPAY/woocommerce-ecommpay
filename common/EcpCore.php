<?php

namespace common;

defined( 'ABSPATH' ) || exit;

use common\gateways\EcpApplepay;
use common\gateways\EcpBanks;
use common\gateways\EcpBlik;
use common\gateways\EcpBrazilOnlineBanks;
use common\gateways\EcpCard;
use common\gateways\EcpDirectDebitBACS;
use common\gateways\EcpDirectDebitSEPA;
use common\gateways\EcpGateway;
use common\gateways\EcpGooglepay;
use common\gateways\EcpHumm;
use common\gateways\EcpIdeal;
use common\gateways\EcpKlarna;
use common\gateways\EcpMore;
use common\gateways\EcpPayPal;
use common\gateways\EcpPayPalPayLater;
use common\helpers\EcpGatewayAPIProtocol;
use common\includes\EcpCallbacksHandler;
use common\includes\EcpGatewayOrder;
use common\includes\EcpGatewayRefund;
use common\includes\EcpGatewaySubscription;
use common\install\EcpGatewayInstall;
use common\modules\EcpModuleAdminUI;
use common\modules\EcpModuleAuth;
use common\modules\EcpModuleCancel;
use common\modules\EcpModuleCapture;
use common\modules\EcpModulePaymentPage;
use common\modules\EcpModuleRefund;
use common\modules\EcpModuleSubscription;
use common\settings\EcpSettings;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsBanks;
use common\settings\EcpSettingsBlik;
use common\settings\EcpSettingsBrazilOnline_Banks;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsDirectDebitBACS;
use common\settings\EcpSettingsDirectDebitSEPA;
use common\settings\EcpSettingsGeneral;
use common\settings\EcpSettingsGooglepay;
use common\settings\EcpSettingsHumm;
use common\settings\EcpSettingsIdeal;
use common\settings\EcpSettingsKlarna;
use common\settings\EcpSettingsMore;
use common\settings\EcpSettingsPayPal;
use common\settings\EcpSettingsPayPalPayLater;
use common\settings\forms\EcpForm;
use WC_Settings_API;

final class EcpCore extends WC_Settings_API {

	/**
	 * <h2>Plugin version.</h2>
	 * <p>Sent into headers for open PP and Gate 2025 API.</p>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	public const WC_ECP_VERSION = '4.2.0';

	public const ECOMMPAY_PAYMENT_METHOD = 'ecommpay';

	/**
	 * @var string
	 */
	public $id = EcpCore::ECOMMPAY_PAYMENT_METHOD;
	/**
	 * @var ?EcpForm
	 */
	private ?EcpForm $form;
	/**
	 * @var ?EcpGateway[]
	 */
	private ?array $methods;

	/**
	 * <h2>Identifier for interface type.</h2>
	 *
	 * @var int
	 * @since 2.0.0
	 */
	private const INTERFACE_TYPE = 18;

	/**
	 * @var ?EcpCore
	 */
	private static ?EcpCore $instance = null;
	private static array $classes = [
		EcpCard::class,
		EcpApplepay::class,
		EcpGooglepay::class,
		EcpDirectDebitBACS::class,
		EcpDirectDebitSEPA::class,
		EcpBanks::class,
		EcpPayPal::class,
		EcpPayPalPayLater::class,
		EcpIdeal::class,
		EcpKlarna::class,
		EcpBlik::class,
		EcpHumm::class,
		EcpBrazilOnlineBanks::class,
		EcpMore::class,
	];

	/**
	 * <h2>Adds action links inside the plugin overview.</h2>
	 *
	 * @return array <p>Action link list.</p>
	 * @since 2.0.0
	 */
	public static function add_action_links( array $links ): array {
		return array_merge( [
			'<a href="' . ecp_settings_page_url() . '">' . __( 'Settings', 'woo-ecommpay' ) . '</a>',
		], $links );
	}

	/**
	 * <h2>Returns the ECOMMPAY external interface type.</h2>
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_interface_type(): array {
		return [
			'id' => self::INTERFACE_TYPE,
		];
	}

	/**
	 * <h2>Applies plugin hooks and filters.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function hooks(): void {
		EcpModuleAdminUI::get_instance();
		EcpModulePaymentPage::get_instance();
		EcpModuleRefund::get_instance();
		EcpModuleAuth::get_instance();
		EcpModuleCapture::get_instance();
		EcpModuleCancel::get_instance();
		EcpGatewayAPIProtocol::get_instance();

		$this->set_payment_methods();

		if ( ecp_subscription_is_active() ) {
			EcpModuleSubscription::get_instance();
		}

		add_action( 'woocommerce_api_wc_' . $this->id, [ EcpCallbacksHandler::class, 'handle' ] );

		$this->installation_hooks();

		add_filter(
			includes\filters\EcpFilters::FILTER_ACTION_LINKS,
			[ $this, 'add_action_links' ]
		);
	}

	public static function get_instance(): ?EcpCore {
		if ( ! self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	private function set_payment_methods(): void {
		$this->methods = [
			EcpSettingsCard::ID               => EcpCard::get_instance(),
			EcpSettingsPayPal::ID             => EcpPayPal::get_instance(),
			EcpSettingsPayPalPayLater::ID     => EcpPayPalPayLater::get_instance(),
			EcpSettingsKlarna::ID             => EcpKlarna::get_instance(),
			EcpSettingsBlik::ID               => EcpBlik::get_instance(),
			EcpSettingsIdeal::ID              => EcpIdeal::get_instance(),
			EcpSettingsBanks::ID              => EcpBanks::get_instance(),
			EcpSettingsHumm::ID => EcpHumm::get_instance(),
			EcpSettingsBrazilOnline_Banks::ID => EcpBrazilOnlineBanks::get_instance(),
			EcpSettingsGooglepay::ID          => EcpGooglepay::get_instance(),
			EcpSettingsApplepay::ID => EcpApplepay::get_instance(),
			EcpSettingsDirectDebitBACS::ID    => EcpDirectDebitBACS::get_instance(),
			EcpSettingsDirectDebitSEPA::ID    => EcpDirectDebitSEPA::get_instance(),
			EcpSettingsMore::ID               => EcpMore::get_instance(),
		];
	}

	/**
	 * <h2>Setup plugin installation hooks.</h2>
	 *
	 * @return void
	 * @since 3.0.0
	 */
	private function installation_hooks(): void {
		add_action( 'wp_ajax_ecommpay_run_data_upgrader', [ EcpGatewayInstall::get_instance(), 'ajax_run_upgrade' ] );
		add_action(
			'in_plugin_update_message-woocommerce-ecommpay/woocommerce-ecommpay.php',
			[ $this, 'in_plugin_update_message' ]
		);
	}

	/**
	 * <h2>Returns the merchant project identifier.</h2>
	 *
	 * @return int
	 * @since 3.0.0
	 */
	public function get_project_id(): int {
		return (int) ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_PROJECT_ID );
	}

	public function get_general_option( $key, $default = null ) {
		return $this->get_pm_option( EcpSettingsGeneral::ID, $key, $default );
	}

	public function get_pm_option( $payment_method, $key, $default = null ) {
		$settings = $this->get_option( $payment_method );

		// Get option default if unset.
		if ( ! isset ( $settings[ $key ] ) ) {
			$form_fields      = $this->get_form_fields();
			$settings[ $key ] = isset ( $form_fields[ $key ] ) ? $this->get_field_default( $form_fields[ $key ] ) : '';
		}

		return ! is_null( $default ) && in_array( $settings[ $key ], [
			'',
			EcpSettings::VALUE_DISABLED
		] ) ? $default : $settings[ $key ];
	}

	public function get_option( $key, $empty_value = [] ) {
		if ( empty ( $this->settings ) ) {
			$this->init_settings();
		}

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$this->settings = $this->settings()->get_default_settings();
		}

		return array_key_exists( $key, $this->settings )
			? $this->settings[ $key ]
			: $empty_value;
	}

	public function settings(): ?EcpForm {
		if ( empty ( $this->form ) ) {
			$this->form = EcpForm::get_instance();
		}

		return $this->form;
	}

	/**
	 * <h2>Output the admin options table.</h2>
	 * <p>Overrides the base function and renders an HTML-page.</p>
	 *
	 * @override
	 * @return void
	 * @since 2.0.0
	 */
	public function admin_options(): void {
		echo '<img src="' . ecp_img_url( 'ecommpay.svg' ) . '" alt="" class="ecp_logo right">';
		echo '<h2>ECOMMPAY';
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
		echo '</h2>';
		$this->settings()->output();
	}

	/**
	 * <h2>Show plugin changes. Code adapted from W3 Total Cache.</h2>
	 *
	 * @param $args
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function in_plugin_update_message( $args ): void {
		$upgrade_notice = '';
		echo wp_kses_post( $upgrade_notice );
	}

	public function get_payment_methods(): ?array {
		if ( empty ( $this->methods ) ) {
			$this->set_payment_methods();
		}

		return $this->methods;
	}

	public function get_payment_classnames(): array {
		return self::$classes;
	}

	public function update_pm_option( $payment_method, $key, $value = '' ): bool {
		$settings         = $this->get_option( $payment_method );
		$settings[ $key ] = $value;

		return $this->update_option( $payment_method, $settings );
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return bool
	 * @since 3.0.0
	 */
	public function update_option( $key, $value = '' ): bool {
		if ( empty ( $this->settings ) ) {
			$this->init_settings();
		}

		$this->settings[ $key ] = $value;

		return update_option(
			$this->get_option_key(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ),
			EcpSettings::VALUE_ENABLED
		);
	}

	/**
	 * @inheritDoc
	 * @override
	 * @return string
	 * @since 3.0.0
	 */
	public function get_option_key(): string {
		return $this->plugin_id . $this->id . '_settings';
	}

	/**
	 * <h2>Returns the redeclaration of the class name for the object type.</h2>
	 *
	 * @param string $classname <p>Base class name.</p>
	 * @param string $type <p>Object type.</p>
	 *
	 * @return string <p>Wrapped or base class name.</p>
	 * @since 3.0.0
	 */
	public function type_wrapper( string $classname, string $type ): string {
		switch ( $type ) {
			case EcpModuleSubscription::SHOP_ORDER:
				return EcpGatewayOrder::class;
			case EcpModuleSubscription::SHOP_ORDER_REFUND:
				return EcpGatewayRefund::class;
			case EcpModuleSubscription::SHOP_SUBSCRIPTION:
				return EcpGatewaySubscription::class;
			default:
				return $classname;
		}
	}
}
