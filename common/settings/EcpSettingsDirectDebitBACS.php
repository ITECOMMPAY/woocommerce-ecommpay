<?php

namespace common\settings;

use common\enums\EcpWcPaymentMethods;
use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsDirectDebitBACS class
 *
 * @class    EcpSettingsDirectDebitBACS
 * @version  3.4.3
 * @package  Ecp_Gateway/Settings
 * @category Class
 */
class EcpSettingsDirectDebitBACS extends EcpSettings {

	/**
	 * Direct Debit BACS settings section identifier
	 */
	const DIRECTDEBIT_BACS_SETTINGS = 'directdebit_bacs_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = EcpWcPaymentMethods::DIRECTDEBIT_BACS;
		$this->label_key = 'Direct Debit BACS';
		$this->icon      = 'directdebit-bacs.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_directdebit_bacs' ) );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_directdebit_bacs(): array {
		$settings = array(
			array(
				self::FIELD_ID    => self::DIRECTDEBIT_BACS_SETTINGS,
				self::FIELD_TITLE => $this->safe_translate( 'Direct Debit BACS settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			),
			array(
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable/Disable', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TIP     => $this->safe_translate( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings Direct Debit BACS payments' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => $this->safe_translate( 'Title', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the tittle which the user sees during checkout.', 'Settings Direct Debit BACS payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Direct Debit BACS', 'Settings Direct Debit BACS payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Show Description', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Display the payment method description which user sees during checkout.', 'Settings Direct Debit BACS payments' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => $this->safe_translate( 'Description', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => $this->safe_translate( 'This controls the description which the user sees during checkout', 'Settings Direct Debit BACS payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'You will be redirected to Direct Debit BACS.', 'Settings Direct Debit BACS payments' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => $this->safe_translate( 'Order button text', 'Settings Direct Debit BACS payments' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => $this->safe_translate( 'Text shown on the submit button when choosing payment method.', 'Settings Direct Debit BACS payments' ),
				self::FIELD_DEFAULT => $this->safe_translate( 'Go to payment', 'Settings Direct Debit BACS payments' ),
			),
			array(
				self::FIELD_ID   => self::DIRECTDEBIT_BACS_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
