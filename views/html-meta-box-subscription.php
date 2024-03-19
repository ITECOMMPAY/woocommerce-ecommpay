<?php
/**
 * Template for ECOMMPAY Payment meta box.
 *
 * @var string $status
 * @var string $logo
 * @var bool $is_test
 * @var int $recurring_id
 */
?>

<ul class="order_action">
    <?php if (isset ($status) && !empty ($status)): ?>
        <li class="wide ecp-meta-box-header">
            <p class="ecp-full-width">
                <img class="ecp-pm-logo" src="<?php echo esc_url_raw($logo); ?>" alt="" title="" />
                <mark class="order-status status-<?php echo esc_attr($status); ?> subscription-status right">
                    <span>
                        <?php if ($is_test): ?>
                            <?php esc_html_e('Test', 'woo-ecommpay'); ?>
                        <?php endif; ?>
                        <?php echo esc_html(ecp_get_subscription_status_name($status)); ?>
                    </span>
                </mark>
            </p>
        </li>
    <?php endif; ?>

    <li class="wide">
        <?php if ($is_test): ?>
            <p class="ecp-full-width is_test">
                <strong>
                    <?php esc_html_e('Test subscription', 'woo-ecommpay'); ?>
                </strong>
            </p>
        <?php endif; ?>
        <p class="ecp-full-width">
            <small>
                <strong>
                    <?php esc_html_e('Recurring ID', 'woo-ecommpay'); ?>:
                </strong>
                <?php echo esc_html($recurring_id); ?>
            </small>
        </p>
        <?php if (isset ($transaction_order_id) && !empty ($transaction_order_id)): ?>
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
</ul>