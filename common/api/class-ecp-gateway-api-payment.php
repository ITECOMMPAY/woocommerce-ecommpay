<?php

defined('ABSPATH') || exit;

/**
 * <h2>Payment ECOMMPAY Gate2025 API.</h2>
 *
 * @class    Ecp_Gateway_API_Payment
 * @since    2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class Ecp_Gateway_API_Payment extends Ecp_Gateway_API
{
    /**
     * <h2>Payment Gate2025 API constructor.</h2>
     *
     * @since 2.0.0
     */
    public function __construct()
    {
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

        add_filter('ecp_api_refund_form_data', [$this, 'filter_create_refund_request_form_data'], 10, 2);
        add_filter('ecp_api_status_form_data', [$this, 'filter_create_status_request_form_data'], 10, 1);
        add_filter('ecp_api_append_callback_settings', [$this, 'filter_append_callback_options'], 10, 1);
        add_filter('ecp_api_transaction_form_data', [$this, 'filter_create_transaction_status_request_form_data'], 10, 1);
    }

    /**
     * <h2>Sends a request and returns the information about the transaction.</h2>
     *
     * @param string $request_id <p>Request identifier.</p>
     * @since 2.0.0
     * @return Ecp_Gateway_Info_Response <p>Transaction information data.</p>
     */
    public function operation_status($request_id)
    {
        ecp_get_log()->info(__('Run check transaction status API process.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Request ID:', 'woo-ecommpay'), $request_id);

        // Create form data
        $data = apply_filters('ecp_api_transaction_form_data', $request_id);
        // Append signature
        $data = apply_filters('ecp_api_append_signature', $data);
        // Run request
        $response = new Ecp_Gateway_Info_Response($this->post('status/request', $data));

        ecp_get_log()->info(__('Check transaction status process completed.', 'woo-ecommpay'));
        return $response;
    }

    /**
     * <h2>Sends a request and returns information about the payment.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order for request.</p>
     * @since 2.0.0
     * @return Ecp_Gateway_Info_Status <p>Payment status information.</p>
     */
    public function status(Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Run check payment status API process.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Order ID:', 'woo-ecommpay'), $order->get_id());
        ecp_get_log()->debug(__('Current payment status:', 'woo-ecommpay'), $order->get_ecp_status());
        ecp_get_log()->debug(__('Payment method:', 'woo-ecommpay'), $order->get_payment_system());

        if ($order->get_ecp_status() === Ecp_Gateway_Payment_Status::INITIAL) {
            return new Ecp_Gateway_Info_Status();
        }

        // Create form data
        $data = apply_filters('ecp_api_status_form_data', $order);
        // Append signature
        $data = apply_filters('ecp_api_append_signature', $data);
        // Run request
        $response = new Ecp_Gateway_Info_Status($this->post('status', $data));

        ecp_get_log()->info(__('Check payment status process completed.', 'woo-ecommpay'));

        return $response;
    }

    /**
     * <h2>Sends data and return created refund transaction data.</h2>
     *
     * @param Ecp_Gateway_Refund $refund <p>Refund object.</p>
     * @param Ecp_Gateway_Order $order <p>Refunding order.</p>
     */
    public function refund(Ecp_Gateway_Refund $refund, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Run refund payment API process.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Refund ID:', 'woo-ecommpay'), $refund->get_id());
        ecp_get_log()->debug(__('Order ID:', 'woo-ecommpay'), $order->get_id());

        // Create form data
        $data = apply_filters('ecp_api_refund_form_data', $refund, $order);
        // Append interface type option
        $data = apply_filters('ecp_api_append_interface_type', $data);
        /** @var array $variables */
        $variables = ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_CUSTOM_VARIABLES, []);

        if (array_search(Ecp_Gateway_Settings_Page::CUSTOM_RECEIPT_DATA, $variables, true)) {
            // Append receipt data
            $data = apply_filters('ecp_append_receipt_data', $refund, $data);
        }

        // Run request
        $response = new Ecp_Gateway_Info_Response(
            $this->post(
                sprintf(
                    '%s/%s',
                    Ecp_Gateway_Payment_Methods::get_code($order->get_payment_system()),
                    'refund'
                ),
                apply_filters('ecp_api_append_signature', $data)
            )
        );

        ecp_get_log()->info(__('Refund payment process completed.', 'woo-ecommpay'));

        return $response;
    }

    /**
     * <h2>Returns the underlying form data for the status request.</h2>
     *
     * @param ECP_Gateway_Order_Extension $order <p>Order with payment.</p>
     * @since 2.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function filter_create_status_request_form_data($order)
    {
        ecp_get_log()->info(__('Create form data for status request.', 'woo-ecommpay'));
        return [
            // Object that contains general request details
            Ecp_Gateway_Signer::GENERAL => [
                // Identifier of merchant project received from ECOMMPAY
                'project_id' => ecommpay()->get_project_id(),
                // Identifier of the payment, must be unique within the project.
                'payment_id' => $order->get_payment_id(),
                // URL for callbacks received by Merchant
                'merchant_callback_url' => ecp_callback_url(),
            ],
            'destination' => 'merchant',
        ];
    }

    /**
     * <h2>Returns the underlying form data for the refund request.</h2>
     *
     * @param Ecp_Gateway_Refund $refund <p>Refund object.</p>
     * @param Ecp_Gateway_Order $order <p>Refunding order.</p>
     * @since 2.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function filter_create_refund_request_form_data(Ecp_Gateway_Refund $refund, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Create form data for refund request.', 'woo-ecommpay'));
        return [
            // Object that contains general request details
            Ecp_Gateway_Signer::GENERAL => [
                // Identifier of merchant project received from ECOMMPAY
                'project_id' => ecommpay()->get_project_id(),
                // Identifier of the payment, must be unique within the project.
                'payment_id' => $order->get_payment_id(),
                // URL for callbacks received by Merchant
                'merchant_callback_url' => ecp_callback_url(),
            ],
            // Object that contains payment details
            'payment' => [
                // Refund amount
                'amount' => ecp_price_multiply(abs($refund->get_total()), $refund->get_currency()),
                // Refund currency in ISO 4217 alpha-3 format, must match the currency in the initial auth request
                'currency' => $refund->get_currency(),
                // Refund ECOMMPAY identifier in WooCommerce.
                'merchant_refund_id' => $refund->get_payment_id(),
                // Refund comment. REQUIRED!!!
                'description' => (string) $refund->get_reason() !== ''
                    ? $refund->get_reason()
                    : sprintf('User %s create refund', wp_get_current_user()->ID),
            ]
        ];
    }

    /**
     * <h2>Returns the underlying form data for the transaction status request.</h2>
     *
     * @param string $request_id <p>ECOMMPAY transaction identifier.</p>
     * @since 2.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function filter_create_transaction_status_request_form_data($request_id)
    {
        ecp_get_log()->info(__('Create form data for transaction status request.', 'woo-ecommpay'));
        return [
            // Identifier of merchant project received from ECOMMPAY
            'project_id' => ecommpay()->get_project_id(),
            // Identifier of the payment, must be unique within the project.
            'request_id' => $request_id,
        ];
    }

    /**
     * <h2>Form data filter to add callback parameters.</h2>
     *
     * @param array $data <p>Incoming form data.</p>
     * @since 2.0.0
     * @return array <p>Filtered form data.</p>
     */
    final public function filter_append_callback_options(array $data)
    {
        ecp_get_log()->info(__('Append callback parameters to form data.', 'woo-ecommpay'));
        // Object that contains additional callback sending conditions
        $data['callback'] = [
            // Delay time for callback sending in seconds. From 0 to 600.
            'delay' => 0,
            // Parameter that disables sending callbacks (true or false value is available)
            'force_disable' => false
        ];

        return $data;
    }
}
