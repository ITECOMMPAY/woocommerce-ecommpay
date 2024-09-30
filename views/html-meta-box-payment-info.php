<?php
/**
 * Template for ECOMMPAY Payment meta box.
 *
 * @var Ecp_Gateway_Info_Payment $data
 * @var string $status
 * @var string $status_name
 * @var string $operation_type
 * @var int|int[] $operation_code
 * @var string|string[] $operation_message
 * @var string $payment_method
 * @var ?int $transaction_order_id
 * @var ?string $payment_id
 * @var ?string $logo
 * @var ?string $amount
 */

?>
<ul class="order_actions">
	<?php if ( ! empty ( $status ) ): ?>
        <li class="wide ecp-meta-box-header">
            <p class="ecp-full-width">
                <img class="ecp-pm-logo" src="<?php echo esc_attr($logo); ?>" alt="" title="" />
                <mark class="ecp-payment-status status-<?php echo esc_html($status); ?> right">
                    <span>
                        <?php echo esc_html(Ecp_Gateway_Payment_Status::get_status_name($status)); ?>
                    </span>
                </mark>
            </p>
        </li>
    <?php endif; ?>

    <li class="wide">
	    <?php if ( ! empty ( $payment_id ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Payment ID', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php echo esc_html($payment_id); ?>
                </small>
            </p>
        <?php endif; ?>

	    <?php if ( ! empty ( $payment_method ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Payment method', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php echo esc_html($payment_method); ?>
                </small>
            </p>
        <?php endif; ?>

	    <?php if ( ! empty ( $operation_type ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Operation type', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php echo esc_html($operation_type); ?>
                </small>
            </p>
        <?php endif; ?>

	    <?php if ( ! empty ( $operation_code ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Code', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php if (is_array($operation_code)): ?>
                        <?php foreach ($operation_code as $code): ?>
                            <a target="_blank" href="<?php echo esc_url_raw(ecp_error_code_link($code)); ?>">
                                <?php echo esc_html($code); ?>
                            </a>,
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a target="_blank" href="<?php echo esc_url_raw(ecp_error_code_link($operation_code)); ?>">
                            <?php echo esc_html($operation_code); ?>
                        </a>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

	    <?php if ( ! empty ( $operation_message ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Message', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php if (is_array($operation_message)): ?>
                        <?php echo implode('<br>', esc_html($operation_message)); ?>
                    <?php else: ?>
                        <?php echo esc_html($operation_message); ?>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

	    <?php if ( ! empty ( $transaction_order_id ) ): ?>
            <p class="ecp-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Transaction Order ID', 'woo-ecommpay'); ?>:
                    </strong>
                    <?php echo esc_html($transaction_order_id); ?>
                </small>
            </p>
        <?php endif; ?>
    </li>
    <li class="wide">
        <strong class="ecp-amount">
            <?php echo wp_kses_post($amount); ?>
        </strong>
        <button type="button" data-action="refresh" class="button refresh-info button-secondary" name="save"
            value="Refresh">Refresh</button>
    </li>
</ul>