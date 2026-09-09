<?php

namespace common\settings;

use common\enums\EcpWcPaymentMethods;
use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsCard class
 *
 * @class    EcpSettingsCard
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsCard extends EcpSettings {

	/**
	 * Card settings section identifier
	 */
	const CARD_SETTINGS = 'card_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = EcpWcPaymentMethods::CARD;
		$this->label_key = 'Card settings';
		$this->icon      = 'card.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_card' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_card(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::CARD_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'Card settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings card payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings card payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings card payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Card payments', 'Settings card payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings card payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout', 'Settings card payments' ),
				self::FIELD_DEFAULT => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings card payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings card payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_MODE,
				self::FIELD_TITLE   => $this->safe_translate( 'Display mode', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => $this->safe_translate( 'Payment page display mode', 'Settings card payments' ),
				self::FIELD_OPTIONS => array(
					self::MODE_REDIRECT => $this->safe_translate( 'Redirect', 'Display mode' ),
					self::MODE_POPUP    => $this->safe_translate( 'Popup', 'Display mode' ),
					self::MODE_EMBEDDED => $this->safe_translate( 'Embedded', 'Display mode' ),
				),
				self::FIELD_DEFAULT => self::MODE_EMBEDDED,
			),
			array(
				self::FIELD_ID      => self::OPTION_POPUP_MISS_CLICK,
				self::FIELD_TITLE   => $this->safe_translate( 'Close on miss click', 'Settings card payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Close popup window on mouse miss click', 'Settings card payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID   => self::CARD_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
