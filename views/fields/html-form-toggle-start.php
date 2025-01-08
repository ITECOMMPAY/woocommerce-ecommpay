<?php
/**
 * @var string $title
 * @var string $description
 * @var string $id
 */
?>

<?php if ( ! empty ( $title ) ): ?>
	<div class="ecp-toggle">
        <span id="<?php echo esc_attr( sanitize_title( $id ) ); ?>" class="ecp-toggle-switcher">
            <?php echo esc_html( $title ); ?>
        </span>
	</div>
<?php endif; ?>

<div id="<?php echo esc_attr( sanitize_title( $id ) ); ?>-toggle" class="hidden">

<?php
if ( ! empty ( $id ) ) {
	do_action( 'woocommerce_settings_' . sanitize_title( $id ) );
}
