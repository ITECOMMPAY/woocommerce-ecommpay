<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_More class
 *
 * @class    Ecp_Gateway_Settings_More
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_More extends Ecp_Gateway_Settings {


	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-more';

	/**
	 * Shop section identifier
	 */
	const MORE_SETTINGS = 'more_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'More methods', 'Settings page', 'woo-ecommpay' );

		parent::__construct();

		add_filter( 'ecp_get_settings_' . $this->id, [ $this, 'get_settings_more_methods' ] );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_more_methods() {
		$settings = [
			[
				self::FIELD_ID    => self::MORE_SETTINGS,
				self::FIELD_TITLE => _x( 'More payment methods settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			],
			[
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable/Disable', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x( 'Enable', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TIP     => _x(
					'Display all methods in one or use it for an additional method.',
					'Settings more payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::NO
			],
			[
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => _x( 'Title', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'This controls the tittle which the user sees during checkout.',
					'Settings more payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'More payment methods', 'Settings more payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Show Description', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Display the payment method description which user sees during checkout.',
					'Settings more payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::YES,
			],
			[
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Description', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => _x(
					'This controls the description which the user sees during checkout.',
					'Settings more payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x(
					'Payment form with additional payment methods.',
					'Settings more payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => _x( 'Order button text', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'Text shown on the submit button when choosing payment method.',
					'Settings more payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Go to payment', 'Settings more payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID    => self::OPTION_FORCE_CODE,
				self::FIELD_TITLE => _x( 'Payment method code', 'Settings more payments', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_TEXT,
				self::FIELD_DESC  => sprintf(
					'%s <a href="%s">%s</a>.',
					_x(
						'The ID of the payment method that is opened to customers without an option to select another one. The list of codes is provided in ',
						'Settings more payments',
						'woo-ecommpay'
					),
					esc_url_raw( 'https://developers.ecommpay.com/en/en_pm_codes.html' ),
					_x(
						'Payment method codes',
						'Settings more payments',
						'woo-ecommpay'
					)
				),
				self::FIELD_TIP   => _x(
					'If the field is empty, then all available payment methods will be displayed on the payment form',
					'Settings more payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID   => self::MORE_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
