<?php

ecp_get_log()->emergency( 'Run update settings to version 3.0.0' );

// Previous plugin settings
$prev_settings = get_option( Ecp_Gateway_Install::SETTINGS_NAME, null );

// New default settings
$form_fields = Ecp_Form::get_instance()->get_default_settings();
$all_fields  = array_column(
	Ecp_Form::get_instance()->get_all_form_fields(),
	Ecp_Gateway_Settings::FIELD_ID
);

$map = [
	Ecp_Gateway_Settings_General::ID => [
		'test' => 'test',
		'language'                => Ecp_Gateway_Settings_General::OPTION_LANGUAGE,
		'caching_enabled'         => Ecp_Gateway_Settings_General::OPTION_CACHING_ENABLED,
		'caching_expiration'      => Ecp_Gateway_Settings_General::OPTION_CACHING_EXPIRATION,
		'log_level'               => Ecp_Gateway_Settings_General::OPTION_LOG_LEVEL,
		'orders_transaction_info' => Ecp_Gateway_Settings_General::OPTION_TRANSACTION_INFO,
		'project_id'              => Ecp_Gateway_Settings_General::OPTION_PROJECT_ID,
		'salt'                    => Ecp_Gateway_Settings_General::OPTION_SECRET_KEY,
		'custom_variables'        => Ecp_Gateway_Settings_General::OPTION_CUSTOM_VARIABLES,
	],
	Ecp_Gateway_Settings_Card::ID    => [
		'enabled'             => Ecp_Gateway_Settings::OPTION_ENABLED,
		'mode'                => Ecp_Gateway_Settings::OPTION_MODE,
		'close_on_miss_click' => Ecp_Gateway_Settings::OPTION_POPUP_MISS_CLICK,
	],
];

// Clean old unused settings via map
foreach ( $prev_settings as $key => $value ) {
	$key = str_replace( 'ecommpay_', '', $key );
	foreach ( $map as $section => $options ) {
		if ( array_key_exists( $key, $options ) ) {
			$form_fields[ $section ][ $options[ $key ] ] = $value;
		}
	}
}

// Update plugin settings
update_option( Ecp_Gateway_Install::SETTINGS_NAME, $form_fields );
