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
        switch (wc_get_var($_REQUEST['action'])) {
            case 'ecommpay_process':
                if (wc_get_var($_REQUEST['woocommerce-process-checkout-nonce']) !== null) {
                    ecp_get_log()->debug(__('Ecommpay checkout process', 'woo-ecommpay'));
                    // Checkout page
                    WC()->checkout()->process_checkout();
                } elseif (wc_get_var($_REQUEST['woocommerce-pay-nonce']) !== null) {
                    // Checkout pay page
                    global $wp;

                    ecp_get_log()->debug(__('Ecommpay pay process', 'woo-ecommpay'));
                    foreach (wc_get_var($_GET) as $key => $value) {
                        $wp->query_vars[$key] = $value;
                    }

                    Ecp_Gateway_Form_Handler::pay_action();
                }
                break;
            case 'ecommpay_break':
                ecp_get_log()->debug(__('Ecommpay break process', 'woo-ecommpay'));
                $order_id = intval(wc_get_post_data_by_key('order_id', 0));

                if ($order_id > 0) {
                    $order = wc_get_order($order_id);

                    $result = [
                        'redirect' => $order->get_checkout_payment_url(),
                    ];
                    wp_send_json($result);
                }
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
        wp_enqueue_script(
            'ecommpay_frontend_helpers_script',
            ecp_js_url('frontend-helpers.js'),
            ['jquery'],
            ecp_version()
        );
        wp_localize_script(
            'ecommpay_checkout_script',
            'ECP',
            [
                'ajax_url' => admin_url("admin-ajax.php"),
                'origin_url' => $url,
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

        return '<div id="ecommpay-loader"><div class="lds-ecommpay"><div></div><div></div><div></div></div></div>'
            . '<div id="ecommpay-iframe"></div><div id="woocommerce_ecommpay_checkout_page">'
            . $content . "</div>";
    }

    /**
     * <h2>Returns ECOMMPAY request form data for an order.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @since 2.0.0
     * @return array <p>Form data as key-value array.</p>
     * </p>
     */
    public function get_request_url($order, $gateway)
    {
        return apply_filters('ecp_append_signature', $this->get_form_data($order, $gateway));
    }

    /**
     * <h2>Returns form data for ECOMMPAY Payment Page.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for payment.</p>
     * @param Ecp_Gateway $gateway
     * @since 2.0.0
     * @return array <p>Form data.</p>
     */
    private function get_form_data(Ecp_Gateway_Order $order, $gateway)
    {
        $return_url = esc_url_raw(add_query_arg('utm_nooverride', '1', $gateway->get_return_url($order)));
        $info = apply_filters('ecp_create_payment_info', $order);

        // General options
        $values = apply_filters('ecp_create_payment_data', $order);
        $values['baseUrl'] = $this->endpoint;

        // Set payment information
        foreach ($info as $key => $value) {
            $values['payment_' . $key] = $value;
        }

        // Set Payment Page Language
        $values = apply_filters('ecp_append_language_code', $values);
        // Set Additional data: Customer and Billing data, Receipt etc
        $values = apply_filters('ecp_append_additional_variables', $values, $order);
        // Set merchant success url with additional options
        $values = apply_filters('ecp_append_merchant_success_url', $values, $return_url);
        // Set merchant fail url with additional options
        $values = apply_filters('ecp_append_merchant_fail_url', $values, $return_url);
        // Set merchant return url with additional options
        $values = apply_filters('ecp_append_merchant_return_url', $values, esc_url_raw($order->get_checkout_payment_url()));
        // Set merchant callback url
        $values = apply_filters('ecp_append_merchant_callback_url', $values);
        // Set arguments by current payment gateway
        $values = apply_filters('ecp_append_gateway_arguments_' . $gateway->id, $values, $order);
        // Set environment versions
        $values = apply_filters('ecp_append_versions', $values);
        // Set ECOMMPAY internal interface type
        $values = apply_filters('ecp_append_interface_type', $values, true);

        // Clean arguments and return
        return apply_filters('ecp_payment_page_clean_parameters', $values);
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
