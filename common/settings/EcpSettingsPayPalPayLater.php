<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsPayPalPayLater class
 *
 * @class    EcpSettingsPayPalPayLater
 * @version  3.4.3
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsPayPalPayLater extends EcpSettings {

	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-paypal-paylater';

	/**
	 * Shop section identifier
	 */
	const PAYPAL_PAYLATER_SETTINGS = 'paypal_paylater_settings';

	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'PayPal PayLater';
		$this->icon      = 'paypal-paylater.svg';

		parent::__construct();

		add_filter(
			EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id,
			array(
				$this,
				'get_settings_paypal_paylater_methods',
			)
		);
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_paypal_paylater_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::PAYPAL_PAYLATER_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'PayPal PayLater settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings paypal paylater payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings paypal paylater payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings paypal paylater payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings paypal paylater payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings paypal paylater payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'PayPal Buy Now Pay Later', 'Settings paypal paylater payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings paypal paylater payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings paypal paylater payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings paypal paylater payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings paypal paylater payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'You will be redirected to PayPal.', 'Settings paypal paylater payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings paypal paylater payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings paypal paylater payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings paypal paylater payments' ),
			),
			array(
				self::FIELD_ID   => self::PAYPAL_PAYLATER_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
