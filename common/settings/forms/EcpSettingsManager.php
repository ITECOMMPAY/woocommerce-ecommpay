<?php

namespace common\settings\forms;

use common\includes\filters\EcpFilters;
use common\install\EcpGatewayInstall;
use common\settings\EcpSettings;

class EcpSettingsManager {

	/**
	 * Setting fields
	 * @var ?array
	 */
	private ?array $settings;
	private EcpForm $ecp_form;

	public function __construct( EcpForm $ecp_form ) {
		$this->ecp_form = $ecp_form;
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
	public function get_option( array $value, string $method ) {
		if ( ! array_key_exists( EcpSettings::FIELD_ID, $value ) ) {
			return '';
		}

		$key     = $value[ EcpSettings::FIELD_ID ];
		$default = $value[ EcpSettings::FIELD_DEFAULT ] ?? null;

		if ( empty ( $this->settings ) ) {
			$this->init_settings();
		}

		if ( ! array_key_exists( $method, $this->settings ) ) {

			$this->settings[ $method ] = [];
		}

		if ( ! is_null( $default ) && ( ! array_key_exists( $key, $this->settings[ $method ] ) || '' === $this->settings[ $method ][ $key ] ) ) {
			$this->settings[ $method ][ $key ] = $default;
		}

		return $this->settings[ $method ][ $key ] ?? '';
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
	public function init_settings() {
		$this->settings = get_option( EcpGatewayInstall::SETTINGS_NAME, null );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$this->settings = $this->get_default_settings();
		}
	}

	/**
	 * <h2>Returns the default plugin settings.</h2>
	 *
	 * @return array
	 * @since 2.0.3
	 */
	public function get_default_settings(): array {
		$data = [];

		// Prepare all data
		foreach ( $this->ecp_form->get_tabs() as $tab ) {
			$part = [];

			foreach (
				apply_filters( 'woocommerce_settings_api_form_fields_' . $tab->get_id(),
					array_map( [ $this->ecp_form, 'set_defaults' ],
						apply_filters( EcpFilters::ECP_PREFIX_GET_SETTINGS . $tab->get_id(), [] ) ) ) as $value
			) {
				$default = $this->get_field_default( $value );

				if ( ! empty ( $default ) ) {
					$part[ $value['id'] ] = $default;
				}
			}

			$data[ $tab->get_id() ] = $part;
		}

		return $data;
	}

	/**
	 * Get a fields default value. Defaults to "" if not set.
	 *
	 * @param array $field Field key.
	 *
	 * @return string
	 */
	public function get_field_default( array $field ): string {
		return empty ( $field[ EcpSettings::FIELD_DEFAULT ] )
			? ''
			: $field[ EcpSettings::FIELD_DEFAULT ];
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
		if ( is_null( $data ) ) {
			$data = $_POST;
		}

		if ( empty ( $data ) ) {
			return false;
		}

		// Options to update will be stored here and saved later.
		$update_options   = [];
		$autoload_options = [];

		// Loop options and get values to save.
		foreach ( $options->get_settings() as $option ) {
			if (
				! isset ( $option[ EcpSettings::FIELD_ID ] )
				|| ! isset ( $option[ EcpSettings::FIELD_TYPE ] )
			) {
				continue;
			}

			// Get posted value.
			if ( strstr( $option[ EcpSettings::FIELD_ID ], '[' ) ) {
				parse_str( $option[ EcpSettings::FIELD_ID ], $option_name_array );
				$option_name  = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );
				$raw_value    = isset ( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
			} else {
				$option_name  = $option[ EcpSettings::FIELD_ID ];
				$setting_name = '';
				$raw_value    = isset ( $data[ $option[ EcpSettings::FIELD_ID ] ] )
					? wp_unslash( $data[ $option[ EcpSettings::FIELD_ID ] ] )
					: null;
			}

			// Format the value based on option type.
			switch ( $option[ EcpSettings::FIELD_TYPE ] ) {
				case EcpSettings::TYPE_CHECKBOX:
					$value = in_array( $raw_value, [
						EcpSettings::VALUE_CHECKED,
						EcpSettings::VALUE_ENABLED
					], true ) ? EcpSettings::VALUE_ENABLED : EcpSettings::VALUE_DISABLED;
					break;
				case EcpSettings::TYPE_AREA:
					$value = wp_kses_post( trim( $raw_value ) );
					break;
				case EcpSettings::TYPE_MULTI_SELECT:
				case EcpSettings::TYPE_MULTI_SELECT_COUNTRIES:
					$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
					break;
				case EcpSettings::TYPE_IMAGE_WIDTH:
					$value = [];
					if ( isset ( $raw_value['width'] ) ) {
						$value['width']  = wc_clean( $raw_value['width'] );
						$value['height'] = wc_clean( $raw_value['height'] );
						$value['crop']   = isset ( $raw_value['crop'] ) ? 1 : 0;
					} else {
						$value['width']  = $option['default']['width'];
						$value['height'] = $option['default']['height'];
						$value['crop']   = $option['default']['crop'];
					}
					break;
				case EcpSettings::TYPE_DROPDOWN:
					$allowed_values = empty ( $option[ EcpSettings::FIELD_OPTIONS ] )
						? []
						: array_map( 'strval', array_keys( $option[ EcpSettings::FIELD_OPTIONS ] ) );
					if ( empty ( $option[ EcpSettings::FIELD_DEFAULT ] ) && empty ( $allowed_values ) ) {
						$value = null;
						break;
					}
					$default = ( empty ( $option[ EcpSettings::FIELD_DEFAULT ] )
						? $allowed_values[0]
						: $option[ EcpSettings::FIELD_DEFAULT ] );
					$value   = in_array( $raw_value, $allowed_values, true )
						? $raw_value
						: $default;
					break;
				case EcpSettings::TYPE_RELATIVE_DATE_SELECTOR:
					$value = wc_parse_relative_date_option( $raw_value );
					break;
				default:
					$value = wc_clean( $raw_value );
					break;
			}

			if ( is_null( $value ) ) {
				continue;
			}

			// Check if option is an array and handle that differently to single values.
			if ( $option_name && $setting_name ) {
				if ( ! isset ( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = get_option( $option_name, [] );
				}
				if ( ! is_array( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = [];
				}
				$update_options[ $option_name ][ $setting_name ] = $value;
			} else {
				$update_options[ $option_name ] = $value;
			}

			$autoload_options[ $option_name ] = ! isset ( $option['autoload'] ) || $option['autoload'];
		}

		ecp_get_log()->debug( 'Options data', $update_options );
		$this->init_settings();

		foreach ( $update_options as $key => $value ) {
			$this->settings[ $options->get_id() ][ $key ] = $value;
		}

		// Save all options in our array.
		update_option(
			EcpGatewayInstall::SETTINGS_NAME,
			$this->settings,
			array_key_exists( EcpGatewayInstall::SETTINGS_NAME, $autoload_options ) ? EcpSettings::VALUE_ENABLED : EcpSettings::VALUE_DISABLED
		);

		ecp_get_log()->debug( 'Updated settings', $this->settings );

		return true;
	}
}
