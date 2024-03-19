<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings class
 *
 * @class    Ecp_Gateway_Settings
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Form extends Ecp_Gateway_Registry
{
    /**
     * Setting fields
     * @var ?array
     */
    private $settings;

    /**
     * Setting pages.
     *
     * @var Ecp_Gateway_Settings[]
     */
    private $tabs = [];

    /**
     * @inheritDoc
     */
    protected function init()
    {
        add_filter('ecp_field_normalisation', [$this, 'normalize_field'], 10, 1);

        add_action('ecp_html_render_field_section_start', [$this, 'render_fieldset_start'], 10, 1);
        add_action('ecp_html_render_field_section_end', [$this, 'render_fieldset_end'], 10, 1);
        add_action('ecp_html_render_field_toggle_start', [$this, 'render_toggle_start'], 10, 1);
        add_action('ecp_html_render_field_toggle_end', [$this, 'render_toggle_end'], 10, 1);
        add_action('ecp_html_render_field_text', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_password', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_datetime', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_datetime-local', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_date', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_month', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_time', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_week', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_number', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_email', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_url', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_tel', [$this, 'render_field_input'], 10, 1);
        add_action('ecp_html_render_field_color', [$this, 'render_field_color'], 10, 1);
        add_action('ecp_html_render_field_textarea', [$this, 'render_field_text'], 10, 1);
        add_action('ecp_html_render_field_select', [$this, 'render_field_select'], 10, 1);
        add_action('ecp_html_render_field_multiselect', [$this, 'render_field_select'], 10, 1);
        add_action('ecp_html_render_field_radio', [$this, 'render_field_radio'], 10, 1);
        add_action('ecp_html_render_field_checkbox', [$this, 'render_field_checkbox'], 10, 1);
        add_action('ecp_html_render_field_single_select_page', [$this, 'render_field_single_select_page'], 10, 1);
        add_action('ecp_html_render_field_single_select_country', [$this, 'render_field_single_select_country'], 10, 1);
        add_action('ecp_html_render_field_multi_select_country', [$this, 'render_field_multi_select_country'], 10, 1);
        add_action('ecp_html_render_field_relative_date_selector', [$this, 'render_field_relative_date_selector'], 10, 1);
        add_action('admin_notices', [$this, 'admin_notice_settings']);

        if (empty ($this->tabs)) {
            $tabs = [
                new Ecp_Gateway_Settings_General(),
                new Ecp_Gateway_Settings_Card(),
                new Ecp_Gateway_Settings_Applepay(),
                new Ecp_Gateway_Settings_Googlepay(),
                new Ecp_Gateway_Settings_Banks(),
                new Ecp_Gateway_Settings_PayPal(),
                new Ecp_Gateway_Settings_Sofort(),
                new Ecp_Gateway_Settings_Ideal(),
                new Ecp_Gateway_Settings_Klarna(),
                new Ecp_Gateway_Settings_Blik(),
                new Ecp_Gateway_Settings_Giropay(),
                new Ecp_Gateway_Settings_Brazil_Online_Banks(),
                new Ecp_Gateway_Settings_More(),
            ];

            $this->tabs = apply_filters('ecp_get_settings_pages', $tabs);
        }
    }

    /**
     * Saving the settings.
     */
    public function save()
    {
        $current_tab = $this->get_section();
        ecp_get_log()->debug('Run saving plugin settings. Section:', $current_tab);

        // Trigger actions
        do_action('ecp_settings_save_' . $current_tab);
        do_action('ecp_update_options_' . $current_tab);
        do_action('ecp_update_options');
        wp_schedule_single_event(time(), 'ecp_flush_rewrite_rules');
        do_action('ecp_settings_saved');

        ecp_get_log()->info('Plugin settings successfully saved. Section:', $current_tab);
    }

    private function get_section()
    {
        $current_tab = $_REQUEST['section'];
        if (wc_get_var($_REQUEST['sub']) === Ecp_Gateway_Settings_General::ID) {
            $current_tab = Ecp_Gateway_Settings_General::ID;
        }

        return $current_tab;
    }

    /**
     * Display settings page.
     */
    public function output($tab = null)
    {
        $current_tab = $this->get_section();
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : ''; //'.min';

        do_action('ecp_settings_start');

        wp_enqueue_script(
            'ecp_settings',
            ecp_js_url('settings' . $suffix . '.js'),
            ['jquery'],
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
                'tabs' => apply_filters('ecp_settings_tabs_array', [])
            ]
        );
    }

    /**
     * Display admin fields.
     *
     * Loops though the WooCommerce ECOMMPAY options array and outputs each field.
     *
     * @param Ecp_Gateway_Settings $options Opens array to output.
     */
    public function output_fields($options)
    {
        foreach ($options->get_settings() as $value) {
            if (!isset ($value['type'])) {
                continue;
            }

            $value = apply_filters('ecp_field_normalisation', $value);
            do_action(
                'ecp_html_render_field_' . $value['type'],
                $this->get_general_rendering_options($value, $options->get_id())
            );
        }
    }

    /**
     * Save admin fields.
     *
     * Loops though the woocommerce options array and outputs each field.
     *
     * @param Ecp_Gateway_Settings $options Options array to output.
     * @param array $data Optional. Data to use for saving. Defaults to $_POST.
     * @return bool
     */
    public function save_fields(Ecp_Gateway_Settings $options, $data = null)
    {
        if (is_null($data)) {
            $data = $_POST;
        }

        if (empty ($data)) {
            return false;
        }

        // Options to update will be stored here and saved later.
        $update_options = [];
        $autoload_options = [];

        // Loop options and get values to save.
        foreach ($options->get_settings() as $option) {
            if (
                !isset ($option[Ecp_Gateway_Settings::FIELD_ID])
                || !isset ($option[Ecp_Gateway_Settings::FIELD_TYPE])
            ) {
                continue;
            }

            // Get posted value.
            if (strstr($option[Ecp_Gateway_Settings::FIELD_ID], '[')) {
                parse_str($option[Ecp_Gateway_Settings::FIELD_ID], $option_name_array);
                $option_name = current(array_keys($option_name_array));
                $setting_name = key($option_name_array[$option_name]);
                $raw_value = isset ($data[$option_name][$setting_name]) ? wp_unslash($data[$option_name][$setting_name]) : null;
            } else {
                $option_name = $option[Ecp_Gateway_Settings::FIELD_ID];
                $setting_name = '';
                $raw_value = isset ($data[$option[Ecp_Gateway_Settings::FIELD_ID]])
                    ? wp_unslash($data[$option[Ecp_Gateway_Settings::FIELD_ID]])
                    : null;
            }

            // Format the value based on option type.
            switch ($option[Ecp_Gateway_Settings::FIELD_TYPE]) {
                case Ecp_Gateway_Settings::TYPE_CHECKBOX:
                    $value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
                    break;
                case Ecp_Gateway_Settings::TYPE_AREA:
                    $value = wp_kses_post(trim($raw_value));
                    break;
                case Ecp_Gateway_Settings::TYPE_MULTI_SELECT:
                case 'multi_select_countries':
                    $value = array_filter(array_map('wc_clean', (array) $raw_value));
                    break;
                case 'image_width':
                    $value = [];
                    if (isset ($raw_value['width'])) {
                        $value['width'] = wc_clean($raw_value['width']);
                        $value['height'] = wc_clean($raw_value['height']);
                        $value['crop'] = isset ($raw_value['crop']) ? 1 : 0;
                    } else {
                        $value['width'] = $option['default']['width'];
                        $value['height'] = $option['default']['height'];
                        $value['crop'] = $option['default']['crop'];
                    }
                    break;
                case Ecp_Gateway_Settings::TYPE_DROPDOWN:
                    $allowed_values = empty ($option[Ecp_Gateway_Settings::FIELD_OPTIONS])
                        ? []
                        : array_map('strval', array_keys($option[Ecp_Gateway_Settings::FIELD_OPTIONS]));
                    if (empty ($option[Ecp_Gateway_Settings::FIELD_DEFAULT]) && empty ($allowed_values)) {
                        $value = null;
                        break;
                    }
                    $default = (empty ($option[Ecp_Gateway_Settings::FIELD_DEFAULT])
                        ? $allowed_values[0]
                        : $option[Ecp_Gateway_Settings::FIELD_DEFAULT]);
                    $value = in_array($raw_value, $allowed_values, true)
                        ? $raw_value
                        : $default;
                    break;
                case 'relative_date_selector':
                    $value = wc_parse_relative_date_option($raw_value);
                    break;
                default:
                    $value = wc_clean($raw_value);
                    break;
            }

            if (is_null($value)) {
                continue;
            }

            // Check if option is an array and handle that differently to single values.
            if ($option_name && $setting_name) {
                if (!isset ($update_options[$option_name])) {
                    $update_options[$option_name] = get_option($option_name, []);
                }
                if (!is_array($update_options[$option_name])) {
                    $update_options[$option_name] = [];
                }
                $update_options[$option_name][$setting_name] = $value;
            } else {
                $update_options[$option_name] = $value;
            }

            $autoload_options[$option_name] = !isset ($option['autoload']) || $option['autoload'];
        }

        ecp_get_log()->debug('Options data', $update_options);
        $this->init_settings();

        foreach ($update_options as $key => $value) {
            $this->settings[$options->get_id()][$key] = $value;
        }

        // Save all options in our array.
        update_option(
            Ecp_Gateway_Install::SETTINGS_NAME,
            $this->settings,
            array_key_exists(Ecp_Gateway_Install::SETTINGS_NAME, $autoload_options) ? 'yes' : 'no'
        );

        ecp_get_log()->debug('Updated settings', $this->settings);
        return true;
    }

    /**
     * Get option from DB.
     *
     * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
     *
     * @param array $value Option
     * @param string $method Payment method
     * @return string|array The value specified for the option or a default value for the option.
     */
    private function get_option($value, $method)
    {
        if (!array_key_exists(Ecp_Gateway_Settings::FIELD_ID, $value)) {
            return '';
        }

        $key = $value[Ecp_Gateway_Settings::FIELD_ID];
        $default = $value[Ecp_Gateway_Settings::FIELD_DEFAULT] ?? null;

        if (empty ($this->settings)) {
            $this->init_settings();
        }

        if (!array_key_exists($method, $this->settings)) {

            $this->settings[$method] = [];
        }

        if (!is_null($default) && (!array_key_exists($key, $this->settings[$method]) || '' === $this->settings[$method][$key])) {
            $this->settings[$method][$key] = $default;
        }

        return $this->settings[$method][$key] ?? '';
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
    private function init_settings()
    {
        $this->settings = get_option(Ecp_Gateway_Install::SETTINGS_NAME, null);

        // If there are no settings defined, use defaults.
        if (!is_array($this->settings)) {
            $this->settings = $this->get_default_settings();
        }
    }

    /**
     * <h2>Returns the default plugin settings.</h2>
     *
     * @since 2.0.3
     * @return array
     */
    public function get_default_settings()
    {
        $data = [];

        // Prepare all data
        foreach ($this->tabs as $tab) {
            $part = [];

            foreach (apply_filters('woocommerce_settings_api_form_fields_' . $tab->get_id(), array_map([$this, 'set_defaults'], apply_filters('ecp_get_settings_' . $tab->get_id(), []))) as $value) {
                $default = $this->get_field_default($value);

                if (!empty ($default)) {
                    $part[$value['id']] = $default;
                }
            }

            $data[$tab->get_id()] = $part;
        }

        return $data;
    }

    /**
     * <h2>Returns the all form fields.</h2>
     *
     * @since 2.0.3
     * @return array of options
     */
    public function get_all_form_fields()
    {
        $fields = [];

        foreach ($this->tabs as $tab) {
            $fields = array_merge(
                $fields,
                apply_filters(
                    'woocommerce_settings_api_form_fields_' . $tab->get_id(),
                    array_map([$this, 'set_defaults'], apply_filters('ecp_get_settings_' . $tab->get_id(), []))
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
    public function get_form_fields($current_tab = null)
    {
        if ($current_tab === null) {
            $current_tab = wc_get_var($_REQUEST['section']);
            $current_tab = $current_tab !== null ? sanitize_title($current_tab) : Ecp_Gateway_Settings_General::ID;
        }

        return apply_filters(
            'woocommerce_settings_api_form_fields_' . $current_tab,
            array_map([$this, 'set_defaults'], apply_filters('ecp_get_settings_' . $current_tab, []))
        );
    }

    /**
     * Set default required properties for each field.
     *
     * @param array $field Setting field array.
     * @return array
     */
    public function set_defaults($field)
    {
        if (!isset ($field[Ecp_Gateway_Settings::FIELD_DEFAULT])) {
            $field[Ecp_Gateway_Settings::FIELD_DEFAULT] = '';
        }

        return $field;
    }

    /**
     * Get a fields default value. Defaults to "" if not set.
     *
     * @param array $field Field key.
     * @return string
     */
    public function get_field_default($field)
    {
        return empty ($field[Ecp_Gateway_Settings::FIELD_DEFAULT])
            ? ''
            : $field[Ecp_Gateway_Settings::FIELD_DEFAULT];
    }

    /**
     * Shows an admin notice if the setup is not complete.
     *
     * @return void
     */
    public function admin_notice_settings()
    {
        $this->init_settings();

        //        if (!ecp_is_enabled('enabled')) {
//            // Exit if plugin disabled.
//            return;
//        }

        $error_fields = [];

        $mandatory_fields = [
            Ecp_Gateway_Settings_General::OPTION_PROJECT_ID => __('Project ID', 'woo-ecommpay'),
            Ecp_Gateway_Settings_General::OPTION_SECRET_KEY => __('Secret key', 'woo-ecommpay')
        ];

        if (!ecp_is_enabled(Ecp_Gateway_Settings_General::OPTION_TEST)) {
            // Check mandatory parameters
            foreach ($mandatory_fields as $mandatory_field_setting => $mandatory_field_label) {
                $post_key = 'woocommerce_ecommpay_' . $mandatory_field_setting;
                $setting_key = $this->get_option(
                    ['id' => $mandatory_field_setting],
                    Ecp_Gateway_Settings_General::ID
                );

                if (wc_get_post_data_by_key($post_key, null) === null && empty ($setting_key)) {
                    $error_fields[] = $mandatory_field_label;
                }
            }
        }

        if (!empty ($error_fields)) {
            ecp_get_view('html-notice-settings.php', ['errors' => $error_fields]);
        }
    }

    // Section Titles.
    public function render_fieldset_start($value)
    {
        ecp_get_view('fields/html-form-fieldset-start.php', $value);
    }

    // Section Ends.
    public function render_fieldset_end($value)
    {
        ecp_get_view('fields/html-form-fieldset-end.php', $value);
    }

    // Toggle block start.
    public function render_toggle_start($value)
    {
        ecp_get_view('fields/html-form-toggle-start.php', $value);
    }

    // Toggle block end.
    public function render_toggle_end($value)
    {
        ecp_get_view('fields/html-form-toggle-end.php', $value);
    }

    // Standard text inputs and subtypes like 'number'.
    public function render_field_input($value)
    {
        ecp_get_view('fields/html-form-field-input.php', $value);
    }

    // Color picker.
    public function render_field_color($value)
    {
        ecp_get_view('fields/html-form-field-color.php', $value);
    }

    // Textarea.
    public function render_field_text($value)
    {
        ecp_get_view('fields/html-form-field-text.php', $value);
    }

    // Select boxes.
    public function render_field_select($value)
    {
        ecp_get_view('fields/html-form-field-select.php', $value);
    }

    // Radio inputs.
    public function render_field_radio($value)
    {
        ecp_get_view('fields/html-form-field-radio.php', $value);
    }

    // Checkbox input.
    public function render_field_checkbox($value)
    {
        $visibility_class = [];

        if (!isset ($value['hide_if_checked'])) {
            $value['hide_if_checked'] = false;
        }
        if (!isset ($value['show_if_checked'])) {
            $value['show_if_checked'] = false;
        }
        if ('yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked']) {
            $visibility_class[] = 'hidden_option';
        }
        if ('option' === $value['hide_if_checked']) {
            $visibility_class[] = 'hide_options_if_checked';
        }
        if ('option' === $value['show_if_checked']) {
            $visibility_class[] = 'show_options_if_checked';
        }

        $value['visibility_class'] = $visibility_class;

        ecp_get_view('fields/html-form-field-checkbox.php', $value);
    }

    // Single page selects.
    public function render_field_single_select_page($value)
    {
        $args = [
            'name' => $value[Ecp_Gateway_Settings::FIELD_ID],
            'id' => $value[Ecp_Gateway_Settings::FIELD_ID],
            'sort_column' => 'menu_order',
            'sort_order' => 'ASC',
            'show_option_none' => ' ',
            'class' => $value[Ecp_Gateway_Settings::FIELD_CLASS],
            'echo' => false,
            'selected' => absint($value['option_value']),
            'post_status' => 'publish,private,draft',
        ];

        if (isset ($value[Ecp_Gateway_Settings::FIELD_ARGS])) {
            $value['args'] = wp_parse_args($value[Ecp_Gateway_Settings::FIELD_ARGS], $args);
        }

        ecp_get_view('fields/html-form-field-single_select_page.php', $value);
    }

    // Single country selects.
    public function render_field_single_select_country($value)
    {
        $country_setting = $value['option_value'];

        if (strstr($country_setting, ':')) {
            $country_setting = explode(':', $country_setting);
            $value['country'] = current($country_setting);
            $value['state'] = end($country_setting);
        } else {
            $value['country'] = $country_setting;
            $value['state'] = '*';
        }

        ecp_get_view('fields/html-form-field-single-select-country.php', $value);
    }

    public function normalize_field($value)
    {
        $property = [
            Ecp_Gateway_Settings::FIELD_ID => '',
            Ecp_Gateway_Settings::FIELD_TITLE => '',
            Ecp_Gateway_Settings::FIELD_CLASS => '',
            Ecp_Gateway_Settings::FIELD_STYLE => '',
            Ecp_Gateway_Settings::FIELD_DEFAULT => '',
            Ecp_Gateway_Settings::FIELD_DESC => '',
            Ecp_Gateway_Settings::FIELD_TIP => false,
            Ecp_Gateway_Settings::FIELD_PLACEHOLDER => '',
            Ecp_Gateway_Settings::FIELD_SUFFIX => '',
            Ecp_Gateway_Settings::FIELD_OPTIONS => null,
            'checkboxgroup' => null,
        ];

        foreach ($property as $key => $default) {
            if (!isset ($value[$key])) {
                $value[$key] = $default;
            }
        }

        return $value;
    }

    private function get_custom_attributes($value)
    {
        // Custom attribute handling.
        $custom_attributes = [];

        if (
            !empty ($value[Ecp_Gateway_Settings::FIELD_CUSTOM])
            && is_array($value[Ecp_Gateway_Settings::FIELD_CUSTOM])
        ) {
            foreach ($value[Ecp_Gateway_Settings::FIELD_CUSTOM] as $attribute => $attribute_value) {
                $custom_attributes[$attribute] = $attribute_value;
            }
        }

        return $custom_attributes;
    }

    /**
     * Returns formatted description for a given form field.
     * Plugins can call this when implementing their own custom settings types.
     *
     * @param array $value The form field value array.
     * @return string The description as a formatted string
     */
    private function get_description($value)
    {
        if (
            true !== $value[Ecp_Gateway_Settings::FIELD_TIP]
            && !empty ($value[Ecp_Gateway_Settings::FIELD_DESC])
        ) {
            return $value[Ecp_Gateway_Settings::FIELD_DESC];
        }

        return '';
    }

    /**
     * Returns the formatted tip HTML for a given form field.
     * Plugins can call this when implementing their own custom settings types.
     *
     * @param array $value The form field value array.
     * @return string The tip as a formatted string.
     */
    private function get_tooltip($value)
    {
        if (true === $value[Ecp_Gateway_Settings::FIELD_TIP]) {
            return $value[Ecp_Gateway_Settings::FIELD_DESC];
        }

        if (!empty ($value[Ecp_Gateway_Settings::FIELD_TIP])) {
            return $value[Ecp_Gateway_Settings::FIELD_TIP];
        }

        return '';
    }

    /**
     * Returns based form field array from setting options.
     *
     * @param array $value Setting options
     * @return array Form field as array
     */
    private function get_general_rendering_options($value, $gateway)
    {
        return [
            'id' => $value[Ecp_Gateway_Settings::FIELD_ID],
            'type' => $value[Ecp_Gateway_Settings::FIELD_TYPE],
            'title' => $value[Ecp_Gateway_Settings::FIELD_TITLE],
            'tooltip' => $this->get_tooltip($value),
            'css' => $value[Ecp_Gateway_Settings::FIELD_STYLE],
            'option_value' => $this->get_option($value, $gateway),
            'options' => $value[Ecp_Gateway_Settings::FIELD_OPTIONS],
            'class' => $value[Ecp_Gateway_Settings::FIELD_CLASS],
            'custom_attributes' => $this->get_custom_attributes($value),
            'description' => $this->get_description($value),
            'placeholder' => $value[Ecp_Gateway_Settings::FIELD_PLACEHOLDER],
            'suffix' => $value[Ecp_Gateway_Settings::FIELD_SUFFIX],
            'checkboxgroup' => $value['checkboxgroup'],
        ];
    }
}