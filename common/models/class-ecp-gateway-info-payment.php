<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Payment
 *
 * Contains the payment data
 *
 * @class    Ecp_Gateway_Info_Payment
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Payment extends Ecp_Gateway_Json
{
    /**
     * Label for unique ECOMMPAY ID of the payment.
     */
    const FIELD_ID = 'id';

    /**
     * Label for payment type.
     */
    const FIELD_TYPE = 'type';

    /**
     * Label for payment status.
     */
    const FIELD_STATUS = 'status';

    /**
     * Label for date and time of the last payment status update.
     */
    const FIELD_DATE = 'date';

    /**
     * Label for payment method
     */
    const FIELD_METHOD = 'method';

    /**
     * Label for the description of the payment as specified in the initial request.
     */
    const FIELD_DESCRIPTION = 'description';

    /**
     * Label for sum in the initial request.
     */
    const FIELD_SUM = 'sum';

    /**
     * Label for the region of the country where the card operation is processed.
     */
    const FIELD_REGION = 'region';

    /**
     * Label for the information about per-operation fee charged by ecommpay.
     */
    const FIELD_OPERATION_FEE = 'operation_fee';

    /**
     * Label for unique ECOMMPAY identifier of a refund.
     */
    const FIELD_MERCHANT_REFUND_ID = 'merchant_refund_id';

    /**
     * Label for the time when the customer can retry the payment after an initial decline.
     */
    const FIELD_TIMEOUT_ATTEMPTS = 'attempts_timeout';

    /**
     * Label for the indicator that shows if the customer can retry the payment.
     */
    const FIELD_IS_NEW_ATTEMPTS_AVAILABLE = 'is_new_attempts_available';

    /**
     * Label for the indicator shows whether you need to redirect the customer.
     */
    const FIELD_CASCADING_WITH_REDIRECT = 'cascading_with_redirect';

    /**
     * <h2>Payment information constructor.</h2>
     *
     * @param array $data [optional] <p>Json-data as array.</p>
     */
    public function __construct(array $data = [])
    {
        $this->register(self::FIELD_SUM, Ecp_Gateway_Info_Sum::class);
        $this->register(self::FIELD_OPERATION_FEE, Ecp_Gateway_Info_Operation_Fee::class);

        parent::__construct($data);
    }

    /**
     * <h2>Returns unique ECOMMPAY ID of the payment.</h2>
     *
     * @return string
     */
    public function get_id()
    {
        $this->try_get_string($id, self::FIELD_ID);
        return $id;
    }

    /**
     * <h2>Returns the payment type.</h2>
     *
     * @return ?string
     */
    public function get_type()
    {
        if ($this->try_get_string($type, self::FIELD_TYPE)) {
            return $type;
        }

        return null;
    }

    /**
     * <h2>Returns the date and time of the last payment status update.</h2>
     *
     * @return ?DateTime
     */
    public function get_date()
    {
        if ($this->try_get_object($date, self::FIELD_DATE)) {
            return $date;
        }

        return null;
    }

    /**
     * <h2>Returns the payment status.</h2>
     *
     * @return string
     */
    public function get_status()
    {
        $this->try_get_string($status, self::FIELD_STATUS, 'error');
        return $status;
    }

    /**
     * <h2>Returns the payment method.</h2>
     *
     * @return ?string
     */
    public function get_method()
    {
        if ($this->try_get_string($method, self::FIELD_METHOD)) {
            return $method;
        }

        return null;
    }

    /**
     * <h2>Returns the payment description.</h2>
     *
     * @return ?string
     */
    public function get_description()
    {
        if ($this->try_get_description($description)) {
            return $description;
        }

        return null;
    }

    public function try_get_description(&$description)
    {
        return $this->try_get_string($description, self::FIELD_DESCRIPTION);
    }

    /**
     * <h2>Returns the information about payment amount, including refunds.</h2>
     *
     * @return ?Ecp_Gateway_Info_Sum
     */
    public function get_sum()
    {
        if ($this->try_get_sum($sum)) {
            return $sum;
        }

        return null;
    }

    /**
     * <h2>Returns the result of checking the availability of information about the payment amount.</h2>
     *
     * @param Ecp_Gateway_Info_Sum &$sum <p>Container fo Sum information object.</p>
     * @return bool <b>TRUE</b> if information exists or <b>FALSE</b> otherwise.
     */
    public function try_get_sum(&$sum)
    {
        return $this->try_get_json($sum, self::FIELD_SUM);
    }

    /**
     * <h2>Returns the region of the country where the card operation is processed.</h2>
     *
     * @return ?string
     */
    public function get_region()
    {
        if ($this->try_get_string($region, self::FIELD_REGION)) {
            return $region;
        }

        return null;
    }

    /**
     * <h2>Returns unique ECOMMPAY identifier of a refund.</h2>
     *
     * @return ?string
     */
    public function get_merchant_refund_id()
    {
        if ($this->try_get_string($id, self::FIELD_MERCHANT_REFUND_ID)) {
            return $id;
        }

        return null;
    }

    public function try_get_operation_fee(&$operation_fee)
    {
        return $this->try_get_json($operation_fee, self::FIELD_OPERATION_FEE);
    }

    /**
     * <h2>Returns the information about per-operation fee charged by ecommpay.</h2>
     *
     * @return ?Ecp_Gateway_Info_Operation_Fee
     */
    public function get_operation_fee()
    {
        if ($this->try_get_operation_fee($operation_fee)) {
            return $operation_fee;
        }

        return null;
    }

    /**
     * <h2>Returns the time when the customer can retry the payment after an initial decline.</h2>
     * <p>This parameter is issued, if the "Try Again" functionality is enabled in Payment Page.</p>
     *
     * @return bool
     */
    public function get_timeout_attempts()
    {
        $this->try_get_bool($flag, self::FIELD_TIMEOUT_ATTEMPTS);
        return $flag;
    }

    /**
     * <h2>Returns the indicator that shows if the customer can retry the payment.</h2>
     * <p>This parameter is issued, if the "Try Again" functionality is enabled in Payment Page</p>
     *
     * @return bool <b>TRUE</b> if customer can retry the payment or <b>FALSE</b> otherwise.
     */
    public function get_is_new_attempts_available()
    {
        $this->try_get_bool($flag, self::FIELD_IS_NEW_ATTEMPTS_AVAILABLE);
        return $flag;
    }

    /**
     * <h2>Returns cascading with redirect indicator.</h2>
     * <p>The indicator shows whether you need to redirect the customer to a different ACS URL to retry the 3D-Secure
     * authentication when an external provider declines the operation, and to display a page with an error message
     * and the Try Again button.</p>
     *
     * @return bool
     */
    public function get_cascading_with_redirect()
    {
        $this->try_get_bool($flag, self::FIELD_CASCADING_WITH_REDIRECT);
        return $flag;
    }

    /**
     * @inheritDoc
     */
    protected function packRules()
    {
        return [
            self::FIELD_DATE => function ($value) {
                return $value->format(DateTime::RFC3339);
            },
        ];
    }

    /**
     * @inheritDoc
     */
    protected function unpackRules()
    {
        return [
            self::FIELD_DATE => function ($value) {
                return DateTime::createFromFormat(DateTime::RFC3339, $value);
            },
            self::FIELD_IS_NEW_ATTEMPTS_AVAILABLE => function ($value) {
                return (bool) $value;
            },
            self::FIELD_CASCADING_WITH_REDIRECT => function ($value) {
                return (bool) $value;
            }
        ];
    }
}