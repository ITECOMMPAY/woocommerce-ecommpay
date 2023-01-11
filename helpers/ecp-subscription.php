<?php

/**
 * Checks if a subscription is up for renewal.
 * Ensures backwards compatibility.
 *
 * @param Ecp_Gateway_Order $order [description]
 * @return bool
 */
function ecp_subscription_is_renewal($order)
{
    if (function_exists('wcs_order_contains_renewal')) {
        return wcs_order_contains_renewal($order);
    }

    return false;
}

/**
 * Checks if a subscription is resubscribed.
 *
 * @param Ecp_Gateway_Order $order [description]
 * @since 2.1.0
 * @return bool
 */
function ecp_subscription_is_resubscribe($order)
{
    if (function_exists('wcs_order_contains_resubscribe')) {
        return wcs_order_contains_resubscribe($order);
    }

    return false;
}

/**
 * Checks if Woocommerce Subscriptions is enabled or not
 * @return bool
 */
function ecp_subscription_is_active()
{
    return class_exists('WC_Subscriptions') && WC_Subscriptions::$name = 'subscription';
}


/**
 * Convenience wrapper for wcs_get_subscriptions_for_renewal_order
 *
 * @param $order
 * @param bool - to return a single item or not
 * @return Ecp_Gateway_Subscription|Ecp_Gateway_Subscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_renewal_order($order, $single = false)
{
    if (function_exists('wcs_get_subscriptions_for_renewal_order')) {
        add_filter(
            'woocommerce_order_class',
            [ecommpay(), 'type_wrapper'],
            101,
            2
        );

        $subscriptions = wcs_get_subscriptions_for_renewal_order($order);

        remove_filter(
            'woocommerce_order_class',
            [ecommpay(), 'type_wrapper'],
            101
        );

        return $single ? end($subscriptions) : $subscriptions;
    }

    return [];
}

/**
 * Convenience wrapper for wcs_get_subscriptions_for_renewal_order
 *
 * @param $order
 * @param bool - to return a single item or not
 * @return Ecp_Gateway_Subscription|Ecp_Gateway_Subscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_resubscribe_order($order, $single = false)
{
    if (function_exists('wcs_get_subscriptions_for_resubscribe_order')) {
        add_filter(
            'woocommerce_order_class',
            [ecommpay(), 'type_wrapper'],
            101,
            2
        );

        $subscriptions = wcs_get_subscriptions_for_resubscribe_order($order);

        remove_filter(
            'woocommerce_order_class',
            [ecommpay(), 'type_wrapper'],
            101
        );

        return $single ? end($subscriptions) : $subscriptions;
    }

    return [];
}

function ecp_get_subscription_statuses()
{
    if (function_exists('wcs_get_subscription_statuses')) {
        return wcs_get_subscription_statuses();
    }

    return [];
}

function ecp_get_subscription_status_name($status)
{
    if (!function_exists('wcs_get_subscription_status_name')) {
        return 'Unknown';
    }

    return wcs_get_subscription_status_name($status);
}

/**
 * Convenience wrapper for wcs_get_subscriptions_for_order
 *
 * @param $order
 *
 * @return WC_Subscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_order($order)
{
    if (function_exists('wcs_get_subscriptions_for_order')) {
        return wcs_get_subscriptions_for_order($order);
    }

    return [];
}

/**
 * @param $id
 * @return WC_Subscription_Order|false
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscription($id)
{
    if (function_exists('wcs_get_subscription')) {
        return wcs_get_subscription($id);
    }

    return false;
}

/**
 * @param Ecp_Gateway_Order $order The parent order
 *
 * @return bool
 */
function ecp_get_subscription_id($order)
{
    $order_id = $order->get_id();

    if (ecp_is_subscription($order_id)) {
        return $order_id;
    }

    if ($order->contains_subscription()) {
        // Find all subscriptions
        $subscriptions = ecp_get_subscriptions_for_order($order_id);
        // Get the last one and base the transaction on it.
        $subscription = end($subscriptions);
        // Fetch the post ID of the subscription, not the parent order.
        return $subscription->get_id();
    }

    return false;
}

/**
 * Activates subscriptions on a parent order
 *
 * @param Ecp_Gateway_Order $order
 * @return false
 */
function ecp_activate_subscriptions_for_order($order)
{
    if (
        ecp_subscription_is_active()
        && class_exists('WC_Subscriptions_Manager')
    ) {
        WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
    }

    return false;
}

/**
 * Check if a given object is a WC_Subscription (or child class of WC_Subscription), or if a given ID
 * belongs to a post with the subscription post type ('shop_subscription')
 *
 * @param $subscription
 * @return bool
 */
function ecp_is_subscription($subscription)
{
    if (function_exists('wcs_is_subscription')) {
        return wcs_is_subscription($subscription);
    }

    return false;
}

/**
 * Checks if the current cart has a switch product
 * @return bool
 */
function ecp_cart_contains_switches()
{
    if (
        class_exists('WC_Subscriptions_Switcher')
        && method_exists('WC_Subscriptions_Switcher', 'cart_contains_switches')
    ) {
        return WC_Subscriptions_Switcher::cart_contains_switches() !== false;
    }

    return false;
}

function ecp_order_contains_switch($order)
{
    if (!function_exists('wcs_order_contains_switch')) {
        return false;
    }

    return wcs_order_contains_switch($order);
}

function ecp_order_contains_subscription($order)
{
    if (!function_exists('wcs_order_contains_subscription')) {
        ecp_get_log()->debug(__('The order does not contain subscription products', 'woo-ecp'));
        return false;
    }

    return wcs_order_contains_subscription($order);
}