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
        return $this->get_ecp_meta('_payment_id');
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
        if ($value != $current_payment_id) {
            if (is_a($this, "Ecp_Gateway_Order")) {
                $this->add_order_note(__('New payment id is ' . $value, 'woocommerce'));
            }
            $this->set_ecp_meta('_payment_id', $value);
        }
    }

    /**
     * Returns payment status.
     *
     * @return string
     */
    public function get_ecp_status()
    {
        return $this->get_ecp_meta('_payment_status');
    }

    /**
     * Sets payment status.
     *
     * @param string $status
     * @return void
     */
    public function set_ecp_status($status)
    {
        $this->set_ecp_meta('_payment_status', $status);
    }

    /**
     * Returns ECOMMPAY payment method.
     *
     * @return string
     */
    public function get_payment_system()
    {
        return $this->get_ecp_meta('_ecommpay_payment_method');
    }

    /**
     * Sets ECOMMPAY payment method.
     *
     * @param string $name
     * @return void
     */
    public function set_payment_system($name)
    {
        $this->set_ecp_meta('_ecommpay_payment_method', $name);
    }

    public function get_is_test()
    {
        return (bool) $this->get_ecp_meta('_ecommpay_payment_test');
    }

    public function set_is_test()
    {
        $this->set_ecp_meta('_ecommpay_payment_test', 1);
    }

    /**
     * @param string $context
     *
     * @return mixed|string
     */
    public function get_ecp_transaction_id($context = 'view')
    {
        $id = $this->get_id();

        // Search for custom transaction meta to avoid transaction ID sometimes being empty on subscriptions in WC 3.0.
        $transaction_id = $this->get_ecp_meta('_transaction_id');

        if (!empty ($transaction_id)) {
            return $transaction_id;
        }

        // Try getting transaction ID from parent object.
        $transaction_id = $this->get_prop('transaction_id');

        if (!empty ($transaction_id)) {
            return $transaction_id;
        }

        // Search for original transaction ID. The transaction might be temporarily removed by
        // subscriptions. Use this one instead (if available).
        $transaction_id = $this->get_ecp_meta('_transaction_id_original');

        if (!empty ($transaction_id)) {
            return $transaction_id;
        }

        // Default search transaction ID.
        return $this->get_ecp_meta('transaction_id');
    }

    public function get_transaction_order_id($context = 'view')
    {
        return $this->get_ecp_meta('_ecommpay_request_id', true, $context);
    }

    /**
     * Increase the amount of payment attempts done
     *
     * @return int
     */
    public function get_failed_ecommpay_payment_count()
    {
        $count = $this->get_ecp_meta(self::META_FAILED_PAYMENT_COUNT);

        if (!empty ($count)) {
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
        $count = $this->get_failed_ecommpay_payment_count() + 1;
        $this->set_ecp_meta(self::META_FAILED_PAYMENT_COUNT, $count);

        return $count;
    }

    /**
     * Checks if the order is paid with the ECOMMPAY plugin.
     *
     * @return bool
     */
    public function is_ecp()
    {
        $pm = $this->get_ecp_meta('_payment_method');

        foreach (ecp_payment_methods() as $method) {
            if ($pm === $method->id) {
                return true;
            }
        }

        return $pm === 'ecommpay';
    }

    /**
     * Sets meta data by key.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $unique
     * @return void
     */
    public function set_ecp_meta($key, $value, $unique = true)
    {
        $this->add_meta_data($key, $value, $unique);
        $this->save_meta_data();
    }

    /**
     * Returns meta data by key.
     *
     * @return string
     */
    public function get_ecp_meta($key, $single = true, $context = 'view')
    {
        $meta = $this->get_meta($key, $single, $context);

        // For compatibility with older versions of ECOMMPAY plugin.
        if (empty ($meta)) {
            $meta = get_post_meta($this->get_id(), $key, $single);
        }

        return $meta;
    }
}