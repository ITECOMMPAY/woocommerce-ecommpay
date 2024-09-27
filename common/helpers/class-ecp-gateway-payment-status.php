<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Payment_Status class
 *
 * @class    Ecp_Gateway_Payment_Status
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class Ecp_Gateway_Payment_Status {
	/**
	 * Internal initialization payment.
	 * @internal
	 */
	const INITIAL = 'initial';

	/**
	 * Payment processing at Gate
	 */
	const PROCESSING = 'processing';

	/**
	 * Awaiting approval from customer on Payment Page.
	 */
	const AWAITING_APPROVAL = 'awaiting approval';

	/**
	 * Awaiting AVS-specific data from merchant for UK.
	 */
	const AWAITING_CLARIFY = 'awaiting clarify data';

	/**
	 * Awaiting a request with the result of a 3-D Secure Verification
	 */
	const AWAITING_3DS = 'awaiting 3ds result';

	/**
	 * Awaiting a merchant auth from customer.
	 */
	const AWAITING_MERCHANT_AUTH = 'awaiting merchant auth';

	/**
	 * Awaiting customer return after redirect to an external provider system
	 */
	const AWAITING_REDIRECT = 'awaiting redirect result';

	/**
	 * Awaiting customer actions, if the customer may perform additional attempts to make a payment
	 */
	const AWAITING_CUSTOMER_ACTION = 'awaiting customer action';

	/**
	 * Awaiting additional parameters from customer.
	 */
	const AWAITING_CLARIFICATION = 'awaiting clarification';

	/**
	 * Awaiting request for withdrawal of funds (capture) or cancellation of payment (cancel) from merchant.
	 */
	const AWAITING_CAPTURE = 'awaiting capture';

	/**
	 * Payment processing at external payment system.
	 */
	const EXTERNAL_PROCESSING = 'external processing';

	/**
	 * Scheduled recurring than we wait other payments.
	 */
	const SCHEDULED_RECURRING_PROCESSING = 'scheduled recurring processing';

	/**
	 * Successful payment
	 */
	const SUCCESS = 'success';

	/**
	 * Partially paid transaction.
	 */
	const PARTIALLY_PAID = 'partially paid';

	/**
	 * Partially paid out transaction.
	 */
	const PARTIALLY_PAID_OUT = 'partially paid out';

	/**
	 * Rejected payment
	 */
	const DECLINE = 'decline';

	/**
	 * An error occurred while reviewing data for payment processing
	 */
	const ERROR = 'error';

	/**
	 * Holding of funds (produced on authorization request) is cancelled
	 */
	const CANCELLED = 'cancelled';

	/**
	 * Refund after a successful payment before closing of the business day
	 */
	const REVERSED = 'reversed';

	/**
	 * Completed partial refund after a successful payment
	 */
	const PARTIALLY_REFUNDED = 'partially refunded';

	/**
	 * Completed partial reverse after a successful payment.
	 */
	const PARTIALLY_REVERSED = 'partially reversed';

	/**
	 * Successfully completed the full refund after a successful payment
	 */
	const REFUNDED = 'refunded';

	/**
	 * Awaiting customer actions, if the customer may perform additional attempts to make a payment
	 */
	const AWAITING_CUSTOMER = 'awaiting customer';

	/**
	 * Fallen into internal error during its processing.
	 */
	const INTERNAL_ERROR = 'internal error';

	/**
	 * Failed payment due to external payment system malfunction.
	 */
	const EXTERNAL_ERROR = 'external error';

	/**
	 * Invoice email was sent to customer.
	 */
	const INVOICE_SENT = 'invoice sent';

	/**
	 * Invoice was cancelled be merchant before customer paid for it.
	 */
	const INVOICE_CANCELLED = 'invoice cancelled';

	/**
	 * Initial state of invoice operation.
	 */
	const AWAITING_PAYMENT = 'awaiting payment';

	/**
	 * Best before expired.
	 */
	const EXPIRED = 'expired';

	private const HTML_UNDEFINED = 'Undefined';

	// Status when customer can try pay one more time using cascading.
	const AWAITING_RETRY = 'awaiting retry';
	const AWAITING_CLEARING = 'awaiting clearing';
	const AWAITING_PARTIALLY_CLEARING = 'awaiting partially clearing';
	const CLEARING_PROCESSING = 'clearing processing';
	const AWAITING_CONFIRMATION = 'awaiting confirmation';
	const DECLINE_RENEWAL = 'decline renewal';

	private static $names;
	private static $codes;

	public static function get_status_code( $status ) {
		return array_key_exists( $status, self::get_status_codes() )
			? self::get_status_codes()[ $status ]
			: self::HTML_UNDEFINED;
	}

	public static function get_status_codes(): array {
		if ( ! self::$codes ) {
			self::$codes = self::compile_codes();
		}

		return self::$codes;
	}

	private static function compile_codes(): array {
		$data = [];

		foreach ( self::get_status_names() as $key => $value ) {
			$data[ $key ] = str_replace( ' ', '-', $key );
		}

		return $data;
	}

	public static function get_status_names(): array {
		if ( ! self::$names ) {
			self::$names = self::compile_names();
		}

		return self::$names;
	}

	private static function compile_names(): array {
		return [
			self::PROCESSING                     => _x( 'Processing', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_APPROVAL              => _x( 'Awaiting approval', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CLARIFY               => _x( 'Awaiting clarify', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_3DS                   => _x( 'Awaiting 3ds result', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_MERCHANT_AUTH         => _x( 'Awaiting merchant auth', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_REDIRECT              => _x( 'Awaiting redirect result', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CUSTOMER_ACTION       => _x( 'Awaiting customer action', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CLARIFICATION         => _x( 'Awaiting clarification', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CAPTURE               => _x( 'Awaiting capture', 'Payment status', 'woo-ecommpay' ),
			self::EXTERNAL_PROCESSING            => _x( 'External processing', 'Payment status', 'woo-ecommpay' ),
			self::SCHEDULED_RECURRING_PROCESSING => _x( 'Scheduled recurring processing', 'Payment status', 'woo-ecommpay' ),
			self::SUCCESS                        => _x( 'Success', 'Payment status', 'woo-ecommpay' ),
			self::DECLINE                        => _x( 'Decline', 'Payment status', 'woo-ecommpay' ),
			self::ERROR                          => _x( 'Error', 'Payment status', 'woo-ecommpay' ),
			self::CANCELLED                      => _x( 'Cancelled', 'Payment status', 'woo-ecommpay' ),
			self::REVERSED                       => _x( 'Reversed', 'Payment status', 'woo-ecommpay' ),
			self::PARTIALLY_REFUNDED             => _x( 'Partially refunded', 'Payment status', 'woo-ecommpay' ),
			self::PARTIALLY_REVERSED             => _x( 'Partially reversed', 'Payment status', 'woo-ecommpay' ),
			self::REFUNDED                       => _x( 'Refunded', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CUSTOMER              => _x( 'Awaiting customer', 'Payment status', 'woo-ecommpay' ),
			self::INTERNAL_ERROR                 => _x( 'Internal error', 'Payment status', 'woo-ecommpay' ),
			self::EXTERNAL_ERROR                 => _x( 'External error', 'Payment status', 'woo-ecommpay' ),
			self::INVOICE_SENT                   => _x( 'Invoice sent', 'Payment status', 'woo-ecommpay' ),
			self::INVOICE_CANCELLED              => _x( 'Invoice cancelled', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_PAYMENT               => _x( 'Awaiting payment', 'Payment status', 'woo-ecommpay' ),
			self::EXPIRED                        => _x( 'Expired', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_RETRY                 => _x( 'Awaiting retry', 'Payment status', 'woo-ecommpay' ),
			self::PARTIALLY_PAID                 => _x( 'Partially paid', 'Payment status', 'woo-ecommpay' ),
			self::PARTIALLY_PAID_OUT             => _x( 'Partially paid out', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CLEARING              => _x( 'Awaiting clearing', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_PARTIALLY_CLEARING    => _x( 'Awaiting partially clearing', 'Payment status', 'woo-ecommpay' ),
			self::CLEARING_PROCESSING            => _x( 'Clearing processing', 'Payment status', 'woo-ecommpay' ),
			self::AWAITING_CONFIRMATION          => _x( 'Awaiting confirmation', 'Payment status', 'woo-ecommpay' ),
			self::DECLINE_RENEWAL                => _x( 'Decline renewal', 'Payment status', 'woo-ecommpay' ),
			// INTERNAL STATUS
			self::INITIAL                        => _x( 'Awaiting payment', 'Payment status', 'woo-ecommpay' ),
		];
	}

	public static function get_status_name( $status ) {
		return array_key_exists( $status, self::get_status_names() )
			? self::get_status_names()[ $status ]
			: self::HTML_UNDEFINED;
	}
}
