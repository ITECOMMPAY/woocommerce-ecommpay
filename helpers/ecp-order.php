<?php

/**
 * Returns the price with decimals. 1010 returns as 10.10.
 *
 * @param int $price
 * @param string $currency
 *
 * @return float
 */
function ecp_price_normalize($price, $currency)
{
    if (ecp_is_currency_using_decimals($currency)) {
        return number_format($price / 100, 2, wc_get_price_decimal_separator(), '');
    }

    return $price;
}

/**
 * @param int $price
 * @param string $currency
 *
 * @return float
 */
function ecp_price_multiplied_to_float($price, $currency)
{
    if (ecp_is_currency_using_decimals($currency)) {
        return round($price / 100, 2);
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
function ecp_price_custom_to_multiplied($price, $currency)
{
    $decimal_separator = get_option('woocommerce_price_decimal_sep');
    $thousand_separator = get_option('woocommerce_price_thousand_sep');

    $price = str_replace([$thousand_separator, $decimal_separator], ['', '.'], $price);

    return ecp_price_multiply($price, $currency);
}


/**
 * Returns the price with no decimals. 10.10 returns as 1010.
 *
 * @param $price
 * @param ?string $currency
 *
 * @return int
 */
function ecp_price_multiply($price, $currency = null)
{
    if ($currency && ecp_is_currency_using_decimals($currency)) {
        return (int) ($price * 100);
    }

    return $price;
}

/**
 * @param $currency
 *
 * @return bool
 */
function ecp_is_currency_using_decimals($currency)
{
    $non_decimal_currencies = [
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

    return !in_array(strtoupper($currency), $non_decimal_currencies, true);
}

/**
 * Returns the order's main callback url
 *
 * @param null $post_id
 * @return string
 */
function ecp_callback_url($post_id = null)
{
    $args = ['wc-api' => 'WC_Ecommpay'];

    if ($post_id !== null) {
        $args['order_post_id'] = $post_id;
    }

    $args = apply_filters('woocommerce_ecommpay_callback_args', $args, $post_id);

    return apply_filters('woocommerce_ecommpay_callback_url', add_query_arg($args, home_url('/')), $args, $post_id);
}

/**
 * Returns ECOMMPAY order.
 *
 * @param $the_order
 * @return ?Ecp_Gateway_Order
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
function ecp_get_order($the_order = false)
{
    add_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100,
        2
    );

    $order = wc_get_order($the_order);

    remove_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100
    );

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $order;
}

/**
 * Returns 1d3 order.
 *
 * @param array $params
 * @return Ecp_Gateway_Order[]
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
function ecp_get_orders(array $params)
{
    add_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100,
        2
    );

    $orders = wc_get_orders($params);

    remove_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100
    );

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $orders;
}

/**
 * Returns ECOMMPAY refund.
 *
 * @param $the_refund
 * @return ?Ecp_Gateway_Refund
 */
function ecp_get_refund($the_refund = false)
{
    add_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100,
        2
    );

    $refund = wc_get_order($the_refund);

    remove_filter(
        'woocommerce_order_class',
        [ecommpay(), 'type_wrapper'],
        100
    );

    return $refund instanceof Ecp_Gateway_Refund
        ? $refund
        : null;
}