<?php

namespace common\settings;

use common\includes\filters\EcpFilters;
use WC_Log_Levels;

defined( 'ABSPATH' ) || exit;

/**
 * EcpSettingsGeneral class
 *
 * @class    EcpSettingsGeneral
 * @version  2.0.0
 * @package  Ecp_Gateway/Settings
 * @category Class
 * @internal
 */
class EcpSettingsGeneral extends EcpSettings {
	const OPTION_PROJECT_ID = 'project_id';
	const OPTION_SECRET_KEY = 'salt';
	const OPTION_DELETE_ON_UNINSTALL = 'delete_orders_on_uninstall';
	const OPTION_CACHING_ENABLED = 'caching_enabled';
	const OPTION_CACHING_EXPIRATION = 'caching_expiration';
	const OPTION_LANGUAGE = 'language';
	const OPTION_LOG_LEVEL = 'log_level';
	const OPTION_TRANSACTION_INFO = 'orders_transaction_info';
	const OPTION_AUTO_COMPETE_ORDER = 'complete_order';
	const OPTION_CUSTOM_VARIABLES = 'custom_variables';
	public const PURCHASE_TYPE = 'purchase_type';
	public const AUTOMATIC_CANCELLATION = 'automatic_cancellation';

	// ECOMMPAY Custom variables data
	const CUSTOM_RECEIPT_DATA = 'receipt_data';

	// ECOMMPAY available language modes
	const LANG_BY_CUSTOMER = 'by_customer_browser';
	const LANG_BY_WORDPRESS = 'by_wordpress';
	const LANG_ENGLISH = 'EN';
	const LANG_FRANCE = 'FR';
	const LANG_ITALIAN = 'IT';
	const LANG_GERMANY = 'DE';
	const LANG_SPANISH = 'ES';
	const LANG_RUSSIAN = 'RU';

	public const PURCHASE_TYPE_SALE = 'sale';
	public const PURCHASE_TYPE_AUTH = 'auth';

	/**
	 * Internal identifier
	 */
	public const ID = 'general';

	/**
	 * General section identifier
	 */
	const SECTION_GENERAL = 'general_options';

	const CACHING_OPTIONS = 'caching_options';
	const ADMIN_OPTIONS = 'admin_options';

	/**
	 * Uninstall section identifier
	 */
	const SECTION_UNINSTALL = 'uninstall_options';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::ID;
		$this->label = _x( 'General', 'Settings page', 'woo-ecommpay' );
		$this->icon  = 'ecommpay.svg';

		parent::__construct();

