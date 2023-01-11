<?php

ecp_get_log()->emergency('Run update settings to version 2.0.3');

// Previous plugin settings
$prev_settings = get_option(Ecp_Gateway_Install::SETTINGS_NAME, null);
// New default settings
$form_fields = Ecp_Gateway_Settings::get_instance()->get_default_settings();
$all_fields = array_column(
    Ecp_Gateway_Settings::get_instance()->get_all_form_fields(),
    Ecp_Gateway_Settings_Page::FIELD_ID
);
$map = [
    'mode' => Ecp_Gateway_Settings_Page::OPTION_MODE,
    'project_id' => Ecp_Gateway_Settings_Page::OPTION_PROJECT_ID,
    'salt' => Ecp_Gateway_Settings_Page::OPTION_SECRET_KEY,
    'test' => Ecp_Gateway_Settings_Page::OPTION_TEST,
];

// Clean old unused settings via map
foreach ($prev_settings as $key => $value) {
    if (array_key_exists($key, $map)) {
        $prev_settings[$map[$key]] = $value;
    }

    if (!in_array($key, $all_fields)) {
        unset($prev_settings[$key]);
    }
}

// Merged settings
$settings = array_merge($form_fields, $prev_settings);

// Update plugin settings
update_option(Ecp_Gateway_Install::SETTINGS_NAME, $settings);
