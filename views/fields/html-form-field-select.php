<?php
/**
 * @var string $id
 * @var string $type
 * @var string $title
 * @var string $tooltip
 * @var string $css
 * @var string|array $option_value
 * @var array $options
 * @var string $class
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
        <select
                name="<?php echo esc_attr($id); ?><?php echo ('multiselect' === $type) ? '[]' : ''; ?>"
                id="<?php echo esc_attr($id); ?>"
                style="<?php echo esc_attr($css); ?>"
                class="<?php echo esc_attr($class); ?>"
            <?php echo implode(' ', $custom_attributes); ?>
            <?php echo 'multiselect' === $type ? 'multiple="multiple"' : ''; ?>
        >
            <?php foreach ($options as $key => $val): ?>
                <option value="<?php echo esc_attr($key); ?>"
                    <?php if (is_array($option_value)): ?>
                        <?php echo selected(in_array((string)$key, $option_value, true)); ?>
                    <?php else: ?>
                        <?php echo selected($option_value, (string)$key); ?>
                    <?php endif; ?>
                ><?php echo esc_html($val); ?></option>
            <?php endforeach; ?>
        </select><br/>
        <?php echo $description; ?>
    </td>
</tr>