<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsDirectDebitBACS class
 *
 * @class    EcpSettingsDirectDebitBACS
 * @version  3.4.3
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class EcpSettingsDirectDebitBACS extends EcpSettings {


	/**
	 * Internal identifier
	 */
	const ID = 'ecommpay-directdebit-bacs';

	/**
	 * Direct Debit BACS settings section identifier
	 */
	const DIRECTDEBIT_BACS_SETTINGS = 'directdebit_bacs_settings';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'Direct Debit BACS', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'directdebit-bacs.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, [ $this, 'get_settings_directdebit_bacs' ] );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_directdebit_bacs(): array {
		$settings = [
			[
				self::FIELD_ID    => self::DIRECTDEBIT_BACS_SETTINGS,
				self::FIELD_TITLE => _x( 'Direct Debit BACS settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			],
			[
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable/Disable', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x( 'Enable', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TIP     => _x(
					'Before enabling the payment method please contact support@ecommpay.com',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => _x( 'Title', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'This controls the tittle which the user sees during checkout.',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Direct Debit BACS', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Show Description', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Display the payment method description which user sees during checkout.',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Description', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => _x(
					'This controls the description which the user sees during checkout',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x(
					'You will be redirected to Direct Debit BACS.',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => _x( 'Order button text', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'Text shown on the submit button when choosing payment method.',
					'Settings Direct Debit BACS payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Go to payment', 'Settings Direct Debit BACS payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID   => self::DIRECTDEBIT_BACS_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
