<?php

defined('ABSPATH') || exit;

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
abstract class Ecp_Gateway_Settings
{
    // region Constants

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

    // Yes and No values
    const YES = 'yes';
    const NO = 'no';

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
    const TYPE_TOGGLE_START = 'toggle_start';
    const TYPE_TOGGLE_END = 'toggle_end';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    const TYPE_NUMBER = 'number';
    const TYPE_PASSWORD = 'password';
    const TYPE_TEXT = 'text';
    const TYPE_AREA = 'textarea';
    const TYPE_DROPDOWN = 'select';
    const TYPE_MULTI_SELECT = 'multiselect';

    // endregion


    /**
     * Setting page identifier.
     *
     * @var string
     */
    protected $id = '';

    /**
     * Setting page label.
     *
     * @var string
     */
    protected $label = '';

    /**
     * @var ?string
     */
    protected $icon = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('ecp_settings_tabs_array', [$this, 'add_settings_tab'], 20);
        add_action('ecp_settings_' . $this->id, [$this, 'output']);
        add_action('ecp_settings_save_' . $this->id, [$this, 'save']);
    }

    /**
     * Get settings page ID.
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Get settings page label.
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * Add this page to settings.
     *
     * @param array $pages
     *
     * @return array
     */
    public function add_settings_tab($pages)
    {
        $pages[$this->id] = [
            'label' => $this->label,
            'icon' => $this->icon
        ];

        return $pages;
    }

    /**
     * Returns the fields settings as array.
     *
     * @return array
     */
    public function get_settings()
    {
        return apply_filters('ecp_get_settings_' . $this->id, []);
    }

    /**
     * Output the settings.
     */
    public function output()
    {
        ecommpay()->settings()->output_fields($this);
    }

    /**
     * Save settings.
     */
    public function save()
    {
        $nonce = wc_get_var($_REQUEST['_wpnonce']);

        if ($nonce === null || !wp_verify_nonce($nonce, 'woocommerce-settings')) {
            die(__('Action failed. Please refresh the page and retry.', 'woo-ecommpay'));
        }

        ecp_get_log()->debug('Run saving plugin settings. Section:', $this->id);

        ecommpay()->settings()->save_fields($this);
    }
}