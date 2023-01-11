<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Admin class
 *
 * @class    Ecp_Gateway_Settings_Admin
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Admin extends Ecp_Gateway_Settings_Page
{

    const CACHING_OPTIONS = 'caching_options';
    const ADMIN_OPTIONS = 'admin_options';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'shop_admin';
        $this->label = _x('Shop admin', 'Settings page', 'woo-ecommpay');

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_administration'], 10, 0);
    }

    /**
     * Returns the administration fields settings as array
     *
     * @return array
     */
    public function get_settings_administration()
    {
        $settings = [
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
        ];

        return apply_filters('ecp_' . $this->id . '_settings', $settings);
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
