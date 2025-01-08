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
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $title ); ?>
			<?php if ( $tooltip !== '' ): ?>
				<?php echo wc_help_tip( $tooltip ); ?>
			<?php endif; ?>
		</label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $type ) ); ?>">
		<input name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>"
			   type="<?php echo esc_attr( $type ); ?>" style="<?php echo esc_attr( $css ); ?>"
			   value="<?php echo esc_attr( $option_value ); ?>" class="<?php echo esc_attr( $class ); ?>"
			   placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo ecp_custom_attributes( $custom_attributes ); ?> />
		<?php echo esc_html( $suffix ); ?><br />
		&lrm;
		<?php if ( $description !== '' ): ?>
			<span class="description">
                <?php echo wp_kses_post( $description ); ?>
            </span>
		<?php endif; ?>

	</td>
</tr>
