<?php
/**
 * Plugin Name:       ECOMMPAY Payments
 * Plugin URI:        https://ecommpay.com
 * GitHub Plugin URI:
 * Description:       Easy payment from WooCommerce by different methods in single Payment Page.
 * Version:           4.2.1
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-ecommpay
 * Domain Path:       /language/
 * Copyright:         © 2017-2023 Ecommpay, London
 *
 * @package Ecp_Gateway
 * @author ECOMMPAY
 * @copyright © 2017-2023 ECOMMPAY, London
 */

use common\install\EcpGatewayInstall;
use common\WCDependencies;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ECP_PLUGIN_PATH' ) ) {
	define( 'ECP_PLUGIN_PATH', __FILE__ );
}

require_once __DIR__ . '/helpers/ecp-woo-blocks-support.php';

add_action(
	'plugins_loaded',
	function () {
		// Check available woocommerce classes
		if ( ! class_exists( 'WCDependencies' ) ) {
			require_once __DIR__ . '/common/WCDependencies.php';
		}

		// Check if WooCommerce is active.
		if ( ! WCDependencies::woocommerce_active_check() ) {
			add_action( 'admin_notices', function () {
				$class    = 'notice notice-error';
				$headline = __( 'ECOMMPAY requires WooCommerce to be active.', 'woo-ecommpay' );
				$message  = __( 'Go to the plugins page to activate WooCommerce', 'woo-ecommpay' );
				printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message );
			} );

			return;
		}

		require_once __DIR__ . '/common/__autoload.php';

		// Instantiate
		ecommpay();

		if ( ecp_has_available_methods() ) {
			ecommpay()->hooks();
		}

		// Add the gateway to WooCommerce
		add_filter( 'woocommerce_payment_gateways', function ( array $methods ) {
			foreach ( ecp_payment_classnames() as $class_name ) {
				$methods[] = $class_name;
			}

			return $methods;
		} );

		// Include wp-admin styles
		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_style(
					'woocommerce-ecommpay-admin-style',
					ecp_css_url( 'woocommerce-ecommpay-admin.css' ),
					[],
					ecp_version()
				);
			}
		);

		// Include wp-frontend styles
		add_action(
			'wp_enqueue_scripts',
			function () {
				global $wp;

				wp_enqueue_style(
					'woocommerce-ecommpay-frontend-style',
					ecp_css_url( 'woocommerce-ecommpay-frontend.css' ),
					[],
					ecp_version()
				);

				$is_checkout_scripts_needed = is_checkout() || is_wc_endpoint_url( 'order-pay' );

				if ( $is_checkout_scripts_needed ) {

					$url = ecp_payment_page()->get_url();

					// Ecommpay merchant bundle.
					wp_enqueue_script(
						'ecommpay_merchant_js',
						sprintf( '%s/shared/merchant.js', $url ),
						[],
						null
					);
					wp_enqueue_style(
						'ecommpay_merchant_css',
						sprintf( '%s/shared/merchant.css', $url ),
						[],
						null
					);
					wp_enqueue_script(
						'ecommpay_checkout_script',
						ecp_js_url( 'checkout.js' ),
						[ 'jquery' ],
						ecp_version()
					);

					try {
						if ( absint( $wp->query_vars['order-pay'] ?? 0) > 0 ) {
							$order_id = absint( $wp->query_vars['order-pay'] ); // The order ID
						} else {
							$order_id = is_wc_endpoint_url( 'order-pay' );
						}
					} catch ( Exception $e ) {
						$order_id = 0;
					}

					// Woocommerce Ecommpay Plugin frontend
					wp_enqueue_script(
						'ecommpay_frontend_helpers_script',
						ecp_js_url( 'frontend-helpers.js' ),
						[ 'jquery' ],
						ecp_version()
					);

					wp_localize_script(
						'ecommpay_checkout_script',
						'ECP',
						[
							'ajax_url'   => admin_url( "admin-ajax.php" ),
							'origin_url' => $url,
							'order_id'   => $order_id,
						]
					);

					wp_enqueue_style( 'ecommpay_loader_css', ecp_css_url( 'loader.css' ) );
				}
			}
		);
	},
	0
);

/**
 * <h2>Run ECOMMPAY Gateway installer.</h2>
 *
 * @param string __FILE__ - The current file
 * @param callable - Do the installer/update logic.
 *
 * @noinspection PhpVarTagWithoutVariableNameInspection
 */
register_activation_hook( __FILE__, function () {
	require_once __DIR__ . '/common/__autoload.php';

	$installer = EcpGatewayInstall::get_instance();

	// Run the installer on the first install.
	if ( $installer->is_first_install() ) {
		$installer->install();
	}

	if ( $installer->is_update_required() ) {
		$installer->update();
	}
} );
