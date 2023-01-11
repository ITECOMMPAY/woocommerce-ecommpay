<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Response
 *
 * @class    Ecp_Gateway_Info_Response
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Response extends Ecp_Gateway_Json
{
    /**
     * Identifier of merchant project received from ECOMMPAY.
     */
    const FIELD_PROJECT_ID = 'project_id';

    const FIELD_PAYMENT_ID = 'payment_id';

    /**
     * Transaction identifier in ECOMMPAY.
     */
    const FIELD_REQUEST_ID = 'request_id';

    /**
     * Response status
     */
    const FIELD_STATUS = 'status';

    const FIELD_CODE = 'code';

    const FIELD_MESSAGE = 'message';

    public function get_payment_id()
    {
        $this->try_get_string($id, self::FIELD_PAYMENT_ID);
        return $id;
    }

    public function get_request_id()
    {
        $this->try_get_string($id, self::FIELD_REQUEST_ID);
        return $id;
    }

    /**
     * @return int
     */
    public function get_project_id()
    {
        $this->try_get_int($id, self::FIELD_PROJECT_ID);
        return $id;
    }

    /**
     * Returns the current payment state
     *
     * @return string
     */
    public function get_status()
    {
        $this->try_get_string($status, self::FIELD_STATUS);
        return $status;
    }

    public function get_code()
    {
        $this->try_get_string($code, self::FIELD_CODE, 0);
        return $code;
    }

    public function get_message()
    {
        $this->try_get_string($message, self::FIELD_MESSAGE, __('Undefined error', 'woo-ecommpay'));
        return $message;
    }
}
