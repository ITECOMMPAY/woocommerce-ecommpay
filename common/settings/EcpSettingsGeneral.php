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
 */
class EcpSettingsGeneral extends EcpSettings {
	const OPTION_PROJECT_ID             = 'project_id';
	const OPTION_SECRET_KEY             = 'salt';
	const OPTION_DELETE_ON_UNINSTALL    = 'delete_orders_on_uninstall';
	const OPTION_CACHING_ENABLED        = 'caching_enabled';
	const OPTION_CACHING_EXPIRATION     = 'caching_expiration';
	const OPTION_LANGUAGE               = 'language';
	const OPTION_LOG_LEVEL              = 'log_level';
	const OPTION_TRANSACTION_INFO       = 'orders_transaction_info';
	const OPTION_AUTO_COMPETE_ORDER     = 'complete_order';
	const OPTION_CUSTOM_VARIABLES       = 'custom_variables';
	public const PURCHASE_TYPE          = 'purchase_type';
	public const AUTOMATIC_CANCELLATION = 'automatic_cancellation';

	// ECOMMPAY Payment page version
	public const OPTION_PAYMENT_PAGE_VERSION = 'payment_page_version';
	public const PP_VERSION_LEGACY = 'v4';
	public const PP_VERSION_MODERN = 'v5';

	// ECOMMPAY Custom variables data
	const CUSTOM_RECEIPT_DATA = 'receipt_data';

	// ECOMMPAY available language modes
	const LANG_BY_CUSTOMER  = 'by_customer_browser';
	const LANG_BY_WORDPRESS = 'by_wordpress';
	const LANG_ENGLISH      = 'EN';
	const LANG_FRANCE       = 'FR';
	const LANG_ITALIAN      = 'IT';
	const LANG_GERMANY      = 'DE';
	const LANG_SPANISH      = 'ES';
	const LANG_RUSSIAN      = 'RU';

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
	const ADMIN_OPTIONS   = 'admin_options';

	/**
	 * Uninstall section identifier
	 */
	const SECTION_UNINSTALL = 'uninstall_options';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id        = self::ID;
		$this->label_key = 'General';
		$this->icon      = 'ecommpay.svg';

		parent::__construct();

