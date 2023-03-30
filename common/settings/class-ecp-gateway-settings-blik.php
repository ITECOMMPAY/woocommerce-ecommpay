<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Blik class
 *
 * @class    Ecp_Gateway_Settings_Blik
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Blik extends Ecp_Gateway_Settings
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'ecommpay-blik';

    /**
     * Shop section identifier
     */
    const BLIK_SETTINGS = 'blik_settings';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Blik', 'Settings page', 'woo-ecommpay');
        $this->icon = 'blik.svg';

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_blik_methods']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_blik_methods()
    {
        $settings = [
            [
                self::FIELD_ID => self::BLIK_SETTINGS,
                self::FIELD_TITLE => _x('Blik settings', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_ENABLED,
                self::FIELD_TITLE => _x('Enable/Disable', 'Settings blik payments', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x('Enable', 'Settings blik payments', 'woo-ecommpay'),
                self::FIELD_TIP => _x(
                    'Before enabling the payment method please contact support@ecommpay.com',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::NO
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings blik payments', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the tittle which the user sees during checkout.',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x('Blik', 'Settings blik payments', 'woo-ecommpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_SHOW_DESCRIPTION,
                self::FIELD_TITLE => _x('Show Description', 'Settings blik payments', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Display the payment method description which user sees during checkout.',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => self::YES,
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Description', 'Settings blik payments','woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout.',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x(
                    'You will be redirected to finish your Blik payment.',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings blik payments', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings blik payments',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings blik payments', 'woo-ecommpay'),
            ],
            [
                self::FIELD_ID => self::BLIK_SETTINGS,
                self::FIELD_TYPE => self::TYPE_END,
            ],
        ];

        return apply_filters('ecp_' . $this->id . '_settings', $settings);
    }
}