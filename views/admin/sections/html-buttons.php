<?php

use common\helpers\EcpGatewayPaymentStatus;
use common\modules\EcpModuleAdminUI;

if ( empty( $order ) ) {
	return;
}

$order = ecp_get_order( $order );

if ( $order->is_ecp() && $order->get_ecp_status() === EcpGatewayPaymentStatus::AWAITING_CAPTURE ) :
	$refundUnavailableText = ecpL(
		"Refunds are unavailable for the order. Cancel the payment if you want to return the funds to the payer.",
		"Refund is unavailable text"
	);
	?>

	<button class="button button-primary <?= esc_attr( EcpModuleAdminUI::ACTION_BUTTON_CLASS ); ?>"
			data-ecp-action="<?= esc_attr( EcpModuleAdminUI::ACTION_CAPTURE ); ?>"
			data-order-id="<?= esc_attr( $order->get_id() ); ?>">
		<?= ecpL( 'Capture', 'Capture payment from dashboard' ) . ' ' . $order->get_formatted_order_total(); ?>
	</button>
	<button class="button button-secondary <?= esc_attr( EcpModuleAdminUI::ACTION_BUTTON_CLASS ); ?>"
			data-ecp-action="<?= esc_attr( EcpModuleAdminUI::ACTION_CANCEL ); ?>"
			data-order-id="<?= esc_attr( $order->get_id() ); ?>">
		<?= esc_html( ecpL( 'Cancel payment', 'Cancel payment from dashboard' ) ); ?>
	</button>
	<script type="text/javascript">
		jQuery(function($) {
			const refundButtonSelector = '<?= esc_js( EcpModuleAdminUI::WP_REFUND_BUTTON_SELECTOR ); ?>'
			const actionButtonClass = '<?= esc_js( EcpModuleAdminUI::ACTION_BUTTON_CLASS ); ?>'
			const ACTION_CAPTURE = '<?= esc_js( EcpModuleAdminUI::ACTION_CAPTURE ); ?>'
			const ACTION_CANCEL = '<?= esc_js( EcpModuleAdminUI::ACTION_CANCEL ); ?>'

			// Refund button handler
			$(refundButtonSelector).on('click', function(e) {
				e.stopPropagation()
				alert('<?= esc_js( $refundUnavailableText ); ?>')
			})

			// Action button handler
			$('.' + actionButtonClass).on('click', function(e) {
				e.preventDefault()

				const orderId = $(this).data('order-id')
				const action = $(this).data('ecp-action')
				const confirmMessages = {
					[ACTION_CAPTURE]: 'Are you sure you wish to process this capture? This action cannot be undone.',
					[ACTION_CANCEL]: 'Are you sure you wish to process this cancel? This action cannot be undone.',
				}

				if (!confirm(confirmMessages[action])) return

				$('.' + actionButtonClass).hide()

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'ecp_process_' + action + '_order',
						order_id: orderId,
					},
					success: function(response) {
						console.warn('Action ' + action + ' processed: ' + response.message)
						setTimeout(() => location.reload(), 1000)
					},
					error: function(xhr) {
						const errorMessage = 'Action ' + action + ' failed: ' + xhr.responseJSON?.data || 'Unknown error'
						console.error(errorMessage)
						alert(errorMessage)
					},
				})
			})
		})
	</script>
<?php endif; ?>
