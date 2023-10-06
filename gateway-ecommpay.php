<?php
/**
 * Plugin Name:       ECOMMPAY Payments
 * Plugin URI:        https://ecommpay.com
 * GitHub Plugin URI:
 * Description:       Easy payment from WooCommerce by different methods in single Payment Page.
 * Version:           3.3.4
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
defined('ABSPATH') || exit;


if (!defined('ECP_PLUGIN_PATH')) {
    define( 'ECP_PLUGIN_PATH', __FILE__ );
}

add_action(
    'plugins_loaded',
    function () {
        // Check available woocommerce classes
        if (!class_exists('WC_Dependencies')) {
            require_once __DIR__ . '/common/class-wc-dependencies.php';
        }

        // Check if WooCommerce is active.
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        if (!WC_Dependencies::woocommerce_active_check()) {
            add_action('admin_notices', function () {
                $class = 'notice notice-error';
                $headline = __('ECOMMPAY requires WooCommerce to be active.', 'woo-ecommpay');
                $message = __('Go to the plugins page to activate WooCommerce', 'woo-ecommpay');
                printf('<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message);
            });
            return;
        }

        require_once __DIR__ . '/common/__autoload.php';

        // Instantiate
        ecommpay();

        if (ecp_has_available_methods()) {
            ecommpay()->hooks();
        }

        // Add the gateway to WooCommerce
        add_filter('woocommerce_payment_gateways', function (array $methods) {
            foreach (ecp_payment_classnames() as $class_name) {
                $methods[] = $class_name;
            }
            return $methods;
        });

        // Include wp-admin styles
        add_action(
            'admin_enqueue_scripts',
            function() {
                wp_enqueue_style(
                    'woocommerce-ecommpay-admin-style',
                    ecp_css_url('woocommerce-ecommpay-admin.css'),
                    [],
                    ecp_version()
                );
            }
        );

        // Include wp-frontend styles
        add_action(
            'wp_enqueue_scripts',
            function() {
                wp_enqueue_style(
                    'woocommerce-ecommpay-frontend-style',
                    ecp_css_url('woocommerce-ecommpay-frontend.css'),
                    [],
                    ecp_version()
                );
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
 */
register_activation_hook(__FILE__, function () {
    require_once __DIR__ . '/common/__autoload.php';

    $installer = Ecp_Gateway_Install::get_instance();

    // Run the installer on the first install.
    if ($installer->is_first_install()) {
        $installer->install();
    }

    if ($installer->is_update_required()) {
        $installer->update();
    }
});
