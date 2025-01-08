<?php

namespace common\settings;

use common\includes\filters\EcpFiltersList;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_Admin class
 *
 * @class    ECP_Gateway_Settings_Page
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @abstract
 * @internal
 */
abstract class EcpSettings {
	const OPTION_ENABLED = 'enabled';
	const OPTION_TITLE = 'title';
	const OPTION_SHOW_DESCRIPTION = 'show_description';
	const OPTION_DESCRIPTION = 'description';
	const OPTION_FORCE_CODE = 'force';
	const OPTION_CHECKOUT_BUTTON_TEXT = 'checkout_button_text';
	const OPTION_MODE = 'pp_mode';
	const OPTION_POPUP_MISS_CLICK = 'pp_close_on_miss_click';


	// ECOMMPAY Payment Page Display modes
	const MODE_REDIRECT = 'redirect';
	const MODE_POPUP = 'popup';
	const MODE_IFRAME = 'iframe';
	const MODE_EMBEDDED = 'embedded';

	const VALUE_ENABLED = 'yes';
	const VALUE_DISABLED = 'no';
	const VALUE_CHECKED = '1';

	const FIELD_ID = 'id';
	const FIELD_TYPE = 'type';
	const FIELD_TITLE = 'title';
	const FIELD_DESC = 'desc';
	const FIELD_DEFAULT = 'default';
	const FIELD_TIP = 'desc_tip';
	const FIELD_OPTIONS = 'options';
	const FIELD_SUFFIX = 'suffix';
	const FIELD_CLASS = 'class';
	const FIELD_STYLE = 'css';
	const FIELD_CUSTOM = 'custom_attributes';
	const FIELD_PLACEHOLDER = 'placeholder';
	const FIELD_ARGS = 'args';

	const TYPE_START = 'section_start';
	const TYPE_END = 'section_end';
	const TYPE_DESCRIPTION = 'section_description';

	const TYPE_TOGGLE_START = 'toggle_start';
	const TYPE_TOGGLE_END = 'toggle_end';
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_NUMBER = 'number';
	const TYPE_PASSWORD = 'password';
	const TYPE_TEXT = 'text';
	const TYPE_AREA = 'textarea';
	const TYPE_DROPDOWN = 'select';
	const TYPE_MULTI_SELECT = 'multiselect';
	public const SETTINGS_TABS = [
		EcpSettingsGeneral::ID,
		EcpSettingsProducts::ID,
		EcpSettingsSubscriptions::ID
	];
	public const CHECKBOXGROUP = 'checkboxgroup';

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
	 * @var ?string
	 */
	protected ?string $icon = null;

	protected ?string $context = '';
	protected bool $visible = true;
	protected bool $disabled = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'ecp_settings_tabs_array', [ $this, 'add_settings_tab' ], 20 );
		add_action( 'ecp_settings_' . $this->id, [ $this, 'output' ] );
		add_action( 'ecp_settings_save_' . $this->id, [ $this, 'save' ] );
	}

	/**
	 * Get settings page ID.
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get settings page label.
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $pages
	 *
	 * @return array
	 */
	public function add_settings_tab( array $pages ): array {
		$pages[ $this->id ] = [
			'label'    => $this->label,
			'icon'     => $this->icon,
			'visible'  => $this->visible,
			'disabled' => $this->disabled,
		];

		return $pages;
	}

	/**
	 * Returns the fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		return apply_filters( EcpFiltersList::ECP_PREFIX_GET_SETTINGS . $this->id, [] );
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
		$nonce = wc_get_var( $_REQUEST['_wpnonce'] );

		if ( $nonce === null || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
			die ( __( 'Action failed. Please refresh the page and retry.', 'woo-ecommpay' ) );
		}

		ecp_get_log()->debug( 'Run saving plugin settings. Section:', $this->id );

		ecommpay()->settings()->save_fields( $this );
	}

	protected function fieldText( string $text ): ?string {
		return ecpL( $text, $this->context );
	}
}
