<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_Giropay class
 *
 * @class    Ecp_Gateway_Settings_Giropay
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class Ecp_Gateway_Settings_Giropay extends Ecp_Gateway_Settings {


	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-giropay';

	/**
	 * Shop section identifier
	 */
	const GIROPAY_SETTINGS = 'giropay_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'Giropay', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'giropay.svg';

		parent::__construct();

		add_filter( 'ecp_get_settings_' . $this->id, [ $this, 'get_settings_giropay_methods' ] );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_giropay_methods() {
		$settings = [
			[
				self::FIELD_ID    => self::GIROPAY_SETTINGS,
				self::FIELD_TITLE => _x( 'Giropay settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			],
			[
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable/Disable', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x( 'Enable', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TIP     => _x(
					'Before enabling the payment method please contact support@ecommpay.com.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::NO
			],
			[
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => _x( 'Title', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'This controls the tittle which the user sees during checkout.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Giropay', 'Settings giropay payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Show Description', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Display the payment method description which user sees during checkout.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::YES,
			],
			[
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Description', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => _x(
					'This controls the description which the user sees during checkout.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x(
					'You will be redirected to Giropay.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => _x( 'Order button text', 'Settings giropay payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'Text shown on the submit button when choosing payment method.',
					'Settings giropay payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Go to payment', 'Settings giropay payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID   => self::GIROPAY_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
