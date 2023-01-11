<?php

defined('ABSPATH') || exit;

/**
 * <h2>Subscription ECOMMPAY Gate2025 API.</h2>
 *
 * @class    Ecp_Gateway_API_Subscription
 * @since    2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class Ecp_Gateway_API_Subscription extends Ecp_Gateway_API
{
    /**
     * <h2>Subscription Gate2025 API constructor.</h2>
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        // Run the parent construct
        parent::__construct('payment');
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    protected function hooks()
    {
        parent::hooks();

        add_filter('ecp_api_recurring_form_data', [$this, 'filter_create_recurring_request_form_data'], 10, 3);
        add_filter('ecp_api_recurring_cancel_form_data', [$this, 'filter_create_recurring_cancel_request_form_data'], 10, 2);
    }

    /**
     * <h2>Sends data and return created subscription transaction data.</h2>
     *
     * @param int $subscription_id <p>Subscription identifier.</p>
     * @param Ecp_Gateway_Order $order <p>Renew subscription order.</p>
     * @param int $amount <p>Amount of renewal subscription.</p>
     * @return Ecp_Gateway_Info_Response
     * @throws Ecp_Gateway_API_Exception <p>
     * If subscriptions is not enabled or payment_method not supported subscriptions.
     * </p>
     */
    public function recurring($subscription_id, Ecp_Gateway_Order $order, $amount = null)
    {
        ecp_get_log()->info(__('Run recurring API process.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Subscription ID:', 'woo-ecommpay'), $subscription_id);
        ecp_get_log()->debug(__('Order ID:', 'woo-ecommpay'), $order->get_id());
        ecp_get_log()->debug(__('Payment status:', 'woo-ecommpay'), $order->get_ecp_status());

        if (!class_exists('WC_Subscriptions_Order')) {
            ecp_get_log()->alert(__(
                'Woocommerce Subscription plugin is not available. Interrupt process.',
                'woo-ecommpay'
            ));
            throw new Ecp_Gateway_API_Exception(__('Woocommerce Subscription plugin is not available.', 'woo-ecommpay'));
        }

        // Check if a custom amount has been set
        if ($amount === null) {
            // No custom amount set. Default to the order total
            $amount = WC_Subscriptions_Order::get_recurring_total($order);
        }

        ecp_get_log()->debug(__('Amount:', 'woo-ecommpay'), $amount);

        $payment_method = Ecp_Gateway_Payment_Methods::get_code($order->get_payment_system());

        if (!$payment_method) {
            throw new Ecp_Gateway_API_Exception(__('Payment method is not supported subscription.', 'woo-ecommpay'));
        }

        ecp_get_log()->debug(__('Payment method:', 'woo-ecommpay'), $payment_method);

        // Create form data
        $data = apply_filters('ecp_api_recurring_form_data', $subscription_id, $order, $amount);

        /** @var array $variables */
        $variables = ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_CUSTOM_VARIABLES, []);

        if (array_search(Ecp_Gateway_Settings_Page::CUSTOM_RECEIPT_DATA, $variables, true)) {
            // Append receipt data
            $data = apply_filters('ecp_append_receipt_data', $order, $data);
        }

        // Run request
        $response = new Ecp_Gateway_Info_Response(
            $this->post(
                sprintf('%s/%s', $payment_method, 'recurring'),
                apply_filters('ecp_api_append_signature', $data)
            )
        );

        ecp_get_log()->info(__('Recurring process completed.', 'woo-ecommpay'));

        return $response;
    }

    /**
     * <h2>Sends data and return subscription cancellation data.</h2>
     *
     * @param int $subscription_id <p>Recurring identifier.</p>
     * @param Ecp_Gateway_Order $order <p>Cancellation order.</p>
//     * @return Ecp_Gateway_Info_Response
     * @return bool
     *@since 2.0.0
     */
    public function cancel($subscription_id, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Run recurring cancel API process.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Subscription ID:', 'woo-ecommpay'), $subscription_id);
        ecp_get_log()->debug(__('Order ID:', 'woo-ecommpay'), $order->get_id());
        ecp_get_log()->debug(__('Payment status:', 'woo-ecommpay'), $order->get_ecp_status());

        return true;
// todo: get sub_id and send request to cancelled
//        $data = apply_filters('ecp_api_recurring_cancel_form_data', $subscription_id, $order);
//        $request_url = sprintf('%s/%s/%s',
//            Ecp_Gateway_Payment_Methods::get_code($order->get_payment_system()),
//            'recurring',
//            'cancel'
//        );
//
//        $response = new Ecp_Gateway_Info_Response(
//            $this->post($request_url, apply_filters('ecp_api_append_signature', $data))
//        );
//
//        ecp_get_log()->info(__('Recurring cancel process completed.', 'woo-ecommpay'));
//
//        return $response;
    }

    /**
     * <h2>Returns the underlying form data for the recurring request.</h2>
     *
     * @param int $subscription_id <p>ECOMMPAY recurring identifier.</p>
     * @param Ecp_Gateway_Order $order <p>Renewal subscription order.</p>
     * @param float $amount <p>Subscription amount.</p>
     * @since 2.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function filter_create_recurring_request_form_data($subscription_id, Ecp_Gateway_Order $order, $amount)
    {
        ecp_get_log()->info(__('Create form data for recurring request.', 'woo-ecommpay'));
        return [
            'general' => [
                'project_id' => ecommpay()->get_project_id(),
                'payment_id' => $order->create_payment_id(),
                'merchant_callback_url' => ecp_callback_url()
            ],
            'payment' => [
                'amount' => ecp_price_multiply($amount, $order->get_currency()),
                'currency' => $order->get_currency(),
            ],
            'recurring' => [
                'id' => $subscription_id
            ],
            'customer' => [
                'id' => (string) $order->get_customer_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
            ],
            'recurring_id' => $subscription_id,
            'interface_type' => Ecp_Gateway::get_interface_type(),
        ];
    }

    /**
     * <h2>Returns the underlying form data for the recurring cancel request.</h2>
     *
     * @param int $subscription_id <p>ECOMMPAY recurring identifier.</p>
     * @param Ecp_Gateway_Order $order <p>Renewal subscription order.</p>
     * @since 2.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function filter_create_recurring_cancel_request_form_data($subscription_id, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Create form data for recurring cancel request.', 'woo-ecommpay'));
        return [
            'general' => [
                'project_id' => ecommpay()->get_project_id(),
                'payment_id' => $order->create_payment_id(),
                'merchant_callback_url' => ecp_callback_url()
            ],
            'recurring' => [
                'id' => $subscription_id
            ],
            'interface_type' => Ecp_Gateway::get_instance()
        ];
    }
}
