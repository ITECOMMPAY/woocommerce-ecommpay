<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div id="woocommerce-upgrade-notice" class="updated woocommerce-message wc-connect">
    <h3><strong><?php esc_html_e( 'ECOMMPAY Payments - Data Update', 'woo-ecommpay'); ?></strong></h3>
    <p><?php esc_html_e( 'To ensure you get the best experience at all times, we need to update your store\'s database to the latest version.', 'woo-ecommpay'); ?></p>
    <p class="submit"><a href="#" class="woocommerce-ecommpay-update-now button-primary"><?php esc_html_e( 'Run the updater', 'woo-ecommpay' ); ?></a></p>
</div>
<script type="text/javascript">
    (function ($) {
        $( '.woocommerce-ecommpay-update-now' ).click( 'click', function() {
            var confirm = window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woo-ecommpay' ) ); ?>' ); // jshint ignore:line

            if (confirm) {
                var message = $('#woocommerce-upgrade-notice');

                message.find('p').fadeOut();

                $.post('<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>', {
                    action: 'ecommpay_run_data_upgrader',
                    nonce: '<?php echo esc_js(Ecp_Gateway_Install::get_instance()->create_run_upgrade_nonce()); ?>'
                }, function () {
                    message.append($('<p></p>').text("<?php esc_html_e('The upgrader is now running. This might take a while. The notice will disappear once the upgrade is complete.', 'woo-ecommpay'); ?>"));
                });
            }
        });
    })(jQuery);
</script>
