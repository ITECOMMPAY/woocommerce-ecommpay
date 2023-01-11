<?php

///**
// * Returns the proper transaction instance type
// *
// * @param Ecp_Gateway_Order $order
// * @return Ecp_Gateway_API_Payment|Ecp_Gateway_API_Subscription
// *@since  2.0.0
// */
//function woocommerce_ecommpay_get_transaction_instance_by_order($order)
//{
//    // If the order is a subscription or an attempt of updating the payment method
//    if (
//        !ecp_cart_contains_switches()
//        && ($order->contains_subscription() || $order->is_request_to_change_payment())
//    ) {
//        // Instantiate a subscription transaction instead of a payment transaction
//        return new Ecp_Gateway_API_Subscription();
//    }
//
//    return new Ecp_Gateway_API_Payment();
//}

///**
// * Creates a new transaction based on the order and persists the transaction ID on the object.
// *
// * @param mixed $order
// *
// * @return int
// * @throws WC_Gateway_Ecommpay_API_Exception
// */
//function woocommerce_ecommpay_create_order_transaction($order)
//{
//    $order = woocommerce_ecommpay_get_order($order);
//    $transaction = woocommerce_ecommpay_get_transaction_instance_by_order($order);
//    $result = $transaction->create($order);
//    $order->set_payment_id($result->id);
//
//    return (int)$result->id;
//}

///**
// * Returns an existing payment link if available or creates a new one.
// *
// * @param $order
// *
// * @param bool $force_update
// *
// * @return string
// * @throws WC_Gateway_Ecommpay_API_Exception
// * @throws Exception
// */
//function woocommerce_ecommpay_create_payment_link($order, $force_update = true)
//{
//    $order = woocommerce_ecommpay_get_order($order);
//
//    if (!$order->needs_payment() && !$order->is_request_to_change_payment()) {
//        throw new Exception(__('Order does not need payment', 'woo-ecommpay'));
//    }
//
//    $transaction = woocommerce_ecommpay_get_transaction_instance_by_order($order);
//    $payment_link = $order->get_payment_link();
//    $payment_id = $order->get_payment_id();
//
//    if (empty($payment_id) && empty($payment_link)) {
//        $payment_id = woocommerce_ecommpay_create_order_transaction($order);
//    } else {
//        $transaction->patch_payment($payment_id, $order);
//    }
//
//    if (empty($payment_link) || $force_update) {
//        // Create or update the payment link. This is necessary to do EVERY TIME
//        // to avoid fraud with changing amounts.
//        $link = $transaction->patch_link($payment_id, $order);
//
//        if (WC_Gateway_Ecommpay_Helper::is_url($link->url)) {
//            $order->set_payment_link($link->url);
//            $payment_link = $link->url;
//        }
//    }
//
//    return $payment_link;
//}

///**
// * Creates a payment transaction.
// *
// * @param mixed $order
// *
// * @return WC_Gateway_Ecommpay_Order
// */
//function woocommerce_ecommpay_get_order($order)
//{
//    if (!is_object($order)) {
//        return new WC_Gateway_Ecommpay_Order($order);
//    }
//
//    if ($order instanceof WC_Order && !$order instanceof WC_Gateway_Ecommpay_Order) {
//        return new WC_Gateway_Ecommpay_Order($order->get_id());
//    }
//
//    return $order;
//}

///**
// * Returns the locale used in the payment window
// * @return string
// */
//function woocommerce_ecommpay_get_language()
//{
//    list($language) = explode('_', get_locale());
//
//    return apply_filters('woocommerce_ecommpay_language', $language);
//}

/**
 * Get all ECOMMPAY payment statuses.
 *
 * @return array
 * @since  2.0.0
 * @used-by Ecp_Gateway_Payment::set_status
 */
function ecp_get_payment_statuses()
{
    $payment_statuses = Ecp_Gateway_Payment_Status::get_status_names();
    return apply_filters('ecp_payment_statuses', $payment_statuses);
}

/**
 * Get the nice name for a payment status.
 *
 * @since  2.0.0
 * @param  string $status Status.
 * @return string
 */
function ecp_get_payment_status_name($status)
{
    return Ecp_Gateway_Payment_Status::get_status_name($status);
}

/**
 * See if a string is an ECOMMPAY payment status.
 *
 * @since  2.0.0
 * @param string $maybe_status
 * @return bool
 */
function ecp_is_payment_status($maybe_status)
{
    return array_key_exists($maybe_status, ecp_get_payment_statuses());
}
//
///**
// * Get all ECOMMPAY operation statuses.
// *
// * @return array
// * @since  2.0.0
// * @used-by Ecp_Gateway_Payment::set_status
// */
//function ecp_get_operation_statuses()
//{
//    $operation_statuses = Ecp_Gateway_Operation_Status::get_status_names();
//    return apply_filters('ecp_operation_statuses', $operation_statuses);
//}

///**
// * Get the nice name for an operation status.
// *
// * @since  2.0.0
// * @param  string $status Status.
// * @return string
// */
//function ecp_get_operation_status_name($status)
//{
//    return Ecp_Gateway_Operation_Status::get_status_name($status);
//}
//
///**
// * See if a string is an ECOMMPAY operation status.
// *
// * @since  2.0.0
// * @param string $maybe_status
// * @return bool
// */
//function ecp_is_operation_status($maybe_status)
//{
//    return array_key_exists($maybe_status, ecp_get_operation_statuses());
//}
//
///**
// * Get all ECOMMPAY operation types.
// *
// * @return array
// * @since  2.0.0
// * @used-by Ecp_Gateway_Payment::set_status
// */
//function ecp_get_operation_types()
//{
//    $payment_types = Ecp_Gateway_Operation_Type::get_status_names();
//    return apply_filters('ecp_operation_types', $payment_types);
//}

/**
 * Get the nice name for an operation type.
 *
 * @since  2.0.0
 * @param  string $status Status.
 * @return string
 */
function ecp_get_operation_type_name($status)
{
    return Ecp_Gateway_Operation_Type::get_status_name($status);
}

///**
// * See if a string is an ECOMMPAY operation type.
// *
// * @since  2.0.0
// * @param string $maybe_type
// * @return bool
// */
//function ecp_is_operation_type($maybe_type)
//{
//    return array_key_exists($maybe_type, ecp_get_operation_types());
//}