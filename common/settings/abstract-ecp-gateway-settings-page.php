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
abstract class Ecp_Gateway_Settings_Page
{
    // region Constants

    // ECOMMPAY Payment Gateway plugin options
    const OPTION_ENABLED = 'enabled';
    const OPTION_TEST = 'ecommpay_test';
    const OPTION_PROJECT_ID = 'ecommpay_project_id';
    const OPTION_SECRET_KEY = 'ecommpay_salt';
    const OPTION_DELETE_ON_UNINSTALL = 'ecp_delete_orders_on_uninstall';
    const OPTION_CACHING_ENABLED = 'ecommpay_caching_enabled';
    const OPTION_CACHING_EXPIRATION = 'ecommpay_caching_expiration';
    const OPTION_TITLE = 'title';
    const OPTION_DESCRIPTION = 'description';
    const OPTION_CHECKOUT_BUTTON_TEXT = 'checkout_button_text';
    const OPTION_MODE = 'ecommpay_mode';
    const OPTION_POPUP_MISS_CLICK = 'ecommpay_close_on_miss_click';
    const OPTION_LANGUAGE = 'ecommpay_language';
    const OPTION_LOG_LEVEL = 'ecommpay_log_level';
    const OPTION_TRANSACTION_INFO = 'ecommpay_orders_transaction_info';
    const OPTION_CUSTOM_VARIABLES = 'ecommpay_custom_variables';
    const OPTION_SUBSCRIPTION_AUTOCOMPLETE = 'subscription_autocomplete_renewal_orders';

    // ECOMMPAY Payment Page Display modes
    const MODE_REDIRECT = 'redirect';
    const MODE_POPUP = 'popup';
    const MODE_IFRAME = 'iframe';

    // ECOMMPAY Custom variables data
    const CUSTOM_CUSTOMER_EMAIL = 'customer_email';
    const CUSTOM_CUSTOMER_PHONE = 'customer_phone';
    const CUSTOM_CUSTOMER_NAME = 'customer_full_name';
    const CUSTOM_CUSTOMER_ADDRESS = 'customer_address';
    const CUSTOM_ACCOUNT_INFO = 'customer_account_info';
    const CUSTOM_MPI_RESULT = 'mpi_result';
    const CUSTOM_SHIPPING_DATA = 'shipping_data';
    const CUSTOM_BILLING_DATA = 'billing_data';
    const CUSTOM_RECEIPT_DATA = 'receipt_data';
    const CUSTOM_CASH_VOUCHER = 'cash_voucher_data';

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

    const TYPE_START = 'title';
    const TYPE_END = 'section_end';
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
        $pages[$this->id] = $this->label;

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
        Ecp_Gateway_Settings::get_instance()->output_fields($this->get_settings());
    }

    /**
     * Save settings.
     */
    public function save()
    {
        Ecp_Gateway_Settings::get_instance()->save_fields($this->get_settings());
    }
}