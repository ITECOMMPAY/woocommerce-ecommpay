<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Payment
 *
 * Contains payment details.
 *
 * @class    Ecp_Gateway_Payment
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Payment
{
    // region Properties

    /**
     * <h2>Status transition.</h2>
     *
     * @var ?Ecp_Gateway_Payment_Status_Transition
     */
    private $status_transition = null;

    /**
     * <h2>Parent order.</h2>
     *
     * @var Ecp_Gateway_Order
     */
    private $order;

    /**
     * <h2>Transactions.</h2>
     *
     * @var Ecp_Gateway_Info_Operation[]
     */
    private $operations = [];

    /**
     * <h2>Customer information.</h2>
     *
     * @var ?Ecp_Gateway_Info_Customer
     */
    private $customer;

    /**
     * <h2>Account information.</h2>
     *
     * @var ?Ecp_Gateway_Info_Account
     */
    private $account;

    /**
     * <h2>ACS information.</h2>
     *
     * @var ?Ecp_Gateway_Info_ACS
     */
    private $acs;

    /**
     * <h2>The identifier of the initial request.</h2>
     * <p>If there are no transactions in the payment, the request identifier is null.</p>
     * @var ?string
     */
    private $request_id;

    /**
     * <h2>List of errors.</h2>
     *
     * @var Ecp_Gateway_Info_Error[]
     */
    private $errors = [];

    /**
     * <h2>Payment information.</h2>
     *
     * @var Ecp_Gateway_Info_Payment
     */
    private $info;

    // endregion

    public static function stub($order)
    {
        $obj = new static($order);
        $obj->set_info(new Ecp_Gateway_Info_Payment([
            'status' => Ecp_Gateway_Payment_Status::INITIAL,
            'method' => 'Not selected',
        ]));

        return $obj;
    }

    /**
     * <h2>ECOMMPAY payment details constructor.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Parent order for payment.</p>
     * @since 2.0.0
     */
    public function __construct(Ecp_Gateway_Order $order)
    {
        $this->order = $order;
    }

    /**
     * <h2>Returns the payment identifier.</h2>
     *
     * @return string Payment identifier as string.
     * @since 2.0.0
     */
    public function get_id()
    {
        return $this->order->get_payment_id();
    }

    /**
     * <h2>Returns parent order.</h2>
     *
     * @return Ecp_Gateway_Order Parent order object
     * @since 2.0.0
     */
    public function get_order()
    {
        return $this->order;
    }

    /**
     * <h2>Stores payment details to the cache.</h2>
     *
     * @return void
     * @since 2.0.0
     */
    public function save()
    {
        Ecp_Gateway_Payment_Provider::get_instance()->save($this);
    }

    /**
     * <h2>Set payment status.</h2>
     * <p>Note: This method does not save the new status. To save the new status, you must run the
     * {@see Ecp_Gateway_Payment::status_transition() status transition} process.</p>
     * <p>When you saved payment details, the status transition will be performed automatically.</p>
     *
     * @param string $new_status <p>Status to change the payment to.</p>
     * @param string $note [optional] <p>Optional note to add. Default: blank string.</p>
     * @return void
     * @since 2.0.0
     */
    public function set_status($new_status, $note = '')
    {
        ecp_get_log()->info(__('Setting the payment status.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('New payment status:', 'woo-ecommpay'), $new_status);
        ecp_get_log()->debug(__('Note:', 'woo-ecommpay'), $note !== '' ? $note : __('* Not defined *', 'woo-ecommpay'));
        $old_status = $this->order->get_ecp_status();

        if (!$this->order->get_object_read()) {
            ecp_get_log()->warning(__('Order object could not be read. Process interrupted.'), 'woo-ecommpay');
            return;
        }

        $this->status_transition = new Ecp_Gateway_Payment_Status_Transition(
            [
                'old' => $old_status,
                'new' => $new_status,
                'note' => $note
            ]
        );

        if (!$this->status_transition->is_changed()) {
            ecp_get_log()->debug(__('Old and new payment status are identically. Process interrupted.'), 'woo-ecommpay');
            return;
        }

        $this->order->set_ecp_status($this->status_transition->get_new());
        $this->order->maybe_set_date_paid();
        ecp_get_log()->debug(__('The payment status has settled.', 'woo-ecommpay'));
    }

    /**
     * <h2>Transition the payment status.</h2>
     *
     * @return void
     * @since 2.0.0
     */
    public function status_transition()
    {
        ecp_get_log()->info(__('Transition the payment status.', 'woo-ecommpay'));

        if (!$this->status_transition) {
            ecp_get_log()->warning(__('Transition is not set. Interrupt process.', 'woo-ecommpay'));
            return;
        }

        // Copy status transition to local variable.
        $transition = $this->status_transition;
        // Reset status transition variable.
        $this->status_transition = null;

        try {
            do_action('ecommpay_payment_status_' . $transition->get_new(), $this->get_id(), $this);

            switch (true) {
                case !empty ($transition->get_old()):
                    if (!$transition->is_changed()) {
                        return;
                    }

                    /* translators: 1: old payment status 2: new payment status */
                    $note = sprintf(
                        __('Payment status changed from %1$s to %2$s.', 'woo-ecommpay'),
                        ecp_get_payment_status_name($transition->get_old()),
                        ecp_get_payment_status_name($transition->get_new())
                    );

                    do_action(
                        'ecommpay_payment_status_' . $transition->get_old() . '_to_' . $transition->get_new(),
                        $this->get_id(),
                        $this
                    );
                    do_action(
                        'ecommpay_payment_status_changed',
                        $this->get_id(),
                        $transition->get_old(),
                        $transition->get_new(),
                        $this
                    );
                    break;
                default:
                    /* translators: %s: new payment status */
                    $note = sprintf(
                        __('Payment status set to %s.', 'woo-ecommpay'),
                        ecp_get_payment_status_name($transition->get_new())
                    );
            }

            // Note the transition occurred.
            $this->order->add_order_note(trim($transition->get_note() . ' ' . $note));
        } catch (Exception $e) {
            ecp_get_log()->error(
                sprintf(__('Status transition of payment #%d errored!', 'woo-ecommpay'), $this->get_id())
            );

            $this->order->add_order_note(
                __('Error during payment status transition.', 'woocommerce') . ' ' . $e->getMessage()
            );
        }

        ecp_get_log()->debug(__('The payment status has changed.', 'woo-ecommpay'));
    }

    /**
     * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a
     * transient before it asks the ECOMMPAY API. Cached data will always be used if available.
     *
     * If no data is cached, we will fetch the transaction from the API and cache it.
     *
     * @return Ecp_Gateway_Info_Payment
     * @since 2.0.0
     */
    public function get_info()
    {
        if (!$this->info) {
            $this->info = new Ecp_Gateway_Info_Payment([
                'status' => Ecp_Gateway_Payment_Status::INITIAL,
                'method' => 'Not selected',
            ]);
        }

        return $this->info;
    }

    /**
     * <h2>Sets payment information.</h2>
     *
     * @param Ecp_Gateway_Info_Payment $info
     * @return static Current payment object.
     * @since 2.0.0
     */
    public function set_info(Ecp_Gateway_Info_Payment $info)
    {
        $this->info = $info;
        $this->set_status($info->get_status());

        return $this;
    }

    /**
     * <h2>Returns customer information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Customer
     * @since 2.0.0
     */
    public function get_customer()
    {
        return $this->customer;
    }

    /**
     * <h2>Sets customer information.</h2>
     *
     * @param Ecp_Gateway_Info_Customer $customer [optional]
     * @return static Current payment object.
     * @since 2.0.0
     */
    public function set_customer(Ecp_Gateway_Info_Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * <h2>Returns ACS information.</h2>
     *
     * @return ?Ecp_Gateway_Info_ACS
     * @since 2.0.0
     */
    public function get_acs()
    {
        return $this->acs;
    }

    /**
     * <h2>Sets ACS information.</h2>
     *
     * @param Ecp_Gateway_Info_ACS $acs [optional]
     * @return static Current payment object.
     * @since 2.0.0
     */
    public function set_acs(Ecp_Gateway_Info_ACS $acs = null)
    {
        $this->acs = $acs;

        return $this;
    }

    /**
     * <h2>Returns account information.</h2>
     * @return ?Ecp_Gateway_Info_Account
     * @since 2.0.0
     */
    public function get_account()
    {
        return $this->account;
    }

    /**
     * <h2>Sets account information.</h2>
     *
     * @param Ecp_Gateway_Info_Account $account [optional]
     * @return static Current payment object.
     * @since 2.0.0
     */
    public function set_account(Ecp_Gateway_Info_Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * <h2>Returns transactions.</h2>
     * @return Ecp_Gateway_Info_Operation[]
     * @since 2.0.0
     */
    public function get_operations()
    {
        return $this->operations;
    }

    /**
     * <h2>Sets transactions.</h2>
     *
     * @param Ecp_Gateway_Info_Operation[] $operations [optional]
     * @return static Current payment object.
     * @since 2.0.0
     */
    public function set_operations(array $operations = [])
    {
        foreach ($operations as $operation) {
            $this->add_operation($operation);
        }

        return $this;
    }

    public function get_request_id()
    {
        return $this->get_last_operation()->get_request_id();
    }

    public function get_operation_status()
    {
        return $this->get_last_operation()->get_status();
    }

    /**
     * @param Ecp_Gateway_Info_Operation $operation
     * @return void
     * @since 2.0.0
     */
    public function add_operation(Ecp_Gateway_Info_Operation $operation)
    {
        ecp_get_log()->info(__('Add operation to payment data', 'woo-ecommpay'));

        foreach ($this->operations as &$origin) {
            ecp_get_log()->debug(__('Check operation request id', 'woo-ecommpay'), $origin->get_request_id());

            // Find operation in current information
            if ($origin->get_request_id() === $operation->get_request_id()) {
                if (!$origin->try_get_date($origin_date)) {
                    ecp_get_log()->debug(
                        __('Old operation date is not exists. Update operation.', 'woo-ecommpay')
                    );
                    // Replace current by new value and save
                    $origin = $operation;
                    ecp_get_log()->debug(__('Complete - operation information changed', 'woo-ecommpay'));
                    return;
                }

                if (!$operation->try_get_date($operation_date)) {
                    ecp_get_log()->debug(
                        __('New operation date is not exists. Skip update operation.', 'woo-ecommpay')
                    );
                    return;
                }

                ecp_get_log()->debug(
                    __('Find. Check operation last date', 'woo-ecommpay'),
                    $origin_date->format(DateTime::RFC1123)
                );

                if ($origin_date > $operation_date) {

                    ecp_get_log()->debug(
                        sprintf(
                            __('New operation date [%s] is less then old operation date [%s]', 'woo-ecommpay'),
                            $operation_date->format(DateTime::RFC1123),
                            $origin_date->format(DateTime::RFC1123)
                        )
                    );
                    return;
                }

                ecp_get_log()->debug(
                    sprintf(
                        __('New operation date [%s] is great then old operation date [%s]. Skip update', 'woo-ecommpay'),
                        $operation_date->format(DateTime::RFC1123),
                        $origin_date->format(DateTime::RFC1123)
                    )
                );

                $origin = $operation;
                return;
            }
        }

        // New operation - add to list
        ecp_get_log()->info(__('Operation added to payment.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Operation request id:', 'woo-ecommpay'), $operation->get_request_id());
        $this->operations[] = $operation;
    }

    /**
     * Returns a transaction currency
     *
     * @return string
     * @throws Ecp_Gateway_API_Exception
     * @since 2.0.0
     */
    public function get_currency()
    {
        if (!$this->info instanceof Ecp_Gateway_Info_Payment) {
            throw new Ecp_Gateway_API_Exception('No API payment resource data available.', 0);
        }

        return $this->info->get_sum()->get_currency();
    }

    /**
     * Returns a remaining balance
     *
     * @return mixed
     * @since 2.0.0
     */
    public function get_remaining_balance()
    {
        $balance = $this->get_balance();

        $authorized_operations = array_filter($this->operations, function ($operation) {
            return in_array($operation->get_type(), ['auth', 'recurring']);
        });

        if (empty ($authorized_operations)) {
            return null;
        }

        $operation = reset($authorized_operations);
        $amount = $operation->get_sum_initial()->get_amount();
        $remaining = $amount;

        if ($balance > 0) {
            $remaining = $amount - $balance;
        }

        return $remaining;
    }

    /**
     * Returns the transaction balance
     *
     * @return float|int|null
     * @since 2.0.0
     */
    public function get_balance()
    {
        if (is_null($this->info)) {
            return null;
        }

        return !empty ($this->info->get_sum()) ? $this->info->get_sum()->get_amount() : null;
    }

    /**
     * Returns the current payment type
     *
     * @return string
     * @since 2.0.0
     */
    public function get_current_type()
    {
        if (!$this->has_operations()) {
            return '';
        }

        return $this->get_last_operation()->get_type();
    }

    /**
     * <h2>Returns the last successful transaction operation.</h2>
     *
     * @return ?Ecp_Gateway_Info_Operation
     * @since 2.0.0
     */
    public function get_last_operation()
    {
        // Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
        foreach (array_reverse($this->get_operations()) as $operation) {
            if ($operation->get_status() === Ecp_Gateway_Operation_Status::SUCCESS) {
                return $operation;
            }
        }

        return $this->get_first_operation();
    }

    /**
     * <h2>Returns the first operation info.</h2>
     *
     * @return Ecp_Gateway_Info_Operation
     * @since 2.0.0
     */
    public function get_first_operation()
    {
        $operations = $this->operations;
        usort($operations, [$this, 'sort_operation']);
        return $operations[0];
    }

    /**
     * @param Ecp_Gateway_Info_Operation $a
     * @param Ecp_Gateway_Info_Operation $b
     * @return int
     * @since 2.0.0
     */
    public function sort_operation($a, $b)
    {
        switch (true) {
            case $a->get_created_date() > $b->get_created_date():
                return 1;
            case $a->get_created_date() < $b->get_created_date():
                return -1;
            default:
                return 0;
        }
    }

    /**
     * <h2>Returns result of check operations exists.</h2>
     *
     * @return bool <b>TRUE</b> if operations exists or <b>FALSE</b> otherwise.
     * @since 2.0.0
     */
    public function has_operations()
    {
        return count($this->operations) > 0;
    }

    /**
     * <h2>Returns the last operation by type.</h2>
     *
     * @param string $type <p>
     * Possible values:<br/>
     *      - {@see Ecp_Gateway_Operation_Type::SALE} Payment operation<br/>
     *      - {@see Ecp_Gateway_Operation_Type::REVERSAL} Reversal operation<br/>
     *      - {@see Ecp_Gateway_Operation_Type::REFUND} Refund operation<br/>
     *      - {@see Ecp_Gateway_Operation_Type::RECURRING} Recurring operation<br/>
     *      - {@see Ecp_Gateway_Operation_Type::RECURRING_UPDATE} Update recurring operation<br/>
     *      - {@see Ecp_Gateway_Operation_Type::RECURRING_CANCEL} Cancel recurring operation<br/>
     * </p>
     *
     * @return ?Ecp_Gateway_Info_Operation Operation if exists or <b>NULL</b> otherwise.
     * @since 2.0.0
     */
    public function get_last_operation_of_type($type)
    {
        foreach (array_reverse($this->operations) as $operation) {
            if ($operation->get_type() === $type) {
                return $operation;
            }
        }

        return null;
    }

    /**
     * <h2>Returns the operation by ECOMMPAY request identifier.</h2>
     *
     * @param string $request_id
     *
     * @return ?Ecp_Gateway_Info_Operation Operation if exists or <b>NULL</b> otherwise.
     * @since 2.0.0
     */
    public function get_operation_by_request($request_id)
    {
        ecp_get_log()->info(__('Try get operation by request identifier.', 'woo-ecommpay'));
        ecp_get_log()->debug(__('Request ID:', 'woo-ecommpay'), $request_id);
        ecp_get_log()->debug(__('Count of operations:', 'woo-ecommpay'), count($this->operations));

        foreach ($this->operations as $operation) {
            ecp_get_log()->debug(__('Operation checked request:', 'woo-ecommpay'), $operation->get_request_id());

            if ($operation->get_request_id() === $request_id) {
                ecp_get_log()->info(__('Found required operation information.', 'woo-ecommpay'));
                return $operation;
            }
        }

        ecp_get_log()->info(__('Not found required operation information.', 'woo-ecommpay'));
        return null;
    }

    /**
     * Check if the action we are about to perform is allowed according to the current transaction state.
     *
     * @return boolean
     * @since 2.0.0
     */
    public function is_action_allowed($action)
    {
        $allowed_states = [
            'refund' => [
                Ecp_Gateway_Payment_Status::PARTIALLY_REFUNDED,
                Ecp_Gateway_Payment_Status::PARTIALLY_REVERSED,
                Ecp_Gateway_Payment_Status::SUCCESS
            ],
            'renew' => [
                Ecp_Gateway_Payment_Status::AWAITING_CAPTURE,
            ],
            'recurring' => [
                'subscribe'
            ],
            'subscription' => [
                'success'
            ]
        ];

        return in_array($this->get_info()->get_status(), $allowed_states[$action]);
    }

    /**
     * @return ?int|int[]
     * @since 2.0.0
     */
    public function get_code()
    {
        if (count($this->errors) > 0) {
            $codes = [];

            foreach ($this->errors as $error) {
                $codes[] = $error->get_code();
            }

            return $codes;
        }

        if ($this->has_operations()) {
            return $this->get_last_operation()->get_code();
        }

        return null;
    }

    /**
     * @return ?string|string[]
     * @since 2.0.0
     */
    public function get_message()
    {
        /** @var Ecp_Gateway_Info_Error[] $errors */
        if (count($this->errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->get_message();
            }
            return $messages;
        }

        if ($this->has_operations()) {
            return $this->get_last_operation()->get_message();
        }

        return null;
    }
}
