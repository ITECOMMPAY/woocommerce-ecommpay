<?php

namespace common\helpers;

use common\includes\EcpGatewayOrder;
use WC_Order_Item;
use WC_Order_Item_Product;

class EcpReceiptDataExtractor {

	/**
	 * <h2>Returns receipt data by abstract order.</h2>
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function extract( EcpGatewayOrder $order ) {
		$total_tax   = abs( $order->get_total_tax() );
		$total_price = abs( $order->get_total() );

		return $total_tax > 0
			? array(
				// Item positions.
				'positions'        => self::get_positions( $order ),
				// Total tax amount per payment.
				'total_tax_amount' => ecp_price_multiply( $total_tax, $order->get_currency() ),
				'common_tax'       => $total_price !== $total_tax ? round( $total_tax / ( $total_price - $total_tax ), 2 ) : 0,
			)
			: array(
				// Item positions.
				'positions' => self::get_positions( $order ),
			);
	}

	/**
	 * <h2>Returns order positions for receipt.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for payment.</p>
	 *
	 * @return array
	 */
	private static function get_positions( EcpGatewayOrder $order ) {
		$positions = array();

		foreach ( $order->get_items() as $item ) {
			$positions[] = self::get_receipt_position( $item, $order->get_currency() );
		}

		return $positions;
	}

	/**
	 * <h2>Returns position for receipt.</h2>
	 *
	 * @param string $currency <p>Current currency.</p>
	 * @param WC_Order_Item $item <p>Order item - product, subscription etc.</p>
	 *
	 * @return array
	 */
	private static function get_receipt_position( WC_Order_Item $item, string $currency ): array {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			return array();
		}

		$quantity    = abs( $item->get_quantity() );
		$price       = abs( $item->get_total() );
		$description = esc_attr( $item->get_name() );

		$data = array(
			// Required. Amount of the positions.
			'amount' => ecp_price_multiply( $quantity > 0 ? $price / $quantity : $price, $currency ),
		);

		if ( $quantity > 0 ) {
			// Quantity of the goods or services. Multiple of: 0.000001.
			$data['quantity'] = $quantity;
		}

		if ( strlen( $description ) > 0 ) {
			// Goods or services description. >= 1 characters<= 255 characters.
			$data['description'] = self::limit_length( $description, 255 );
		}

		$total_tax = abs( $item->get_total_tax() );

		if ( $total_tax > 0 ) {
			// Tax percentage for the position. Multiple of: 0.01.
			$data['tax'] = $price !== 0 ? round( $total_tax / $price, 2 ) : 0;
			// Tax amount for the position.
			$data['tax_amount'] = ecp_price_multiply( $total_tax / $quantity, $currency );
		}

		return $data;
	}

	/**
	 * <h2>Crops and returns string.</h2>
	 *
	 * @param string $string <p>Original string.</p>
	 * @param integer $limit <p>Limit size in characters.</p>
	 *
	 * @return string <p>Cropped string.</p>
	 */
	private static function limit_length( string $string, int $limit = 127 ): string {
		$str_limit = $limit - 3;

		if ( function_exists( 'mb_strimwidth' ) ) {
			return mb_strlen( $string ) > $limit
				? mb_strimwidth( $string, 0, $str_limit ) . '...'
				: $string;
		}

		return strlen( $string ) > $limit
			? substr( $string, 0, $str_limit ) . '...'
			: $string;
	}
}
