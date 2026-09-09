<?php

namespace common\enums;

defined( 'ABSPATH' ) || exit;

/**
 * EcpWcPaymentMethods class
 *
 * Enumeration of internal method names in Woocommerce
 * used as gateway ID in settings and stored in WC_Order (can get with WC_Order::get_payment_method)
 */
class EcpWcPaymentMethods {
	public const APPLE_PAY           = 'ecommpay-apple-pay';
	public const GOOGLE_PAY          = 'ecommpay-google-pay';
	public const CARD                = 'ecommpay-card';
	public const BANKS               = 'ecommpay-banks';
	public const BLIK                = 'ecommpay-blik';
	public const BRAZIL_ONLINE_BANKS = 'ecommpay-brazil';
	public const DIRECTDEBIT_BACS    = 'ecommpay-directdebit-bacs';
	public const DIRECTDEBIT_SEPA    = 'ecommpay-directdebit-sepa';
	public const HUMM                = 'ecommpay-humm';
	public const IDEAL               = 'ecommpay-ideal';
	public const KLARNA              = 'ecommpay-klarna';
	public const PAYPAL              = 'ecommpay-paypal';
	public const PAYPAL_PAYLATER     = 'ecommpay-paypal-paylater';
	public const MORE                = 'ecommpay-more';
}
