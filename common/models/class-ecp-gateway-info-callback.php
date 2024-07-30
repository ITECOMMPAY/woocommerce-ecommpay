<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Callback
 *
 * @class    Ecp_Gateway_Info_Callback
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Callback extends Ecp_Gateway_Json
{
    // region Constants

    /**
     * Label for payment instrument information.
     */
    const FIELD_ACCOUNT = 'account';

    /**
     * Label for 3-D Secure data information.
     */
    const FIELD_ACS = 'acs';

    /**
     * Label for customer information.
     */
    const FIELD_CUSTOMER = 'customer';

    /**
     * Label for the Risk Control System decision related to the payment.
     */
    const FIELD_DECISION = 'decision';

    /**
     * Label for the array of strings with the messages from the Risk Control System.
     */
    const FIELD_DECISION_MESSAGE = 'decision_message';

    /**
     * Label for array of errors information.
     */
    const FIELD_ERRORS = 'errors';

    /**
     * Label for operation information.
     */
    const FIELD_OPERATION = 'operation';

    /**
     * Label for payment information.
     */
    const FIELD_PAYMENT = 'payment';

    /**
     * Label for identifier of merchant project received from ECOMMPAY.
     */
    const FIELD_PROJECT_ID = 'project_id';

    /**
     * Label for the data from the payment provider that are required to complete the payment or to compile reports.
     */
    const FIELD_PROVIDER_EXTRA_FIELDS = 'provider_extra_fields';

    /**
     * Label for recurring information.
     */
    const FIELD_RECURRING = 'recurring';

    /**
     * Label for redirect information.
     */
    const FIELD_REDIRECT_DATA = 'redirect_data';

    // endregion

    /**
     * Callback information constructor.
     *
     * @param array $data [optional] <p>JSON-data as array.</p>
     */
    public function __construct(array $data = [])
    {
        $this->register(self::FIELD_ACCOUNT, Ecp_Gateway_Info_Account::class);
        $this->register(self::FIELD_ACS, Ecp_Gateway_Info_ACS::class);
        $this->register(self::FIELD_CUSTOMER, Ecp_Gateway_Info_Customer::class);
        $this->register(self::FIELD_PAYMENT, Ecp_Gateway_Info_Payment::class);
        $this->register(self::FIELD_RECURRING, Ecp_Gateway_Info_Recurring::class);
        $this->register(self::FIELD_OPERATION, Ecp_Gateway_Info_Operation::class);

        parent::__construct($data);
    }

    /**
     * <h2>Returns the payment instrument information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Account
     */
    public function get_account()
    {
        if ($this->try_get_json($account, self::FIELD_ACCOUNT)) {
            return $account;
        }

        return null;
    }

    /**
     * <h2>Returns the 3-D Secure data information.</h2>
     *
     * @return ?Ecp_Gateway_Info_ACS
     */
    public function get_acs()
    {
        if ($this->try_get_json($acs, self::FIELD_ACS)) {
            return $acs;
        }

        return null;
    }

    /**
     * <h2>Returns the customer information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Customer
     */
    public function get_customer()
    {
        if ($this->try_get_json($customer, self::FIELD_CUSTOMER)) {
            return $customer;
        }

        return null;
    }

    /**
     * <h2>Returns the Risk Control System decision related to the payment.</h2>
     *
     * @return ?string
     */
    public function get_decision()
    {
        if ($this->try_get_string($decision, self::FIELD_DECISION)) {
            return $decision;
        }

        return null;
    }

    /**
     * <h2>Returns the messages from the Risk Control System related to the decision regarding the payment.</h2>
     *
     * @return string[]
     */
    public function get_decision_message()
    {
        $this->try_get_array($messages, self::FIELD_DECISION_MESSAGE);
        return $messages;
    }

    /**
     * <h2>Returns list of errors information.</h2>
     *
     * @return Ecp_Gateway_Info_Error[]
     */
    public function get_errors()
    {
        $this->try_get_array($errors, self::FIELD_ERRORS);
        return $errors;
    }

    /**
     * <h2>Returns the operation information.</h2>
     * @return Ecp_Gateway_Info_Operation
     */
    public function get_operation()
    {
        if ($this->try_get_json($operation, self::FIELD_OPERATION)) {
            return $operation;
        }

        return null;
    }

    /**
     * <h2>Returns the payment information.</h2>
     *
     * @return Ecp_Gateway_Info_Payment
     */
    public function get_payment()
    {
        $this->try_get_json($payment, self::FIELD_PAYMENT);
        return $payment;
    }

    /**
     * <h2>Returns the project identifier in the payment platform ECOMMPAY.</h2>
     *
     * @return int
     */
    public function get_project_id()
    {
        $this->try_get_int($id, self::FIELD_PROJECT_ID);
        return $id;
    }

    /**
     * <h2>Returns the object that contains the data from the payment provider.</h2>
     * <p>This data are required to complete the payment or to compile reports.</p>
     *
     * @return ?array
     */
    public function get_provider_extra_fields()
    {
        if ($this->try_get_array($extra_data, self::FIELD_PROVIDER_EXTRA_FIELDS)) {
            return $extra_data;
        }

        return null;
    }

    /**
     * <h2>Returns the recurring information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Recurring
     */
    public function get_recurring()
    {
        if ($this->try_get_recurring($recurring, self::FIELD_RECURRING)) {
            return $recurring;
        }

        return null;
    }

    /**
     * <h2>Returns result of checking for the existence of recurring information.</h2>
     *
     * @param ?Ecp_Gateway_Info_Recurring <p>Container for Recurring information object - variable by reference.</p>
     * @return bool <b>TRUE</b> if the recurring information exists or <b>FALSE</b> otherwise.
     */
    public function try_get_recurring(&$recurring)
    {
        return $this->try_get_json($recurring, self::FIELD_RECURRING);
    }

    /**
     * <h2>Returns the error message
     * @return void
     */
    public function get_message()
    {

    }

    public function get_payment_sum(): ?Ecp_Gateway_Json
    {
        $payment = $this->get_payment();
        return $payment ? $payment->get_sum() : null;
    }

    public function get_payment_amount_minor(): ?int
    {
        $payment_sum = $this->get_payment_sum();
        return $payment_sum ? $payment_sum->get_amount() : null;
    }

    public function get_payment_amount(): ?float
    {
        $payment_amount_minor = $this->get_payment_amount_minor();
        $currency = $this->get_payment_currency();
        if (!$payment_amount_minor || !$currency) {
            return null;
        }
        return ecp_price_multiplied_to_float($payment_amount_minor, $currency);
    }

    public function get_payment_currency(): ?string
    {
        if (!$payment_sum = $this->get_payment_sum()) {
            return null;
        }
        $currency = $payment_sum->get_currency();
        return $currency ? strtoupper($currency) : null;
    }

    /**
     * @inheritDoc
     */
    protected function unpackRules()
    {
        return [
            self::FIELD_PROJECT_ID => function ($value) {
                return (int) $value;
            },
            self::FIELD_ERRORS => function ($value) {
                foreach ($value as &$item) {
                    $item = new Ecp_Gateway_Info_Error($item);
                }
                return $value;
            }
        ];
    }
}
