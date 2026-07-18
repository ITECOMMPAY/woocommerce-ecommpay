<?php
/**
 * Plugin Name:       ECOMMPAY Payments
 * Plugin URI:        https://ecommpay.com
 * GitHub Plugin URI:
 * Description:       Easy payment from WooCommerce by different methods in single Payment Page.
 * Version:           5.0.3
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-ecommpay
 * Domain Path:       /language/
 * Copyright:         © 2017-2026 Ecommpay, London
 *
 * @package Ecp_Gateway
 * @author ECOMMPAY
 * @copyright © 2017-2026 ECOMMPAY, London
 */

use common\install\EcpGatewayInstall;
use common\modules\EcpModulePaymentPage;
use common\WCDependencies;
use common\helpers\EcpLoader;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ECP_PLUGIN_PATH' ) ) {
	define( 'ECP_PLUGIN_PATH', __FILE__ );
}

if ( ! defined( 'ECP_PLUGINS_LOADED_PRIORITY' ) ) {
	define( 'ECP_PLUGINS_LOADED_PRIORITY', 11 );
}

require_once __DIR__ . '/helpers/ecp-woo-blocks-support.php';

add_action(
	'plugins_loaded',
	function () {
			// Check available woocommerce classes.
		if ( ! class_exists( 'WCDependencies' ) ) {
			require_once __DIR__ . '/common/WCDependencies.php';
		}

			// Check if WooCommerce is active.
		if ( ! WCDependencies::woocommerce_active_check() ) {
			add_action(
				'admin_notices',
				function () {
							$class    = 'notice notice-error';
							$headline = __( 'ECOMMPAY requires WooCommerce to be active.', 'woo-ecommpay' );
							$message  = __( 'Go to the plugins page to activate WooCommerce', 'woo-ecommpay' );
							printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message );
				}
			);

			return;
		}

			require_once __DIR__ . '/common/__autoload.php';

			// Instantiate.
			ecommpay();

		if ( ecp_has_available_methods() ) {
			ecommpay()->hooks();
		}

		// Add the gateway to WooCommerce.
		add_filter(
			'woocommerce_payment_gateways',
			function ( array $methods ) {
				foreach ( ecp_payment_classnames() as $class_name ) {
					$methods[] = $class_name;
				}

				return $methods;
			}
		);

			// Include wp-admin styles.
			add_action(
				'admin_enqueue_scripts',
				function () {
							wp_enqueue_style(
								'woocommerce-ecommpay-admin-style',
								ecp_css_url( 'woocommerce-ecommpay-admin.css' ),
								array(),
								ecp_version()
							);
				}
			);

		// Include wp-frontend styles.
		add_action(
			'wp_enqueue_scripts',
			function () {
				global $wp;

				wp_enqueue_style(
					'woocommerce-ecommpay-frontend-style',
					ecp_css_url( 'woocommerce-ecommpay-frontend.css' ),
					array(),
					ecp_version()
				);

				$is_checkout_scripts_needed = ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) )
					|| is_wc_endpoint_url( 'order-pay' );

				ecp_debug( sprintf( '[ECP DEBUG] gateway-ecommpay: is_checkout=%s, is_checkout_scripts_needed=%s', is_checkout() ? 'true' : 'false', $is_checkout_scripts_needed ? 'true' : 'false' ) );

				if ( $is_checkout_scripts_needed ) {

					$url = ecp_payment_page()->get_url();

					// Ecommpay merchant bundle.
					wp_enqueue_script(
						'ecommpay_merchant_js',
						sprintf( '%s/shared/merchant.js', $url ),
						array(),
						null
					);
					wp_enqueue_style(
						'ecommpay_merchant_css',
						sprintf( '%s/shared/merchant.css', $url ),
						array(),
						null
					);

					// Enqueue common checkout functions (must be before version-specific script).
					wp_enqueue_script(
						'ecommpay_checkout_common_script',
						ecp_js_url( 'checkout-common.js' ),
						array( 'jquery' ),
						ecp_version()
					);

					// Choose a checkout script based on the version.
					$checkout_script = EcpModulePaymentPage::is_modern_embedded_mode() ? 'checkout.js' : 'checkout-legacy.js';

					wp_enqueue_script(
						'ecommpay_checkout_script',
						ecp_js_url( $checkout_script ),
						array( 'jquery', 'ecommpay_checkout_common_script' ),
						ecp_version()
					);

					try {
						if ( absint( $wp->query_vars['order-pay'] ?? 0 ) > 0 ) {
							$order_id = absint( $wp->query_vars['order-pay'] ); // The order ID
						} else {
							$order_id = is_wc_endpoint_url( 'order-pay' );
						}
					} catch ( Exception $e ) {
						$order_id = 0;
					}

					// Woocommerce Ecommpay Plugin frontend.
					wp_enqueue_script(
						'ecommpay_frontend_helpers_script',
						ecp_js_url( 'frontend-helpers.js' ),
						array( 'jquery' ),
						ecp_version()
					);

					wp_localize_script(
						'ecommpay_checkout_common_script',
						'ECP',
						array(
							'ajax_url'   => admin_url( 'admin-ajax.php' ),
							'origin_url' => $url,
							'order_id'   => $order_id,
						)
					);

					$loader = new EcpLoader();
					$loader->append_loader_on_page();
				}
			}
		);
	},
	ECP_PLUGINS_LOADED_PRIORITY
);


// Load translations at the correct time.
add_action(
	'init',
	function () {
			load_plugin_textdomain(
				'woo-ecommpay',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/language/'
			);
	}
);

/**
 * <h2>Run ECOMMPAY Gateway installer.</h2>
 *
 * @param string __FILE__ - The current file
 * @param callable - Do the installer/update logic.
 *
 * @noinspection PhpVarTagWithoutVariableNameInspection
 */
register_activation_hook(
	__FILE__,
	function () {
			require_once __DIR__ . '/common/__autoload.php';

			$installer = EcpGatewayInstall::get_instance();

			// Run the installer on the first install.
		if ( $installer->is_first_install() ) {
			$installer->install();
		}

		if ( $installer->is_update_required() ) {
			$installer->update();
		}
	}
);
