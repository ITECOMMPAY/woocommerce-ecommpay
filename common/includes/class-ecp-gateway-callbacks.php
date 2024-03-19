<?php

defined('ABSPATH') || exit;

/**
 * <h2>Callback handler.</h2>
 *
 * @class    Ecp_Gateway_Callbacks
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 * @internal
 */
class Ecp_Gateway_Callbacks
{
    /**
     * <h2> List of supported operations.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
    private $operations = [
        Ecp_Gateway_Operation_Type::SALE => 'woocommerce_ecommpay_callback_sale',
        Ecp_Gateway_Operation_Type::REFUND => 'woocommerce_ecommpay_callback_refund',
        Ecp_Gateway_Operation_Type::REVERSAL => 'woocommerce_ecommpay_callback_reversal',
        Ecp_Gateway_Operation_Type::RECURRING => 'woocommerce_ecommpay_callback_recurring',
        Ecp_Gateway_Operation_Type::ACCOUNT_VERIFICATION => 'woocommerce_ecommpay_callback_verify',
        Ecp_Gateway_Operation_Type::RECURRING_CANCEL => 'woocommerce_ecommpay_callback_recurring_cancel',
        Ecp_Gateway_Operation_Type::PAYMENT_CONFIRMATION => 'woocommerce_ecommpay_callback_payment_confirmation',
    ];

    /**
     * <h2>Callback handler constructor.</h2>
     *
     * @param array $data <p>Callback data.</p>
     * @since 2.0.0
     */
    private function __construct(array $data)
    {
        add_action('woocommerce_ecommpay_callback_refund', [Ecp_Gateway_Module_Refund::get_instance(), 'handle'], 10, 2);
        add_action('woocommerce_ecommpay_callback_reversal', [Ecp_Gateway_Module_Refund::get_instance(), 'handle'], 10, 2);
        add_action('woocommerce_ecommpay_callback_sale', [$this, 'sale'], 10, 2);
        add_action('woocommerce_ecommpay_callback_recurring', [$this, 'recurring'], 10, 2);
        add_action('woocommerce_ecommpay_callback_verify', [$this, 'verify'], 10, 2);
        add_action('woocommerce_ecommpay_callback_payment_confirmation', [$this, 'confirm'], 10, 2);

        // Decode the body into JSON
        $info = new Ecp_Gateway_Info_Callback($data);

        // Instantiate order object
        $order = $this->get_order($info);

        // Execute callback process.
        $this->processor($info, $order);
    }

    public static function handle()
    {
        ecp_get_log()->info(_x('Run callback handler.', 'Log information', 'woo-ecommpay'));

        // Get callback body
        $body = file_get_contents('php://input');

        $data = json_decode($body, true);

        ecp_get_log()->debug(__('Incoming callback data:', 'woo-ecommpay'), $body);

        // Check signature
        self::check_signature($data);

        return new static($data);
    }

