<?php

namespace common\settings;

use common\includes\filters\EcpFiltersList;

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
class EcpSettingsProducts extends EcpSettings {

	public const ID = 'ecommpay-products';
	private const SECTION = 'ecommpay-products';
	private const CONTEXT = 'Settings payment form products';
	public const OPTION_ID_VIRTUAL_PRODUCTS_CONFIRMATION = 'ecp_virtual_products_confirmation_option';
	public const OPTION_ID_DOWNLOADABLE_PRODUCTS_CONFIRMATION = 'ecp_downloadable_products_confirmation_option';

	public function __construct() {
		$this->id      = self::ID;
		$this->label   = _x( 'Products', 'Settings page', 'woo-ecommpay' );
		$this->icon    = 'ecommpay.svg';
		$this->context = self::CONTEXT;

		add_filter( EcpFiltersList::ECP_PREFIX_GET_SETTINGS . $this->id, [ $this, 'get_settings_products' ] );

		parent::__construct();
	}

	public function get_settings_products(): array {
		$custom_attributes  = [];
		$purchaseTypeOption = ecommpay()->get_general_option( EcpSettingsGeneral::PURCHASE_TYPE );
		if ( $purchaseTypeOption !== EcpSettingsGeneral::PURCHASE_TYPE_AUTH ) {
			$custom_attributes['disabled'] = true;
		}
		$settings = [
			[
				self::FIELD_ID    => self::SECTION,
				self::FIELD_TITLE => $this->fieldText( 'Product Settings' ),
				self::FIELD_TYPE  => self::TYPE_START,
			],
			[
				self::FIELD_TITLE => $this->fieldText( 'Payments only for the selected product type will be '
				                                       . 'automatically confirmed (captured) in case of two-step purchases.' ),
				self::FIELD_TYPE  => self::TYPE_DESCRIPTION,
			],
			[
				self::FIELD_ID     => self::OPTION_ID_VIRTUAL_PRODUCTS_CONFIRMATION,
				self::FIELD_TITLE  => $this->fieldText( 'Virtual products' ),
				self::FIELD_TYPE   => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $this->fieldText( 'Enable automatic confirmation of payments' ),
				self::FIELD_CUSTOM => $custom_attributes,
			],
			[
				self::FIELD_ID     => self::OPTION_ID_DOWNLOADABLE_PRODUCTS_CONFIRMATION,
				self::FIELD_TITLE  => $this->fieldText( 'Downloadable products' ),
				self::FIELD_TYPE   => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $this->fieldText( 'Enable automatic confirmation of payments' ),
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
