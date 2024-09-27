<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_PayPal_PayLater class
 *
 * @class    Ecp_Gateway_Settings_PayPal_PayLater
 * @version  3.4.3
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_PayPal_PayLater extends Ecp_Gateway_Settings {
	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-paypal-paylater';

	/**
	 * Shop section identifier
	 */
	const PAYPAL_PAYLATER_SETTINGS = 'paypal_paylater_settings';

	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'PayPal PayLater', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'paypal-paylater.svg';

		parent::__construct();

		add_filter( 'ecp_get_settings_' . $this->id, [ $this, 'get_settings_paypal_paylater_methods' ] );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_paypal_paylater_methods(): array {
		$settings = [
			[
				self::FIELD_ID    => self::PAYPAL_PAYLATER_SETTINGS,
				self::FIELD_TITLE => _x( 'PayPal PayLater settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			],
			[
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable/Disable', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x( 'Enable', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TIP     => _x(
					'Before enabling the payment method please contact support@ecommpay.com',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::NO
			],
			[
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => _x( 'Title', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'This controls the tittle which the user sees during checkout.',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'PayPal Buy Now Pay Later', 'Settings paypal paylater payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Show Description', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Display the payment method description which user sees during checkout.',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::YES,
			],
			[
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Description', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => _x(
					'This controls the description which the user sees during checkout.',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x(
					'You will be redirected to PayPal.',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => _x( 'Order button text', 'Settings paypal paylater payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'Text shown on the submit button when choosing payment method.',
					'Settings paypal paylater payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Go to payment', 'Settings paypal paylater payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID   => self::PAYPAL_PAYLATER_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
