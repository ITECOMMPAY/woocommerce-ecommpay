<?php
/**
 * @var string $id
 * @var string $type
 * @var string $title
 * @var string $tooltip
 * @var string $option_value
 * @var string $css
 * @var string $class
 * @var string $placeholder
 * @var array $custom_attributes
 * @var string $description
 */

?>
<tr>
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($title); ?><?php if ($tooltip !== ''): ?><?php echo wc_help_tip($tooltip); ?><?php endif; ?>
        </label>
    </th>
    <td class="forminp forminp-<?php echo esc_attr(sanitize_title($type)); ?>">&lrm;
        <span class="colorpickpreview" style="background: <?php echo esc_attr($option_value); ?>">&nbsp;</span>
        <input
                name="<?php echo esc_attr($id); ?>"
                id="<?php echo esc_attr($id); ?>"
                type="text"
                dir="ltr"
                style="<?php echo esc_attr($css); ?>"
                value="<?php echo esc_attr($option_value); ?>"
                class="<?php echo esc_attr($class); ?>colorpick"
                placeholder="<?php echo esc_attr($placeholder); ?>"
            <?php echo ecp_custom_attributes($custom_attributes); ?>
        />
        &lrm;<?php if ($description !== ''): ?>
            <span class="description">
                <?php echo wp_kses_post($description); ?>
            </span>
        <?php endif; ?>
        <div id="colorPickerDiv_<?php echo esc_attr($id); ?>"
             class="colorpickdiv"
             style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;">
        </div>
    </td>
</tr>