    /**
     * @param Ecp_Gateway_Order $order
     * @param Ecp_Gateway_Info_Callback $callback
     * @return void
     * @since 2.0.0
     */
    public function processor($callback, $order)
    {
        ecp_get_log()->info(__('Run callback processor', 'woo-ecommpay'));

        do_action('ecp_accepted_callback_before_processing', $order, $callback);
        do_action('ecp_accepted_callback_before_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        // Clear card - payment is not initial.
        WC()->cart->empty_cart();

        if (array_key_exists($callback->get_operation()->get_type(), $this->operations)) {
            do_action($this->operations[$callback->get_operation()->get_type()], $callback, $order);
            $message = 'OK';
        } else {
            $message = sprintf(
                __('Not supported operation type: %s', 'woo-ecommpay'),
                $callback->get_operation()->get_type()
            );
            ecp_get_log()->warning($message);
        }

        do_action('ecp_accepted_callback_after_processing', $order, $callback);
        do_action('ecp_accepted_callback_after_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        http_response_code(200);
        die ($message);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    public function verify($callback, $order)
    {
        ecp_get_log()->info(__('Apply verify callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);

        $order->set_transaction_order_id($callback->get_operation()->get_request_id());
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    public function confirm($callback, $order)
    {
        ecp_get_log()->info(__('Apply payment confirmation callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @throws WC_Data_Exception
     */
    public function recurring($callback, $order)
    {
        ecp_get_log()->info(__('Apply recurring callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->process($callback, $order);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @throws WC_Data_Exception
     */
    public function sale($callback, $order)
    {
        ecp_get_log()->info(__('Apply sale callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $this->update_subscription($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->process($callback, $order);
    }

    private function log_order_data(Ecp_Gateway_Order $order)
    {
        ecp_get_log()->debug(__('Order ID:', 'woo-ecommpay'), $order->get_id());
        ecp_get_log()->debug(__('Payment ID:', 'woo-ecommpay'), $order->get_payment_id());
        ecp_get_log()->debug(__('Transaction ID:', 'woo-ecommpay'), $order->get_ecp_transaction_id());
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function process(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        switch ($callback->get_payment()->get_status()) {
            case Ecp_Gateway_Operation_Status::AWAITING_CONFIRMATION:
                $this->hold_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::AWAITING_CUSTOMER:
                $this->decline_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::EXTERNAL_PROCESSING:
                break;
            default:
                $this->processOperation($callback, $order);
                break;
        }
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function processOperation(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        switch ($callback->get_operation()->get_status()) {
            case Ecp_Gateway_Operation_Status::SUCCESS:
                $this->complete_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::DECLINE:
            case Ecp_Gateway_Operation_Status::EXPIRED:
            case Ecp_Gateway_Operation_Status::INTERNAL_ERROR:
            case Ecp_Gateway_Operation_Status::EXTERNAL_ERROR:
                $this->decline_order($callback, $order);
                break;
        }
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function hold_order(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->debug(__('Run awaiting confirmation process.', 'woo-ecommpay'), $order->get_id());
        $order->set_transaction_id($callback->get_operation()->get_request_id());
        $order->update_status('on-hold');
        ecp_get_log()->debug(__('Awaiting confirmation process completed.', 'woo-ecommpay'), $order->get_id());
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     */
    private function complete_order(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        $is_amount_equal = (string) $callback['payment']['sum']['amount'] === (string) str_replace('.', '', $order->get_total());
        $is_currency_equal = $callback['payment']['sum']['currency'] === $order->get_currency();

        if ($is_amount_equal && $is_currency_equal) {
            ecp_get_log()->debug(__('Run success process.', 'woo-ecommpay'), $order->get_id());
            $order->payment_complete($callback->get_operation()->get_request_id());
            ecp_get_log()->debug(__('Success process completed.', 'woo-ecommpay'), $order->get_id());
        } else {
            $order->add_order_note(
                __('The payment amount does not match the order amount. The order is on hold.', 'woo-ecommpay')
            );
            $this->hold_order($callback, $order);
            return;
        }
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function decline_order(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->debug(__('Run failed process.', 'woo-ecommpay'), $order->get_id());
        $order->set_transaction_id($callback->get_operation()->get_request_id());
        $order->update_status('failed');
        $order->increase_failed_ecommpay_payment_count();
        ecp_get_log()->debug(__('Failed process completed.', 'woo-ecommpay'), $order->get_id());
    }

    /**
     * @param $data
     * @return void
     */
    private static function check_signature($data)
    {
        ecp_get_log()->debug(__('Verify signature', 'woo-ecommpay'));
        try {
            if (!ecp_check_signature($data)) {
                $message = _x('Invalid callback signature.', 'Error message', 'woo-ecommpay');
                ecp_get_log()->error($message);

                http_response_code(400);
                die ($message);
            }

            ecp_get_log()->debug(__('Signature verified.', 'woo-ecommpay'));
        } catch (Ecp_Gateway_Signature_Exception $e) {
            $e->write_to_logs();
            http_response_code(500);
            die ($e->getMessage());
        }
    }

    /**
     * <h2>Returns order by callback information.</h2>
     *
     * @param Ecp_Gateway_Info_Callback $info <p>Callback information.</p>
     * @since 2.0.0
     * @return Ecp_Gateway_Order <p>Payment order.</p>
     */
    private function get_order($info)
    {
        // Fetch order number;
        $order_number = Ecp_Gateway_Order::get_order_id_from_callback($info, Ecp_Core::CMS_PREFIX);
        $order = ecp_get_order($order_number);

        if (!$order) {
            // Print debug information to logs
            $message = __('Order not found', 'woo-ecommpay');
            ecp_get_log()->error($message);
            ecp_get_log()->info(__('Transaction failed for', 'woo-ecommpay'), $order_number);

            foreach ($info->get_errors() as $error) {
                ecp_get_log()->add(__('Error code:', 'woo-ecommpay'), $error->get_code());
                ecp_get_log()->add(__('Error field:', 'woo-ecommpay'), $error->get_field());
                ecp_get_log()->add(__('Error message:', 'woo-ecommpay'), $error->get_message());
                ecp_get_log()->add(__('Error description:', 'woo-ecommpay'), $error->get_description());
            }

            ecp_get_log()->add(__('Response data: %s', 'woo-ecommpay'), json_encode($info));

            http_response_code(404);
            die ($message);
        }

        return $order;
    }

    /**
     * <h2>Update payment data.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Payment order.</p>
     * @param Ecp_Gateway_Info_Callback $callback <p>Callback information.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_payment($order, $callback)
    {
        $payment = $order->get_payment();
        $payment->add_operation($callback->get_operation());
        $payment->set_info($callback->get_payment());
        $payment->save();
    }

    /**
     * <h2>Sets to subscriptions recurring information.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Parent payment order.</p>
     * @param Ecp_Gateway_Info_Callback $callback <p>Callback information.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_subscription($order, $callback)
    {
        if ($order->contains_subscription()) {
            ecp_get_log()->debug(__('Order has subscriptions', 'woo-ecommpay'));
            $subscriptions = $order->get_subscriptions();

            if (count($subscriptions) <= 0) {
                return;
            }

            if (!$callback->try_get_recurring($recurring)) {
                ecp_get_log()->critical(
                    __('No recurring information found in callback data. The Subscription cannot be renewed.', 'woo-ecommpay')
                );
                return;
            }

            ecp_get_log()->debug(__('Recurring ID:', 'woo-ecommpay'), $recurring->get_id());

            foreach ($subscriptions as $subscription) {
                ecp_get_log()->debug(__('Subscription ID:', 'woo-ecommpay'), $subscription->get_id());
                $subscription->set_recurring_id($callback->get_recurring()->get_id());
                $subscription->save();
            }
        }
    }
}
