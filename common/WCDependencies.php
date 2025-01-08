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
	private static array $active_plugins = [];

	public static function woocommerce_active_check(): bool {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'woocommerce/woocommerce.php', self::$active_plugins )
		       || array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );
	}

	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge(
				self::$active_plugins,
				get_site_option( 'active_sitewide_plugins', [] )
			);
		}
	}

}


