<?php

use common\includes\filters\EcpWCFilters;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'woocommerce_ecommpay_can_user_empty_logs' ) ) {
	/**
	 * @return mixed|void
	 */
	function woocommerce_ecommpay_can_user_empty_logs() {
		return apply_filters( EcpWCFilters::WOOCOMMERCE_ECOMMPAY_CAN_USER_EMPTY_LOGS, current_user_can( 'administrator' ) );
	}
}

if ( ! function_exists( 'woocommerce_ecommpay_can_user_flush_cache' ) ) {
	/**
	 * @return mixed|void
	 */
	function woocommerce_ecommpay_can_user_flush_cache() {
		return apply_filters( EcpWCFilters::WOOCOMMERCE_ECOMMPAY_CAN_USER_FLUSH_CACHE, current_user_can( 'administrator' ) );
	}
}

if ( ! function_exists( 'woocommerce_ecommpay_can_user_manage_payments' ) ) {
	/**
	 * @param string|null $action
	 *
	 * @return bool
	 */
	function woocommerce_ecommpay_can_user_manage_payments( string $action = null ): bool {
		$default_cap = current_user_can( 'manage_woocommerce' );

		$cap = apply_filters( EcpWCFilters::WOOCOMMERCE_ECOMMPAY_CAN_USER_MANAGE_PAYMENT, $default_cap );

		if ( ! empty ( $action ) ) {
			$cap = apply_filters( EcpWCFilters::WOOCOMMERCE_ECOMMPAY_CAN_USER_MANAGE_PAYMENT_PREFIX . $action, $default_cap );
		}

		return $cap;
	}
}
