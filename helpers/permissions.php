<?php

if (!function_exists('woocommerce_ecommpay_can_user_empty_logs')) {
    /**
     * @return mixed|void
     */
    function woocommerce_ecommpay_can_user_empty_logs()
    {
        return apply_filters('woocommerce_ecommpay_can_user_empty_logs', current_user_can('administrator'));
    }
}

if (!function_exists('woocommerce_ecommpay_can_user_flush_cache')) {
    /**
     * @return mixed|void
     */
    function woocommerce_ecommpay_can_user_flush_cache()
    {
        return apply_filters('woocommerce_ecommpay_can_user_flush_cache', current_user_can('administrator'));
    }
}

if (!function_exists('woocommerce_ecommpay_can_user_manage_payments')) {
    /**
     * @param string $action
     *
     * @return bool
     */
    function woocommerce_ecommpay_can_user_manage_payments($action = null)
    {
        $default_cap = current_user_can('manage_woocommerce');

        $cap = apply_filters('woocommerce_ecommpay_can_user_manage_payment', $default_cap);

        if (!empty ($action)) {
            $cap = apply_filters('woocommerce_ecommpay_can_user_manage_payment_' . $action, $default_cap);
        }

        return $cap;
    }
}
