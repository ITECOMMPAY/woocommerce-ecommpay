<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsBanks class
 *
 * @class    EcpSettingsBanks
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsBanks extends EcpSettings {

	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-banks';

	/**
	 * Shop section identifier
	 */
	const BANKS_SETTINGS = 'banks_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'Pay by Bank';
		$this->icon      = 'banks.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_banks_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_banks_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::BANKS_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'Pay by Bank (Open Banking) settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings banks payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings banks payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings banks payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings banks payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the title which the user sees during checkout.', 'Settings banks payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Pay by Bank', 'Settings banks payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings banks payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings banks payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings banks payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings banks payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Pay securely with your local bank.', 'Settings banks payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings banks payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings banks payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings banks payments' ),
			),
			array(
				self::FIELD_ID   => self::BANKS_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