		add_filter( 'ecp_' . $this->id . '_settings', [ $this, 'add_uninstall_setting' ] );
		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, [ $this, 'get_settings_general' ] );
	}

	/**
	 * Returns the general fields settings as array
	 *
	 * @return array
	 */
	public function get_settings_general(): array {
		return [
			[
				self::FIELD_ID    => self::SECTION_GENERAL,
				self::FIELD_TITLE => _x( 'General Settings', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
			],
			[
				self::FIELD_TITLE   => _x( 'Merchant callback URL', 'Settings integration', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_TEXT,
				self::FIELD_GENERATE_VALUE => 'ecp_callback_url',
				self::FIELD_CUSTOM  => [
					'readonly' => 'readonly',
				],
			],
			[
				self::FIELD_ID    => self::OPTION_PROJECT_ID,
				self::FIELD_TITLE => _x( 'Project ID', 'Settings integration', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_NUMBER,
				self::FIELD_TIP   => _x(
					'Your project ID you could get from ECOMMPAY helpdesk. Leave it blank if test mode',
					'Settings integration',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID    => self::OPTION_SECRET_KEY,
				self::FIELD_TITLE => _x( 'Secret key', 'Settings integration', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_PASSWORD,
				self::FIELD_TIP   => _x(
					'Secret key which is using to sign payment request. You could get it from ECOMMPAY helpdesk',
					'Settings integration',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::PURCHASE_TYPE,
				self::FIELD_TITLE   => _x( 'Purchase type', 'Settings payment form', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => _x(
					'A one-step purchase is a payment type that uses a single step to make an immediate transfer of funds from the customer to the merchant. '
					. 'A two-step purchase is a payment type that uses two steps to make a transfer of funds from the customer to the merchant. '
					. 'On the first step, upon a single Payment Page session, the purchase amount is held. '
					. 'On the second step, this amount is either withdrawn (captured) or released (cancelled) by the merchant.',
					'Settings payment form',
					'woo-ecommpay'
				),
				self::FIELD_OPTIONS => [
					self::PURCHASE_TYPE_SALE => _x( 'Sale (one-step purchase)', 'Purchase type', 'woo-ecommpay' ),
					self::PURCHASE_TYPE_AUTH => _x( 'Auth (two-step purchase)', 'Purchase type', 'woo-ecommpay' ),
				],
				self::FIELD_DEFAULT => self::PURCHASE_TYPE_SALE,
			],
			[
				self::FIELD_ID    => self::AUTOMATIC_CANCELLATION,
				self::FIELD_TITLE => _x( 'Automatic cancellation of payments',
					'Settings payment form', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_CHECKBOX,
				self::FIELD_DESC  => _x(
					'Enable',
					'Settings payment form', 'woo-ecommpay'
				),
				self::FIELD_TIP   => _x(
					'By enabling this, you can have payments cancelled automatically when cancelling related orders.',
					'Settings payment form', 'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_LANGUAGE,
				self::FIELD_TITLE   => _x( 'Language', 'Settings payment form', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => _x(
					'Payment page language',
					'Settings payment form',
					'woo-ecommpay'
				),
				self::FIELD_OPTIONS => $this->language_options(),
				self::FIELD_DEFAULT => 'by_customer_browser',
			],
			[
				self::FIELD_ID   => self::SECTION_GENERAL,
				self::FIELD_TYPE => self::TYPE_END,
			],
			[
				self::FIELD_ID    => 'advanced',
				self::FIELD_TITLE => _x( 'Advanced options', 'Settings general form', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_TOGGLE_START
			],
			[
				self::FIELD_ID    => self::CACHING_OPTIONS,
				self::FIELD_TITLE => _x( 'Transaction Cache', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => _x(
					'Transaction cache is strongly recommended enable!',
					'Settings cache',
					'woo-ecommpay'
				),
			],
			[
				self::FIELD_ID      => self::OPTION_CACHING_ENABLED,
				self::FIELD_TITLE   => _x( 'Enable Caching', 'Settings cache', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Enable',
					'Settings cache',
					'woo-ecommpay'
				),
				self::FIELD_TIP     => _x(
					'Caches transaction data to improve application and web-server performance.',
					'Settings cache',
					'woo-ecommpay'
				),
				self::FIELD_SUFFIX  => _x( 'Recommended.', 'Settings cache', 'woo-ecommpay' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_CACHING_EXPIRATION,
				self::FIELD_TITLE   => _x( 'Cache Expiration', 'Settings cache', 'woo-ecommpay' ),
				self::FIELD_TYPE    => 'number',
				self::FIELD_TIP     => _x(
					'Time in seconds for how long a transaction should be cached.',
					'Settings cache',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => 7 * DAY_IN_SECONDS,
				self::FIELD_DESC    => _x( 'Default: 604800 (7 days).', 'Settings cache', 'woo-ecommpay' ),
			],
			[
				self::FIELD_ID   => self::CACHING_OPTIONS,
				self::FIELD_TYPE => self::TYPE_END,
			],

			[
				self::FIELD_ID    => self::ADMIN_OPTIONS,
				self::FIELD_TITLE => _x( 'Shop Admin Setup', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
			],
			[
				self::FIELD_ID      => self::OPTION_LOG_LEVEL,
				self::FIELD_TITLE   => _x( 'Log level', 'Settings shop admin setup', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => _x(
					'Level of save log data.',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_OPTIONS => [
					WC_Log_Levels::EMERGENCY => _x( 'Emergency', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::CRITICAL  => _x( 'Critical', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::ALERT     => _x( 'Alert', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::ERROR     => _x( 'Error', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::WARNING   => _x( 'Warning', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::NOTICE    => _x( 'Notice', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::INFO      => _x( 'Info', 'Log level', 'woo-ecommpay' ),
					WC_Log_Levels::DEBUG     => _x( 'Debug', 'Log level', 'woo-ecommpay' ),
				],
				self::FIELD_DEFAULT => WC_Log_Levels::ERROR,
				self::FIELD_DESC    => sprintf(
					_x( 'Default: %s', 'Settings shop admin setup', 'woo-ecommpay' ),
					_x( 'Error', 'Log level', 'woo-ecommpay' )
				),
			],
			[
				self::FIELD_ID      => self::OPTION_TRANSACTION_INFO,
				self::FIELD_TITLE   => _x( 'Fetch Payment Info', 'Settings shop admin setup', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Enable',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_TIP     => _x(
					'Show payment information in the order overview.',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			],
			[
				self::FIELD_ID      => self::OPTION_AUTO_COMPETE_ORDER,
				self::FIELD_TITLE   => _x( 'Сomplete order automatically', 'Settings shop admin setup', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => _x(
					'Enable',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_TIP     => _x(
					'Automatically complete the order in case of successful payment. Otherwise, the order will be in the Processing status.',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			],
			[
				self::FIELD_ID => self::ADMIN_OPTIONS,
				self::FIELD_TYPE => self::TYPE_END,
			],

			[
				self::FIELD_ID   => 'advanced',
				self::FIELD_TYPE => self::TYPE_TOGGLE_END
			],

		];
	}

	/**
	 * Provides a list of custom variable options used in the settings
	 *
	 * @return array
	 */
	private function language_options(): array {
		return [
			self::LANG_BY_CUSTOMER  => _x( 'By Customer browser setting', 'Language', 'woo-ecommpay' ),
			self::LANG_BY_WORDPRESS => _x( 'By WordPress', 'Language', 'woo-ecommpay' ),
			self::LANG_ENGLISH      => _x( 'English', 'Language', 'woo-ecommpay' ),
			self::LANG_FRANCE       => _x( 'France', 'Language', 'woo-ecommpay' ),
			self::LANG_ITALIAN      => _x( 'Italian', 'Language', 'woo-ecommpay' ),
			self::LANG_GERMANY      => _x( 'Germany', 'Language', 'woo-ecommpay' ),
			self::LANG_SPANISH      => _x( 'Spanish', 'Language', 'woo-ecommpay' ),
			self::LANG_RUSSIAN      => _x( 'Russian', 'Language', 'woo-ecommpay' ),
		];
	}

	/**
	 * Add uninstall settings only for Super Admin
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function add_uninstall_setting( $settings ): array {
		if ( ! is_multisite() || ( is_main_site() ) ) {
			$settings[] = [
				self::FIELD_ID    => self::SECTION_UNINSTALL,
				self::FIELD_TITLE => _x( 'Uninstalling', 'Settings section', 'woo-ecommpay' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			];

			$settings[] = [
				self::FIELD_ID      => self::OPTION_DELETE_ON_UNINSTALL,
				self::FIELD_TITLE   => _x( 'Delete orders', 'Settings uninstalling', 'woo-ecommpay' ),
				self::FIELD_DESC    => _x(
					'Delete orders with payment via ECOMMPAY when uninstalling plugin.',
					'Settings uninstalling',
					'woo-ecommpay'
				),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
			];

			$settings[] = [
				self::FIELD_ID   => self::SECTION_UNINSTALL,
				self::FIELD_TYPE => self::TYPE_END,
			];
		}

		return $settings;
	}

	/**
	 * @inheritDoc
	 */
	public function output() {
		ecp_get_view( 'html-admin-settings-log.php' );

		parent::output();
	}
}
