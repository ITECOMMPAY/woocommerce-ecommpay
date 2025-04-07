<?php

namespace common\includes;

use common\EcpCore;

trait EcpGatewayOrderExtension {
	/**
	 * Sets payment identifier.
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	public function set_payment_id( string $value ): void {
		$current_payment_id = $this->get_payment_id();
		if ( $value != $current_payment_id ) {
			if ( is_a( $this, EcpGatewayOrder::class ) ) {
				$this->add_order_note( __( 'New payment id is ' . $value, 'woocommerce' ) );
			}
			$this->set_ecp_meta( '_payment_id', $value, false );
		}
	}

	/**
	 * Returns the payment identifier.
	 *
	 * @return string
	 */
	public function get_payment_id(): string {
		$meta_data = $this->get_ecp_meta( '_payment_id', false );
		$meta_object = end( $meta_data );
		if ( is_object( $meta_object )) {
            return $meta_object->value;
        }
		return $meta_object;
	}

	/**
	 * Returns meta data by key.
	 *
	 * @param $key
	 * @param bool $single Return type, array if false
	 * @param string $context
	 *
	 * @return string|array|WC_Meta_Data[]
	 */
	public function get_ecp_meta( $key, bool $single = true, string $context = 'view' ) {
		$meta = $this->get_meta( $key, $single, $context );

		// For compatibility with older versions of ECOMMPAY plugin.
		if ( empty ( $meta ) ) {
			$meta = get_post_meta( $this->get_id(), $key, $single );
		}

		return $meta;
	}

	/**
	 * Sets meta data by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $unique
	 *
	 * @return void
	 */
	public function set_ecp_meta( string $key, $value, bool $unique = true ): void {
		$this->add_meta_data( $key, $value, $unique );
		$this->save_meta_data();
	}

	/**
	 * Returns payment status.
	 *
	 * @return string
	 */
	public function get_ecp_status(): string {
		return $this->get_ecp_meta( '_payment_status' );
	}

	/**
	 * Sets payment status.
	 *
	 * @param string $status
	 *
	 * @return void
	 */
	public function set_ecp_payment_status( string $status ): void {
		$this->set_ecp_meta( '_payment_status', $status );
	}

	/**
	 * Returns ECOMMPAY payment method.
	 *
	 * @return string
	 */
	public function get_payment_system(): string {
		return $this->get_ecp_meta( '_ecommpay_payment_method' );
	}

	/**
	 * Sets ECOMMPAY payment method.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function set_payment_system( ?string $name ): void {
		if ( $name ) {
			$this->set_ecp_meta( '_ecommpay_payment_method', $name );
		}
	}

	public function get_is_test(): bool {
		return (bool) $this->get_ecp_meta( '_ecommpay_payment_test' );
	}

	/**
	 * @return mixed|string
	 */
	public function get_ecp_transaction_id() {
		// Search for custom transaction meta to avoid transaction ID sometimes being empty on subscriptions in WC 3.0.
		$transaction_id = $this->get_ecp_meta( '_transaction_id' );

		if ( ! empty ( $transaction_id ) ) {
			return $transaction_id;
		}

		// Try getting transaction ID from parent object.
		$transaction_id = $this->get_prop( 'transaction_id' );

		if ( ! empty ( $transaction_id ) ) {
			return $transaction_id;
		}

		// Search for original transaction ID. The transaction might be temporarily removed by
		// subscriptions. Use this one instead (if available).
		$transaction_id = $this->get_ecp_meta( '_transaction_id_original' );

		if ( ! empty ( $transaction_id ) ) {
			return $transaction_id;
		}

		// Default search transaction ID.
		return $this->get_ecp_meta( 'transaction_id' );
	}

	/**
	 * @param string $context
	 * @param string|null $operation
	 *
	 * @return string
	 */
	public function get_transaction_order_id( string $context = 'view', string $operation = '' ): string {
		return $this->get_ecp_meta( '_ecommpay' . $this->add_op_code_prefix( $operation ) . '_request_id', true, $context );
	}

	/**
	 * Adds _ prefix to the operation code
	 *
	 * @param string $operation
	 *
	 * @return string
	 */
	private function add_op_code_prefix( string $operation = '' ): string {
		return ( ! empty( $operation ) ) ? '_' . $operation : '';
	}

	/**
	 * Set the transaction order ID on an order
	 *
	 * @param string $transaction_order_id
	 * @param string|null $operation
	 *
	 * @return void
	 */
	public function set_transaction_order_id( string $transaction_order_id, string $operation = '' ): void {
		$this->set_ecp_meta( '_ecommpay' . $this->add_op_code_prefix( $operation ) . '_request_id', $transaction_order_id );
	}

	/**
	 * @param string $context
	 * @param string $operation
	 *
	 * @return string
	 */
	public function get_operation_status( string $context = 'view', string $operation = '' ): string {
		return $this->get_ecp_meta( '_ecommpay_operation' . $this->add_op_code_prefix( $operation ) . '_status', true, $context );
	}

	/**
	 * @param string $status
	 * @param string $operation
	 *
	 * @return void
	 */
	public function set_operation_status( string $status, string $operation = '' ): void {
		$this->set_ecp_meta( '_ecommpay_operation' . $this->add_op_code_prefix( $operation ) . '_status', $status );
	}

	/**
	 * Increase the amount of payment attempts done through ECOMMPAY
	 *
	 * @return int
	 */
	public function increase_failed_ecommpay_payment_count(): int {
		$count = $this->get_failed_ecommpay_payment_count() + 1;
		$this->set_ecp_meta( self::META_FAILED_PAYMENT_COUNT, $count );

		return $count;
	}

	/**
	 * Increase the amount of payment attempts done
	 *
	 * @return int
	 */
	public function get_failed_ecommpay_payment_count(): int {
		$count = $this->get_ecp_meta( self::META_FAILED_PAYMENT_COUNT );

		if ( ! empty ( $count ) ) {
			return $count;
		}

		return 0;
	}

	/**
	 * Checks if the order is paid with the ECOMMPAY plugin.
	 *
	 * @return bool
	 */
	public function is_ecp(): bool {
		$pm = $this->get_ecp_meta( '_payment_method' );

		if ( array_key_exists( $pm, ecp_payment_methods() ) ) {
			return true;
		}

		return $pm === EcpCore::ECOMMPAY_PAYMENT_METHOD;
	}

	public function get_currency_uppercase(): string {
		return strtoupper( $this->get_currency() );
	}

	public function get_total_minor(): int {
		return ecp_price_multiply( $this->get_total(), $this->get_currency() );
	}
}
