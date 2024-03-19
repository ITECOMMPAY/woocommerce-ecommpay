<?php
/**
 * Template for log-panel on Admin Settings panel.
 */

defined('ABSPATH') || exit;
?>
<p class="right">
    <a id="wc-ecp_wiki" class="wc-ecp-debug-button button button-primary"
        href="<?php echo esc_url_raw(ecp_doc_link()); ?>" target="_blank">
        <?php esc_html_e('Got problems? Go get help.', 'woo-ecommpay'); ?>
    </a>
    <a id="wc-ecp_logs" class="wc-ecp-debug-button button" href="<?php echo esc_url_raw(ecp_admin_link()); ?>">
        <?php esc_html_e('View debug logs', 'woo-ecommpay'); ?>
    </a>

    <?php if (woocommerce_ecommpay_can_user_empty_logs()): ?>
        <button role="button" id="wc-ecp_logs_clear" class="wc-ecp-debug-button button">
            <?php esc_html_e('Empty debug logs', 'woo-ecommpay'); ?>
        </button>
    <?php endif; ?>
    <?php if (woocommerce_ecommpay_can_user_flush_cache()): ?>
        <button role="button" id="wc-ecp_flush_cache" class="wc-ecp-debug-button button">
            <?php esc_html_e('Empty transaction cache', 'woo-ecommpay'); ?>
        </button>
    <?php endif; ?>
</p>