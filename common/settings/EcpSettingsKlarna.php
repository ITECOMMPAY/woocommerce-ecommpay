<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsKlarna class
 *
 * @class    EcpSettingsKlarna
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsKlarna extends EcpSettings {

	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-klarna';

	/**
	 * Shop section identifier
	 */
	const KLARNA_SETTINGS = 'klarna_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'Klarna';
		$this->icon      = 'klarna.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_klarna_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_klarna_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::KLARNA_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'Klarna settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings klarna payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings klarna payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings klarna payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings klarna payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings klarna payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Klarna', 'Settings klarna payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings klarna payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings klarna payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings klarna payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings klarna payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'You will be redirected to Klarna.', 'Settings klarna payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings klarna payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings klarna payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings klarna payments' ),
			),
			array(
				self::FIELD_ID   => self::KLARNA_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
