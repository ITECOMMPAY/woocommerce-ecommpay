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

        // Run request
        $response = new Ecp_Gateway_Info_Status(
            $this->post(
                'status',
                apply_filters('ecp_append_signature', $this->create_status_request_form_data($order))
            )
        );

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
        $data = $this->create_refund_request_form_data($refund, $order);

        /** @var array $variables */
        $variables = ecommpay()->get_general_option(Ecp_Gateway_Settings_General::OPTION_CUSTOM_VARIABLES, []);

        if (array_search(Ecp_Gateway_Settings_General::CUSTOM_RECEIPT_DATA, $variables, true)) {
            // Append receipt data
            $data = apply_filters('ecp_append_receipt_data', $data, $refund);
        }

        // Run request
        $response = new Ecp_Gateway_Info_Response(
            $this->post(
                sprintf(
                    '%s/%s',
                    apply_filters('ecp_api_refund_endpoint_' . $order->get_payment_method(), $order->get_payment_system()),
                    'refund'
                ),
                apply_filters('ecp_append_signature', $data)
            )
        );

        ecp_get_log()->info(__('Refund payment process completed.', 'woo-ecommpay'));

        return $response;
    }

    /**
     * <h2>Returns the underlying form data for the status request.</h2>
     *
     * @param ECP_Gateway_Order_Extension $order <p>Order with payment.</p>
     * @since 3.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    private function create_status_request_form_data($order)
    {
        ecp_get_log()->info(__('Create form data for status request.', 'woo-ecommpay'));
        $data = $this->create_general_section(
            apply_filters(
                'ecp_append_merchant_callback_url',
                apply_filters('ecp_create_general_data', $order)
            )
        );
        $data['destination'] = 'merchant';

        return $data;
    }

    /**
     * <h2>Returns the underlying form data for the refund request.</h2>
     *
     * @param Ecp_Gateway_Refund $refund <p>Refund object.</p>
     * @param Ecp_Gateway_Order $order <p>Refunding order.</p>
     * @since 3.0.0
     * @return array[] <p>Basic form-data.</p>
     */
    final public function create_refund_request_form_data(Ecp_Gateway_Refund $refund, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->info(__('Create form data for refund request.', 'woo-ecommpay'));
        $data = $this->create_general_section(
            apply_filters(
                'ecp_append_merchant_callback_url',
                apply_filters('ecp_create_payment_data', $order)
            )
        );
        $data = apply_filters('ecp_append_payment_section', $data, $refund);

        return apply_filters('ecp_append_interface_type', $data);
    }
}
