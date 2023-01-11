<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Subscriptions class
 *
 * @class    Ecp_Gateway_Settings_Subscriptions
 * @version  dev
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Subscriptions extends Ecp_Gateway_Settings_Page
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'subscriptions';

    /**
     * Shop section identifier
     */
    const RENEWAL_OPTIONS = 'renewal_options';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Subscriptions', 'Settings page', 'woo-ecommpay');

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_subscriptions']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_subscriptions()
    {
        $settings = [
            [
                self::FIELD_ID => self::RENEWAL_OPTIONS,
                self::FIELD_TITLE => _x('Renewal', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_SUBSCRIPTION_AUTOCOMPLETE,
                self::FIELD_TITLE => _x('Complete renewal orders', 'Settings subscription renewal', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings subscription renewal', 'woo-ecommpay'),
                self::FIELD_CLASS => 'ecp-two-step-purchase-only',
                self::FIELD_TIP => _x(
                    'Automatically mark a renewal order as complete on successful recurring payments.',
                    'Settings subscription renewal',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::RENEWAL_OPTIONS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ecp_' . $this->id . '_settings', $settings);
    }
}