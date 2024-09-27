<?php
/**
 * Template for column ECOMMPAY Payment.
 *
 * @var string $payment_status Status of payment
 */
?>
<mark
    class="ecp-payment-status status-<?php echo esc_html(Ecp_Gateway_Payment_Status::get_status_code($payment_status)); ?>">
    <span>
        <?php echo esc_html(Ecp_Gateway_Payment_Status::get_status_name($payment_status)); ?>
    </span>
</mark>
