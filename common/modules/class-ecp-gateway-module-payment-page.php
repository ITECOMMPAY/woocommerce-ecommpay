<?php

defined('ABSPATH') || exit;

/**
 * <h2>Request generator to open ECOMMPAY Payment Page.</h2>
 *
 * @class    Ecp_Gateway_Module_Payment_Page
 * @version  2.0.0
 * @package  Ecp_Gateway/Modules
 * @category Class
 */
class Ecp_Gateway_Module_Payment_Page extends Ecp_Gateway_Registry
{
    // region Constants

    /**
     * <h2>ECOMMPAY Payment Page URL protocol.</h2>
     *
     * @const
     * @var string
     * @since 2.0.0
     */
    const PROTOCOL = 'https';

    /**
     * <h2>ECOMMPAY Payment Page URL host name.</h2>
     *
     * @const
     * @var string
     * @since 2.0.0
     */
    const HOST = 'paymentpage.ecommpay.com';

    // endregion

    // region Properties

    /**
     * <h2>Stores line items to send to ECOMMPAY.</h2>
     *
     * @var array
     * @since 2.0.0
     */
    protected $line_items = [];

    /**
     * <h2>Endpoint for ECOMMPAY Payment Page.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    protected $endpoint;

    // endregion

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    protected function init()
    {
        $this->endpoint = sprintf('%s://%s', $this->get_protocol(), $this->get_host());

        add_filter('ecp_append_customer_address', [$this, 'append_customer_address'], 10, 2);
        add_filter('ecp_append_billing_data', [$this, 'append_billing_data'], 10, 2);
        add_filter('ecp_payment_page_clean_parameters', [$this, 'filter_clean'], 10, 1);

        // register hooks for AJAX requests
        add_action('wp_ajax_ecommpay_process', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_ecommpay_break', [$this, 'ajax_process']); // Authorised user
        add_action('wp_ajax_nopriv_ecommpay_process', [$this, 'ajax_process']); // Non-authorised user: Guest access
        add_action('wp_ajax_nopriv_ecommpay_break', [$this, 'ajax_process']); // Non-authorised user: Guest access

        // register hooks for display payment form on checkout page
        add_action('woocommerce_before_checkout_form', [$this, 'include_frontend_scripts']);

        // register hooks for display payment form on payment page
        add_action('before_woocommerce_pay', [$this, 'include_frontend_scripts']);

        // register hooks for additional container on checkout pages
        add_filter('the_content', [$this, 'append_iframe_container'], 10, 1);
    }

    /**
     * <h2>Returns the ECOMMPAY Payment page URL.</h2>
     *
     * @since 2.0.0
     * @return string <p>Payment Page URL.</p>
     */
    public function get_url()
    {
        return $this->endpoint;
    }

    /**
     * <h2>Return payment page options for AJAX request.</h2>
     *
     * @since 2.0.0
     * @throws Exception
     */
    public function ajax_process()
    {
        switch ($_REQUEST['action']) {
            case 'ecommpay_process':
                if (isset($_REQUEST['woocommerce-process-checkout-nonce'])) {
                    ecp_get_log()->debug(__('Ecommpay checkout process', 'woo-ecommpay'));
                    // Checkout page
                    WC()->checkout()->process_checkout();
                } elseif (isset($_REQUEST['woocommerce-pay-nonce'])) {
                    // Checkout pay page
                    global $wp;

                    ecp_get_log()->debug(__('Ecommpay pay process', 'woo-ecommpay'));
                    foreach ($_GET as $key => $value) {
                        $wp->query_vars[$key] = $value;
                    }

                    Ecp_Gateway_Form_Handler::pay_action();
                }
                break;
            case 'ecommpay_break':
                ecp_get_log()->debug(__('Ecommpay break process', 'woo-ecommpay'));
                $order_id = $_POST['order_id'];
                $order = wc_get_order($order_id);

                $result = [
                    'redirect' => $order->get_checkout_payment_url(),
                ];
                wp_send_json($result);
                return;
        }
    }

