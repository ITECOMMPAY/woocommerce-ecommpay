<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsIdeal class
 *
 * @class    EcpSettingsIdeal
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsIdeal extends EcpSettings {

	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-ideal';

	/**
	 * Shop section identifier
	 */
	const IDEAL_SETTINGS = 'ideal_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'iDEAL';
		$this->icon      = 'ideal.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_ideal_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_ideal_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::IDEAL_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'iDEAL settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings ideal payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings ideal payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings ideal payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings ideal payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings ideal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'iDEAL', 'Settings ideal payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings ideal payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings ideal payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings ideal payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings ideal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'You will be redirected to iDEAL.', 'Settings ideal payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings ideal payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings ideal payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings ideal payments' ),
			),
			array(
				self::FIELD_ID   => self::IDEAL_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
