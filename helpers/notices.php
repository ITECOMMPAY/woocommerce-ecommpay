<?php

/**
 * <h2>Handler for system non catch error.</h2>
 * @param int $severity <p>Error type.</p>
 * @param string $message <p>Error message.</p>
 * @param string $file <p>File name with error.</p>
 * @param int $line <p>Line in file with error.</p>
 * @return void
 * @throws Ecp_Gateway_Error_Exception
 */
function ecp_exception_error_handler($severity, $message, $file, $line)
{
    throw new Ecp_Gateway_Error_Exception($message, 0, $severity, $file, $line);
}

/**
 * Store a message to display in WP admin.
 *
 * @param string The message to display
 *
 * @since 4.9.4
 */
function woocommerce_ecommpay_add_admin_notice($message, $notice_type = 'success')
{
    $notices = get_transient('_wc_ecp_admin_notices');

    if (false === $notices) {
        $notices = [];
    }

    $notices[$notice_type][] = $message;

    set_transient('_wc_ecp_admin_notices', $notices, 60 * 60);
}

/**
 * Store a message to display in WP admin.
 *
 * @param $error
 * @since 4.9.4
 */
function woocommerce_ecommpay_add_runtime_error_notice($error)
{
    $errors = get_transient('_wc_ecp_admin_runtime_errors');

    if (false === $errors) {
        $errors = [];
    }

    $errors[] = $error;

    set_transient('_wc_ecp_admin_runtime_errors', $errors);
}

/**
 * Delete any admin notices we stored for display later.
 *
 * @since 2.0
 */
function woocommerce_ecommpay_clear_admin_notices()
{
    delete_transient('_wc_ecp_admin_notices');
}

/**
 * Delete any admin notices we stored for display later.
 *
 * @since 2.0
 */
function woocommerce_ecommpay_clear_runtime_error_notices()
{
    delete_transient('_wc_ecp_admin_runtime_errors');
}

/**
 * Display any notices added with @param bool $clear
 *
 * @see woocommerce_ecommpay_add_admin_notice()
 *
 * This method is also hooked to 'admin_notices' to display notices there.
 *
 * @since 2.0
 */
function woocommerce_ecommpay_display_admin_notices($clear = true)
{
    $notices = get_transient('_wc_ecp_admin_notices');

    if (!empty ($notices)) {


        if (!empty ($notices['success'])) {
            array_walk($notices['success'], 'esc_html');
            echo '<div class="notice notice-info"><p>' . wp_kses_post(implode("</p>\n<p>", $notices['success'])) . '</p></div>';
        }

        if (!empty ($notices['error'])) {
            array_walk($notices['error'], 'esc_html');
            echo '<div class="notice notice-error"><p>' . wp_kses_post(implode("</p>\n<p>", $notices['error'])) . '</p></div>';
        }
    }

    if (false !== $clear) {
        woocommerce_ecommpay_clear_admin_notices();
    }
}

add_action('admin_notices', 'woocommerce_ecommpay_display_admin_notices', 100);

/**
 * Display any notices added with
 *
 * @see woocommerce_ecommpay_add_admin_notice()
 *
 * This method is also hooked to 'admin_notices' to display notices there.
 *
 * @since 2.0
 */
function woocommerce_ecommpay_display_dismissible_admin_notices()
{
    $notices = get_transient('_wc_ecp_admin_runtime_errors');

    if (!empty ($notices)) {
        array_walk($notices, 'esc_html');
        echo '<div class="ecp-notice notice notice-error is-dismissible">';
        printf('<h3>%s</h3>', __('ECOMMPAY - Payment related problems registered'));
        echo '<p>' . wp_kses_post(implode("</p>\n<p>", $notices)) . '</p>';
        echo '</div>';
    }
}

add_action('admin_notices', 'woocommerce_ecommpay_display_dismissible_admin_notices', 100);

/**
 * Endpoint to flush the persisted errors
 */
function woocommerce_ecommpay_ajax_flush_runtime_errors()
{
    if (current_user_can('manage_woocommerce')) {
        delete_transient('_wc_ecp_admin_runtime_errors');
    }
}

add_action('wp_ajax_woocommerce_ecommpay_flush_runtime_errors', 'woocommerce_ecommpay_ajax_flush_runtime_errors');
function check_before_ecommpay_plugin_update() {
	add_action( 'current_screen', function ( $screen ) {
		// Minimum requirements
		$required_wc_version  = '8.2';
		$required_php_version = '7.4';
		$required_wp_version  = '6.2';
		// Check if we're on the Plugins page
		if ( $screen && $screen->id !== 'plugins' ) {
			return;
		}

		// Check if WooCommerce is installed and active
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		// Check WordPress, PHP, and WooCommerce versions
		if ( version_compare( get_bloginfo( 'version' ), $required_wp_version, '<' )
		     || version_compare( PHP_VERSION, $required_php_version, '<' )
		     || version_compare( WC_VERSION, $required_wc_version, '<' ) ) {
			add_action( 'admin_notices', function () use ( $required_wp_version, $required_php_version, $required_wc_version ) {
				$ecommpay_domain = 'woo-ecommpay';
				$woo_website     = 'https://woocommerce.com/document/update-php-wordpress/';
				echo '<div class="notice notice-error"><p>';
				echo sprintf(
					__( 'Before updating the Ecommpay Payments plugin, please ensure that your WordPress version '
					    . 'is at least %1$s, your PHP version is at least %2$s, and your WooCommerce version is at least %3$s to avoid compatibility issues. '
					    . 'More details: <a href="%4$s" target="_blank">Click here</a>', $ecommpay_domain ),
					$required_wp_version, $required_php_version, $required_wc_version, $woo_website
				);
				echo '</p></div>';
			} );
		}
	} );
}

add_action( 'admin_menu', 'check_before_ecommpay_plugin_update' );