<?php
/**
 * @var string $id
 */

if (!empty ($value['id'])) {
    do_action('woocommerce_settings_' . sanitize_title($id) . '_end');
}
?>
</div>
<?php
if (!empty ($value['id'])) {
    do_action('woocommerce_settings_' . sanitize_title($id) . '_after');
}