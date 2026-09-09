<?php

namespace common\settings;

use common\enums\EcpWcPaymentMethods;
use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsMore class
 *
 * @class    EcpSettingsMore
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsMore extends EcpSettings {

	/**
	 * Shop section identifier
	 */
	const MORE_SETTINGS = 'more_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = EcpWcPaymentMethods::MORE;
		$this->label_key = 'More methods';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_more_methods' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_more_methods(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::MORE_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'More payment methods settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings more payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings more payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Display all methods in one or use it for an additional method.', 'Settings more payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings more payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings more payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'More payment methods', 'Settings more payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings more payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings more payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings more payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout.', 'Settings more payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Payment form with additional payment methods.', 'Settings more payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings more payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings more payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings more payments' ),
			),
			array(
				self::FIELD_ID    => self::OPTION_FORCE_CODE,
				self::FIELD_TITLE => $this->safe_translate( 'Payment method code', 'Settings more payments' ),
				self::FIELD_TYPE  => self::TYPE_TEXT,
				self::FIELD_DESC  => sprintf(
					'%s <a href="%s">%s</a>.',
					$this->safe_translate( 'The ID of the payment method that is opened to customers without an option to select another one. The list of codes is provided in ', 'Settings more payments' ),
					esc_url_raw( 'https://developers.ecommpay.com/en/en_pm_codes.html' ),
					$this->safe_translate( 'Payment method codes', 'Settings more payments' )
				),
				self::FIELD_TIP   => $this->safe_translate( 'If the field is empty, then all available payment methods will be displayed on the payment form', 'Settings more payments' ),
			),
			array(
				self::FIELD_ID   => self::MORE_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
