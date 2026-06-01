<?php

namespace common;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>WC Dependency Checker</h2>
 *
 * Checks if WooCommerce is enabled
 *
 * @class    WCDependencies
 * @version  2.0.0
 * @package  Ecp_Gateway
 * @category Class
 */
class WCDependencies {

	/**
	 * Minimum PHP version required
	 */
	public const MIN_PHP_VERSION = '7.4.0';

	/**
	 * Minimum WordPress version required
	 */
	public const MIN_WP_VERSION = '5.0.0';

	/**
	 * Minimum WooCommerce version required
	 */
	public const MIN_WC_VERSION = '4.0.0';

	private static array $active_plugins = array();

	public static function woocommerce_active_check(): bool {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'woocommerce/woocommerce.php', self::$active_plugins, true )
				|| array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );
	}

	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge(
				self::$active_plugins,
				get_site_option( 'active_sitewide_plugins', array() )
			);
		}
	}

	/**
	 * Check if PHP version meets minimum requirements
	 *
	 * @param string $version PHP version to check
	 *
	 * @return bool
	 */
	public static function check_php_version( string $version ): bool {
		return version_compare( $version, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check if WordPress version meets minimum requirements
	 *
	 * @param string $version WordPress version to check
	 *
	 * @return bool
	 */
	public static function check_wp_version( string $version ): bool {
		return version_compare( $version, self::MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if WooCommerce version meets minimum requirements
	 *
	 * @param string $version WooCommerce version to check
	 *
	 * @return bool
	 */
	public static function check_wc_version( string $version ): bool {
		return version_compare( $version, self::MIN_WC_VERSION, '>=' );
	}
}
