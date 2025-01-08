<?php

defined( 'ABSPATH' ) || exit;


use common\includes\EcpGatewayOrder;
use common\includes\filters\EcpWCFilterList;
use common\includes\EcpGatewaySubscription;

/**
 * Checks if a subscription is up for renewal.
 * Ensures backwards compatibility.
 *
 * @param EcpGatewayOrder $order [description]
 *
 * @return bool
 */
function ecp_subscription_is_renewal( EcpGatewayOrder $order ): bool {
	if ( function_exists( 'wcs_order_contains_renewal' ) ) {
		return wcs_order_contains_renewal( $order );
	}

	return false;
}

/**
 * Checks if a subscription is resubscribed.
 *
 * @param EcpGatewayOrder $order [description]
 *
 * @return bool
 * @since 2.1.0
 */
function ecp_subscription_is_resubscribe( EcpGatewayOrder $order ): bool {
	if ( function_exists( 'wcs_order_contains_resubscribe' ) ) {
		return wcs_order_contains_resubscribe( $order );
	}

	return false;
}

/**
 * Checks if Woocommerce Subscriptions is enabled or not
 * @return bool
 */
function ecp_subscription_is_active(): bool {
	return class_exists( 'WC_Subscriptions' ) && WC_Subscriptions::$name = 'subscription';
}

/**
 * Convenience wrapper for wcs_get_subscriptions_for_renewal_order
 *
 * @param $order
 * @param bool $single - to return a single item or not
 *
 * @return EcpGatewaySubscription|EcpGatewaySubscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_renewal_order( $order, bool $single = false ) {
	if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
		add_filter(
			EcpWCFilterList::WOOCOMMERCE_ORDER_CLASS,
			[ ecommpay(), 'type_wrapper' ],
			101,
			2
		);

		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

		remove_filter(
			EcpWCFilterList::WOOCOMMERCE_ORDER_CLASS,
			[ ecommpay(), 'type_wrapper' ],
			101
		);
		if ( $single ) {
			return new EcpGatewaySubscription( end( $subscriptions )->get_id() );
		} else {
			return array_map(
				function ( $subscription ) {
					return new EcpGatewaySubscription( $subscription->get_id() );
				},
				$subscriptions
			);
		}
	}

	return [];
}

/**
 * Convenience wrapper for wcs_get_subscriptions_for_renewal_order
 *
 * @param $order
 * @param bool $single - to return a single item or not
 *
 * @return false|WC_Subscription|WC_Subscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_resubscribe_order( $order, bool $single = false ) {
	if ( function_exists( 'wcs_get_subscriptions_for_resubscribe_order' ) ) {
		add_filter(
			EcpWCFilterList::WOOCOMMERCE_ORDER_CLASS,
			[ ecommpay(), 'type_wrapper' ],
			101,
			2
		);

		$subscriptions = wcs_get_subscriptions_for_resubscribe_order( $order );

		remove_filter(
			EcpWCFilterList::WOOCOMMERCE_ORDER_CLASS,
			[ ecommpay(), 'type_wrapper' ],
			101
		);

		return $single ? end( $subscriptions ) : $subscriptions;
	}

	return [];
}

function ecp_get_subscription_status_name( $status ) {
	if ( ! function_exists( 'wcs_get_subscription_status_name' ) ) {
		return 'Unknown';
	}

	return wcs_get_subscription_status_name( $status );
}

/**
 * Convenience wrapper for wcs_get_subscriptions_for_order
 *
 * @param $order
 *
 * @return WC_Subscription[]
 * @noinspection PhpUndefinedClassInspection
 */
function ecp_get_subscriptions_for_order( $order ): array {
	if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
		return wcs_get_subscriptions_for_order( $order );
	}

	return [];
}

/**
 * Check if a given object is a WC_Subscription (or child class of WC_Subscription), or if a given ID
 * belongs to a post with the subscription post type ('shop_subscription')
 *
 * @param $subscription
 *
 * @return bool
 */
function ecp_is_subscription( $subscription ): bool {
	if ( function_exists( 'wcs_is_subscription' ) ) {
		return wcs_is_subscription( $subscription );
	}

	return false;
}