    /**
     * <h2>Injects scripts and styles into the site.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function include_frontend_scripts()
    {
        global $wp;

        try {
            if (isset($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0) {
                $order_id = absint($wp->query_vars['order-pay']); // The order ID
            } else {
                $order_id = is_wc_endpoint_url('order-pay');
            }
        } catch (Exception $e) {
            $order_id = 0;
        }

        $url = ecp_payment_page()->get_url();

        // Ecommpay merchant bundle.
        wp_enqueue_script(
            'ecommpay_merchant_js',
            sprintf('%s/shared/merchant.js', $url),
            [],
            null
        );
        wp_enqueue_style(
            'ecommpay_merchant_css',
            sprintf('%s/shared/merchant.css', $url),
            [],
            null
        );

        // Woocommerce Ecommpay Plugin frontend
        wp_enqueue_script(
            'ecommpay_checkout_script',
            ecp_js_url('checkout.js'),
            ['jquery'],
            ecp_version()
        );
        wp_localize_script(
            'ecommpay_checkout_script',
            'ECP',
            [
                'ajax_url' => admin_url("admin-ajax.php"),
                'origin_url' => $url,
                'mode' => ecommpay()->get_option(
                    Ecp_Gateway_Settings_Page::OPTION_MODE,
                    Ecp_Gateway_Settings_Page::MODE_REDIRECT
                ),
                'order_id' => $order_id,
            ]
        );

        wp_enqueue_style('ecommpay_loader_css', ecp_css_url('loader.css'));
    }

    /**
     * <h2></h2>
     *
     * @param string $content
     * @since 2.0.0
     * @return string
     */
    public function append_iframe_container($content)
    {
        if (!is_checkout()) {
            return $content;
        }

        return <<<HTML
<div id="ecommpay-loader">
  <div class="lds-ecommpay">
    <div></div><div></div><div></div>
  </div>
</div>
<div id="ecommpay-iframe"></div>
<div id="woocommerce_ecommpay_checkout_page">
HTML
            . $content . "</div>";
    }

    /**
     * <h2>Returns ECOMMPAY request form data for an order.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @since 2.0.0
     * @return array <p>Form data as key-value array.</p>
     * @throws Ecp_Gateway_Signature_Exception <p>
     * When the key or value of one of the parameters contains the character
     * {@see Ecp_Gateway_Signer::VALUE_SEPARATOR} symbol.
     * </p>
     */
    public function get_request_url($order)
    {
        // Prepare form data
        $ecommpay_args = $this->get_form_data($order);
        // Add signature
        ecp_sign_request_data($ecommpay_args);

        ecp_get_log()->debug('ECOMMPAY Request Args for order ' . $order->get_order_number() . ':', $ecommpay_args);
        return $ecommpay_args;
    }

    /**
     * <h2>Appends customer address.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @param array $values <p>Base array for appending data</p>
     * @since 2.0.0
     * @return array <p>Result of appending data as new array.</p>
     */
    public function append_customer_address($order, $values)
    {
        $customer = [
            'customer_country' => $order->get_billing_country(),
            //ToDo: Disabled. Wrong value in some countries.
            // 'customer_state' => $order->get_billing_state(),
            'customer_city' => $order->get_billing_city(),
            'customer_address' => $order->get_billing_address(),
            'customer_zip' => wc_format_postcode($order->get_billing_postcode(), $order->get_billing_country()),
        ];

        apply_filters('ecp_payment_page_clean_parameters', $customer);

        return count($customer) > 0 ? array_merge($values, $customer) : $values;
    }

    /**
     * <h2>Appends billing information.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @param array $values <p>Base array for appending data</p>
     * @since 2.0.0
     * @return array <p>Result of appending data as new array.</p>
     */
    public function append_billing_data($order, $values)
    {
        $billing = [
            'billing_address' => $this->limit_length($order->get_billing_address(), 255),
            'billing_city' => $order->get_billing_city(),
            'billing_country' => $order->get_billing_country(),
            'billing_postal' => wc_format_postcode($order->get_billing_postcode(), $order->get_billing_country()),
            //ToDo: Disabled. Wrong value in some countries.
            // 'billing_region' => $order->get_billing_state(),
            // 'billing_region_code' => ecp_region_code($order->get_billing_country(), $order->get_billing_state()),
        ];

        apply_filters('ecp_payment_page_clean_parameters', $billing);

        return count($billing) > 0 ? array_merge($values, $billing) : $values;
    }

    /**
     * <h2>Cleans and returns form data.</h2>
     * <p>The process of removing blank or empty arguments from form data.</p>
     *
     * @param array $data <p>ECOMMPAY Payment Page form data.</p>
     * @since 2.0.0
     * @return array <p>Cleaned up list of form data.</p>
     */
    public function filter_clean(array $data)
    {
        foreach ($data as $key => $value) {
            switch (true) {
                case $value === null:
                case is_string($value) && strlen(trim($value)) <= 0:
                    unset($data[$key]);
                    break;
            }
        }

        return $data;
    }

