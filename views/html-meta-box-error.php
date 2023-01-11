<?php
/**
 * Template for ECOMMPAY Payment meta box error message.
 */

?>
<ul class="order_actions">
    <li class="wide">
        <p class="ecp-full-width">
            <?php echo __('An error occurred. For more information check out the', 'woo-ecommpay'); ?>
            <strong><?php echo ecp_get_log()->get_domain(); ?></strong>
            <?php echo __('logs inside'); ?>
            <strong><?php echo __('WooCommerce -> System Status -> Logs'); ?></strong>.
        </p>
    </li>
    <li class="wide">
        <strong class="ecp-amount"></strong>
        <button type="button" data-action="refresh" class="button refresh-info button-secondary" name="save" value="Refresh">Refresh</button>
    </li>
</ul>
