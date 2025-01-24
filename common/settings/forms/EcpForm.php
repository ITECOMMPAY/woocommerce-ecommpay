<?php

namespace common\settings\forms;

use common\helpers\EcpGatewayRegistry;
use common\includes\filters\EcpFiltersList;
use common\includes\filters\EcpHtmlFilterList;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettings class
 *
 * @class    EcpSettings
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class EcpForm extends EcpGatewayRegistry {


	private ?EcpSettingsManager $ecp_settings_manager;
	private ?EcpFieldRenderer $ecp_field_renderer;
	private ?EcpTabManager $ecp_tab_manager;

	public function __construct() {
		$this->ecp_settings_manager = new EcpSettingsManager( $this );
		$this->ecp_field_renderer   = new EcpFieldRenderer();
		$this->ecp_tab_manager      = new EcpTabManager();

		parent::__construct();
	}

	/**
	 * Saving the settings.
	 */
	public function save() {
		$current_tab = $this->ecp_tab_manager->get_section();
		ecp_get_log()->debug( 'Run saving plugin settings. Section:', $current_tab );

		// Trigger actions
		do_action( 'ecp_settings_save_' . $current_tab );
		do_action( 'ecp_update_options_' . $current_tab );
		do_action( 'ecp_update_options' );
		wp_schedule_single_event( time(), 'ecp_flush_rewrite_rules' );
		do_action( 'ecp_settings_saved' );

		ecp_get_log()->info( 'Plugin settings successfully saved. Section:', $current_tab );
	}

	/**
	 * Display settings page.
	 */
	public function output() {
		$current_tab = $this->ecp_tab_manager->get_section();
		$suffix      = '';

		do_action( 'ecp_settings_start' );

		wp_enqueue_script(
			'ecp_settings',
			ecp_js_url( 'settings' . $suffix . '.js' ),
			[ 'jquery' ],
			ecp_version(),
			true
		);

		wp_localize_script(
			'ecp_settings',
			'ecp_settings_params',
			[
				'nav_warning' => __(
					'The changes you made will be lost if you navigate away from this page.',
					'woo-ecommpay'
				),
			]
		);

		ecp_get_view(
			'html-admin-settings.php',
			[
				'current_tab' => $current_tab,
				'tabs'        => apply_filters( 'ecp_settings_tabs_array', [] )
			]
		);
	}

	/**
	 * Display admin fields.
	 *
	 * Loops though the WooCommerce ECOMMPAY options array and outputs each field.
	 *
	 * @param EcpSettings $options Opens array to output.
	 */
	public function output_fields( EcpSettings $options ) {
		foreach ( $options->get_settings() as $value ) {
			if ( ! isset ( $value['type'] ) ) {
				continue;
			}

			$value = apply_filters( EcpFiltersList::ECP_FIELD_NORMALISATION, $value );
			do_action(
				'ecp_html_render_field_' . $value['type'],
				$this->get_general_rendering_options( $value, $options->get_id() )
			);
		}
	}

	/**
	 * Returns based form field array from setting options.
	 *
	 * @param array $value Setting options
	 * @param string $gateway Gateway
	 *
	 * @return array Form field as array
	 */
	private function get_general_rendering_options( array $value, string $gateway ): array {
		return [
			'id'          => $value[ EcpSettings::FIELD_ID ],
			'type'        => $value[ EcpSettings::FIELD_TYPE ],
			'title'                    => $value[ EcpSettings::FIELD_TITLE ],
			'tooltip'                  => $this->get_tooltip( $value ),
			'css'                      => $value[ EcpSettings::FIELD_STYLE ],
			'option_value'             => $this->get_option( $value, $gateway ),
			'options'                  => $value[ EcpSettings::FIELD_OPTIONS ],
			'class'                    => $value[ EcpSettings::FIELD_CLASS ],
			'custom_attributes'        => $this->get_custom_attributes( $value ),
			'description'              => $this->get_description( $value ),
			'placeholder'              => $value[ EcpSettings::FIELD_PLACEHOLDER ],
			'suffix'                   => $value[ EcpSettings::FIELD_SUFFIX ],
			EcpSettings::CHECKBOXGROUP => $value[ EcpSettings::CHECKBOXGROUP ],
		];
	}

	/**
	 * Returns the formatted tip HTML for a given form field.
	 * Plugins can call this when implementing their own custom settings types.
	 *
	 * @param array $value The form field value array.
	 *
	 * @return string The tip as a formatted string.
	 */
	private function get_tooltip( array $value ) {
		if ( true === $value[ EcpSettings::FIELD_TIP ] ) {
			return $value[ EcpSettings::FIELD_DESC ];
		}

		if ( ! empty ( $value[ EcpSettings::FIELD_TIP ] ) ) {
			return $value[ EcpSettings::FIELD_TIP ];
		}

		return '';
	}

	/**
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param array $value Option
	 * @param string $method Payment method
	 *
	 * @return string|array The value specified for the option or a default value for the option.
	 */
	private function get_option( array $value, string $method ) {
		return $this->ecp_settings_manager->get_option( $value, $method );
	}

	/**
	 * Initialise Settings.
	 *
	 * Store all settings in a single database entry
	 * and make sure the $settings array is either the default
	 * or the settings stored in the database.
	 *
	 * @since 1.0.0
	 * @uses get_option(), add_option()
	 */
	private function init_settings() {
		$this->ecp_settings_manager->init_settings();
	}

	/**
	 * <h2>Returns the default plugin settings.</h2>
	 *
	 * @return array
	 * @since 2.0.3
	 */
	public function get_default_settings(): array {
		return $this->ecp_settings_manager->get_default_settings();
	}


	private function get_custom_attributes( $value ): array {
		// Custom attribute handling.
		$custom_attributes = [];

		if (
			! empty ( $value[ EcpSettings::FIELD_CUSTOM ] )
			&& is_array( $value[ EcpSettings::FIELD_CUSTOM ] )
		) {
			foreach ( $value[ EcpSettings::FIELD_CUSTOM ] as $attribute => $attribute_value ) {
				$custom_attributes[ $attribute ] = $attribute_value;
			}
		}

		return $custom_attributes;
	}

	/**
	 * Returns formatted description for a given form field.
	 * Plugins can call this when implementing their own custom settings types.
	 *
	 * @param array $value The form field value array.
	 *
	 * @return string The description as a formatted string
	 */
	private function get_description( array $value ): string {
		if (
			true !== $value[ EcpSettings::FIELD_TIP ]
			&& ! empty ( $value[ EcpSettings::FIELD_DESC ] )
		) {
			return $value[ EcpSettings::FIELD_DESC ];
		}

		return '';
	}

	/**
	 * Save admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param EcpSettings $options Options array to output.
	 * @param array|null $data Optional. Data to use for saving. Defaults to $_POST.
	 *
	 * @return bool
	 */
	public function save_fields( EcpSettings $options, array $data = null ): bool {
		return $this->ecp_settings_manager->save_fields( $options, $data );
	}

	/**
	 * <h2>Returns the all form fields.</h2>
	 *
	 * @return array of options
	 * @since 2.0.3
	 */
	public function get_all_form_fields(): array {
		$fields = [];

		foreach ( $this->ecp_tab_manager->tabs as $tab ) {
			$fields = array_merge(
				$fields,
				apply_filters(
					'woocommerce_settings_api_form_fields_' . $tab->get_id(),
					array_map( [
						$this,
						'set_defaults'
					], apply_filters( EcpFiltersList::ECP_PREFIX_GET_SETTINGS . $tab->get_id(), [] ) )
				)
			);
		}

		return $fields;
	}

	/**
	 * Get the form fields after they are initialized.
	 *
	 * @return array of options
	 */
	public function get_form_fields( $current_tab = null ): array {
		if ( $current_tab === null ) {
			$current_tab = wc_get_var( $_REQUEST['section'] );
			$current_tab = $current_tab !== null ? sanitize_title( $current_tab ) : EcpSettingsGeneral::ID;
		}

		return apply_filters(
			'woocommerce_settings_api_form_fields_' . $current_tab,
			array_map( [
				$this,
				'set_defaults'
			], apply_filters( EcpFiltersList::ECP_PREFIX_GET_SETTINGS . $current_tab, [] ) )
		);
	}

	/**
	 * Set default required properties for each field.
	 *
	 * @param array $field Setting field array.
	 *
	 * @return array
	 */
	public function set_defaults( array $field ): array {
		if ( ! isset ( $field[ EcpSettings::FIELD_DEFAULT ] ) ) {
			$field[ EcpSettings::FIELD_DEFAULT ] = '';
		}

		return $field;
	}

	/**
	 * Shows an admin notice if the setup is not complete.
	 *
	 * @return void
	 */
	public function admin_notice_settings(): void {
		$this->init_settings();

		$error_fields = [];

		$mandatory_fields = [
			EcpSettingsGeneral::OPTION_PROJECT_ID => __( 'Project ID', 'woo-ecommpay' ),
			EcpSettingsGeneral::OPTION_SECRET_KEY => __( 'Secret key', 'woo-ecommpay' )
		];

		// Check mandatory parameters
		foreach ( $mandatory_fields as $mandatory_field_setting => $mandatory_field_label ) {
			$post_key    = 'woocommerce_ecommpay_' . $mandatory_field_setting;
			$setting_key = $this->get_option(
				[ 'id' => $mandatory_field_setting ],
				EcpSettingsGeneral::ID
			);

			if ( filter_input( INPUT_POST, $post_key ) === null && empty( $setting_key ) ) {
				$error_fields[] = $mandatory_field_label;
			}
		}

		if ( ! empty ( $error_fields ) ) {
			ecp_get_view( 'html-notice-settings.php', [ 'errors' => $error_fields ] );
		}
	}

	public function render_fieldset_start( $value ) {
		$this->ecp_field_renderer->render_fieldset_start( $value );
	}

	public function render_fieldset_end( $value ) {
		$this->ecp_field_renderer->render_fieldset_end( $value );
	}

	public function render_field_description( $value ) {
		$this->ecp_field_renderer->render_field_description( $value );
	}

	public function render_toggle_start( $value ) {
		$this->ecp_field_renderer->render_toggle_start( $value );
	}

	public function render_toggle_end( $value ) {
		$this->ecp_field_renderer->render_toggle_end( $value );
	}

	public function render_field_input( $value ) {
		$this->ecp_field_renderer->render_field_input( $value );
	}

	public function render_field_color( $value ) {
		$this->ecp_field_renderer->render_field_color( $value );
	}

	public function render_field_text( $value ) {
		$this->ecp_field_renderer->render_field_text( $value );
	}

	public function render_field_select( $value ) {
		$this->ecp_field_renderer->render_field_select( $value );
	}

	public function render_field_radio( $value ) {
		$this->ecp_field_renderer->render_field_radio( $value );
	}

	public function render_field_checkbox( $value ) {
		$this->ecp_field_renderer->render_field_checkbox( $value );
	}

	public function render_field_single_select_page( $value ) {
		$this->ecp_field_renderer->render_field_single_select_page( $value );
	}

	public function render_field_single_select_country( $value ) {
		$this->ecp_field_renderer->render_field_single_select_country( $value );
	}

	public function normalize_field( $value ) {
		$property = [
			EcpSettings::FIELD_ID          => '',
			EcpSettings::FIELD_TITLE       => '',
			EcpSettings::FIELD_CLASS       => '',
			EcpSettings::FIELD_STYLE       => '',
			EcpSettings::FIELD_DEFAULT     => '',
			EcpSettings::FIELD_DESC        => '',
			EcpSettings::FIELD_TIP         => false,
			EcpSettings::FIELD_PLACEHOLDER => '',
			EcpSettings::FIELD_SUFFIX      => '',
			EcpSettings::FIELD_OPTIONS     => null,
			EcpSettings::CHECKBOXGROUP => null,
		];

		foreach ( $property as $key => $default ) {
			if ( ! isset ( $value[ $key ] ) ) {
				$value[ $key ] = $default;
			}
		}

		return $value;
	}

	/**
	 * @return array
	 */
	public function get_tabs(): array {
		return $this->ecp_tab_manager->get_tabs();
	}

	/**
	 * @inheritDoc
	 */
	protected function init(): void {
		add_filter( EcpFiltersList::ECP_FIELD_NORMALISATION, [ $this, 'normalize_field' ] );

		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SECTION_START, [ $this, 'render_fieldset_start' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SECTION_END, [ $this, 'render_fieldset_end' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SECTION_DESCRIPTION, [
			$this,
			'render_field_description'
		] );

		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TOGGLE_START, [ $this, 'render_toggle_start' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TOGGLE_END, [ $this, 'render_toggle_end' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TEXT, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_PASSWORD, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_DATETIME, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_DATETIME_LOCAL, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_DATE, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_MONTH, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TIME, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_WEEK, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_NUMBER, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_EMAIL, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_URL, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TEL, [ $this, 'render_field_input' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_COLOR, [ $this, 'render_field_color' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_TEXTAREA, [ $this, 'render_field_text' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SELECT, [ $this, 'render_field_select' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_MULTISELECT, [ $this, 'render_field_select' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_RADIO, [ $this, 'render_field_radio' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_CHECKBOX, [ $this, 'render_field_checkbox' ] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SINGLE_SELECT_PAGE, [
			$this,
			'render_field_single_select_page'
		] );
		add_action( EcpHtmlFilterList::ECP_HTML_RENDER_FIELD_SINGLE_SELECT_COUNTRY, [
			$this,
			'render_field_single_select_country'
		] );
		add_action( EcpFiltersList::WP_ADMIN_NOTICES_FILTER, [ $this, 'admin_notice_settings' ] );

		$this->ecp_tab_manager->init_tabs();
	}
}
