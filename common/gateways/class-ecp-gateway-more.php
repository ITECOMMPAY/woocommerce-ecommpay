<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  2.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class Ecp_Gateway_More extends Ecp_Gateway
{
    // region Properties
    /**
     * @inheritDoc
     * @override
     * @var string[]
     * @since 1.0.0
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
     * <h2>ECOMMPAY Gateway constructor.</h2>
     */
    public function __construct()
    {
        $this->id = Ecp_Gateway_Settings_More::ID;
        $this->method_title = __('ECOMMPAY', 'woo-ecommpay');
        $this->method_description = __('Open the payment page with all payment methods or select an additional alternative payment method.', 'woo-ecommpay');
        $this->has_fields = false;
        $this->title = $this->get_option(Ecp_Gateway_Settings::OPTION_TITLE);
        $this->order_button_text = $this->get_option(Ecp_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT);
        $this->enabled = $this->get_option(Ecp_Gateway_Settings::OPTION_ENABLED);
        $this->icon = '';

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
        $force = $this->get_option(Ecp_Gateway_Settings::OPTION_FORCE_CODE);

        if ($force !== null && $force !== '') {
            $values = apply_filters('ecp_append_force_mode', $values, $force);
        }

        return parent::apply_payment_args($values, $order);
    }

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>If the signature could not be created.</p>
     * @since 2.0.0
     */
    public function process_payment($order_id)
    {
        $order = ecp_get_order($order_id);
//        $order->update_status('pending', _x('Awaiting payment', 'Status payment', 'woo-ecommpay'));

        return [
            'result' => 'success',
            'redirect' => ecp_payment_page()->get_request_url($order, $this),
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
}