    /**
     * <h2>Returns form data for ECOMMPAY Payment Page.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return array <p>Form data.</p>
     */
    private function get_form_data(Ecp_Gateway_Order $order)
    {
        $payment_id = $order->create_payment_id();
        $return_url = esc_url_raw(add_query_arg('utm_nooverride', '1', ecommpay()->get_return_url($order)));

        return array_merge(
            // General options
            [
                'project_id' => ecommpay()->get_project_id(),
                'payment_id' => $payment_id,
                'payment_currency' => get_woocommerce_currency(),
                'merchant_success_enabled' => 2,
                'merchant_success_url' => $return_url,
                'merchant_success_redirect_mode' => 'parent_page',
                'merchant_fail_enabled' => 2,
                'merchant_fail_url' => $return_url,
                'merchant_fail_redirect_mode' => 'parent_page',
                'baseUrl' => $this->endpoint,
                'merchant_callback_url' => ecp_callback_url(),
                'interface_type' => json_encode(Ecp_Gateway::get_interface_type()),
                //ToDo: Control auth|sale operation type disable. Need implements.
                'card_operation_type' => 'sale',
                '_plugin_version' => Ecp_Gateway::WC_ECP_VERSION,
                '_wordpress_version' => wp_version(),
                '_woocommerce_version' => wc_version(),
            ],
            // Setup Payment Page Mode
            $this->get_mode($order),
            // Setup Payment Page Display Mode
            $this->get_display_mode($order),
            // Setup Payment PAge Language
            $this->get_language(),
            // Setup Recurring (Subscriptions)
            $this->get_recurring_args($order),
            // Setup Additional data: Customer and Billing data, Receipt etc
            $this->get_custom_variables($order),
            // Temporary force mode for subscriptions
            $this->get_force_mode($order)
        );
    }

    /**
     * <h2>Returns ECOMMPAY Payment Page Mode settings.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return array <p>Payment Page mode settings.</p>
     */
    private function get_mode(Ecp_Gateway_Order $order)
    {
        $amount = ecp_price_multiply($order->get_total(), $order->get_currency());

        return [
            'payment_amount' => $amount,
            //ToDo: Subscription order amount can be 0 then we switches to verify. For cards only. Needs more accurate.
            'mode' => $amount > 0 ? 'purchase' : 'card_verify'
        ];
    }

    /**
     * <h2>Returns ECOMMPAY Payment Page Display mode settings.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return array <p>Payment page display mode settings.</p>
     */
    private function get_display_mode(Ecp_Gateway_Order $order)
    {
        $mode = ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_MODE, Ecp_Gateway_Settings_Page::MODE_REDIRECT);

        $data = [
//            'merchant_return_url' => esc_url_raw($order->get_checkout_payment_url(true)),
            'merchant_return_url' => esc_url_raw($order->get_checkout_payment_url()),
            'merchant_return_enable' => 2,
            'merchant_return_mode' => 'parent_page',
        ];

        if ($mode !== Ecp_Gateway_Settings_Page::MODE_REDIRECT) {
            $data['frame_mode'] = $mode;

            if ($mode === Ecp_Gateway_Settings_Page::MODE_IFRAME) {
                $data['target_element'] = 'ecommpay-iframe';
            } elseif (
                $mode === Ecp_Gateway_Settings_Page::MODE_POPUP
                && ecp_is_enabled(Ecp_Gateway_Settings_Page::OPTION_POPUP_MISS_CLICK)
            ) {
                $data['close_on_missclick'] = 1;
            }
        }

