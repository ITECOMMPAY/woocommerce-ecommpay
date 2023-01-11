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
 * @var string $suffix
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
        <input
                name="<?php echo esc_attr($id); ?>"
                id="<?php echo esc_attr($id); ?>"
                type="<?php echo esc_attr($type); ?>"
                style="<?php echo esc_attr($css); ?>"
                value="<?php echo esc_attr($option_value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
            <?php echo implode(' ', $custom_attributes); ?>
        /><?php echo esc_html($suffix); ?><br/>
        <?php echo $description;  ?>
    </td>
</tr>