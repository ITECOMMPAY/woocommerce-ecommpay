<?php
/**
 * Template for column ECOMMPAY Payment.
 *
 * @var string $payment_status Status of payment
 */

use common\helpers\EcpGatewayPaymentStatus;

?>
<mark
	class="ecp-payment-status status-<?php echo esc_html( EcpGatewayPaymentStatus::get_status_code( $payment_status ) ); ?>">
    <span>
        <?php echo esc_html( EcpGatewayPaymentStatus::get_status_name( $payment_status ) ); ?>
    </span>
</mark>