        return $data;
    }

    /**
     * <h2>Returns force payment mode if the order contains subscription.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return string[] <p>Payment page force mode settings.</p>
     */
    private function get_force_mode(Ecp_Gateway_Order $order)
    {
        return $order->contains_subscription()
            ? ['force_payment_method' => 'card']
            : [];
    }

    /**
     * <h2>Returns language code settings.</h2>
     *
     * @since 2.0.0
     * @return string[] <p>Payment page language settings.</p>
     */
    private function get_language()
    {
        switch (ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_LANGUAGE, 'by_customer_browser')) {
            case 'by_customer_browser':
                return [];
            case 'by_wordpress':
                $lang = get_bloginfo("language");
                if (strpos($lang, '-') !== false) {
                    list($lang, ) = explode('-', $lang, 2);
                }
                return ['language_code' => strtoupper($lang)];
            default:
                return ['language_code' => ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_LANGUAGE, 'by_customer_browser')];
        }
    }

    /**
     * <h2>Returns ECOMMPAY Payment Page custom variables.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return array <p>Payment page custom settings.</p>
     */
    private function get_custom_variables($order)
    {
        /** @var array $variables */
        $variables = ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_CUSTOM_VARIABLES, []);

        $customer = $order->get_customer_id();
        $values = [];

        if ($customer !== 0) {
            $values['customer_id'] = $customer;
        }

        foreach ($variables as $variable) {
            switch ($variable) {
                case Ecp_Gateway_Settings_Page::CUSTOM_CUSTOMER_PHONE:
                    $values['customer_phone'] = wc_format_phone_number($order->get_billing_phone());
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_CUSTOMER_EMAIL:
                    $values['customer_email'] = $order->get_billing_email();
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_CUSTOMER_NAME:
                    $values['customer_first_name'] = $order->get_billing_first_name();
                    $values['customer_last_name'] = $order->get_billing_last_name();
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_CUSTOMER_ADDRESS:
                    $values = apply_filters('ecp_append_customer_address', $order, $values);
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_BILLING_DATA:
                    $values = apply_filters('ecp_append_billing_data', $order, $values);
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_SHIPPING_DATA:
                    //ToDo: Temporary disabled
                    // $values = apply_filters('ecp_append_shipping_data', $order, $values);
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_RECEIPT_DATA:
                    $values = apply_filters('ecp_append_receipt_data', $order, $values, true);
                    break;
                case Ecp_Gateway_Settings_Page::CUSTOM_CASH_VOUCHER:
                    //ToDo: Temporary disabled
                    // $values = apply_filters('ecp_append_cash_voucher_data', $order, $values);
                    break;
            }
        }

        apply_filters('ecp_payment_page_clean_parameters', $values);

        return $values;
    }

    /**
     * <h2>Returns ECOMMPAY Payment Page Subscription information.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @since 2.0.0
     * @return array <p>An array of the recurring data if available, or an empty array.</p>
     */
    private function get_recurring_args($order)
    {
        if (!ecp_subscription_is_active()) {
            return [];
        }

        switch (true) {
            case ecp_subscription_is_resubscribe($order):
                $subscriptions = ecp_get_subscriptions_for_resubscribe_order($order);
                break;
            case $order->contains_subscription():
                $subscriptions = ecp_get_subscriptions_for_order($order);
                break;
            default:
                return [];
        }

        if (count($subscriptions) <= 0) {
            return [];
        }

        $amount = 0;

        foreach ($subscriptions as $subscription) {
            $amount += $subscription->get_total();
        }

        $recurring = [
            'register' => true,
            'type' => Ecp_Gateway_Recurring_Types::AUTO,
            'amount' => ecp_price_multiply($amount, $order->get_currency()),
        ];

        $this->filter_clean($recurring);

        return [
            'recurring' => json_encode($recurring),
            'recurring_register' => 1
        ];
    }

    /**
     * <h2>Crops and returns string.</h2>
     *
     * @param string $string <p>Original string.</p>
     * @param integer $limit <p>Limit size in characters.</p>
     * @since 2.0.0
     * @return string <p>Cropped string.</p>
     */
    private function limit_length($string, $limit = 127)
    {
        $str_limit = $limit - 3;

        if (function_exists('mb_strimwidth')) {
            return mb_strlen($string) > $limit
                ? mb_strimwidth($string, 0, $str_limit) . '...'
                : $string;
        }

        return strlen($string) > $limit
            ? substr($string, 0, $str_limit) . '...'
            : $string;
    }

    /**
     * <h2>Returns the ECOMMPAY Payment Page protocol name.</h2>
     *
     * @since 2.0.0
     * @return string <p>ECOMMPAY Payment Page protocol name.</b>
     */
    private function get_protocol()
    {
        $proto = getenv('ECP_PROTO');

        return is_string($proto) ? $proto : self::PROTOCOL;
    }

    /**
     * <h2>Returns the ECOMMPAY Payment Page host name.</h2>
     *
     * @since 2.0.0
     * @return string <p>ECOMMPAY Payment Page host name.</p>
     */
    private function get_host()
    {
        $host = getenv('ECP_PAYMENTPAGE_HOST');

        return is_string($host) ? $host : self::HOST;
    }
}
