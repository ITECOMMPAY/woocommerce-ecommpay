<?php
/**
 * Template for dropdown country list with single available value select.
 *
 * @var string $id Field identifier
 * @var string $title Field label
 * @var string $tooltip Field tooltip
 * @var string $css Additional CSS
 * @var string $description Field description
 * @var string $country Selected country
 * @var string $state Selected region/state
 */
?>
<tr>
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($title); ?><?php echo $tooltip; ?>
        </label>
    </th>
    <td class="forminp">
        <select name="<?php echo esc_attr($id); ?>"
                style="<?php echo esc_attr($css); ?>"
                data-placeholder="<?php esc_attr_e('Choose a country&hellip;', 'woocommerce'); ?>"
                aria-label="<?php esc_attr_e('Country', 'woocommerce'); ?>"
                class="wc-enhanced-select">
            <?php WC()->countries->country_dropdown_options($country, $state); ?>
        </select> <?php echo $description; ?>
    </td>
</tr>