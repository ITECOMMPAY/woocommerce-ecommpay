<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Logic_Exception class
 *
 * @class   Ecp_Gateway_Logic_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @category Class
 */
class Ecp_Gateway_Logic_Exception extends Ecp_Gateway_Exception
{

    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ERROR
            ]
        ];
    }
}