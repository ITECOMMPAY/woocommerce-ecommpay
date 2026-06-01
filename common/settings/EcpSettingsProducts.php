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
 */
class EcpSettingsProducts extends EcpSettings {

	public const ID                                      = 'ecommpay-products';
	private const SECTION                                = 'ecommpay-products';
	private const CONTEXT                                = 'Settings payment form products';
	public const OPTION_ID_VIRTUAL_PRODUCTS_CONFIRMATION = 'ecp_virtual_products_confirmation_option';
	public const OPTION_ID_DOWNLOADABLE_PRODUCTS_CONFIRMATION = 'ecp_downloadable_products_confirmation_option';

	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'Products';
		$this->icon      = 'ecommpay.svg';
		$this->context   = self::CONTEXT;

		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_products' ) );

		parent::__construct();
	}

	public function get_settings_products(): array {
		$custom_attributes    = array();
		$purchase_type_option = ecommpay()->get_general_option( EcpSettingsGeneral::PURCHASE_TYPE );
		if ( EcpSettingsGeneral::PURCHASE_TYPE_AUTH !== $purchase_type_option ) {
			$custom_attributes['disabled'] = true;
		}
		$settings = array(
			array(
				self::FIELD_ID    => self::SECTION,
				self::FIELD_TITLE => $this->fieldText( 'Product Settings' ),
				self::FIELD_TYPE  => self::TYPE_START,
			),
			array(
				self::FIELD_TITLE => $this->fieldText(
					'Payments only for the selected product type will be '
														. 'automatically confirmed (captured) in case of two-step purchases.'
				),
				self::FIELD_TYPE  => self::TYPE_DESCRIPTION,
			),
			array(
				self::FIELD_ID     => self::OPTION_ID_VIRTUAL_PRODUCTS_CONFIRMATION,
				self::FIELD_TITLE  => $this->fieldText( 'Virtual products' ),
				self::FIELD_TYPE   => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $this->fieldText( 'Enable automatic confirmation of payments' ),
				self::FIELD_CUSTOM => $custom_attributes,
			),
			array(
				self::FIELD_ID     => self::OPTION_ID_DOWNLOADABLE_PRODUCTS_CONFIRMATION,
				self::FIELD_TITLE  => $this->fieldText( 'Downloadable products' ),
				self::FIELD_TYPE   => self::TYPE_CHECKBOX,
				self::FIELD_DESC   => $this->fieldText( 'Enable automatic confirmation of payments' ),
				self::FIELD_CUSTOM => $custom_attributes,
			),
			array(
				self::FIELD_ID   => self::SECTION,
				self::FIELD_TYPE => self::TYPE_END,
			),
		);

		return apply_filters( 'ecp_' . $this->id . '_settings', $settings );
	}
}
