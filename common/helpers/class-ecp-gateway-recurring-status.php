<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Recurring_Status class
 *
 * @class    Ecp_Gateway_Recurring_Status
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Recurring_Status
{
    /**
     * COF-purchase is active
     */
    const ACTIVE = 'active';

    /**
     * COF-purchase is cancelled
     */
    const CANCELLED = 'cancelled';

    private static $names;
    private static $codes;

    public static function get_status_code($status)
    {
        return array_key_exists($status, self::get_status_codes())
            ? self::get_status_codes()[$status]
            : 'undefined';
    }

    public static function get_status_name($status)
    {
        return array_key_exists($status, self::get_status_names())
            ? self::get_status_names()[$status]
            : 'Undefined';
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
            self::ACTIVE => _x('Active', 'Recurring status', 'woo-ecommpay'),
            self::CANCELLED => _x('Cancelled', 'Recurring status', 'woo-ecommpay'),
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
