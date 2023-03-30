<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Payment_Methods class
 *
 * Contains a list of fully supported payment methods.
 *
 * @class    Ecp_Gateway_Payment_Methods
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Payment_Methods
{
    private static $payment_methods = [
        'card' => 'card',
        'applepay' => 'etoken',
        'googlepay' => 'etoken-google',
    ];

    /**
     * <h2>Returns list of fully supported payment methods.</h2>
     * @return string[] <p>
     * List of payment method:<br/>
     *      - Key: Payment method code;<br/>
     *      - Value: Payment method name;<br/>
     * </p>
     */
    public static function get_method_names()
    {
        return self::$payment_methods;
    }

    /**
     * <h2>Returns codes for fully supported payment methods</h2>
     *
     * @return string[]
     */
    public static function get_method_codes()
    {
        return array_keys(self::$payment_methods);
    }

    /**
     * <h2>Returns payment method code by name.</h2>
     *
     * @param string $method_name
     * @return false|string
     */
    public static function get_code($method_name)
    {
        return array_search($method_name, self::$payment_methods);
    }
}
