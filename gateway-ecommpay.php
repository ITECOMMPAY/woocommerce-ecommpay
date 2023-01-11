<?php
/**
 * Plugin Name:       ECOMMPAY Payments
 * Plugin URI:        https://ecommpay.com
 * GitHub Plugin URI:
 * Description:       Easy payment from WooCommerce by different methods in single Payment Page.
 * Version:           2.2.0
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-ecommpay
 * Domain Path:       /language/
 * Copyright:         © 2017-2022 Ecommpay, London
 *
 * @package Ecp_Gateway
 * @author ECOMMPAY
 * @copyright © 2017-2022 ECOMMPAY, London
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

        if (ecp_is_enabled(Ecp_Gateway_Settings_Page::OPTION_ENABLED)) {
            ecommpay()->hooks();
        }

        // Add the gateway to WooCommerce
        add_filter('woocommerce_payment_gateways', function (array $methods) {
            $methods[] = 'Ecp_Gateway';
            return $methods;
        });

        // Include styles
        add_action(
            'admin_enqueue_scripts',
            function() {
                wp_enqueue_style(
                    'woocommerce-ecommpay-style',
                    ecp_css_url('woocommerce-ecommpay.css'),
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
