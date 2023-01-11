<?php
/**
 * @var string $id
 * @var string $type
 * @var string $title
 * @var string $tooltip
 * @var string $css
 * @var string $option_value
 * @var string $class
 * @var string $placeholder
 * @var array $custom_attributes
 * @var string $description
 */

?>
<tr>
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($title); ?><?php echo $tooltip; ?>
        </label>
    </th>
    <td class="forminp forminp-<?php echo esc_attr(sanitize_title($type)); ?>">
        <?php echo esc_html($description); ?>
        <textarea
                name="<?php echo esc_attr($id); ?>"
                id="<?php echo esc_attr($id); ?>"
                style="<?php echo esc_attr($css); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
            <?php echo implode(' ', $custom_attributes); ?>
        ><?php echo esc_textarea($option_value); ?></textarea>
    </td>
</tr>