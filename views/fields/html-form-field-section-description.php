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
<div style="font-size: 14px; padding: 20px 0 10px 0;">
	<?php echo esc_html( $title ); ?><?php if ( $tooltip !== '' ): ?><?php echo wc_help_tip( $tooltip ); ?><?php endif; ?>
</div>