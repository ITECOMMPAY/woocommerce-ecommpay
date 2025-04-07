<?php

namespace common\settings;

use common\includes\filters\EcpFilters;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsCard class
 *
 * @class    EcpSettingsCard
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class EcpSettingsCard extends EcpSettings {

	/**
	 * Internal identifier
	 */
	public const ID = 'ecommpay-card';

	/**
	 * Card settings section identifier
	 */
	const CARD_SETTINGS = 'card_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'Card settings', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'card.svg';

		parent::__construct();

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, [ $this, 'get_settings_card' ] );
	}

	/**
	 * Returns the Payment Page fields settings as array.
	 *
	 * @return array
	 */
	public function get_settings_card(): array {
		$settings = [
			[
				self::FIELD_ID    => self::CARD_SETTINGS,
				self::FIELD_TITLE => _x( 'Card settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			],
			[
				self::FIELD_ID      => self::OPTION_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable/Disable', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x( 'Enable', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TIP     => _x( 'Before enabling the payment method please contact support@ecommpay.com', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_TITLE,
				self::FIELD_TITLE   => _x( 'Title', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'This controls the tittle which the user sees during checkout.',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Card payments', 'Settings card payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_SHOW_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Show Description', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Display the payment method description which user sees during checkout.',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_DESCRIPTION,
				self::FIELD_TITLE   => _x( 'Description', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_AREA,
				self::FIELD_TIP     => _x(
					'This controls the description which the user sees during checkout',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x(
					'',
					'Settings card payments',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CHECKOUT_BUTTON_TEXT,
				self::FIELD_TITLE   => _x( 'Order button text', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_TIP     => _x(
					'Text shown on the submit button when choosing payment method.',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => _x( 'Go to payment', 'Settings card payments', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID      => self::OPTION_MODE,
				self::FIELD_TITLE   => _x( 'Display mode', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => _x(
					'Payment page display mode',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_OPTIONS => [
					self::MODE_REDIRECT => _x( 'Redirect', 'Display mode', 'woo-ecommpay' ),
					self::MODE_POPUP    => _x( 'Popup', 'Display mode', 'woo-ecommpay' ),
					self::MODE_EMBEDDED => _x( 'Embedded', 'Display mode', 'woo-ecommpay' ),
				],
				self::FIELD_DEFAULT => self::MODE_EMBEDDED,
			],
			[
				self::FIELD_ID      => self::OPTION_POPUP_MISS_CLICK,
				self::FIELD_TITLE   => _x( 'Close on miss click', 'Settings card payments', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Close popup window on mouse miss click',
					'Settings card payments',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_DISABLED
			],
			[
				self::FIELD_ID   => self::CARD_SETTINGS,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
