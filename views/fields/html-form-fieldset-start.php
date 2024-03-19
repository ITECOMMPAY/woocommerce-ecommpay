<?php
/**
 * @var string $title
 * @var string $description
 * @var string $id
 */
?>

<?php if (!empty ($title)): ?>
    <fieldset class="ecp-fieldset">
        <legend>
            <?php echo esc_html($title); ?>
        </legend>
    <?php endif; ?>

    <?php if (!empty ($description)): ?>
        <div id="<?php echo esc_attr(sanitize_title($id)); ?>-description">
            <?php echo wp_kses_post(wpautop(wptexturize($description))); ?>
        </div>
    <?php endif; ?>

    <table class="form-table">

        <?php
        if (!empty ($id)) {
            do_action('woocommerce_settings_' . sanitize_title($id));
        }