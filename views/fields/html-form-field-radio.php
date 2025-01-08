<?php
/**
 * @var string $id
 * @var string $type
 * @var string $title
 * @var string $tooltip
 * @var string $css
 * @var string $option_value
 * @var array $options
 * @var string $class
 * @var array $custom_attributes
 * @var string $description
 */

?>
<tr>
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $title ); ?><?php if ( $tooltip !== '' ): ?><?php echo wc_help_tip( $tooltip ); ?><?php endif; ?>
		</label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $type ) ); ?>">
		<fieldset>
			<?php if ( $description !== '' ): ?>
				<p style="margin-top:0">
					<?php echo wp_kses_post( $description ); ?>
				</p>
			<?php endif; ?>
			<ul>
				<?php foreach ( $options as $key => $val ): ?>
					<li>
						<label><input name="<?php echo esc_attr( $id ); ?>"
									  value="<?php echo esc_attr( $key ); ?>"
									  type="radio"
									  style="<?php echo esc_attr( $css ); ?>"
									  class="<?php echo esc_attr( $class ); ?>"
								<?php echo ecp_custom_attributes( $custom_attributes ); ?>
								<?php checked( $key, $option_value ); ?>
							/> <?php echo esc_html( $val ); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	</td>
</tr>
