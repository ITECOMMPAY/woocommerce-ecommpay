<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Status
 *
 * @class    Ecp_Gateway_Info_Callback
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Status extends Ecp_Gateway_Json
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
     * Label for array of errors information.
     */
    const FIELD_ERRORS = 'errors';

    /**
     * Label for operation information.
     */
    const FIELD_OPERATIONS = 'operations';

    /**
     * Label for payment information.
     */
    const FIELD_PAYMENT = 'payment';

    /**
     * Label for identifier of merchant project received from ECOMMPAY.
     */
    const FIELD_PROJECT_ID = 'project_id';

    const FIELD_GENERAL = 'general';

    /**
     * Label for recurring information.
     */
    const FIELD_RECURRING = 'recurring';

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
     * <h2>Returns the information about transactions.</h2>
     * @return Ecp_Gateway_Info_Operation[]
     */
    public function get_operations()
    {
        if ($this->try_get_array($operation, self::FIELD_OPERATIONS)) {
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
        $this->try_get_payment($payment);
        return $payment;
    }

    public function try_get_payment(&$payment)
    {
        return $this->try_get_json($payment, self::FIELD_PAYMENT);
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
     * <h2>Returns the recurring information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Recurring
     */
    public function get_recurring()
    {
        if ($this->try_get_json($recurring, self::FIELD_RECURRING)) {
            return $recurring;
        }

        return null;
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
            self::FIELD_OPERATIONS => function ($value) {
                foreach ($value as &$item) {
                    $item = new Ecp_Gateway_Info_Operation($item);
                }

                return $value;
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
