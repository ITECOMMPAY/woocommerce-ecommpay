<?php

/**
 * Ecp_Gateway_Form_Handler class
 *
 * Wrapper Handle frontend forms.
 *
 * @class    Ecp_Gateway_Form_Handler
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Form_Handler extends WC_Form_Handler
{
    /**
     * Process the checkout form.
     */
    public static function checkout_action()
    {
        if (isset($_POST['woocommerce_checkout_place_order']) || isset($_POST['woocommerce_checkout_update_totals'])) {
            wc_nocache_headers();

            if (WC()->cart->is_empty()) {
                wp_redirect(wc_get_page_permalink('cart'));
                exit;
            }

            wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);

            WC()->checkout()->process_checkout();
        }
    }

    /**
     * Process the pay form.
     */
    public static function pay_action()
    {
        if (!isset($_POST['woocommerce_pay'])) {
            return;
        }

        wc_nocache_headers();

        $nonce_value = wc_get_var($_REQUEST['woocommerce-pay-nonce'], wc_get_var($_REQUEST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

        if (!wp_verify_nonce($nonce_value, 'woocommerce-pay')) {
            return;
        }

        ob_start();

        // Pay for existing order
        $order_key = $_GET['key'];
//            $order_id = absint($wp->query_vars['order-pay']);
        $order_id = (int) $_POST['order_id'];
        $order = wc_get_order($order_id);

        if ($order_id !== $order->get_id() || !hash_equals($order->get_order_key(), $order_key) || !$order->needs_payment()) {
            return;
        }

        do_action('woocommerce_before_pay_action', $order);

        WC()->customer->set_props([
            'billing_country' => $order->get_billing_country() ? $order->get_billing_country() : null,
            'billing_state' => $order->get_billing_state() ? $order->get_billing_state() : null,
            'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
            'billing_city' => $order->get_billing_city() ? $order->get_billing_city() : null,
        ]);
        WC()->customer->save();

        // Terms
        if (!empty($_POST['terms-field']) && empty($_POST['terms'])) {
            wc_add_notice(__('Please read and accept the terms and conditions to proceed with your order.', 'woocommerce'), 'error');
            return;
        }

        // Update payment method
        if (!$order->needs_payment()) {
            // No payment was required for order
            $order->payment_complete();
            wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        $payment_method = isset($_POST['payment_method']) ? wc_clean($_POST['payment_method']) : false;
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

        if (!$payment_method) {
            wc_add_notice(__('Invalid payment method.', 'woocommerce'), 'error');
            return;
        }

        // Update meta
        update_post_meta($order_id, '_payment_method', $payment_method);

        if (isset($available_gateways[$payment_method])) {
            $payment_method_title = $available_gateways[$payment_method]->get_title();
        } else {
            $payment_method_title = '';
        }

        update_post_meta($order_id, '_payment_method_title', $payment_method_title);

        // Validate
        $available_gateways[$payment_method]->validate_fields();

        // Process
        if (0 === wc_notice_count('error')) {
            $result = $available_gateways[$payment_method]->process_payment($order_id);

            // Redirect to success/confirmation/payment page
            if (!is_ajax()) {
                wp_redirect($result['redirect']);
                exit;
            }
            wc_clear_cart_after_payment();
            wp_send_json($result);
        }

        do_action('woocommerce_after_pay_action', $order);
    }

    /**
     * Cancel a pending order.
     */
    public static function cancel_order()
    {
        if (
            isset($_GET['cancel_order']) &&
            isset($_GET['order']) &&
            isset($_GET['order_id']) &&
            (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'woocommerce-cancel_order'))
        ) {
            wc_nocache_headers();

            $order_key = $_GET['order'];
            $order_id = absint($_GET['order_id']);
            $order = wc_get_order($order_id);
            $user_can_cancel = current_user_can('cancel_order', $order_id);
            $order_can_cancel = $order->has_status(apply_filters('woocommerce_valid_order_statuses_for_cancel', array('pending', 'failed')));
            $redirect = $_GET['redirect'];

            switch (true) {
                case $order->has_status('cancelled'):
                    // Already cancelled - take no action
                    break;
                case $user_can_cancel && $order_can_cancel && $order->get_id() === $order_id && hash_equals($order->get_order_key(), $order_key):
                    // Cancel the order + restore stock
                    WC()->session->set('order_awaiting_payment', false);
                    $order->update_status('cancelled', __('Order cancelled by customer.', 'woocommerce'));

                    // Message
                    wc_add_notice(apply_filters('woocommerce_order_cancelled_notice', __('Your order was cancelled.', 'woocommerce')), apply_filters('woocommerce_order_cancelled_notice_type', 'notice'));

                    do_action('woocommerce_cancelled_order', $order->get_id());
                    break;
                case $user_can_cancel && !$order_can_cancel:
                    wc_add_notice(__('Your order can no longer be cancelled. Please contact us if you need assistance.', 'woocommerce'), 'error');
                    break;
                default:
                    wc_add_notice(__('Invalid order.', 'woocommerce'), 'error');
            }

            if ($redirect) {
                wp_safe_redirect($redirect);
                exit;
            }
        }
    }
}