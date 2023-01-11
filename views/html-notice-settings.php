<?php
/**
 * @var string[] $errors
 */

defined('ABSPATH') || exit;
?>
<div class="notice notice-error">
    <h2><?php echo __("WooCommerce ECOMMPAY", 'woo-ecommpay'); ?></h2>
    <p><?php echo __('You have missing or incorrect settings.', 'woo-ecommpay'); ?>
        <?php echo sprintf(__('Go to the <a href="%s">settings page</a>', 'woo-ecommpay'), ecp_settings_page_url()); ?>
    </p>
    <ul>
    <?php foreach ($errors as $error): ?>
        <li><strong><?php echo $error; ?></strong> <?php echo __('is mandatory.', 'woo-ecommpay'); ?></li>
    <?php endforeach; ?>
    </ul>
</div>