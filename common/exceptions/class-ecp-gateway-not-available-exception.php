<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Not_Available_Exception class
 *
 * @class   Ecp_Gateway_Not_Available_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class Ecp_Gateway_Not_Available_Exception extends Ecp_Gateway_Exception
{
    /**
     * @param string $message Error message.
     * @param int $code [optional] Error code. Default: {@see Ecp_Gateway_Error::NOT_AVAILABLE}.
     * @param ?Exception $previous [optional] Previous exception. Default: none.
     */
    public function __construct(
        $message = '',
        $code = Ecp_Gateway_Error::NOT_AVAILABLE,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritDoc
     * @return string[][]
     */
    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ERROR,
            ],
        ];
    }
}