<?php

if ( empty( $order ) ) {
	return;
}

$order = ecp_get_order( $order );

$refundUnavailableText = ecpL(
	"Refunds are unavailable for the order. Cancel the payment if you want to return the funds to the payer.",
	"Refund is unavailable text"
);

if ( $order->is_ecp() && $order->get_ecp_status() === Ecp_Gateway_Payment_Status::AWAITING_CAPTURE ) : ?>
    <button class="button button-primary <?= esc_attr( Ecp_Gateway_Module_Admin_UI::ACTION_BUTTON_CLASS ); ?>"
            data-ecp-action="capture"
            data-order-id="<?= esc_attr( $order->get_id() ); ?>">
		<?= ecpL( 'Capture', 'Capture payment from dashboard' ) . ' ' . $order->get_formatted_order_total(); ?>
    </button>
    <button class="button button-secondary <?= esc_attr( Ecp_Gateway_Module_Admin_UI::ACTION_BUTTON_CLASS ); ?>"
            data-ecp-action="cancel"
            data-order-id="<?= esc_attr( $order->get_id() ); ?>">
		<?= esc_html( ecpL( 'Cancel payment', 'Cancel payment from dashboard' ) ); ?>
    </button>
    <script type="text/javascript">
        jQuery(function ($) {
            const refundButtonSelector = '<?= esc_js( Ecp_Gateway_Module_Admin_UI::WP_REFUND_BUTTON_SELECTOR ); ?>';
            const actionButtonClass = '<?= esc_js( Ecp_Gateway_Module_Admin_UI::ACTION_BUTTON_CLASS ); ?>';

            // Refund button handler
            $(refundButtonSelector).on("click", function (e) {
                e.stopPropagation();
                alert('<?= esc_js( $refundUnavailableText ); ?>');
            });

            // Action button handler
            $('.' + actionButtonClass).on('click', function (e) {
                e.preventDefault();

                const orderId = $(this).data('order-id');
                const action = $(this).data('ecp-action');
                const confirmMessages = {
                    capture: 'Are you sure you wish to process this capture? This action cannot be undone.',
                    cancel: 'Are you sure you wish to process this cancel? This action cannot be undone.'
                };

                if (!confirm(confirmMessages[action])) return;

                $('.' + actionButtonClass).hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ecp_process_' + action + '_order',
                        order_id: orderId,
                    },
                    success: function (response) {
                        console.warn('Action ' + action + ' processed: ' + response.message);
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function (xhr) {
                        const errorMessage = 'Action ' + action + ' failed: ' + xhr.responseJSON?.data || 'Unknown error';
                        console.error(errorMessage);
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
<?php endif;
?>


