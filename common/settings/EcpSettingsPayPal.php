<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsPayPal class
 *
 * @class    EcpSettingsPayPal
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsPayPal extends EcpSettings {

	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-paypal';

	/**
	 * Shop section identifier
	 */
	const PAYPAL_SETTINGS = 'paypal_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'PayPal';
		$this->icon      = 'paypal-wallet.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_paypal_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_paypal_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::PAYPAL_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'PayPal settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings paypal payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings paypal payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings paypal payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings paypal payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings paypal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'PayPal', 'Settings paypal payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings paypal payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings paypal payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings paypal payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings paypal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'You will be redirected to PayPal.', 'Settings paypal payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings paypal payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings paypal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings paypal payments' ),
			),
			array(
				self::FIELD_ID   => self::PAYPAL_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
