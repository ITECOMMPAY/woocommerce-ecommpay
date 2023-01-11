<?php

class Ecp_Gateway_API_Protocol extends Ecp_Gateway_Registry
{
    /**
     * @inheritDoc
     * @return void
     */
    protected function init()
    {
        add_filter('ecp_append_shipping_data', [$this, 'filter_shipping_data'], 10, 2);
        add_filter('ecp_append_receipt_data', [$this, 'filter_receipt_data'], 10, 3);
        add_filter('ecp_append_cash_voucher_data', [$this, 'filter_cash_voucher_data'], 10, 2);
    }

    /**
     * <h2>Appends ECOMMPAY Payment Page Receipt data.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @param array $values <p>Base array for appending data</p>
     * @param bool $encode [optional] <p>Encode JSON-object as base64 string. By default: none.</p>
     * @return array Result of appending data as new array.
     */
    public function filter_receipt_data($order, $values, $encode = false)
    {
        $data = $this->receipt_data($order);

        apply_filters('ecp_payment_page_clean_parameters', $data);

        if (count($data) <= 0) {
            return $values;
        }

        $receipt['receipt_data'] = $encode
            ? base64_encode(json_encode($data))
            : $data;

        return array_merge($values, $receipt);
    }

    /**
     * <h2>Returns receipt data by abstract order.</h2>
     * @param WC_Order $order
     * @return array
     */
    public function receipt_data($order)
    {
        $totalTax = abs($order->get_total_tax());
        $totalPrice = abs($order->get_total());

        return $totalTax > 0
            ? [
                // Item positions.
                'positions' => $this->get_positions($order),
                // Total tax amount per payment.
                'total_tax_amount' => ecp_price_multiply($totalTax, $order->get_currency()),
                'common_tax' => round($totalTax / ($totalPrice - $totalTax), 2),
            ]
            : [
                // Item positions.
                'positions' => $this->get_positions($order)
            ];
    }

    /**
     * <h2>Appends shipping information.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Order object.</p>
     * @param array $values <p>Base array for appending data</p>
     * @return array Result of appending data as new array.
     */
    public function filter_shipping_data($order, $values)
    {
        if (!$order->needs_shipping_address()) {
            return $values;
        }

        $shipping_args = [
            'type' => $order->get_shipping_type(),
            //ToDo: Needs generate delivery time. Create helper for indicator.
            // 'delivery_time' => '01',
            'delivery_email' => $this->limit_length($order->get_billing_email(), 255),
            //ToDo: Needs generate address usage indicator
            // 'address_usage_indicator' => '01',
            //ToDo: Needs get address last change date!
            // 'address_usage' => '01-10-2019',
            'city' => $this->limit_length($order->get_shipping_city(), 50),
            'country' => $order->get_shipping_country(),
            'address' => $this->limit_length($order->get_shipping_address(), 150),
            'postal' => wc_format_postcode($order->get_shipping_postcode(), $order->get_shipping_country()),
            'region_code' => ecp_region_code($order->get_shipping_country(), $order->get_shipping_state()),
            'name_indicator' => $order->get_shipping_name_indicator(),
        ];

        apply_filters('ecp_payment_page_clean_parameters', $shipping_args);

        if (count($shipping_args) <= 0) {
            return $values;
        }

        $values['customer_shipping'] = base64_encode(json_encode(['customer' => ['shipping' => $shipping_args]]));
        return $values;
    }

    public function filter_cash_voucher_data($order, $values)
    {
        //ToDo: Need to implements.
        return $values;
    }

    /**
     * <h2>Returns order positions for receipt.</h2>
     *
     * @param WC_Order $order <p>Order for payment.</p>
     * @return array
     */
    private function get_positions($order)
    {
        $positions = [];

        foreach ($order->get_items() as $item) {
            $positions[] = $this->get_receipt_position($item, $order->get_currency());
        }

        return $positions;
    }

    /**
     * <h2>Returns position for receipt.</h2>
     *
     * @param string $currency <p>Current currency.</p>
     * @param WC_Order_Item $item <p>Order item - product, subscription etc.</p>
     * @return array
     */
    private function get_receipt_position($item, $currency)
    {
        //ToDo: Temporary remove. Need check VAT calculation.
//        $vat_rate = 0;
//
//        if (wc_tax_enabled()) {
//            $taxes = WC_Tax::get_rates($item->get_tax_class());
//            //Get rates of the product
//            $rates = array_shift($taxes);
//            //Take only the item rate and round it.
//            $vat_rate = !empty($rates) && wc_tax_enabled() ? round(array_shift($rates)) : 0;
//        }

        if (!$item instanceof WC_Order_Item_Product) {
            return [];
        }

        $quantity = abs($item->get_quantity());
        $price = abs($item->get_total());
        $description = esc_attr($item->get_name());

        $data = [
            // Required. Amount of the positions.
            'amount' => ecp_price_multiply($quantity > 0 ? $price / $quantity : $price, $currency),
        ];

        if ($quantity > 0) {
            // Quantity of the goods or services. Multiple of: 0.000001.
            $data['quantity'] = $quantity;
        }

        if (strlen($description) > 0) {
            // Goods or services description. >= 1 characters<= 255 characters.
            $data['description'] = $this->limit_length($description, 255);
        }

        $totalTax = abs($item->get_total_tax());

        if ($totalTax > 0) {
            // Tax percentage for the position. Multiple of: 0.01.
            $data['tax'] = round($totalTax / $price, 2);
            // Tax amount for the position.
            $data['tax_amount'] = ecp_price_multiply($totalTax / $quantity, $currency);
        }

        return $data;
    }

    /**
     * <h2>Crops and returns string.</h2>
     *
     * @param string $string <p>Original string.</p>
     * @param integer $limit <p>Limit size in characters.</p>
     * @return string <p>Cropped string.</p>
     */
    private function limit_length($string, $limit = 127)
    {
        $str_limit = $limit - 3;

        if (function_exists('mb_strimwidth')) {
            return mb_strlen($string) > $limit
                ? mb_strimwidth($string, 0, $str_limit) . '...'
                : $string;
        }

        return strlen($string) > $limit
            ? substr($string, 0, $str_limit) . '...'
            : $string;
    }

}