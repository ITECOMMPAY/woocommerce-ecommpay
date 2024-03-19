<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * <h2>ECOMMPAY Gateway.</h2>
 *
 * @class    WC_Gateway_Ecommpay
 * @version  3.0.0
 * @package  Woocommerce_Ecommpay/Classes
 * @category Class
 */
class Ecp_Gateway_Card extends Ecp_Gateway
{
    const PAYMENT_METHOD = 'card';

    // region Properties

    /**
     * @inheritDoc
     * @override
     * @var string[]
     * @since 1.0.0
     */
    public $supports = [
        'subscriptions',
        'products',
        'subscription_cancellation',
        'subscription_reactivation',
        'subscription_suspension',
        'subscription_amount_changes',
        'subscription_date_changes',
        //        'subscription_payment_method_change_admin',
//        'subscription_payment_method_change_customer',
        'refunds',
        'multiple_subscriptions',
        //        'pre-orders',
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
        $this->id = Ecp_Gateway_Settings_Card::ID;
        $this->method_title = __('ECOMMPAY Cards', 'woo-ecommpay');
        $this->method_description = __('Accept card payments via ECOMMPAY.', 'woo-ecommpay');
        $this->has_fields = false;
        $this->title = $this->get_option(Ecp_Gateway_Settings::OPTION_TITLE);
        $this->order_button_text = $this->get_option(Ecp_Gateway_Settings::OPTION_CHECKOUT_BUTTON_TEXT);
        $this->enabled = $this->get_option(Ecp_Gateway_Settings::OPTION_ENABLED);
        $this->icon = $this->get_icon();

        if ($this->is_enabled(Ecp_Gateway_Settings::OPTION_SHOW_DESCRIPTION)) {
            $this->description = $this->get_option(Ecp_Gateway_Settings::OPTION_DESCRIPTION);
        }
        if ($this->get_option(Ecp_Gateway_Settings::OPTION_MODE) == Ecp_Gateway_Settings::MODE_EMBEDDED) {
            $this->description = '<div id="ecommpay-loader-embedded"></div><div id="ecommpay-iframe-embedded"></div>';
        }

        parent::__construct();

        $this->init_subscription();
    }

    /**
     * @inheritDoc
     * @override
     * @return array
     * @since 3.0.0
     */
    public function apply_payment_args($values, $order)
    {
        $amount = ecp_price_multiply($order->get_total(), $order->get_currency());
        $display_mode = $this->get_option(Ecp_Gateway_Settings::OPTION_MODE, Ecp_Gateway_Settings::MODE_REDIRECT);

        $values = apply_filters('ecp_append_card_operation_type', $values, $order);
        // Setup Payment Page Operation Mode
        $values = apply_filters('ecp_append_operation_mode', $values, $amount > 0 ? 'purchase' : 'card_verify');
        // Setup Payment Page Force Mode
        $values = apply_filters('ecp_append_force_mode', $values, self::PAYMENT_METHOD);
        // Setup Payment Page Display Mode
        $values = apply_filters(
            'ecp_append_display_mode',
            $values,
            $display_mode,
            ecp_is_enabled(Ecp_Gateway_Settings::OPTION_POPUP_MISS_CLICK, $this->id)
        );
        // Setup Recurring (Subscriptions)
        $values = apply_filters('ecp_append_recurring', $values, $order);

        return parent::apply_payment_args($values, $order);
    }

    public function get_refund_endpoint($order)
    {
        return Ecp_Gateway_Payment_Methods::get_code($order);
    }

    /**
     * @inheritDoc
     * @override
     * @return array <p>Settings for redirecting to the ECOMMPAY payment page.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>If the signature could not be created.</p>
     * @since 3.0.0
     */
    public function process_payment($order_id)
    {
        $order = ecp_get_order($order_id);
        $payment_id = $_POST['payment_id'];

        if (!empty ($payment_id)) {
            $order->set_payment_id($payment_id);
        }

        $options = ecp_payment_page()->get_request_url($order, $this);
        $payment_page_url = ecp_payment_page()->get_url() . '/payment?' . http_build_query($options);

        return [
            'result' => 'success',
            'optionsJson' => json_encode($options),
            'redirect' => $payment_page_url,
            'order_id' => $order_id,
        ];
    }

    /**
     * @inheritDoc
     * @override
     * @return bool <p><b>TRUE</b> on process completed successfully, <b>FALSE</b> otherwise.</p>
     * @throws Ecp_Gateway_Logic_Exception <p>If a refund is not available for the selected order.</p>
     * @throws Ecp_Gateway_API_Exception <p>If the API response does not contain the required information.</p>
     * @throws WC_Data_Exception <p>If the data is corrupted while saving.</p>
     * @since 3.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return Ecp_Gateway_Module_Refund::get_instance()->process($order_id, $amount, $reason);
    }

    /**
     * @inheritDoc
     * <p>If false, the automatic refund button is hidden in the UI.</p>
     *
     * @param WC_Order $order <p>Order object.</p>
     * @override
     * @return bool <p><b>TRUE</b> if a refund available for the order, or <b>FALSE</b> otherwise.</p>
     * @since 2.0.0
     */
    public function can_refund_order($order)
    {
        if (!$order) {
            ecp_get_log()->debug(
                _x('Undefined argument order. Hide refund via ECOMMPAY button.', 'Log information', 'woo-ecommpay')
            );
            return false;
        }

        $order = ecp_get_order($order);

        // Check if there is a ECOMMPAY payment
        if (!$order->is_ecp()) {
            return false;
        }

        return Ecp_Gateway_Module_Refund::get_instance()->is_available($order);
    }

    private function init_subscription()
    {
        // WooCommerce Subscriptions hooks/filters
        if (!ecp_subscription_is_active()) {
            return;
        }

        // On scheduled subscription
        add_action(
            'woocommerce_scheduled_subscription_payment_' . $this->id,
            [WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'scheduled_subscription_payment'],
            10,
            2
        );

        // On cancelled subscription
        add_action(
            'woocommerce_subscription_cancelled_' . $this->id,
            [WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'subscription_cancellation']
        );

        // On updated subscription
        add_action(
            'woocommerce_subscription_payment_method_updated_to_' . $this->id,
            [WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'on_subscription_payment_method_updated_to_ecommpay'],
            10,
            2
        );

        add_action(
            'woocommerce_subscription_validate_payment_meta_' . $this->id,
            [WC_Gateway_Ecommpay_Module_Subscription::get_instance(), 'woocommerce_subscription_validate_payment_meta'],
            10,
            2
        );
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

    //    public function payment_fields() {
//        if (!empty($this->get_description())) {
//            echo '<p>' . wp_kses_post($this->get_description()) . '</p>';
//        }
//        $display_mode = $this->get_option(Ecp_Gateway_Settings::OPTION_MODE);
//        if ($display_mode === Ecp_Gateway_Settings::MODE_EMBEDDED) {
//            echo '<div id="ecommpay-loader-embedded"><div class="lds-ecommpay"><div></div><div></div><div></div></div></div>';
//            echo '<div id="ecommpay-iframe-embedded"></div>';
//        }
//    }
}
