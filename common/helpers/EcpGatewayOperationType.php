<?php

namespace common\helpers;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayOperationType class
 *
 * @class    EcpGatewayOperationType
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayOperationType extends EcpAbstractApiObject {
	/**
	 * Single-message purchase
	 */
	public const SALE = 'sale';

	/**
	 * Purchase again using previously registered recurring.
	 */
	public const RECURRING = 'recurring';

	/**
	 * Recurring update in payment system.
	 */
	public const RECURRING_UPDATE = 'recurring update';

	/**
	 * Recurring cancel in payment system.
	 */
	public const RECURRING_CANCEL = 'recurring cancel';

	/**
	 * First step of double-message purchase - hold.
	 */
	public const AUTH = 'auth';

	/**
	 * Second step of double-message purchase - confirmation.
	 */
	public const CAPTURE = 'capture';

	/**
	 * Void previously held double-message transaction.
	 */
	public const CANCEL = 'cancel';

	/**
	 * Revert purchase.
	 */
	public const REVERSAL = 'reversal';

	/**
	 * Refund back purchase.
	 */
	public const REFUND = 'refund';

	/**
	 * Revert of the refund operation.
	 */
	public const REFUND_REVERSE = 'refund reverse';

	/**
	 * Operation for manual change transaction status.
	 */
	public const MANUAL_CHANGE = 'manual change';

	/**
	 * Operation for account verification
	 */
	public const ACCOUNT_VERIFICATION = 'account verification';

	/**
	 * Create cash voucher for OrangeData.
	 */
	public const CREATE_CASH_VOUCHER = 'create_cash_voucher';

	/**
	 * Operation of taking commission.
	 */
	public const COMMISSION = 'commission';

	/**
	 * Operation for pre-confirm incremental.
	 */
	public const INCREMENTAL = 'incremental';

	/**
	 * Invoice operation - first part of invoice transaction.
	 */
	public const INVOICE = 'invoice';

	/**
	 * Customer initiated action.
	 */
	public const CUSTOMER_ACTION = 'customer action';

	/**
	 * Payment confirmation operation.
	 */
	public const PAYMENT_CONFIRMATION = 'payment confirmation';

	/**
	 * Capture settlement operation.
	 */
	public const CAPTURE_SETTLEMENT = 'capture settlement';

	/**
	 * Contract registration operation for Direct Debit
	 */
	public const CONTRACT_REGISTRATION = 'contract registration';

	private const HTML_UNDEFINED = 'Undefined';

	public static array $names = [];

	public static function compile_names(): array {
		return [
			self::SALE                 => _x( 'Sale', 'Operation type', 'woo-ecommpay' ),
			self::RECURRING            => _x( 'Recurring', 'Operation type', 'woo-ecommpay' ),
			self::RECURRING_CANCEL     => _x( 'Cancel recurring', 'Operation type', 'woo-ecommpay' ),
			self::RECURRING_UPDATE     => _x( 'Update recurring', 'Operation type', 'woo-ecommpay' ),
			self::AUTH                 => _x( 'Auth', 'Operation type', 'woo-ecommpay' ),
			self::CAPTURE              => _x( 'Capture', 'Operation type', 'woo-ecommpay' ),
			self::CANCEL               => _x( 'Cancel', 'Operation type', 'woo-ecommpay' ),
			self::REVERSAL             => _x( 'Reversal', 'Operation type', 'woo-ecommpay' ),
			self::REFUND               => _x( 'Refund', 'Operation type', 'woo-ecommpay' ),
			self::REFUND_REVERSE       => _x( 'Reverse refund', 'Operation type', 'woo-ecommpay' ),
			self::MANUAL_CHANGE        => _x( 'Manual change', 'Operation type', 'woo-ecommpay' ),
			self::ACCOUNT_VERIFICATION => _x( 'Account verification', 'Operation type', 'woo-ecommpay' ),
			self::CREATE_CASH_VOUCHER  => _x( 'Create cash voucher', 'Operation type', 'woo-ecommpay' ),
			self::COMMISSION           => _x( 'Commission', 'Operation type', 'woo-ecommpay' ),
			self::INCREMENTAL          => _x( 'Incremental', 'Operation type', 'woo-ecommpay' ),
			self::INVOICE              => _x( 'Invoice', 'Operation type', 'woo-ecommpay' ),
			self::CUSTOMER_ACTION      => _x( 'Customer action', 'Operation type', 'woo-ecommpay' ),
			self::PAYMENT_CONFIRMATION => _x( 'Payment confirmation', 'Operation type', 'woo-ecommpay' ),
			self::CAPTURE_SETTLEMENT   => _x( 'Capture settlement', 'Operation type', 'woo-ecommpay' ),
		];
	}

	public static function get_status_name( $status ) {
		return array_key_exists( $status, self::get_status_names() )
			? self::get_status_names()[ $status ]
			: self::HTML_UNDEFINED;
	}
}
