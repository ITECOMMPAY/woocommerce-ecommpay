<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Products class
 *
 * @class    Ecp_Gateway_Settings_Products
 * @version  dev
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Products extends Ecp_Gateway_Settings_Page
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'products';

    /**
     * Shop section identifier
     */
    const STATE_OPTIONS = 'state_options';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Products', 'Settings page', 'woo-ecommpay');

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_products']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_products()
    {
        $settings = [
            [
                self::FIELD_ID => self::STATE_OPTIONS,
                self::FIELD_TITLE => _x('State Transitions', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_SUBSCRIPTION_AUTOCOMPLETE,
                self::FIELD_TITLE => _x(
                    'Virtual products',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Auto-complete order', 'Settings state transition', 'woo-ecommpay'),
                self::FIELD_CLASS => 'ecp-two-step-purchase-only',
                self::FIELD_TIP => _x(
                    'Automatically mark an order as completed on successful payments if it contains only virtual products.',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::STATE_OPTIONS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ecp_' . $this->id . '_settings', $settings);
    }
}
