<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

const NON_DECIMAL_CURRENCIES = [
	'BIF',
	'CLP',
	'DJF',
	'GNF',
	'ISK',
	'JPY',
	'KMF',
	'KRW',
	'PYG',
	'RWF',
	'UGX',
	'UYI',
	'VND',
	'VUV',
	'XAF',
	'XOF',
	'XPF',
];

function ecp_HPOS_enabled() {
	if ( ! class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
		return false;
	}

	return Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

function ecp_HPOS_sync_enabled() {
	if ( ! function_exists( 'wc_get_container' ) ) {
		return false;
	}

	if ( ! class_exists( 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer' ) ) {
		return false;
	}

	$data_synchronizer = wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class );

	return $data_synchronizer->data_sync_is_enabled();
}

/**
 * Returns the price with decimals. 1010 returns as 10.10.
 *
 * @param int $price
 * @param string $currency
 *
 * @return float
 */
function ecp_price_normalize( int $price, string $currency ) {
	if ( ecp_is_currency_using_decimals( $currency ) ) {
		return number_format( $price / 100, 2, wc_get_price_decimal_separator(), '' );
	}

	return $price;
}

/**
 * @param int $price
 * @param string $currency
 *
 * @return float
 */
function ecp_price_multiplied_to_float( int $price, string $currency ): float {
	if ( ecp_is_currency_using_decimals( $currency ) ) {
		return round( $price / 100, 2 );
	}

	return (float) $price;
}

/**
 * Multiplies a custom formatted price based on the WooCommerce decimal- and a thousand separators
 *
 * @param $price
 * @param $currency
 *
 * @return int
 */
function ecp_price_custom_to_multiplied( $price, $currency ): int {
	$decimal_separator  = get_option( 'woocommerce_price_decimal_sep' );
	$thousand_separator = get_option( 'woocommerce_price_thousand_sep' );

	$price = str_replace( [ $thousand_separator, $decimal_separator ], [ '', '.' ], $price );

	return ecp_price_multiply( $price, $currency );
}


/**
 * Returns the price with no decimals. 10.10 returns as 1010.
 *
 * @param $price
 * @param ?string $currency
 *
 * @return int
 */
function ecp_price_multiply( $price, string $currency = null ): int {
	if ( $currency && ecp_is_currency_using_decimals( $currency ) ) {
		return (int) ( round( $price * 100 ) );
	}

	return (int) ( round( $price ) );
}

/**
 * @param $currency
 *
 * @return bool
 */
function ecp_is_currency_using_decimals( $currency ): bool {
	return ! in_array( strtoupper( $currency ), NON_DECIMAL_CURRENCIES, true );
}

/**
 * Returns the order's main callback url
 *
 * @param null $post_id
 *
 * @return string
 */
function ecp_callback_url( $post_id = null ): string {
	$args = [ 'wc-api' => 'WC_Ecommpay' ];

	if ( $post_id !== null ) {
		$args['order_post_id'] = $post_id;
	}

	$args = apply_filters( 'woocommerce_ecommpay_callback_args', $args, $post_id );

	return apply_filters( 'woocommerce_ecommpay_callback_url', add_query_arg( $args, home_url( '/' ) ), $args, $post_id );
}

/**
 * Returns ECOMMPAY order.
 *
 * @param $order
 *
 * @return Ecp_Gateway_Order|Ecp_Gateway_Refund|Ecp_Gateway_Subscription
 */
function ecp_get_order( $order ) {
	$types    = [ 'shop_order', 'shop_order_refund', 'shop_subscription' ];
	$is_order = OrderUtil::is_order( $order, $types );

	if ( ! $is_order ) {
		return null;
	}

	$order_type = OrderUtil::get_order_type( $order );

	switch ( $order_type ) {
		case 'shop_order':
			return new Ecp_Gateway_Order( $order );
		case 'shop_order_refund':
			return new Ecp_Gateway_Refund( $order );
		case 'shop_subscription':
			return new Ecp_Gateway_Subscription( $order );
	}

	return null;
}

/**
 * Returns order type
 *
 * @param $order int|WP_Post|WC_Order
 *
 * @return String|null
 */
function ecp_get_order_type( $order ): ?string {
	return OrderUtil::get_order_type( $order );
}

/**
 * Returns ECOMMPAY orders.
 *
 * @param array $params
 *
 * @return Ecp_Gateway_Order[]
 * @throws Exception
 */
function ecp_get_orders( array $params ): array {
	$query      = new WC_Order_Query(
		array_merge(
			[ 'return' => 'ids' ],
			$params
		)
	);
	$order_ids  = $query->get_orders();
	$ecp_orders = [];
	foreach ( $order_ids as $order_id ) {
		$ecp_orders[] = ecp_get_order( $order_id );
	}

	return $ecp_orders;
}

/**
 * Returns ECOMMPAY refund.
 *
 * @param WC_Order_Refund $refund
 *
 * @return ?Ecp_Gateway_Refund
 */
function ecp_get_refund( $refund = null ): ?Ecp_Gateway_Refund {
	if ( $refund === null ) {
		return null;
	}

	return new Ecp_Gateway_Refund( $refund->get_id() );
}
