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
class Ecp_Gateway_Settings_General extends Ecp_Gateway_Settings_Page
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'general';

    /**
     * General section identifier
     */
    const SECTION_GENERAL = 'general_options';

    /**
     * Account section identifier
     */
    const SECTION_ACCOUNT = 'account_options';

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
                self::FIELD_TITLE => _x('General options', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
            ],

            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable', 'Settings general', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable plugin', 'Settings general', 'woo-ecommpay'),
                self::FIELD_DEFAULT => self::YES
            ],

            [
                self::FIELD_ID => self::SECTION_GENERAL,
                self::FIELD_TYPE => self::TYPE_END,
            ],

            [
                self::FIELD_ID => self::SECTION_ACCOUNT,
                self::FIELD_TITLE => _x('Integration', 'Settings section', 'woo-ecommpay'),
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
                self::FIELD_ID => self::SECTION_ACCOUNT,
                self::FIELD_TYPE => self::TYPE_END,
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
}