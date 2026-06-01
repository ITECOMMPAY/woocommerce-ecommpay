<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_Admin class
 *
 * @class    ECP_Gateway_Settings_Page
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @abstract
 */
abstract class EcpSettings {
	private const TRANSLATIONS_HOOK = 'plugins_loaded';

	const OPTION_ENABLED              = 'enabled';
	const OPTION_TITLE                = 'title';
	const OPTION_SHOW_DESCRIPTION     = 'show_description';
	const OPTION_DESCRIPTION          = 'description';
	const OPTION_FORCE_CODE           = 'force';
	const OPTION_CHECKOUT_BUTTON_TEXT = 'checkout_button_text';
	const OPTION_MODE                 = 'pp_mode';
	const OPTION_POPUP_MISS_CLICK     = 'pp_close_on_miss_click';


	// ECOMMPAY Payment Page Display modes
	const MODE_REDIRECT = 'redirect';
	const MODE_POPUP    = 'popup';
	const MODE_IFRAME   = 'iframe';
	const MODE_EMBEDDED = 'embedded';

	const VALUE_ENABLED  = 'yes';
	const VALUE_DISABLED = 'no';
	const VALUE_CHECKED  = '1';

	const FIELD_ID             = 'id';
	const FIELD_TYPE           = 'type';
	const FIELD_TITLE          = 'title';
	const FIELD_DESC           = 'desc';
	const FIELD_DEFAULT        = 'default';
	const FIELD_GENERATE_VALUE = 'generate';
	const FIELD_TIP            = 'desc_tip';
	const FIELD_OPTIONS        = 'options';
	const FIELD_SUFFIX         = 'suffix';
	const FIELD_CLASS          = 'class';
	const FIELD_STYLE          = 'css';
	const FIELD_CUSTOM         = 'custom_attributes';
	const FIELD_PLACEHOLDER    = 'placeholder';
	const FIELD_ARGS           = 'args';

	const TYPE_START       = 'section_start';
	const TYPE_END         = 'section_end';
	const TYPE_DESCRIPTION = 'section_description';

	const TYPE_TOGGLE_START                  = 'toggle_start';
	const TYPE_TOGGLE_END                    = 'toggle_end';
	const TYPE_CHECKBOX                      = 'checkbox';
	const TYPE_NUMBER                        = 'number';
	const TYPE_PASSWORD                      = 'password';
	const TYPE_TEXT                          = 'text';
	const TYPE_AREA                          = 'textarea';
	const TYPE_DROPDOWN                      = 'select';
	const TYPE_MULTI_SELECT                  = 'multiselect';
	public const SETTINGS_TABS               = array(
		EcpSettingsGeneral::ID,
		EcpSettingsProducts::ID,
		EcpSettingsSubscriptions::ID,
	);
	public const CHECKBOXGROUP               = 'checkboxgroup';
	public const TYPE_MULTI_SELECT_COUNTRIES = 'multi_select_countries';
	public const TYPE_IMAGE_WIDTH            = 'image_width';
	public const TYPE_RELATIVE_DATE_SELECTOR = 'relative_date_selector';

	/**
	 * Setting page identifier.
	 *
	 * @var string
	 */
	protected string $id = '';

	/**
	 * Setting page label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Untranslated label key for lazy translation.
	 *
	 * @var string|null
	 */
	protected ?string $label_key = null;

	/**
	 * @var ?string
	 */
	protected ?string $icon = null;

	protected ?string $context = '';
	protected bool $visible    = true;
	protected bool $disabled   = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Set untranslated label if label_key is provided
		if ( null !== $this->label_key ) {
			$this->label = $this->label_key;
		}

		add_filter( 'ecp_settings_tabs_array', array( $this, 'add_settings_tab' ), 20 );
		add_action( 'ecp_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'ecp_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings page ID.
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get settings page label with lazy translation.
	 * @return string
	 */
	public function get_label(): string {
		// In WP-CLI context or before init, return untranslated value
		if ( null !== $this->label_key && ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! did_action( 'init' ) ) ) {
			return $this->label_key;
		}

		// After init in web context, return translated value
		if ( null !== $this->label_key && did_action( self::TRANSLATIONS_HOOK ) ) {
			return _x( $this->label_key, 'Settings page', 'woo-ecommpay' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}

		return $this->label;
	}

	/**
	 * Safe translation wrapper that respects init hook and WP-CLI context.
	 *
	 * @param string $text Text to translate.
	 * @param string $context Context for translation.
	 * @param string $domain Text domain.
	 * @return string Translated or original text.
	 */
	protected function safe_translate( string $text, string $context = '', string $domain = 'woo-ecommpay' ): string {
		// In WP-CLI context or before init, return untranslated value
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! did_action( self::TRANSLATIONS_HOOK ) ) {
			return $text;
		}

		// After init, translate
		if ( ! empty( $context ) ) {
			return _x( $text, $context, $domain ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain
		}

		return __( $text, $domain ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $pages
	 *
	 * @return array
	 */
	public function add_settings_tab( array $pages ): array {
		$pages[ $this->id ] = array(
			'label'    => $this->get_label(),
			'icon'     => $this->icon,
			'visible'  => $this->visible,
			'disabled' => $this->disabled,
		);

		return $pages;
	}

	/**
	 * Returns the fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		return apply_filters( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array() );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		ecommpay()->settings()->output_fields( $this );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified on the next line
		$nonce = wc_get_var( $_REQUEST['_wpnonce'] );

		if ( null === $nonce || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
			die( esc_html( __( 'Action failed. Please refresh the page and retry.', 'woo-ecommpay' ) ) );
		}

		ecp_get_log()->debug( 'Run saving plugin settings. Section:', $this->id );

		ecommpay()->settings()->save_fields( $this );
	}

	protected function fieldText( string $text ): ?string {
		return ecpL( $text, $this->context );
	}
}
