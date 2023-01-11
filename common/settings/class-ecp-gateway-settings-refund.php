<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Refund class
 *
 * @class    Ecp_Gateway_Settings_Refund
 * @version  dev
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Refund extends Ecp_Gateway_Settings_Page
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'refund';

    /**
     * Shop section identifier
     */
    const STATE_OPTIONS = 'renewal_options';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Refund', 'Settings page', 'woo-ecommpay');

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_refunds']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_refunds()
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
                    'Auto-refund payment',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('When the order status changed to "Refunded"', 'Settings state transition', 'woo-ecommpay'),
                self::FIELD_CLASS => 'ecp-two-step-purchase-only',
                self::FIELD_TIP => _x(
                    'Attempts to automatically refund a payment when the order status is manually changed to "Refunded"',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_SUBSCRIPTION_AUTOCOMPLETE,
                self::FIELD_TITLE => _x(
                    'Auto-refund payment',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('When the order status changed to "Cancelled"', 'Settings state transition', 'woo-ecommpay'),
                self::FIELD_CLASS => 'ecp-two-step-purchase-only',
                self::FIELD_TIP => _x(
                    'Attempts to automatically refund a payment when the order status is manually changed to "Cancelled"',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO,
            ],
            [
                self::FIELD_ID => self::OPTION_SUBSCRIPTION_AUTOCOMPLETE,
                self::FIELD_TITLE => _x(
                    'Allows refund when order is completed',
                    'Settings state transition',
                    'woo-ecommpay'
                ),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('When the order status changed to "Cancelled"', 'Settings state transition', 'woo-ecommpay'),
                self::FIELD_CLASS => 'ecp-two-step-purchase-only',
                self::FIELD_TIP => _x(
                    'Attempts to automatically refund a payment when the order status is manually changed to "Cancelled"',
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
