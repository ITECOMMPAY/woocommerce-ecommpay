<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Operation
 *
 * Contains information about the operation
 *
 * @class    Ecp_Gateway_Info_Operation
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Operation extends Ecp_Gateway_Json
{
    // region Constants

    /**
     * Label for unique ID of the operation
     */
    const FIELD_ID = 'id';

    /**
     * Label for unique ID of the last request related to the operation
     */
    const FIELD_REQUEST_ID = 'request_id';

    /**
     * Label for operation type
     */
    const FIELD_TYPE = 'type';

    /**
     * Label for operation status
     */
    const FIELD_STATUS = 'status';

    /**
     * Label for date and time the payment status was last updated
     */
    const FIELD_DATE = 'date';

    /**
     * Label for date and time the operation was created.
     */
    const FIELD_CREATED_DATE = 'created_date';

    /**
     * Label for unified payment provider response code
     */
    const FIELD_CODE = 'code';

    /**
     * Label for unified message from the payment provider.
     */
    const FIELD_MESSAGE = 'message';

    /**
     * Label for the indicator that shows the result of the 3‑D Secure customer authentication.
     */
    const FIELD_ECI = 'eci';

    /**
     * Label for object that contains the amount and currency of the operation as specified in the initial request.
     */
    const FIELD_SUM_INITIAL = 'sum_initial';

    /**
     * Label for object that contains the currency of the payment provider account and the initial amount denominated
     * in this currency
     */
    const FIELD_SUM_CONVERTED = 'sum_converted';

    /**
     * Label for object that contains external provider information about the result of the operation.
     */
    const FIELD_PROVIDER = 'provider';

    // endregion

    /**
     * <h2>Information constructor.</h2>
     *
     * @param array $data [optional] Json-data as array.
     */
    public function __construct(array $data = [])
    {
        $this->register(self::FIELD_SUM_INITIAL, Ecp_Gateway_Info_Sum::class);
        $this->register(self::FIELD_SUM_CONVERTED, Ecp_Gateway_Info_Sum::class);
        $this->register(self::FIELD_PROVIDER, Ecp_Gateway_Info_Provider::class);

        parent::__construct($data);
    }

    /**
     * <h2>Returns the unique ID of the operation.</h2>
     *
     * @return int
     */
    public function get_id()
    {
        $this->try_get_int($id, self::FIELD_ID);
        return $id;
    }

    /**
     * <h2>Returns the operation type.</h2>
     *
     * @return string
     */
    public function get_type()
    {
        $this->try_get_string($type, self::FIELD_TYPE);
        return $type;
    }

    /**
     * <h2>Returns the unique ID of the last request related to the operation.</h2>
     *
     * @return string
     */
    public function get_request_id()
    {
        $this->try_get_string($id, self::FIELD_REQUEST_ID);
        return $id;
    }

    /**
     * <h2>Returns the date and time the payment status was last updated.</h2>
     *
     * @return DateTime
     */
    public function get_date()
    {
        $this->try_get_object($date, self::FIELD_DATE);
        return $date;
    }

    /**
     * <h2>Returns the results of check date exists and put value to container.</h2>
     *
     * @return bool <p><b>TRUE</b> if Date exists or <b>FALSE</b> otherwise</p>
     */
    public function try_get_date(&$date)
    {
        return $this->try_get_object($date, self::FIELD_DATE);
    }

    /**
     * <h2>Returns the date and time the operation was created.</h2>
     *
     * @return DateTime
     */
    public function get_created_date()
    {
        $this->try_get_object($date, self::FIELD_CREATED_DATE);
        return $date;
    }

    /**
     * <h2>Returns the operation status.</h2>
     *
     * @return string
     */
    public function get_status()
    {
        $this->try_get_string($status, self::FIELD_STATUS);
        return $status;
    }

    /**
     * <h2>Returns the unified payment provider response code.</h2>
     *
     * @return int
     */
    public function get_code()
    {
        $this->try_get_int($code, self::FIELD_CODE);
        return $code;
    }

    /**
     * <h2>Returns the unified message from the payment provider.</h2>
     *
     * @return string
     */
    public function get_message()
    {
        $this->try_get_string($message, self::FIELD_MESSAGE);
        return $message;
    }

    /**
     * <h2>Returns the initial price information.</h2>
     * <p>Object that contains the amount and currency of the operation as specified in the initial request.</p>
     *
     * @return Ecp_Gateway_Info_Sum
     */
    public function get_sum_initial()
    {
        $this->try_get_json($sum, self::FIELD_SUM_INITIAL, new Ecp_Gateway_Info_Sum());
        return $sum;
    }

    /**
     * <h2>Returns the converts price information.</h2>
     * <p>Price contains the currency of the payment provider account and the initial amount denominated
     * in this currency.</p>
     *
     * @return Ecp_Gateway_Info_Sum
     */
    public function get_sum_converts()
    {
        $this->try_get_json($sum, self::FIELD_SUM_CONVERTED, new Ecp_Gateway_Info_Sum());
        return $sum;
    }

    /**
     * <h2>Returns the indicator that shows the result of the 3‑D Secure customer authentication.</h2>
     *
     * @return string
     */
    public function get_eci()
    {
        $this->try_get_string($eci, self::FIELD_ECI);
        return $eci;
    }

    /**
     * <h2>Returns the provider information.</h2>
     * <p>Provider information contains external provider information about the result of the operation.</p>
     *
     * @return Ecp_Gateway_Info_Provider
     */
    public function get_provider()
    {
        $this->try_get_json($provider, self::FIELD_PROVIDER, new Ecp_Gateway_Info_Provider());
        return $provider;
    }

    protected function unpackRules()
    {
        return [
            self::FIELD_DATE => function ($value) {
                return DateTime::createFromFormat(DateTime::RFC3339, $value);
            },
            self::FIELD_CREATED_DATE => function ($value) {
                return DateTime::createFromFormat(DateTime::RFC3339, $value);
            }
        ];
    }
}