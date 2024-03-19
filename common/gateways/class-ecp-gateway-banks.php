<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * <h2>ECOMMPAY Banks Gateway.</h2>
 *
 * @class    Ecp_Gateway_Banks
 * @version  2.0.0
 * @package  Ecp_Gateway/Gateways
 * @category Class
 */
class Ecp_Gateway_Banks extends Ecp_Gateway
{
    const PAYMENT_METHOD = 'banks';
    // region Properties
    /**
     * @inheritDoc
     * @override
     * @var string[]
     * @since 3.0.0
     */
    public $supports = [
        'products',
    ];

    /**
     * <h2>Instance of ECOMMPAY Gateway.</h2>
     *
     * @var Ecp_Gateway
     * @since 2.0.0
     */
    private static $_instance;

    // endregion

    // region Static methods

    /**
     * <h2>Returns a new instance of self, if it does not already exist.</h2>
     *
     * @return static
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    // endregion

    /**
     * <h2>ECOMMPAY Banks Gateway constructor.</h2>
     */
    public function __construct()
    {
        $this->id = Ecp_Gateway_Settings_Banks::ID;
        $this->method_title = __('ECOMMPAY Open banking', 'woo-ecommpay');
        $this->method_description = __('Accept payments via Open Banking.', 'woo-ecommpay');
        $this->has_fields = false;
        $this->title = $this->get_option(Ecp_Gateway_Settings::OPTION_TITLE);
        $this->order_button_text = $this->get_option(Ecp_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT);
        $this->enabled = $this->get_option(Ecp_Gateway_Settings::OPTION_ENABLED);
        $this->icon = $this->get_icon();

        if ($this->is_enabled(Ecp_Gateway_Settings::OPTION_SHOW_DESCRIPTION)) {
            $this->description = $this->get_option(Ecp_Gateway_Settings::OPTION_DESCRIPTION);
        }

        parent::__construct();
    }

    /**
     * @inheritDoc
     * @override
     * @return array
     * @since 3.0.0
     */
    public function apply_payment_args($values, $order)
    {
        $values['force_payment_group'] = 'openbanking';

        return parent::apply_payment_args($values, $order);
    }

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
     * @since 3.0.0
     */
    public function process_payment($order_id)
    {
        $order = ecp_get_order($order_id);
        $options = ecp_payment_page()->get_request_url($order, $this);
        $payment_page_url = ecp_payment_page()->get_url() . '/payment?' . http_build_query($options);

        return [
            'result' => 'success',
            'redirect' => $payment_page_url,
            'order_id' => $order_id,
        ];
    }

    /**
     * @inheritDoc
     * @override
     * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
     * @since 3.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return false;
    }

    /**
     * @inheritDoc
     * <p>If false, the automatic refund button is hidden in the UI.</p>
     *
     * @param WC_Order $order <p>Order object.</p>
     * @override
     * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
     * @since 3.0.0
     */
    public function can_refund_order($order)
    {
        return false;
    }

    /**
     * @inheritDoc
     * @override
     * @return string DOM element img as a string
     * @since 3.0.0
     */
    public function get_icon()
    {
        $icon_str = '<img src="' . ecp_img_url(self::PAYMENT_METHOD . '.svg')
            . '" style="max-width: 50px" alt="' . self::PAYMENT_METHOD . '" />';

        return apply_filters('woocommerce_gateway_icon', $icon_str, $this->id);
    }
}
