<?php

use common\install\EcpGatewayInstall;
use common\settings\EcpSettings;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsGeneral;
use common\settings\forms\EcpForm;

ecp_get_log()->emergency( 'Run update settings to version 3.0.0' );

// Previous plugin settings
$prev_settings = get_option( EcpGatewayInstall::SETTINGS_NAME, null );

// New default settings
$form_fields = EcpForm::get_instance()->get_default_settings();
$all_fields  = array_column(
	EcpForm::get_instance()->get_all_form_fields(),
	EcpSettings::FIELD_ID
);

$map = [
	EcpSettingsGeneral::ID => [
		'test' => 'test',
		'language'                => EcpSettingsGeneral::OPTION_LANGUAGE,
		'caching_enabled'         => EcpSettingsGeneral::OPTION_CACHING_ENABLED,
		'caching_expiration'      => EcpSettingsGeneral::OPTION_CACHING_EXPIRATION,
		'log_level'               => EcpSettingsGeneral::OPTION_LOG_LEVEL,
		'orders_transaction_info' => EcpSettingsGeneral::OPTION_TRANSACTION_INFO,
		'project_id'              => EcpSettingsGeneral::OPTION_PROJECT_ID,
		'salt'                    => EcpSettingsGeneral::OPTION_SECRET_KEY,
		'custom_variables'        => EcpSettingsGeneral::OPTION_CUSTOM_VARIABLES,
	],
	EcpSettingsCard::ID    => [
		'enabled'             => EcpSettings::OPTION_ENABLED,
		'mode'                => EcpSettings::OPTION_MODE,
		'close_on_miss_click' => EcpSettings::OPTION_POPUP_MISS_CLICK,
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
update_option( EcpGatewayInstall::SETTINGS_NAME, $form_fields );
