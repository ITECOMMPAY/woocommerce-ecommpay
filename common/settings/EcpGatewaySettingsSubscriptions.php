<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Settings_Card class
 *
 * @class    Ecp_Gateway_Settings_Card
 * @version  3.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class EcpGatewaySettingsSubscriptions extends Ecp_Gateway_Settings {

	public const ID = 'ecommpay-subscriptions';
	private const SECTION = 'ecommpay-subscriptions';
	private const CONTEXT = 'Settings payment form subscriptions';
	public const OPTION_ID_VIRTUAL_SUBSCRIPTIONS_CONFIRMATION = 'ecp_virtual_subscriptions_confirmation_option';
	public const OPTION_ID_DOWNLOADABLE_SUBSCRIPTIONS_CONFIRMATION = 'ecp_downloadable_subscriptions_confirmation_option';
	public const OPTION_ID_OTHER_SUBSCRIPTIONS_CONFIRMATION = 'ecp_other_subscriptions_confirmation_option';

	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'Subscriptions', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'ecommpay.svg';
		$this->context = self::CONTEXT;

		add_filter( 'ecp_get_settings_' . $this->id, [ $this, 'get_settings_subscriptions' ] );
		
		parent::__construct();
	}

	public function get_settings_subscriptions(): array {
		$commonDescription = $this->fieldText( 'Enable automatic confirmation of payments' );

		$custom_attributes = [];

		$purchaseTypeOption = ecommpay()->get_general_option( Ecp_Gateway_Settings_General::PURCHASE_TYPE );

		if ( ! ecp_subscription_is_active() || $purchaseTypeOption !== Ecp_Gateway_Settings_General::PURCHASE_TYPE_AUTH ) {
			$custom_attributes['disabled'] = true;
		}
		$settings = [
			[
				self::FIELD_ID    => self::SECTION,
				self::FIELD_TITLE => $this->fieldText( 'Subscription settings' ),
				self::FIELD_TYPE  => self::TYPE_START,
			],
			[
				self::FIELD_TITLE => $this->fieldText( 'Payments only for the selected subscription type will be '
				                                       . 'automatically confirmed (captured) in case of two-step purchases.' ),
				self::FIELD_TYPE  => self::TYPE_DESCRIPTION,
			],
			[
				self::FIELD_ID => self::OPTION_ID_VIRTUAL_SUBSCRIPTIONS_CONFIRMATION,
				self::FIELD_TITLE => $this->fieldText( 'Virtual subscriptions' ),
				self::FIELD_TYPE  => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $commonDescription,
				self::FIELD_CUSTOM => $custom_attributes,
			],
			[
				self::FIELD_ID => self::OPTION_ID_DOWNLOADABLE_SUBSCRIPTIONS_CONFIRMATION,
				self::FIELD_TITLE => $this->fieldText( 'Downloadable subscriptions' ),
				self::FIELD_TYPE  => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $commonDescription,
				self::FIELD_CUSTOM => $custom_attributes,
			],
			[
				self::FIELD_ID => self::OPTION_ID_OTHER_SUBSCRIPTIONS_CONFIRMATION,
				self::FIELD_TITLE => $this->fieldText( 'Other subscriptions' ),
				self::FIELD_TYPE  => self::TYPE_CHECKBOX,
				self::FIELD_DESC  => $commonDescription,
				self::FIELD_TIP   => $this->fieldText(
					'This refers to any other types of subscriptions except for virtual and downloadable ones.'
				),
				self::FIELD_CUSTOM => $custom_attributes,
			],
			[
				self::FIELD_ID   => self::SECTION,
				self::FIELD_TYPE => self::TYPE_END,
			],
		];

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
