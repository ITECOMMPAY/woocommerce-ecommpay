<?php
/**
 * Admin View: Notice - Upgrading
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div id="woocommerce-upgrade-notice" class="updated woocommerce-message wc-connect">
    <h3><strong>
            <?php esc_html_e('WooCommerce QuickPay - Data Update', 'woo-ecommpay'); ?>
        </strong></h3>
    <p>
        <?php esc_html_e('The upgrader is now running. This might take a while. The notice will disappear once the upgrade is complete.', 'woo-ecommpay'); ?>
    </p>
</div>