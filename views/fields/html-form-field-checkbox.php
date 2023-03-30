<?php
/**
 * @var string $id
 * @var string $type
 * @var string $title
 * @var string $tooltip
 * @var string $option_value
 * @var string $class
 * @var array $custom_attributes
 * @var string $description
 * @var string $checkboxgroup
 * @var array $visibility_class
 */
?>

<?php if (!isset($checkboxgroup) || 'start' === $checkboxgroup): ?>
    <tr class="<?php echo esc_attr(implode(' ', $visibility_class)); ?>">
        <th scope="row" class="titledesc"><?php echo esc_html($title); ?></th>
        <td class="forminp forminp-checkbox">
            <fieldset>
<?php else: ?>
    <fieldset class="<?php echo esc_attr(implode(' ', $visibility_class)); ?>">
<?php endif; ?>

<?php if (!empty($title)): ?>
    <legend class="screen-reader-text"><span><?php echo esc_html($title); ?></span></legend>
<?php endif; ?>

    <label for="<?php echo esc_attr($id); ?>">
        <input name="<?php echo esc_attr($id); ?>"
               id="<?php echo esc_attr($id); ?>"
               type="<?php echo esc_attr($type); ?>"
               class="<?php echo esc_attr($class); ?>"
               value="1"
            <?php checked($option_value, 'yes'); ?>
            <?php echo ecp_custom_attributes($custom_attributes); ?>
        /> <?php if ($description !== ''): ?><?php echo wp_kses_post($description); ?><?php endif; ?>
    </label>
    <?php if ($tooltip !== ''): ?>
        <p class="description">
            <?php echo wc_sanitize_tooltip($tooltip); ?>
        </p>
    <?php endif; ?>

<?php if (isset($checkboxgroup) && 'end' !== $checkboxgroup): ?>
    </fieldset>
<?php else: ?>
            </fieldset>
        </td>
    </tr>
<?php endif; ?>