		add_filter( 'ecp_' . $this->id . '_settings', array( $this, 'add_uninstall_setting' ) );
		add_filter( EcpFilters::ECP_PREFIX_GET_SETTINGS . $this->id, array( $this, 'get_settings_general' ) );
	}

	/**
	 * Returns the general fields settings as array
	 *
	 * @return array
	 */
	public function get_settings_general(): array {
		return array(
			array(
				self::FIELD_ID    => self::SECTION_GENERAL,
				self::FIELD_TITLE => $this->safe_translate( 'General Settings', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
			),
			array(
				self::FIELD_TITLE          => $this->safe_translate( 'Merchant callback URL', 'Settings integration' ),
				self::FIELD_TYPE           => self::TYPE_TEXT,
				self::FIELD_GENERATE_VALUE => 'ecp_callback_url',
				self::FIELD_CUSTOM         => array(
					'readonly' => 'readonly',
				),
			),
			array(
				self::FIELD_ID    => self::OPTION_PROJECT_ID,
				self::FIELD_TITLE => $this->safe_translate( 'Project ID', 'Settings integration' ),
				self::FIELD_TYPE  => self::TYPE_NUMBER,
				self::FIELD_TIP   => $this->safe_translate( 'Your project ID you could get from ECOMMPAY helpdesk. Leave it blank if test mode', 'Settings integration' ),
			),
			array(
				self::FIELD_ID    => self::OPTION_SECRET_KEY,
				self::FIELD_TITLE => $this->safe_translate( 'Secret key', 'Settings integration' ),
				self::FIELD_TYPE  => self::TYPE_PASSWORD,
				self::FIELD_TIP   => $this->safe_translate( 'Secret key which is using to sign payment request. You could get it from ECOMMPAY helpdesk', 'Settings integration' ),
			),
			array(
				self::FIELD_ID      => self::PURCHASE_TYPE,
				self::FIELD_TITLE   => $this->safe_translate( 'Purchase type', 'Settings payment form' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => $this->safe_translate( 'A one-step purchase is a payment type that uses a single step to make an immediate transfer of funds from the customer to the merchant. A two-step purchase is a payment type that uses two steps to make a transfer of funds from the customer to the merchant. On the first step, upon a single Payment Page session, the purchase amount is held. On the second step, this amount is either withdrawn (captured) or released (cancelled) by the merchant.', 'Settings payment form' ),
				self::FIELD_OPTIONS => array(
					self::PURCHASE_TYPE_SALE => $this->safe_translate( 'Sale (one-step purchase)', 'Purchase type' ),
					self::PURCHASE_TYPE_AUTH => $this->safe_translate( 'Auth (two-step purchase)', 'Purchase type' ),
				),
				self::FIELD_DEFAULT => self::PURCHASE_TYPE_SALE,
			),
			array(
				self::FIELD_ID      => self::AUTOMATIC_CANCELLATION,
				self::FIELD_TITLE   => $this->safe_translate( 'Automatic cancellation of payments', 'Settings payment form' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings payment form' ),
				self::FIELD_TIP     => $this->safe_translate( 'By enabling this, you can have payments cancelled automatically when cancelling related orders.', 'Settings payment form' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_LANGUAGE,
				self::FIELD_TITLE   => $this->safe_translate( 'Language', 'Settings payment form' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => $this->safe_translate( 'Payment page language', 'Settings payment form' ),
				self::FIELD_OPTIONS => $this->language_options(),
				self::FIELD_DEFAULT => 'by_customer_browser',
			),
			array(
				self::FIELD_ID   => self::SECTION_GENERAL,
				self::FIELD_TYPE => self::TYPE_END,
			),
			array(
				self::FIELD_ID    => 'advanced',
				self::FIELD_TITLE => $this->safe_translate( 'Advanced options', 'Settings general form' ),
				self::FIELD_TYPE  => self::TYPE_TOGGLE_START,
			),
			array(
				self::FIELD_ID    => self::CACHING_OPTIONS,
				self::FIELD_TITLE => $this->safe_translate( 'Transaction Cache', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => $this->safe_translate( 'Transaction cache is strongly recommended enable!', 'Settings cache' ),
			),
			array(
				self::FIELD_ID      => self::OPTION_CACHING_ENABLED,
				self::FIELD_TITLE   => $this->safe_translate( 'Enable Caching', 'Settings cache' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings cache' ),
				self::FIELD_TIP     => $this->safe_translate( 'Caches transaction data to improve application and web-server performance.', 'Settings cache' ),
				self::FIELD_SUFFIX  => $this->safe_translate( 'Recommended.', 'Settings cache' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_CACHING_EXPIRATION,
				self::FIELD_TITLE   => $this->safe_translate( 'Cache Expiration', 'Settings cache' ),
				self::FIELD_TYPE    => 'number',
				self::FIELD_TIP     => $this->safe_translate( 'Time in seconds for how long a transaction should be cached.', 'Settings cache' ),
				self::FIELD_DEFAULT => 7 * DAY_IN_SECONDS,
				self::FIELD_DESC    => $this->safe_translate( 'Default: 604800 (7 days).', 'Settings cache' ),
			),
			array(
				self::FIELD_ID   => self::CACHING_OPTIONS,
				self::FIELD_TYPE => self::TYPE_END,
			),

			array(
				self::FIELD_ID    => self::ADMIN_OPTIONS,
				self::FIELD_TITLE => $this->safe_translate( 'Shop Admin Setup', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
			),
			array(
				self::FIELD_ID      => self::OPTION_LOG_LEVEL,
				self::FIELD_TITLE   => $this->safe_translate( 'Log level', 'Settings shop admin setup' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => $this->safe_translate( 'Level of save log data.', 'Settings shop admin setup' ),
				self::FIELD_OPTIONS => array(
					WC_Log_Levels::EMERGENCY => $this->safe_translate( 'Emergency', 'Log level' ),
					WC_Log_Levels::CRITICAL  => $this->safe_translate( 'Critical', 'Log level' ),
					WC_Log_Levels::ALERT     => $this->safe_translate( 'Alert', 'Log level' ),
					WC_Log_Levels::ERROR     => $this->safe_translate( 'Error', 'Log level' ),
					WC_Log_Levels::WARNING   => $this->safe_translate( 'Warning', 'Log level' ),
					WC_Log_Levels::NOTICE    => $this->safe_translate( 'Notice', 'Log level' ),
					WC_Log_Levels::INFO      => $this->safe_translate( 'Info', 'Log level' ),
					WC_Log_Levels::DEBUG     => $this->safe_translate( 'Debug', 'Log level' ),
				),
				self::FIELD_DEFAULT => WC_Log_Levels::ERROR,
				// translators: %s is the default log level name.
				self::FIELD_DESC    => sprintf(
					$this->safe_translate( 'Default: %s', 'Settings shop admin setup' ),
					$this->safe_translate( 'Error', 'Log level' )
				),
			),
			array(
				self::FIELD_ID      => self::OPTION_TRANSACTION_INFO,
				self::FIELD_TITLE   => $this->safe_translate( 'Fetch Payment Info', 'Settings shop admin setup' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings shop admin setup' ),
				self::FIELD_TIP     => $this->safe_translate( 'Show payment information in the order overview.', 'Settings shop admin setup' ),
				self::FIELD_DEFAULT => self::VALUE_ENABLED,
			),
			array(
				self::FIELD_ID      => self::OPTION_AUTO_COMPETE_ORDER,
				self::FIELD_TITLE   => $this->safe_translate( 'Сomplete order automatically', 'Settings shop admin setup' ),
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
				self::FIELD_DESC    => $this->safe_translate( 'Enable', 'Settings shop admin setup' ),
				self::FIELD_TIP     => $this->safe_translate( 'Automatically complete the order in case of successful payment. Otherwise, the order will be in the Processing status.', 'Settings shop admin setup' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
			),
			[
				self::FIELD_ID      => self::OPTION_PAYMENT_PAGE_VERSION,
				self::FIELD_TITLE   => _x( 'Payment page version', 'Settings shop admin setup', 'woo-ecommpay' ),
				self::FIELD_TYPE    => self::TYPE_DROPDOWN,
				self::FIELD_TIP     => _x(
					'Select the version of the payment page to use.',
					'Settings shop admin setup',
					'woo-ecommpay'
				),
				self::FIELD_OPTIONS => array(
					self::PP_VERSION_LEGACY => _x( 'v4', 'Payment page version', 'woo-ecommpay' ),
					self::PP_VERSION_MODERN => _x( 'v5', 'Payment page version', 'woo-ecommpay' ),
				),
				self::FIELD_DEFAULT => self::PP_VERSION_LEGACY,
			],
			[
				self::FIELD_ID => self::ADMIN_OPTIONS,
				self::FIELD_TYPE => self::TYPE_END,
			],

			array(
				self::FIELD_ID   => 'advanced',
				self::FIELD_TYPE => self::TYPE_TOGGLE_END,
			),

		);
	}

	/**
	 * Provides a list of custom variable options used in the settings
	 *
	 * @return array
	 */
	private function language_options(): array {
		return array(
			self::LANG_BY_CUSTOMER  => $this->safe_translate( 'By Customer browser setting', 'Language' ),
			self::LANG_BY_WORDPRESS => $this->safe_translate( 'By WordPress', 'Language' ),
			self::LANG_ENGLISH      => $this->safe_translate( 'English', 'Language' ),
			self::LANG_FRANCE       => $this->safe_translate( 'France', 'Language' ),
			self::LANG_ITALIAN      => $this->safe_translate( 'Italian', 'Language' ),
			self::LANG_GERMANY      => $this->safe_translate( 'Germany', 'Language' ),
			self::LANG_SPANISH      => $this->safe_translate( 'Spanish', 'Language' ),
			self::LANG_RUSSIAN      => $this->safe_translate( 'Russian', 'Language' ),
		);
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
			$settings[] = array(
				self::FIELD_ID    => self::SECTION_UNINSTALL,
				self::FIELD_TITLE => $this->safe_translate( 'Uninstalling', 'Settings section' ),
				self::FIELD_TYPE  => self::TYPE_START,
				self::FIELD_DESC  => '',
			);

			$settings[] = array(
				self::FIELD_ID      => self::OPTION_DELETE_ON_UNINSTALL,
				self::FIELD_TITLE   => $this->safe_translate( 'Delete orders', 'Settings uninstalling' ),
				self::FIELD_DESC    => $this->safe_translate( 'Delete orders with payment via ECOMMPAY when uninstalling plugin.', 'Settings uninstalling' ),
				self::FIELD_DEFAULT => self::VALUE_DISABLED,
				self::FIELD_TYPE    => self::TYPE_CHECKBOX,
			);

			$settings[] = array(
				self::FIELD_ID   => self::SECTION_UNINSTALL,
				self::FIELD_TYPE => self::TYPE_END,
			);
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
