<?php
/**
 * @var string $title
 * @var string $tooltip
 * @var string $css
 * @var string $class
 * @var string $description
 * @var array $args
 */

?>
<tr class="single_select_page">
    <th scope="row" class="titledesc">
        <label>
            <?php echo esc_html($title); ?>
            <?php if ($tooltip !== ''): ?>
                <?php echo wc_help_tip($tooltip); ?>
            <?php endif; ?>
    </th>
    <td class="forminp">
        <?php echo str_replace(
            ' id=',
            ' data-placeholder="' . esc_attr__('Select a page&hellip;', 'woocommerce')
            . '" style="' . $css
            . '" class="' . $class
            . '" id=',
            wp_dropdown_pages($args)
        ); ?>
        &lrm;
        <?php if ($description !== ''): ?>
            <span class="description">
                <?php echo wp_kses_post($description); ?>
            </span>
        <?php endif; ?>
    </td>
</tr>