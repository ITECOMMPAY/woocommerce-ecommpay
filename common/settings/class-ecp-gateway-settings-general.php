<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_General class
 *
 * @class    Ecp_Gateway_Settings_General
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_General extends Ecp_Gateway_Settings
{
    const OPTION_TEST = 'test';
    const OPTION_PROJECT_ID = 'project_id';
    const OPTION_SECRET_KEY = 'salt';
    const OPTION_DELETE_ON_UNINSTALL = 'delete_orders_on_uninstall';
    const OPTION_CACHING_ENABLED = 'caching_enabled';
    const OPTION_CACHING_EXPIRATION = 'caching_expiration';
    const OPTION_LANGUAGE = 'language';
    const OPTION_LOG_LEVEL = 'log_level';
    const OPTION_TRANSACTION_INFO = 'orders_transaction_info';
    const OPTION_CUSTOM_VARIABLES = 'custom_variables';

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

    // ECOMMPAY available language modes
    const LANG_BY_CUSTOMER = 'by_customer_browser';
    const LANG_BY_WORDPRESS = 'by_wordpress';
    const LANG_ENGLISH = 'EN';
    const LANG_FRANCE = 'FR';
    const LANG_ITALIAN = 'IT';
    const LANG_GERMANY = 'DE';
    const LANG_SPANISH = 'ES';
    const LANG_RUSSIAN = 'RU';

    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'general';

    /**
     * General section identifier
     */
    const SECTION_GENERAL = 'general_options';

    const CACHING_OPTIONS = 'caching_options';
    const ADMIN_OPTIONS = 'admin_options';

    /**
     * Uninstall section identifier
     */
    const SECTION_UNINSTALL = 'uninstall_options';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('General', 'Settings page', 'woo-ecommpay');
        $this->icon = 'ecommpay.svg';

        parent::__construct();

        add_filter('ecp_' . $this->id . '_settings', [$this, 'add_uninstall_setting']);
        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_general']);
    }

    /**
     * Returns the general fields settings as array
     *
     * @return array
     */
    public function get_settings_general()
    {
        $settings = [
            [
                self::FIELD_ID => self::SECTION_GENERAL,
                self::FIELD_TITLE => _x('General Settings', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
            ],

            [
                self::FIELD_ID => self::OPTION_TEST,
                self::FIELD_TITLE => _x('Test mode', 'Settings integration', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Enable test mode integration',
                    'Settings integration',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_PROJECT_ID,
                self::FIELD_TITLE => _x('Project ID', 'Settings integration', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_NUMBER,
                self::FIELD_TIP => _x(
                    'Your project ID you could get from ECOMMPAY helpdesk. Leave it blank if test mode',
                    'Settings integration',
                    'woo-ecommpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_SECRET_KEY,
                self::FIELD_TITLE => _x('Secret key', 'Settings integration', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_PASSWORD,
                self::FIELD_TIP => _x(
                    'Secret key which is using to sign payment request. You could get it from ECOMMPAY helpdesk',
                    'Settings integration',
                    'woo-ecommpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_LANGUAGE,
                self::FIELD_TITLE => _x('Language', 'Settings payment form', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_DROPDOWN,
                self::FIELD_TIP => _x(
                    'Payment page language',
                    'Settings payment form',
                    'woo-ecommpay'
                ),
                self::FIELD_OPTIONS => $this->language_options(),
                self::FIELD_DEFAULT => 'by_customer_browser',
            ],
            [
                self::FIELD_ID => self::SECTION_GENERAL,
                self::FIELD_TYPE => self::TYPE_END,
            ],

            [
                self::FIELD_ID => 'advanced',
                self::FIELD_TITLE => _x('Advanced options', 'Settings general form', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_TOGGLE_START
            ],

            [
                self::FIELD_ID => self::CACHING_OPTIONS,
                self::FIELD_TITLE => _x('Transaction Cache', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => _x(
                    'Transaction cache is strongly recommended enable!',
                    'Settings cache',
                    'woo-ecommpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CACHING_ENABLED,
                self::FIELD_TITLE => _x('Enable Caching', 'Settings cache', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Enable',
                    'Settings cache',
                    'woo-ecommpay'
                ),
                self::FIELD_TIP => _x(
                    'Caches transaction data to improve application and web-server performance.',
                    'Settings cache',
                    'woo-ecommpay'
                ),
                self::FIELD_SUFFIX => _x('Recommended.', 'Settings cache', 'woo-ecommpay'),
                self::FIELD_DEFAULT => self::YES,
            ],
            [
                self::FIELD_ID => self::OPTION_CACHING_EXPIRATION,
                self::FIELD_TITLE => _x('Cache Expiration', 'Settings cache', 'woo-ecommpay'),
                self::FIELD_TYPE => 'number',
                self::FIELD_TIP => _x(
                    'Time in seconds for how long a transaction should be cached.',
                    'Settings cache',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => 7 * DAY_IN_SECONDS,
                self::FIELD_DESC => _x('Default: 604800 (7 days).', 'Settings cache', 'woo-ecommpay'),
            ],
            [
                self::FIELD_ID => self::CACHING_OPTIONS,
                self::FIELD_TYPE => self::TYPE_END,
            ],

            [
                self::FIELD_ID => self::ADMIN_OPTIONS,
                self::FIELD_TITLE => _x('Shop Admin Setup', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
            ],
            [
                self::FIELD_ID => self::OPTION_LOG_LEVEL,
                self::FIELD_TITLE => _x('Log level', 'Settings shop admin setup', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_DROPDOWN,
                self::FIELD_TIP => _x(
                    'Level of save log data.',
                    'Settings shop admin setup',
                    'woo-ecommpay'
                ),
                self::FIELD_OPTIONS => [
                    WC_Log_Levels::EMERGENCY => _x('Emergency', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::CRITICAL => _x('Critical', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::ALERT => _x('Alert', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::ERROR => _x('Error', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::WARNING => _x('Warning', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::NOTICE => _x('Notice', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::INFO => _x('Info', 'Log level', 'woo-ecommpay'),
                    WC_Log_Levels::DEBUG => _x('Debug', 'Log level', 'woo-ecommpay'),
                ],
                self::FIELD_DEFAULT => WC_Log_Levels::ERROR,
                self::FIELD_DESC => sprintf(
                    _x('Default: %s', 'Settings shop admin setup', 'woo-ecommpay'),
                    _x('Error', 'Log level', 'woo-ecommpay')
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_TRANSACTION_INFO,
                self::FIELD_TITLE => _x('Fetch Payment Info', 'Settings shop admin setup', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Enable',
                    'Settings shop admin setup',
                    'woo-ecommpay'
                ),
                self::FIELD_TIP => _x(
                    'Show payment information in the order overview.',
                    'Settings shop admin setup',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::YES,
            ],
            [
                self::FIELD_ID => self::OPTION_CUSTOM_VARIABLES,
                self::FIELD_TITLE => _x('Additional Information', 'Settings shop admin setup', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_MULTI_SELECT,
                self::FIELD_CLASS => 'wc-enhanced-select',
                self::FIELD_STYLE => 'width: 450px;',
                self::FIELD_DEFAULT => '',
                self::FIELD_TIP => _x(
                    'Selected options will store the specific data on your payment inside your ECOMMPAY Dashboard.',
                    'Settings shop admin setup',
                    'woo-ecommpay'
                ),
                self::FIELD_OPTIONS => $this->custom_variable_options(),
                self::FIELD_CUSTOM => [
                    'data-placeholder' => _x('Select order data', 'Settings shop admin setup', 'woo-ecommpay')
                ]
            ],
            [
                self::FIELD_ID => self::ADMIN_OPTIONS,
                self::FIELD_TYPE => self::TYPE_END,
            ],

            [
                self::FIELD_ID => 'advanced',
                self::FIELD_TYPE => self::TYPE_TOGGLE_END
            ],

        ];

        // ToDo: Must be implements in next versions.
        // return apply_filters('ecp_' . $this->id . '_settings', $settings);
        return $settings;
    }

    /**
     * Add uninstall settings only for Super Admin
     *
     * @param $settings
     *
     * @return array
     */
    public function add_uninstall_setting($settings)
    {
        if (!is_multisite() || (is_main_site())) {
            $settings[] = [
                self::FIELD_ID => self::SECTION_UNINSTALL,
                self::FIELD_TITLE => _x('Uninstalling', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ];

            $settings[] = [
                self::FIELD_ID => self::OPTION_DELETE_ON_UNINSTALL,
                self::FIELD_TITLE => _x('Delete orders', 'Settings uninstalling', 'woo-ecommpay'),
                self::FIELD_DESC => _x(
                    'Delete orders with payment via ECOMMPAY when uninstalling plugin.',
                    'Settings uninstalling',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
            ];

            $settings[] = [
                self::FIELD_ID => self::SECTION_UNINSTALL,
                self::FIELD_TYPE => self::TYPE_END,
            ];
        }

        return $settings;
    }


    /**
     * Provides a list of custom variable options used in the settings
     *
     * @return array
     */
    private function language_options()
    {
        return [
            self::LANG_BY_CUSTOMER => _x('By Customer browser setting', 'Language', 'woo-ecommpay'),
            self::LANG_BY_WORDPRESS => _x('By WordPress', 'Language', 'woo-ecommpay'),
            self::LANG_ENGLISH => _x('English', 'Language', 'woo-ecommpay'),
            self::LANG_FRANCE => _x('France', 'Language', 'woo-ecommpay'),
            self::LANG_ITALIAN => _x('Italian', 'Language', 'woo-ecommpay'),
            self::LANG_GERMANY => _x('Germany', 'Language', 'woo-ecommpay'),
            self::LANG_SPANISH => _x('Spanish', 'Language', 'woo-ecommpay'),
            self::LANG_RUSSIAN => _x('Russian', 'Language', 'woo-ecommpay'),
        ];
    }


    /**
     * Provides a list of custom variable options used in the settings
     *
     * @return array
     */
    private function custom_variable_options()
    {
        $options = [
            self::CUSTOM_CUSTOMER_EMAIL => _x('Customer: Email', 'Custom variables', 'woo-ecommpay'),
            self::CUSTOM_CUSTOMER_PHONE => _x('Customer: Phone number', 'Custom variables', 'woo-ecommpay'),
            self::CUSTOM_CUSTOMER_NAME => _x('Customer: Full name', 'Custom variables', 'woo-ecommpay'),
            self::CUSTOM_CUSTOMER_ADDRESS => _x('Customer: Address', 'Custom variables', 'woo-ecommpay'),
            //ToDo: Check and release in next version.
            // self::CUSTOM_ACCOUNT_INFO => _x('3D Secure: Account info', 'Custom variables', 'woo-ecommpay'),
            // self::CUSTOM_MPI_RESULT => _x('3D Secure: Last MPI Result', 'Custom variables', 'woo-ecommpay'),
            // self::CUSTOM_SHIPPING_DATA => _x('3D Secure: Shipping Details', 'Custom variables', 'woo-ecommpay'),
            self::CUSTOM_BILLING_DATA => _x('Billing Details', 'Custom variables', 'woo-ecommpay'),
            self::CUSTOM_RECEIPT_DATA => _x('Receipt Details', 'Custom variables', 'woo-ecommpay'),
        ];

        //ToDo: Need to be developed in the next versions.
        // Maybe only for Russia and additional settings.
        // if ((new WC_Countries())->get_base_country() === 'RU') {
        //     $options[self::CUSTOM_CASH_VOUCHER] = _x('Cash Voucher Details', 'Custom variables', 'woo-ecommpay');
        // }

        asort($options);

        return $options;
    }


    /**
     * @inheritDoc
     */
    public function output()
    {
        ecp_get_view('html-admin-settings-log.php');

        parent::output();
    }
}