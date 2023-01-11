<?php
/**
 * Template for ECOMMPAY Payment meta box.
 *
 * @var WC_Gateway_Ecommpay_Model_Transaction_Info $data
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
 * @var bool $is_test
 */

?>
<ul class="order_actions">
    <?php if (isset($status) && !empty($status)) : ?>
        <li class="wide ecp-meta-box-header">
            <p class="ecp-full-width">
                <img class="ecp-pm-logo" src="<?php echo $logo; ?>" alt="" title=""/>
                <mark class="ecp-payment-status status-<?php echo $status; ?> right">
                    <span>
                        <?php if ($is_test): ?>
                            <?php echo __('Test', 'woo-ecommpay'); ?>
                        <?php endif; ?>
                        <?php echo Ecp_Gateway_Payment_Status::get_status_name($status); ?>
                    </span>
                </mark>
            </p>
        </li>
    <?php endif; ?>

    <li class="wide">
        <?php if ($is_test) : ?>
            <p class="ecp-full-width is_test">
                <strong><?php echo __('Test payment', 'woo-ecommpay'); ?></strong>
            </p>
        <?php endif; ?>
        <?php if (isset($payment_id) && !empty($payment_id)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Payment ID', 'woo-ecommpay'); ?>:</strong>
                    <?php echo $payment_id; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset($payment_method) && !empty($payment_method)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Payment method', 'woo-ecommpay'); ?>:</strong>
                    <?php echo $payment_method; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset($operation_type) && !empty($operation_type)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Operation type', 'woo-ecommpay'); ?>:</strong>
                    <?php echo $operation_type; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset($operation_code) && !empty($operation_code)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Code', 'woo-ecommpay'); ?>:</strong>
                    <?php if (is_array($operation_code)) : ?>
                        <?php foreach ($operation_code as $code) : ?>
                            <a target="_blank" href="https://developers.ecommpay.com/en/en_Gate__Unified_Codes.html?hl=<?php echo $code; ?>">
                                <?php echo $code; ?>
                            </a>,
                        <?php endforeach;?>
                    <?php else: ?>
                        <a target="_blank" href="https://developers.ecommpay.com/en/en_Gate__Unified_Codes.html?hl=<?php echo $operation_code; ?>">
                            <?php echo $operation_code; ?>
                        </a>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset($operation_message) && !empty($operation_message)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Message', 'woo-ecommpay'); ?>:</strong>
                    <?php if (is_array($operation_message)) : ?>
                        <?php echo implode('<br>', $operation_message); ?>
                    <?php else: ?>
                        <?php echo $operation_message; ?>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset($transaction_order_id) && !empty($transaction_order_id)) : ?>
            <p class="ecp-full-width">
                <small>
                    <strong><?php echo __('Transaction Order ID', 'woo-ecommpay'); ?>:</strong>
                    <?php echo $transaction_order_id; ?>
                </small>
            </p>
        <?php endif; ?>
    </li>
    <li class="wide">
        <strong class="ecp-amount"><?php echo $amount; ?></strong>
        <button type="button" data-action="refresh" class="button refresh-info button-secondary" name="save" value="Refresh">Refresh</button>
    </li>
</ul>
