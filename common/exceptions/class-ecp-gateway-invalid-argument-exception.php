<?php

defined('ABSPATH') || exit;

/**
 * Invalid argument exception in plugin.
 *
 * Ecp_Gateway_Invalid_Argument_Exception class
 *
 * @class   Ecp_Gateway_Invalid_Argument_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class Ecp_Gateway_Invalid_Argument_Exception extends Ecp_Gateway_Exception
{
    /**
     * Argument name.
     *
     * @var string
     */
    private $arg;

    /**
     * Expected argument type.
     *
     * @var string
     */
    private $expected;

    /**
     * Received argument type.
     *
     * @var string
     */
    private $received;

    /**
     * Exception constructor.
     *
     * @param string $arg Wrong argument name.
     * @param string $value Expected argument type.
     * @param string $pattern Received argument type.
     * @param int $errorCode [optional] Error code. Default: {@see Ecp_Gateway_Error::INVALID_ARGUMENT}.
     * @param ?string $message [optional] Base exception message. Default: none.
     * @param ?Exception $previous [optional] Previous exception. Default: none.
     */
    public function __construct(
        $arg,
        $value,
        $pattern,
        $errorCode = Ecp_Gateway_Error::INVALID_ARGUMENT,
        $message = null,
        Exception $previous = null
    ) {
        $this->arg = $arg;
        $this->expected = $value;
        $this->received = $pattern;

        if ($message === null) {
            $message = _x('Invalid argument type', 'Exception message', 'woo-ecommpay');
        }

        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Returns argument name.
     *
     * @return string
     */
    final public function get_arg()
    {
        return $this->arg;
    }

    /**
     * Returns expected argument type.
     *
     * @return string
     */
    final public function get_expected()
    {
        return $this->expected;
    }

    /**
     * Returns received argument type.
     *
     * @return string
     */
    final public function get_received()
    {
        return $this->received;
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
                WC_Log_Levels::ALERT,
            ],
            [
                sprintf(_x('Argument name: %s', 'Exception message', 'woo-ecommpay'), $this->get_arg()),
                WC_Log_Levels::ERROR,
            ],
            [
                sprintf(_x('Expected type: %s', 'Exception message', 'woo-ecommpay'), $this->get_expected()),
                WC_Log_Levels::ERROR,
            ],
            [
                sprintf(_x('Received type: %s', 'Exception message', 'woo-ecommpay'), $this->get_received()),
                WC_Log_Levels::ERROR,
            ],
        ];
    }
}
