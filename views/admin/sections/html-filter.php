<?php if ( ! empty( $statuses ) ): ?>
    <select id="ecp_payment_status" name="_payment_status">
        <option value="" style="color: #999;">
			<?= esc_html( ecpL( 'Ecommpay Payment Status', 'ecommpay-filter' ) ); ?>
        </option>
		<?php foreach ( $statuses as $key => $value ): ?>
            <option value="<?= esc_attr( $key ); ?>" <?= selected( $selected_value ?? '', $key, false ); ?>>
				<?= esc_html( $value ); ?>
            </option>
		<?php endforeach; ?>
    </select>
<?php endif; ?>
