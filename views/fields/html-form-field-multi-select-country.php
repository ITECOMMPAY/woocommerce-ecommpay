<?php
/**
 * @var string $id
 * @var string $title
 * @var string $tooltip
 * @var string $description
 * @var array $countries
 * @var array $selections
 */
?>
<tr>
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($title); ?><?php echo $tooltip; ?>
        </label>
    </th>
    <td class="forminp">
        <select multiple="multiple" name="<?php echo esc_attr($id); ?>[]" style="width:350px"
                data-placeholder="<?php esc_attr_e('Choose countries&hellip;', 'woocommerce'); ?>"
                aria-label="<?php esc_attr_e('Country', 'woocommerce'); ?>"
                class="wc-enhanced-select">
            <?php if (!empty($countries)): ?>
                <?php foreach ($countries as $key => $val): ?>
                    <option value="<?php echo esc_attr($key); ?>"
                            <?php echo wc_selected($key, $selections); ?>><?php esc_html($val); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select><br/>
        <?php echo $description; ?> <br/>
        <a class="select_all button"
           href="#"><?php esc_html_e('Select all', 'woocommerce'); ?></a>
        <a class="select_none button" href="#"><?php esc_html_e('Select none', 'woocommerce'); ?></a>
    </td>
</tr>