<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Operation_Status class
 *
 * @class    Ecp_Gateway_Operation_Status
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Operation_Status
{
    /**
     * Payment processing at Gate
     */
    const PROCESSING = 'processing';

    /**
     * Awaiting approval from customer on Payment Page.
     */
    const AWAITING_APPROVAL = 'awaiting approval';

    /**
     * Awaiting a request with the result of a 3-D Secure Verification
     */
    const AWAITING_3DS = 'awaiting 3ds result';

    /**
     * Awaiting customer return after redirect to an external provider system
     */
    const AWAITING_REDIRECT = 'awaiting redirect result';

    /**
     * Awaiting customer, if the customer may perform additional attempts to make a payment
     */
    const AWAITING_CUSTOMER = 'awaiting customer';

    /**
     * Awaiting customer actions, the user performs the necessary actions on the side of the payment system
     */
    const AWAITING_CUSTOMER_ACTION = 'awaiting customer action';

    /**
     * Awaiting additional parameters from customer.
     */
    const AWAITING_CLARIFICATION = 'awaiting clarification';

    /**
     * Payment processing at external payment system.
     */
    const EXTERNAL_PROCESSING = 'external processing';

    /**
     * Failed payment due to external payment system malfunction.
     */
    const EXTERNAL_ERROR = 'external error';

    /**
     * Rejected payment
     */
    const DECLINE = 'decline';

    /**
     * Successful payment
     */
    const SUCCESS = 'success';

    /**
     * Fallen into internal error during its processing.
     */
    const INTERNAL_ERROR = 'internal error';

    /**
     * Undefined status.
     */
    const UNKNOWN = 'unknown';

    /**
     * Awaiting a merchant auth from customer.
     */
    const AWAITING_MERCHANT_AUTH = 'awaiting merchant auth';

    /**
     * Invoice email was sent to customer.
     */
    const INVOICE_SENT = 'invoice sent';

    /**
     * Invoice was cancelled be merchant before customer paid for it.
     */
    const INVOICE_CANCELLED = 'invoice cancelled';

    /**
     * Best before expired.
     */
    const EXPIRED = 'expired';

    /**
     * Initial state of invoice operation.
     */
    const AWAITING_PAYMENT = 'awaiting payment';

    /**
     * Awaiting payment confirmation (Online-banking).
     */
    const AWAITING_CONFIRMATION = 'awaiting confirmation';

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

    public static function is_fail($status)
    {
        return in_array(
            $status,
            [
                self::EXPIRED,
                self::EXTERNAL_ERROR,
                self::INTERNAL_ERROR,
                self::DECLINE,
                self::INVOICE_CANCELLED,
            ]
        );
    }

    public static function is_success($status)
    {
        return in_array(
            $status,
            [
                self::SUCCESS,
            ]
        );
    }

    public static function is_processing($status)
    {
        return in_array(
            $status,
            [
                self::PROCESSING,
                self::AWAITING_APPROVAL,
                self::AWAITING_3DS,
                self::AWAITING_REDIRECT,
                self::AWAITING_CUSTOMER,
                self::AWAITING_CUSTOMER_ACTION,
                self::AWAITING_CLARIFICATION,
                self::EXTERNAL_PROCESSING,
                self::AWAITING_MERCHANT_AUTH,
                self::INVOICE_SENT,
                self::AWAITING_PAYMENT,
                self::UNKNOWN,
            ]
        );
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
            self::PROCESSING => _x('Processing', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_APPROVAL => _x('Awaiting approval', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_3DS => _x('Awaiting 3ds result', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_REDIRECT => _x('Awaiting redirect result', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_CUSTOMER => _x('Awaiting customer', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_CUSTOMER_ACTION => _x('Awaiting customer action', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_CLARIFICATION => _x('Awaiting clarification', 'Operation status', 'woo-ecommpay'),
            self::EXTERNAL_PROCESSING => _x('External processing', 'Operation status', 'woo-ecommpay'),
            self::EXTERNAL_ERROR => _x('External error', 'Operation status', 'woo-ecommpay'),
            self::DECLINE => _x('Decline', 'Operation status', 'woo-ecommpay'),
            self::SUCCESS => _x('Success', 'Operation status', 'woo-ecommpay'),
            self::INTERNAL_ERROR => _x('Internal error', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_MERCHANT_AUTH => _x('Awaiting merchant auth', 'Operation status', 'woo-ecommpay'),
            self::INVOICE_SENT => _x('Invoice sent', 'Operation status', 'woo-ecommpay'),
            self::INVOICE_CANCELLED => _x('Invoice cancelled', 'Operation status', 'woo-ecommpay'),
            self::EXPIRED => _x('Expired', 'Operation status', 'woo-ecommpay'),
            self::AWAITING_PAYMENT => _x('Awaiting payment', 'Operation status', 'woo-ecommpay'),
            self::UNKNOWN => _x('Unknown', 'Operation status', 'woo-ecommpay'),
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
