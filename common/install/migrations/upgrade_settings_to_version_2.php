<?php

use common\install\EcpGatewayInstall;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;
use common\settings\forms\EcpForm;

ecp_get_log()->emergency( 'Run update settings to version 2.0.3' );

// Previous plugin settings
$prev_settings = get_option( EcpGatewayInstall::SETTINGS_NAME, null );
// New default settings
$form_fields = EcpForm::get_instance()->get_default_settings();
$all_fields  = array_column(
	EcpForm::get_instance()->get_all_form_fields(),
	EcpSettings::FIELD_ID
);
$map         = [
	'mode'       => EcpSettings::OPTION_MODE,
	'project_id' => EcpSettingsGeneral::OPTION_PROJECT_ID,
	'salt'       => EcpSettingsGeneral::OPTION_SECRET_KEY,
	'test' => 'test',
];

// Clean old unused settings via map
foreach ( $prev_settings as $key => $value ) {
	if ( array_key_exists( $key, $map ) ) {
		$prev_settings[ $map[ $key ] ] = $value;
	}

	if ( ! in_array( $key, $all_fields ) ) {
		unset( $prev_settings[ $key ] );
	}
}

// Merged settings
$settings = array_merge( $form_fields, $prev_settings );

// Update plugin settings
update_option( EcpGatewayInstall::SETTINGS_NAME, $settings );
