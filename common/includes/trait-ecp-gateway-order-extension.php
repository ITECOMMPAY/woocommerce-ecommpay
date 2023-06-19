<?php

trait ECP_Gateway_Order_Extension
{
    /**
     * Returns the payment identifier.
     *
     * @return string
     */
    public function get_payment_id()
    {
        return get_post_meta($this->get_id(), '_payment_id', true);
    }

    /**
     * Sets payment identifier.
     *
     * @param string $value
     * @return void
     */
    public function set_payment_id($value)
    {
        $current_payment_id = $this->get_payment_id();
        if ($value != $current_payment_id){
            if (is_a($this, "Ecp_Gateway_Order")){
                $this->add_order_note(__('New payment id is ' . $value, 'woocommerce'));
            }
            update_post_meta($this->get_id(), '_payment_id', $value);
        }
    }

    /**
     * Returns payment status.
     *
     * @return string
     */
    public function get_ecp_status()
    {
        return get_post_meta($this->get_id(), '_payment_status', true);
    }

    /**
     * Sets payment status.
     *
     * @param string $status
     * @return void
     */
    public function set_ecp_status($status)
    {
        update_post_meta($this->get_id(), '_payment_status', $status);
    }

    /**
     * Returns ECOMMPAY payment method.
     *
     * @return string
     */
    public function get_payment_system()
    {
        return get_post_meta($this->get_id(), '_ecommpay_payment_method', true);
    }

    /**
     * Sets ECOMMPAY payment method.
     *
     * @param string $name
     * @return void
     */
    public function set_payment_system($name)
    {
        update_post_meta($this->get_id(), '_ecommpay_payment_method', $name);
    }

    public function get_is_test()
    {
        return (bool)get_post_meta($this->get_id(), '_ecommpay_payment_test', true);
    }

    public function set_is_test()
    {
        update_post_meta($this->get_id(), '_ecommpay_payment_test', 1);
    }

    /**
     * @param string $context
     *
     * @return mixed|string
     */
    public function get_transaction_id($context = 'view')
    {
        $id = $this->get_id();

        // Search for custom transaction meta to avoid transaction ID sometimes being empty on subscriptions in WC 3.0.
        $transaction_id = get_post_meta($id, '_transaction_id', true);

        if (!empty($transaction_id)) {
            return $transaction_id;
        }

        // Try getting transaction ID from parent object.
        $transaction_id = $this->get_prop('transaction_id');

        if (!empty($transaction_id)) {
            return $transaction_id;
        }

        // Search for original transaction ID. The transaction might be temporarily removed by
        // subscriptions. Use this one instead (if available).
        $transaction_id = get_post_meta($id, '_transaction_id_original', true);

        if (!empty($transaction_id)) {
            return $transaction_id;
        }

        // Default search transaction ID.
        return get_post_meta($id, 'transaction_id', true);
    }

    public function set_transaction_id($value)
    {
        if (array_key_exists('transaction_id', $this->data)) {
            $this->set_prop('transaction_id', $value);
        }

        update_post_meta($this->get_id(), '_transaction_id', $value);
    }

    public function get_transaction_order_id($context = 'view')
    {
        return $this->get_meta('_ecommpay_request_id', true, $context);
    }

    /**
     * Set the transaction order ID on an order
     *
     * @param string $transaction_order_id
     * @return void
     */
    public function set_transaction_order_id($transaction_order_id)
    {
        update_post_meta($this->get_id(), '_ecommpay_request_id', $transaction_order_id);
    }

    /**
     * Increase the amount of payment attempts done
     *
     * @return int
     */
    public function get_failed_ecommpay_payment_count()
    {
        $count = get_post_meta($this->get_id(), self::META_FAILED_PAYMENT_COUNT, true);

        if (!empty($count)) {
            return $count;
        }

        return 0;
    }

    /**
     * Increase the amount of payment attempts done through ECOMMPAY
     *
     * @return int
     */
    public function increase_failed_ecommpay_payment_count()
    {
        $count = $this->get_failed_ecommpay_payment_count();
        update_post_meta($this->get_id(), self::META_FAILED_PAYMENT_COUNT, ++$count);

        return $count;
    }

    /**
     * Checks if the order is paid with the ECOMMPAY plugin.
     *
     * @return bool
     */
    public function is_ecp()
    {
        $pm = get_post_meta($this->get_id(), '_payment_method', true);

        foreach (ecp_payment_methods() as $method) {
            if ($pm === $method->id) {
                return true;
            }
        }

        return $pm === 'ecommpay';
    }
}