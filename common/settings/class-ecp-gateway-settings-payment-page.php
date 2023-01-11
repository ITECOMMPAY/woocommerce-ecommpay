<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Settings_Payment_Page class
 *
 * @class    Ecp_Gateway_Settings_Payment_Page
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Payment_Page extends Ecp_Gateway_Settings_Page
{
    // region Constants

    /**
     * Internal identifier
     */
    const ID = 'payment_page';

    /**
     * Shop section identifier
     */
    const SHOP_OPTIONS = 'shop_options';

    /**
     * Payment form section identifier
     */
    const FORM_OPTIONS = 'form_options';

    // endregion

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = self::ID;
        $this->label = _x('Payment Page', 'Settings page', 'woo-ecommpay');

        parent::__construct();

        add_filter('ecp_get_settings_' . $this->id, [$this, 'get_settings_payment_page']);
    }

    /**
     * Returns the Payment Page fields settings as array.
     *
     * @return array
     */
    public function get_settings_payment_page()
    {
        $settings = [
            [
                self::FIELD_ID => self::SHOP_OPTIONS,
                self::FIELD_TITLE => _x('Shop setup', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_TITLE,
                self::FIELD_TITLE => _x('Title', 'Settings shop setup', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'This controls the title which the user sees during checkout.',
                    'Settings shop setup',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x('ECOMMPAY', 'Settings shop setup', 'woo-ecommpay'),
            ],
            [
                self::FIELD_ID => self::OPTION_DESCRIPTION,
                self::FIELD_TITLE => _x('Customer Message', 'Settings shop setup','woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_AREA,
                self::FIELD_TIP => _x(
                    'This controls the description which the user sees during checkout.',
                    'Settings shop setup',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x(
                    'You will be redirected to ECOMMPAY payment page. All data you enter in that page are secured',
                    'Settings shop setup',
                    'woo-ecommpay'
                ),
            ],
            [
                self::FIELD_ID => self::OPTION_CHECKOUT_BUTTON_TEXT,
                self::FIELD_TITLE => _x('Order button text', 'Settings shop setup', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_TEXT,
                self::FIELD_TIP => _x(
                    'Text shown on the submit button when choosing payment method.',
                    'Settings shop setup',
                    'woo-ecommpay'
                ),
                self::FIELD_DEFAULT => _x('Go to payment', 'Settings shop setup', 'woo-ecommpay'),
            ],
            [
                self::FIELD_ID => self::SHOP_OPTIONS,
                self::FIELD_TYPE => self::TYPE_END,
            ],

            [
                self::FIELD_ID => self::FORM_OPTIONS,
                self::FIELD_TITLE => _x('Payment form', 'Settings section', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_START,
                self::FIELD_DESC => '',
            ],
            [
                self::FIELD_ID => self::OPTION_MODE,
                self::FIELD_TITLE => _x('Display mode', 'Settings payment form','woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_DROPDOWN,
                self::FIELD_TIP => _x(
                    'Payment page display mode',
                    'Settings payment form',
                    'woo-ecommpay'
                ),
                self::FIELD_OPTIONS => [
                    self::MODE_REDIRECT => _x('Redirect', 'Display mode', 'woo-ecommpay'),
                    self::MODE_POPUP => _x('Popup', 'Display mode', 'woo-ecommpay'),
                    self::MODE_IFRAME => _x('iFrame', 'Display mode', 'woo-ecommpay'),
                ],
                self::FIELD_DEFAULT => self::MODE_REDIRECT,
            ],
            [
                self::FIELD_ID => self::OPTION_POPUP_MISS_CLICK,
                self::FIELD_TITLE => _x('Close on miss click', 'Settings payment form', 'woo-ecommpay'),
                self::FIELD_TYPE => self::TYPE_CHECKBOX,
                self::FIELD_DESC => _x(
                    'Close popup window on mouse miss click',
                    'Settings payment form',
                    'woo-ecommpay'),
                self::FIELD_DEFAULT => self::NO
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
                self::FIELD_ID => self::FORM_OPTIONS,
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
    private function language_options()
    {
        return [
            'by_customer_browser' => _x('By Customer browser setting', 'Language', 'woo-ecommpay'),
            'by_wordpress' => _x('By WordPress', 'Language', 'woo-ecommpay'),
            'EN' => _x('English', 'Language', 'woo-ecommpay'),
            'FR' => _x('France', 'Language', 'woo-ecommpay'),
            'IT' => _x('Italian', 'Language', 'woo-ecommpay'),
            'DE' => _x('Germany', 'Language', 'woo-ecommpay'),
            'ES' => _x('Spanish', 'Language', 'woo-ecommpay'),
            'RU' => _x('Russian', 'Language', 'woo-ecommpay'),
        ];
    }
}