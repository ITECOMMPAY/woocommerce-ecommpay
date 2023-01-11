<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Recurring_Types class
 *
 * @class    Ecp_Gateway_Recurring_Types
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Recurring_Types
{
    /**
     * One-click payment
     */
    const PAYMENT = 'C';

    /**
     * COF-purchase (Regular)
     */
    const REGULAR = 'R';

    /**
     * Auto-payment
     */
    const AUTO = 'U';

    private static $names;
    private static $codes;

    public static function get_status_code($status)
    {
        return array_key_exists($status, self::get_status_codes())
            ? self::get_status_codes()[$status]
            : 'C';
    }

    public static function get_status_name($status)
    {
        return array_key_exists($status, self::get_status_names())
            ? self::get_status_names()[$status]
            : 'C';
    }

    public static function get_status_names()
    {
        if (!self::$names) {
            self::$names = self::compile_names();
        }

        return self::$names;
    }

    public static function get_status_codes()
    {
        if (!self::$codes) {
            self::$codes = self::compile_codes();
        }

        return self::$codes;
    }

    private static function compile_names()
    {
        return [
            self::PAYMENT => _x('One-click', 'Recurring type', 'woo-ecommpay'),
            self::REGULAR => _x('Regular', 'Recurring type', 'woo-ecommpay'),
            self::AUTO => _x('Auto-payment', 'Recurring type', 'woo-ecommpay'),
        ];
    }

    private static function compile_codes()
    {
        $data = [];

        foreach (self::get_status_names() as $key => $value) {
            $data[$key] = str_replace(' ', '-', $key);
        }

        return $data;
    }
}
