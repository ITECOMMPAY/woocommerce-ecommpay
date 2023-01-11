<?php
/**
 * @var string $id
 * @var string $title
 * @var string $tooltip
 * @var array $option_value
 * @var string $class
 * @var string $placeholder
 * @var array $custom_attributes
 * @var string $description
 * @var array $periods
 */

?>
<tr>
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($title); ?><?php echo $tooltip; ?>
        </label>
    </th>
    <td class="forminp">
        <input
                name="<?php echo esc_attr($id); ?>[number]"
                id="<?php echo esc_attr($id); ?>"
                type="number"
                style="width: 80px;"
                value="<?php echo esc_attr($option_value['number']); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                step="1"
                min="1"
            <?php echo implode(' ', $custom_attributes); ?>
        />&nbsp;
        <select name="<?php echo esc_attr($id); ?>[unit]" style="width: auto;">
            <?php foreach ($periods as $period => $label): ?>
                <option value="<?php echo esc_attr($period); ?>"
                        <?php echo selected($option_value['unit'], $period, false) ?>
                ><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select> <?php echo $description; ?>
    </td>
</tr>