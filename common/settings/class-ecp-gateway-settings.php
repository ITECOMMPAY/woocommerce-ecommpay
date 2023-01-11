<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings class
 *
 * @class    Ecp_Gateway_Settings
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings extends Ecp_Gateway_Registry
{
    /**
     * Setting fields
     * @var ?array
     */
    private $settings;

    /**
     * Setting pages.
     *
     * @var Ecp_Gateway_Settings_Page[]
     */
    private $tabs = [];

    /**
     * @inheritDoc
     */
    protected function init()
    {
        add_filter('ecp_field_normalisation', [$this, 'normalize_field'], 10, 1);

        add_action('ecp_html_render_field_title', [$this, 'render_fieldset_start'], 10, 1);
        add_action('ecp_html_render_field_section_end', [$this, 'render_fieldset_end'], 10, 1);
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
        add_action('woocommerce_update_options_payment_gateways_ecommpay', [$this, 'save']);

        if (empty($this->tabs)) {
            $tabs = [];

            $tabs[] = new Ecp_Gateway_Settings_General();
            $tabs[] = new Ecp_Gateway_Settings_Payment_Page();
            $tabs[] = new Ecp_Gateway_Settings_Admin();
            /*
            ToDo: Must be implements in next versions.
            $tabs[] = new Ecp_Gateway_Settings_Products();
            $tabs[] = new Ecp_Gateway_Settings_Refund();

            if (ecp_subscription_is_active()) {
                $tabs[] = new Ecp_Gateway_Settings_Subscriptions();
            }
             */

            $this->tabs = apply_filters('ecp_get_settings_pages', $tabs);
        }
    }

    /**
     * Saving the settings.
     */
    public function save()
    {
        $current_tab = !empty($_REQUEST['sub']) ? sanitize_title($_REQUEST['sub']) : Ecp_Gateway_Settings_General::ID;

        if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'woocommerce-settings')) {
            die(__('Action failed. Please refresh the page and retry.', 'woo-ecommpay'));
        }

        ecp_get_log()->debug('Run saving plugin settings. Section:', $current_tab);

        // Trigger actions
        do_action('ecp_settings_save_' . $current_tab);
        do_action('ecp_update_options_' . $current_tab);
        do_action('ecp_update_options');
        wp_schedule_single_event(time(), 'ecp_flush_rewrite_rules');
        do_action('ecp_settings_saved');

        ecp_get_log()->info('Plugin settings successfully saved. Section:', $current_tab);
    }

    /**
     * Display settings page.
     */
    public function output()
    {
        $current_tab = !empty($_REQUEST['sub']) ? sanitize_title($_REQUEST['sub']) : Ecp_Gateway_Settings_General::ID;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        do_action('ecp_settings_start');

        wp_enqueue_script(
            'ecp_settings',
            ecp_js_path('settings' . $suffix . '.js'),
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
     * @param array[] $options Opens array to output.
     */
    public function output_fields($options)
    {
        foreach ($options as $value) {
            if (!isset($value['type'])) {
                continue;
            }

            $value = apply_filters('ecp_field_normalisation', $value);
            do_action('ecp_html_render_field_' . $value['type'], $value);
        }
    }

    /**
     * Save admin fields.
     *
     * Loops though the woocommerce options array and outputs each field.
     *
     * @param array $options Options array to output.
     * @param array $data Optional. Data to use for saving. Defaults to $_POST.
     * @return bool
     */
    public function save_fields($options, $data = null)
    {
        if (is_null($data)) {
            $data = $_POST;
        }

        if (empty($data)) {
            return false;
        }

        // Options to update will be stored here and saved later.
        $update_options = [];
        $autoload_options = [];

        // Loop options and get values to save.
        foreach ($options as $option) {
            if (
                !isset($option[Ecp_Gateway_Settings_Page::FIELD_ID])
                || !isset($option[Ecp_Gateway_Settings_Page::FIELD_TYPE])
            ) {
                continue;
            }

            // Get posted value.
            if (strstr($option[Ecp_Gateway_Settings_Page::FIELD_ID], '[')) {
                parse_str($option[Ecp_Gateway_Settings_Page::FIELD_ID], $option_name_array);
                $option_name = current(array_keys($option_name_array));
                $setting_name = key($option_name_array[$option_name]);
                $raw_value = isset($data[$option_name][$setting_name]) ? wp_unslash($data[$option_name][$setting_name]) : null;
            } else {
                $option_name = $option[Ecp_Gateway_Settings_Page::FIELD_ID];
                $setting_name = '';
                $raw_value = isset($data[$option[Ecp_Gateway_Settings_Page::FIELD_ID]])
                    ? wp_unslash($data[$option[Ecp_Gateway_Settings_Page::FIELD_ID]])
                    : null;
            }

            // Format the value based on option type.
            switch ($option[Ecp_Gateway_Settings_Page::FIELD_TYPE]) {
                case Ecp_Gateway_Settings_Page::TYPE_CHECKBOX:
                    $value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
                    break;
                case Ecp_Gateway_Settings_Page::TYPE_AREA:
                    $value = wp_kses_post(trim($raw_value));
                    break;
                case Ecp_Gateway_Settings_Page::TYPE_MULTI_SELECT:
                case 'multi_select_countries':
                    $value = array_filter(array_map('wc_clean', (array)$raw_value));
                    break;
                case 'image_width':
                    $value = [];
                    if (isset($raw_value['width'])) {
                        $value['width'] = wc_clean($raw_value['width']);
                        $value['height'] = wc_clean($raw_value['height']);
                        $value['crop'] = isset($raw_value['crop']) ? 1 : 0;
                    } else {
                        $value['width'] = $option['default']['width'];
                        $value['height'] = $option['default']['height'];
                        $value['crop'] = $option['default']['crop'];
                    }
                    break;
                case Ecp_Gateway_Settings_Page::TYPE_DROPDOWN:
                    $allowed_values = empty($option[Ecp_Gateway_Settings_Page::FIELD_OPTIONS])
                        ? []
                        : array_map('strval', array_keys($option[Ecp_Gateway_Settings_Page::FIELD_OPTIONS]));
                    if (empty($option[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]) && empty($allowed_values)) {
                        $value = null;
                        break;
                    }
                    $default = (empty($option[Ecp_Gateway_Settings_Page::FIELD_DEFAULT])
                        ? $allowed_values[0]
                        : $option[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]);
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
                if (!isset($update_options[$option_name])) {
                    $update_options[$option_name] = get_option($option_name, array());
                }
                if (!is_array($update_options[$option_name])) {
                    $update_options[$option_name] = array();
                }
                $update_options[$option_name][$setting_name] = $value;
            } else {
                $update_options[$option_name] = $value;
            }

            $autoload_options[$option_name] = !isset($option['autoload']) || $option['autoload'];
        }

        $this->init_settings();
        ecp_get_log()->debug('New settings package:', $update_options);

        foreach ($update_options as $key => $value) {
            $this->settings[$key] = $value;
        }

        // Save all options in our array.
        update_option(
            Ecp_Gateway_Install::SETTINGS_NAME,
            $this->settings,
            array_key_exists(Ecp_Gateway_Install::SETTINGS_NAME, $autoload_options) ? 'yes' : 'no'
        );


        return true;
    }

    /**
     * Get option from DB.
     *
     * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
     *
     * @param string $key Option key.
     * @param mixed $empty_value Value when empty.
     * @return string|array The value specified for the option or a default value for the option.
     */
    private function get_option($key, $empty_value = null)
    {
        if (empty($this->settings)) {
            $this->init_settings();
        }

        if (!is_null($empty_value) && '' === $this->settings[$key]) {
            $this->settings[$key] = $empty_value;
        }

        return $this->settings[$key];
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

            foreach (
                apply_filters(
                     'woocommerce_settings_api_form_fields_' . $tab->get_id(),
                     array_map([$this, 'set_defaults'], apply_filters('ecp_get_settings_' . $tab->get_id(), []))
                 ) as $value
            ) {
                $default = $this->get_field_default($value);

                if (!empty($default)) {
                    $part[$value['id']] = $default;
                }
            }

            $data = array_merge(
                $data,
                $part
            );
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
    public function get_form_fields()
    {
        $current_tab = !empty($_REQUEST['sub']) ? sanitize_title($_REQUEST['sub']) : 'general';

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
        if (!isset($field[Ecp_Gateway_Settings_Page::FIELD_DEFAULT])) {
            $field[Ecp_Gateway_Settings_Page::FIELD_DEFAULT] = '';
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
        return empty($field[Ecp_Gateway_Settings_Page::FIELD_DEFAULT])
            ? ''
            : $field[Ecp_Gateway_Settings_Page::FIELD_DEFAULT];
    }

    /**
     * Shows an admin notice if the setup is not complete.
     *
     * @return void
     */
    public function admin_notice_settings()
    {
        $this->init_settings();

        if (!ecp_is_enabled(Ecp_Gateway_Settings_Page::OPTION_ENABLED)) {
            // Exit if plugin disabled.
            return;
        }

        $error_fields = [];

        $mandatory_fields = [
            Ecp_Gateway_Settings_Page::OPTION_PROJECT_ID => __('Project ID', 'woo-ecommpay'),
            Ecp_Gateway_Settings_Page::OPTION_SECRET_KEY => __('Secret key', 'woo-ecommpay')
        ];

        if (!ecp_is_enabled(Ecp_Gateway_Settings_Page::OPTION_TEST)) {
            // Check mandatory parameters
            foreach ($mandatory_fields as $mandatory_field_setting => $mandatory_field_label) {
                $post_key = 'woocommerce_ecommpay_' . $mandatory_field_setting;
                $setting_key = $this->get_option($mandatory_field_setting);

                if (empty($_POST[$post_key]) && empty($setting_key)) {
                    $error_fields[] = $mandatory_field_label;
                }
            }
        }

        if (!empty($error_fields)) {
            ecp_get_view('html-notice-settings.php', ['errors' => $error_fields]);
        }
    }

    // Section Titles.
    public function render_fieldset_start($value)
    {
        ecp_get_view(
            'fields/html-form-fieldset-start.php',
            [
                'id' => $value['id'],
                'title' => $value['title'],
                'description' => $this->get_description($value)
            ]
        );
    }

    // Section Ends.
    public function render_fieldset_end($value)
    {
        ecp_get_view(
            'fields/html-form-fieldset-end.php',
            [
                'id' => $value['id'],
            ]
        );
    }

    // Standard text inputs and subtypes like 'number'.
    public function render_field_input($value)
    {
        ecp_get_view(
            'fields/html-form-field-input.php',
            [
                'id' => $value['id'],
                'type' => $value['type'],
                'title' => $value['title'],
                'tooltip' => $this->get_tooltip($value),
                'css' => $value['css'],
                'option_value' => $this->get_option($value['id'], $value['default']),
                'class' => $value['class'],
                'placeholder' => $value['placeholder'],
                'custom_attributes' => $this->get_custom_attributes($value),
                'suffix' => $value['suffix'],
                'description' => $this->get_description($value),
            ]
        );
    }

    // Color picker.
    public function render_field_color($value)
    {
        ecp_get_view(
            'fields/html-form-field-color.php',
            $this->get_general_rendering_options($value)
        );
    }

    // Textarea.
    public function render_field_text($value)
    {
        ecp_get_view(
            'fields/html-form-field-text.php',
            $this->get_general_rendering_options($value)
        );
    }

    // Select boxes.
    public function render_field_select($value)
    {
        ecp_get_view(
            'fields/html-form-field-select.php',
            $this->get_general_rendering_options($value)
        );
    }

    // Radio inputs.
    public function render_field_radio($value)
    {
        ecp_get_view(
            'fields/html-form-field-radio.php',
            $this->get_general_rendering_options($value)
        );
    }

    // Checkbox input.
    public function render_field_checkbox($value)
    {
        $visibility_class = [];

        if (!isset($value['hide_if_checked'])) {
            $value['hide_if_checked'] = false;
        }
        if (!isset($value['show_if_checked'])) {
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

        ecp_get_view(
            'fields/html-form-field-checkbox.php',
            [
                'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                'type' => $value[Ecp_Gateway_Settings_Page::FIELD_TYPE],
                'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
                'tooltip' => $this->get_tooltip($value),
                'option_value' => $this->get_option(
                    $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                    $value[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]
                ),
                'class' => $value[Ecp_Gateway_Settings_Page::FIELD_CLASS],
                'custom_attributes' => $this->get_custom_attributes($value),
                'description' => $this->get_description($value),
                'checkboxgroup' => $value['checkboxgroup'],
                'visibility_class' => $visibility_class
            ]
        );
    }

    // Single page selects.
    public function render_field_single_select_page($value)
    {
        $args = [
            'name' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
            'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
            'sort_column' => 'menu_order',
            'sort_order' => 'ASC',
            'show_option_none' => ' ',
            'class' => $value[Ecp_Gateway_Settings_Page::FIELD_CLASS],
            'echo' => false,
            'selected' => absint(
                $this->get_option(
                    $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                    $value[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]
                )
            ),
            'post_status' => 'publish,private,draft',
        ];

        if (isset($value[Ecp_Gateway_Settings_Page::FIELD_ARGS])) {
            $args = wp_parse_args($value[Ecp_Gateway_Settings_Page::FIELD_ARGS], $args);
        }

        ecp_get_view(
            'fields/html-form-field-single_select_page.php',
            [
                'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
                'tooltip' => $this->get_tooltip($value),
                'css' => $value[Ecp_Gateway_Settings_Page::FIELD_STYLE],
                'class' => $value[Ecp_Gateway_Settings_Page::FIELD_CLASS],
                'description' => $this->get_description($value),
                'args' => $args
            ]
        );
    }

    // Single country selects.
    public function render_field_single_select_country($value)
    {
        $country_setting = (string)$this->get_option($value['id'], $value['default']);

        if (strstr($country_setting, ':')) {
            $country_setting = explode(':', $country_setting);
            $country = current($country_setting);
            $state = end($country_setting);
        } else {
            $country = $country_setting;
            $state = '*';
        }

        ecp_get_view(
            'fields/html-form-field-single-select-country.php',
            [
                'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
                'tooltip' => $this->get_tooltip($value),
                'css' => $value[Ecp_Gateway_Settings_Page::FIELD_STYLE],
                'description' => $this->get_description($value),
                'country' => $country,
                'state' => $state
            ]
        );
    }

    // Country multi selects.
    public function render_field_multi_select_country($value)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $countries = !empty($value['options']) ? $value['options'] : WC()->countries->countries;
        asort($countries);

        ecp_get_view(
            'fields/html-form-field-multi-select-country.php',
            [
                'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
                'tooltip' => $this->get_tooltip($value),
                'description' => $this->get_description($value),
                'countries' => $countries,
                'selections' => (array)$this->get_option(
                    $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                    $value[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]
                )
            ]
        );
    }

    // Days/months/years selector.
    public function render_field_relative_date_selector($value)
    {
        ecp_get_view(
            'fields/html-form-field-relative-date-selector.php',
            [
                'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
                'tooltip' => $this->get_tooltip($value),
                'option_value' => wc_parse_relative_date_option(
                    $this->get_option(
                        $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                        $value[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]
                    )
                ),
                'class' => $value[Ecp_Gateway_Settings_Page::FIELD_CLASS],
                'placeholder' => $value[Ecp_Gateway_Settings_Page::FIELD_PLACEHOLDER],
                'custom_attributes' => $this->get_custom_attributes($value),
                'description' => $this->get_description($value),
                'periods' => [
                    'days' => __('Day(s)', 'woocommerce'),
                    'weeks' => __('Week(s)', 'woocommerce'),
                    'months' => __('Month(s)', 'woocommerce'),
                    'years' => __('Year(s)', 'woocommerce'),
                ]
            ]
        );
    }

    public function normalize_field($value)
    {
        $property = [
            Ecp_Gateway_Settings_Page::FIELD_ID => '',
            Ecp_Gateway_Settings_Page::FIELD_TITLE => '',
            Ecp_Gateway_Settings_Page::FIELD_CLASS => '',
            Ecp_Gateway_Settings_Page::FIELD_STYLE => '',
            Ecp_Gateway_Settings_Page::FIELD_DEFAULT => '',
            Ecp_Gateway_Settings_Page::FIELD_DESC => '',
            Ecp_Gateway_Settings_Page::FIELD_TIP => false,
            Ecp_Gateway_Settings_Page::FIELD_PLACEHOLDER => '',
            Ecp_Gateway_Settings_Page::FIELD_SUFFIX => '',
            Ecp_Gateway_Settings_Page::FIELD_OPTIONS => null,
            'checkboxgroup' => null,
        ];

        foreach ($property as $key => $default) {
            if (!isset($value[$key])) {
                $value[$key] = $default;
            }
        }

        return $value;
    }

    private function get_custom_attributes($value)
    {
        // Custom attribute handling.
        $custom_attributes = array();

        if (
            !empty($value[Ecp_Gateway_Settings_Page::FIELD_CUSTOM])
            && is_array($value[Ecp_Gateway_Settings_Page::FIELD_CUSTOM])
        ) {
            foreach ($value[Ecp_Gateway_Settings_Page::FIELD_CUSTOM] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
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
        $description = '';

        if (
            true !== $value[Ecp_Gateway_Settings_Page::FIELD_TIP]
            && !empty($value[Ecp_Gateway_Settings_Page::FIELD_DESC])
        ) {
            $description = $value[Ecp_Gateway_Settings_Page::FIELD_DESC];
        }

        $upper = [
            Ecp_Gateway_Settings_Page::TYPE_AREA,
            Ecp_Gateway_Settings_Page::TYPE_RADIO
        ];

        if (
            $description
            && in_array($value[Ecp_Gateway_Settings_Page::FIELD_TYPE], $upper, true)
        ) {
            return '<p style="margin-top:0">' . wp_kses_post($description) . '</p>';
        }

        if (
            $description
            && $value[Ecp_Gateway_Settings_Page::FIELD_TYPE] === Ecp_Gateway_Settings_Page::TYPE_CHECKBOX
        ) {
            return wp_kses_post($description);
        }

        if ($description) {
            return '<span class="description">' . wp_kses_post($description) . '</span>';
        }

        return $description;
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
        $tooltip_html = '';

        if (true === $value[Ecp_Gateway_Settings_Page::FIELD_TIP]) {
            $tooltip_html = $value[Ecp_Gateway_Settings_Page::FIELD_DESC];
        } elseif (!empty($value[Ecp_Gateway_Settings_Page::FIELD_TIP])) {
            $tooltip_html = $value[Ecp_Gateway_Settings_Page::FIELD_TIP];
        }

        if ($tooltip_html && $value[Ecp_Gateway_Settings_Page::FIELD_TYPE] === Ecp_Gateway_Settings_Page::TYPE_CHECKBOX) {
            return '<p class="description">' . $tooltip_html . '</p>';
        }

        if ($tooltip_html) {
            return wc_help_tip($tooltip_html);
        }

        return $tooltip_html;
    }

    /**
     * Returns based form field array from setting options.
     *
     * @param array $value Setting options
     * @return array Form field as array
     */
    private function get_general_rendering_options($value)
    {
        return [
            'id' => $value[Ecp_Gateway_Settings_Page::FIELD_ID],
            'type' => $value[Ecp_Gateway_Settings_Page::FIELD_TYPE],
            'title' => $value[Ecp_Gateway_Settings_Page::FIELD_TITLE],
            'tooltip' => $this->get_tooltip($value),
            'css' => $value[Ecp_Gateway_Settings_Page::FIELD_STYLE],
            'option_value' => $this->get_option(
                $value[Ecp_Gateway_Settings_Page::FIELD_ID],
                $value[Ecp_Gateway_Settings_Page::FIELD_DEFAULT]
            ),
            'options' => $value[Ecp_Gateway_Settings_Page::FIELD_OPTIONS],
            'class' => $value[Ecp_Gateway_Settings_Page::FIELD_CLASS],
            'custom_attributes' => $this->get_custom_attributes($value),
            'description' => $this->get_description($value),
            'placeholder' => $value[Ecp_Gateway_Settings_Page::FIELD_PLACEHOLDER],
        ];
    }
}