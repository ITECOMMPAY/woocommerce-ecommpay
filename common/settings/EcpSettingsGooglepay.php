<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_GooglePay class
 *
 * @class    Ecp_Gateway_Settings_GooglePay
 * @version  3.0.1
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsGooglepay extends EcpSettings {

	/**
	 * Internal identifier
	 */
	public const ID = 'ecommpay-google-pay';

	/**
	 * Shop section identifier
	 */
	const GOOGLE_PAY_SETTINGS = 'google-pay_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'GooglePay';
		$this->icon      = 'google_pay_host.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_google_pay_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_google_pay_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::GOOGLE_PAY_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'GooglePay settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings GooglePay payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings GooglePay payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings GooglePay payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings GooglePay payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings GooglePay payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'GooglePay', 'Settings GooglePay payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings GooglePay payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings GooglePay payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings GooglePay payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings GooglePay payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Pay with GooglePay', 'Settings GooglePay payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings GooglePay payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings GooglePay payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings GooglePay payments' ),
			),
			array(
				self::FIELD_ID   => self::GOOGLE_PAY_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
