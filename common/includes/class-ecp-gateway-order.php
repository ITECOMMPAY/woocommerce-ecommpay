<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Order
 *
 * Extends Woocommerce order for easy access to internal data.
 *
 * @class    Ecp_Gateway_Order
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Order extends \Automattic\WooCommerce\Admin\Overrides\Order
{
    use ECP_Gateway_Order_Extension;

    /**
     * @var ?Ecp_Gateway_Payment
     */
    private $payment;

    /**
     * Mark in order metadata for counting changed payment method.
     */
    const META_PAYMENT_METHOD_CHANGE_COUNT = '_ecommpay_payment_method_change_count';

    /**
     * Mark in order metadata for counting failed payments.
     */
    const META_FAILED_PAYMENT_COUNT = '_ecommpay_failed_payment_count';

    const META_REFUND_ATTEMPTS_COUNT = '_ecommpay_refund_attempts_count';

    /**
     * Transaction identifier in order metadata.
     */
    const META_TRANSACTION_ID = '_transaction_id';

    const META_GATEWAY_OPERATION_ID = '_gateway_operation_id';

    /**
     * Returns the order ID based on the ID retrieved from the ECOMMPAY callback.
     *
     * @param Ecp_Gateway_Info_Callback $info The callback data as associative array.
     * @return int Order identifier
     */
    public static function get_order_id_from_callback($info, $prefix)
    {
        global $wpdb;
        $payment_id = $info->get_payment()->get_id() ?? $_GET['payment_id'];

        if (ecp_HPOS_enabled()) {
            $orders = wc_get_orders([
                'limit' => 1,
                'meta_query' => [
                    [
                        'key' => '_payment_id',
                        'value' => $payment_id,
                    ],
                ],
            ]);
            return current($orders) ? current($orders)->get_id() : false;
        } else {
            return $wpdb->get_var($wpdb->prepare("SELECT DISTINCT ID FROM $wpdb->posts as posts LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE meta.meta_value = %s AND meta.meta_key = %s", $payment_id, '_payment_id'));
        }
    }

    /**
     * @return string
     */
    public function create_payment_id()
    {
        $test_mode = ecp_is_enabled(Ecp_Gateway_Settings_General::OPTION_TEST);

        $_payment_id = $this->get_ecp_meta('_payment_id');
        if ($_payment_id != '' & ($_REQUEST['action'] != 'ecommpay_process')) {
            $id = $_payment_id;
        } else if (!empty ($_REQUEST['payment_id'])) {
            $id = $_REQUEST['payment_id'];
        } else {
            $id = $this->get_id() . '_' . ($this->get_failed_ecommpay_payment_count() + 1);
        }
        $prefix = Ecp_Core::CMS_PREFIX . '&' . wc_get_var($_SERVER['SERVER_NAME'], 'undefined') . '&';
        if ($test_mode & (substr($id, 0, strlen($prefix)) !== $prefix)) {
            $id = $prefix . $id;
            $this->set_is_test();
        }

        $this->set_payment_id($id);
        $this->set_ecp_status(Ecp_Gateway_Payment_Status::INITIAL);
        $this->save_meta_data();

        ecp_get_log()->debug(__('New payment identifier created:', 'woo-ecommpay'), $id);
        return $id;
    }

    /**
     * @param string $orderId
     * @param string $prefix
     * @return int
     */
    private static function remove_order_prefix($orderId, $prefix)
    {
        return (int) preg_replace(
            '/^' . $prefix . '&' . preg_quote(wc_get_var($_SERVER['SERVER_NAME'], 'undefined')) . '&/',
            '',
            $orderId
        );
    }

    /**
     * Get order refunds.
     *
     * @return Ecp_Gateway_Refund[] array of WC_Order_Refund objects
     * @since 2.0.0
     */
    public function get_refunds()
    {
        $cache_key = WC_Cache_Helper::get_cache_prefix('orders') . 'refunds' . $this->get_id();
        $cached_data = wp_cache_get($cache_key, $this->cache_group);

        if (false !== $cached_data) {
            return $cached_data;
        }

        /** @var Ecp_Gateway_Refund[] $refunds */
        $refunds = ecp_get_orders(
            [
                'type' => 'shop_order_refund',
                'parent' => $this->get_id(),
                'limit' => -1,
            ]
        );

        wp_cache_set($cache_key, $refunds, $this->cache_group);

        return $refunds;
    }

    /**
     * <h2>Returns subscriptions by order.</h2>
     *
     * @since 2.0.0
     * @return Ecp_Gateway_Subscription[]
     */
    public function get_subscriptions()
    {
        ecp_get_log()->debug(__('Find subscription', 'woo-ecommpay'));

        /** @var Ecp_Gateway_Subscription|Ecp_Gateway_Subscription[] $subscriptions */
        $subscriptions = ecp_get_orders(
            [
                'type' => 'shop_subscription',
                'parent' => $this->get_id(),
            ]
        );

        if (count($subscriptions) <= 0) {
            ecp_get_log()->warning(__('Subscription is not found.', 'woo-ecommpay'));
            ecp_get_log()->debug(__('Parent order ID:', 'woo-ecommpay'), $this->get_id());
            return null;
        }

        return $subscriptions;
    }

    public function get_transaction_order_id($context = 'view')
    {
        return $this->get_ecp_meta('_ecommpay_request_id', true, $context);
    }

    /**
     * Set the transaction order ID on an order
     *
     * @param string $transaction_order_id
     * @return void
     */
    public function set_transaction_order_id($transaction_order_id)
    {
        $this->set_ecp_meta('_ecommpay_request_id', $transaction_order_id);
    }

    /**
     * Adds order transaction fee to the order before sending out the order confirmation
     *
     * @param $fee_amount
     * @return bool
     */
    public function add_transaction_fee($fee_amount)
    {
        if ($fee_amount <= 0) {
            return false;
        }

        $fee = new WC_Order_Item_Fee();
        $fee->set_name(__('Payment Fee', 'woo-ecommpay'));
        $fee->set_total($fee_amount / 100);
        $fee->set_tax_status('none');
        $fee->set_total_tax(0);
        $fee->set_order_id($this->get_id());
        $fee->save();

        $this->add_item(apply_filters('woocommerce_ecommpay_transaction_fee_data', $fee, $this));
        $this->calculate_taxes();
        $this->calculate_totals(false);
        $this->save();

        return true;
    }

    /**
     * <h2>Returns not processed refund object.</h2>
     *
     * @return Ecp_Gateway_Refund <p>Refund object.</b>
     * @throws Ecp_Gateway_Logic_Exception When the refund object is not found.
     */
    public function find_unprocessed_refund()
    {
        ecp_get_log()->debug(__('Find order unprocessed refund.', 'woo-ecommpay'));

        foreach ($this->get_refunds() as $refund) {
            if (!$refund->get_ecp_transaction_id()) {
                ecp_get_log()->debug(__('Unprocessed refund found:', 'woo-ecommpay'), $refund->get_id());
                return $refund;
            }
        }

        throw new Ecp_Gateway_Logic_Exception('Not found refund object.');
    }

    /**
     * <h2>Returns refund object by ECOMMPAY Request ID.</h2>

     * @param string $request_id <p>ECOMMPAY Request ID</p>
     * @return Ecp_Gateway_Refund <p>Refund object</p>
     * @throws Ecp_Gateway_Logic_Exception When the refund object is not found.
     */
    public function find_refund_by_request_id($request_id)
    {
        ecp_get_log()->debug(__('Find order refund by ECOMMPAY Request ID.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Request ID:', 'woo-ecommpay'), $request_id);

        foreach ($this->get_refunds() as $refund) {
            if ($request_id === $refund->get_ecp_transaction_id()) {
                ecp_get_log()->info(__('Refund by request found:', 'woo-ecommpay'), $refund->get_id());
                return $refund;
            }
        }

        throw new Ecp_Gateway_Logic_Exception(__('Not found refund object by ECOMMPAY Request ID.', 'woo-ecommpay'));
    }

    /**
     * Checks if the order is currently in a failed renewal
     *
     * @return bool
     */
    public function subscription_is_renewal_failure()
    {
        if (!ecp_subscription_is_active()) {
            return false;
        }

        return ecp_subscription_is_renewal($this) && $this->get_status() === 'failed';
    }

    /**
     * Check if the current request is trying to change the payment gateway
     *
     * @return bool
     */
    public function is_request_to_change_payment()
    {
        $is_request_to_change_payment = false;

        if (ecp_subscription_is_active() && class_exists('WC_Subscriptions_Change_Payment_Gateway')) {
            $is_request_to_change_payment = WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;

            if (!$is_request_to_change_payment && !empty ($_GET['ecommpay_change_payment_method'])) {
                $is_request_to_change_payment = true;
            }
        }

        return apply_filters('woocommerce_ecommpay_is_request_to_change_payment', $is_request_to_change_payment);
    }

    /**
     * @return bool
     */
    public function contains_switch()
    {
        return ecp_order_contains_switch($this);
    }

    /**
     * <h2>Returns the count of failed payment attempts.</h2>
     *
     * @return int
     */
    public function get_failed_ecommpay_payment_count()
    {
        $count = $this->get_ecp_meta(self::META_FAILED_PAYMENT_COUNT);

        if (!empty ($count)) {
            ecp_get_log()->debug(__('Count of failed payment attempts:', 'woo-ecommpay'), $count);
            return $count;
        }

        ecp_get_log()->debug(__('No failed payment attempts', 'woo-ecommpay'));
        return 0;
    }

    /**
     * <h2>Increase the count of failed payment attempts with ECOMMPAY.</h2>
     *
     * @return int
     * @uses Ecp_Gateway_Order::get_failed_ecommpay_payment_count()
     */
    public function increase_failed_ecommpay_payment_count()
    {
        $count = $this->get_failed_ecommpay_payment_count() + 1;
        $this->set_ecp_meta(self::META_FAILED_PAYMENT_COUNT, $count);

        ecp_get_log()->debug(__('Count of failed payment attempts increased:', 'woo-ecommpay'), $count);
        return $count;
    }

    /**
     * <h2>Returns the count of refund attempts.</h2>
     *
     * @return int
     */
    public function get_refund_attempts_count()
    {
        $count = $this->get_ecp_meta(self::META_REFUND_ATTEMPTS_COUNT);

        if (!empty ($count)) {
            ecp_get_log()->debug(__('Count of refund attempts:', 'woo-ecommpay'), $count);
            return $count;
        }

        ecp_get_log()->debug(__('No refund attempts', 'woo-ecommpay'));
        return 0;
    }

    /**
     * <h2>Increase the count of refund attempts with ECOMMPAY.</h2>
     *
     * @return int
     * @uses Ecp_Gateway_Order::get_refund_attempts_count()
     */
    public function increase_refund_attempts_count()
    {
        $count = $this->get_refund_attempts_count() + 1;
        $this->set_ecp_meta(self::META_REFUND_ATTEMPTS_COUNT, $count);

        ecp_get_log()->debug(__('Count of refund attempts increased:', 'woo-ecommpay'), $count);
        return $count;
    }

    /**
     * Gets the amount of times the customer has updated his card.
     *
     * @return int
     */
    public function get_payment_method_change_count()
    {
        $count = $this->get_ecp_meta(self::META_PAYMENT_METHOD_CHANGE_COUNT);

        if (!empty ($count)) {
            return $count;
        }

        return 0;
    }

    /**
     * Increases the amount of times the customer has updated his card.
     *
     * @return int
     * @uses Ecp_Gateway_Order::get_payment_method_change_count()
     */
    public function increase_payment_method_change_count()
    {
        $count = $this->get_payment_method_change_count() + 1;
        $this->set_ecp_meta(self::META_PAYMENT_METHOD_CHANGE_COUNT, $count);

        return $count;
    }

    /**
     * <h2>Returns the result of checking if an order contains a virtual product.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a virtual product or <b>FALSE</b> otherwise.
     * @uses WC_Order::get_items()
     * @uses WC_Order_Item_Product::get_product()
     * @uses WC_Product::is_virtual()
     */
    public function contains_virtual_product()
    {
        // Loop through the order items
        foreach ($this->get_items() as $order_item) {
            // Get the product
            if ($order_item instanceof WC_Order_Item_Product) {
                $product = $order_item->get_product();

                if (!$product) {
                    continue;
                }

                // Is this product virtual?
                if ($product->is_virtual()) {
                    ecp_get_log()->debug(__('The order contains virtual products', 'woo-ecommpay'));
                    return true;
                }
            }
        }

        ecp_get_log()->debug(__('The order does not contain virtual products', 'woo-ecommpay'));
        return false;
    }

    /**
     * <h2>Returns the result of checking if an order contains a non-virtual product.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a non-virtual product or <b>FALSE</b> otherwise.
     * @uses WC_Order::get_items()
     * @uses WC_Order_Item_Product::get_product()
     * @uses WC_Product::is_virtual()
     */
    public function contains_non_virtual_product()
    {
        // Loop through the order items
        foreach ($this->get_items() as $order_item) {
            // Get the product
            if ($order_item instanceof WC_Order_Item_Product) {
                $product = $order_item->get_product();

                if (!$product) {
                    continue;
                }

                // Is this product virtual?
                if (!$product->is_virtual()) {
                    ecp_get_log()->debug(__('The order contains non-virtual products', 'woo-ecommpay'));
                    return true;
                }
            }
        }

        ecp_get_log()->debug(__('The order does not contain non-virtual products', 'woo-ecommpay'));
        return false;
    }

    /**
     * <h2>Returns the result of checking if am order contains a subscription product.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a subscription product or <b>FALSE</b> otherwise.
     */
    public function contains_subscription()
    {
        if (!ecp_subscription_is_active()) {
            return false;
        }

        if (function_exists('wcs_order_contains_subscription')) {
            return wcs_order_contains_subscription($this);
        }

        ecp_get_log()->debug(__('The order does not contain subscription products', 'woo-ecommpay'));
        return false;
    }

    /**
     * <h2>Returns the result of checking if the order contains only a virtual products.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a virtual products only or <b>FALSE</b> otherwise.
     */
    public function contains_virtual_product_only()
    {
        return $this->contains_virtual_product()
            && !$this->contains_non_virtual_product()
            && !$this->contains_subscription();
    }

    /**
     * <h2>Returns the result of checking if am order contains only a non-virtual products.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a non-virtual products only or <b>FALSE</b> otherwise.
     */
    public function contains_non_virtual_product_only()
    {
        return !$this->contains_virtual_product()
            && $this->contains_non_virtual_product()
            && !$this->contains_subscription();
    }

    /**
     * <h2>Returns the result of checking if am order contains only a subscription products.</h2>
     *
     * @return bool <b>TRUE</b> if order contains a subscription products only or <b>FALSE</b> otherwise.
     */
    public function contains_subscription_only()
    {
        return !$this->contains_virtual_product()
            && !$this->contains_non_virtual_product()
            && $this->contains_subscription();
    }

    /**
     * <h2>Fetches transaction data based on a transaction ID.</h2>
     * <p>This method checks if the transaction is cached in a transient before it asks the ECOMMPAY API.
     * Cached data will always be used if available.</p>
     * <p>If no data is cached, we will fetch the transaction from the API and cache it.</p>
     *
     * @return Ecp_Gateway_Payment Order payment
     */
    public function get_payment($reload = false, $force = false)
    {
        if ($reload || !$this->payment) {
            $this->payment = Ecp_Gateway_Payment_Provider::get_instance()->load($this, $force);
        }

        return $this->payment;
    }

    public function get_billing_address()
    {
        return implode(' ', [$this->get_billing_address_1(), $this->get_billing_address_2()]);
    }

    public function get_shipping_type()
    {
        //ToDo: Need to implement
        // $methods = $this->get_shipping_methods();
        // /** @var WC_Shipping_Method $method */
        // $method = end($methods);

        return '07';
    }

    public function get_shipping_name_indicator()
    {
        return $this->get_billing_first_name() === $this->get_shipping_first_name()
            && $this->get_billing_last_name() === $this->get_shipping_last_name()
            ? '01' : '02';
    }

    public function get_shipping_address()
    {
        return implode(' ', [$this->get_shipping_address_1(), $this->get_shipping_address_2()]);
    }

    /**
     * @param string $comment
     * @param int $parent_comment
     * @return int|null
     */
    public function append_order_comment($comment, $parent_comment = 0)
    {
        $commentData = [
            'comment_post_ID' => $this->get_id(),
            'comment_author' => 'ECOMMPAY',
            'comment_agent' => 'Gate2025',
            'comment_author_email' => 'support@ecommpay.com',
            'comment_author_url' => 'https://ecommpay.com',
            'comment_content' => $comment,
            'comment_type' => 'order_note',
            'comment_approved' => 1,
            'comment_parent' => $parent_comment,
            'user_id' => 0,
        ];

        $result = wp_insert_comment($commentData);

        if (!is_numeric($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Check if the action we are about to perform is allowed according to the current transaction state.
     *
     * @return boolean
     */
    public function is_action_allowed($action)
    {
        $state = $this->get_ecp_status();
        $remaining_balance = $this->get_payment()->get_remaining_balance();

        $allowed_states = [
            Ecp_Gateway_Operation_Type::REFUND => [
                Ecp_Gateway_Payment_Status::PARTIALLY_REVERSED,
                Ecp_Gateway_Payment_Status::PARTIALLY_REFUNDED,
                Ecp_Gateway_Payment_Status::SUCCESS
            ],
            'renew' => ['awaiting capture'],
            'recurring' => ['subscribe'],
            'subscription' => ['success']
        ];

        // We want to still allow captures if there is a remaining balance.
        if ('awaiting capture' === $state && $remaining_balance > 0 && $action !== 'cancel') {
            return true;
        }

        return in_array($state, $allowed_states[$action]);
    }

    public function needs_processing()
    {
        if (ecp_is_enabled(Ecp_Gateway_Settings_General::OPTION_AUTO_COMPETE_ORDER)) {
            return false;
        }
        return parent::needs_processing();
    }
}
