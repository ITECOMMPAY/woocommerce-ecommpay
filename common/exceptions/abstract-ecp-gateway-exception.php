<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Exception abstract class
 *
 * @class   Ecp_Gateway_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @abstract
 * @internal
 */
abstract class Ecp_Gateway_Exception extends Exception
{
    /**
     * <h2>Base exception message.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    private $base_message;

    /**
     * <h2>Base exception constructor.</h2>
     *
     * @param string $message <p>Error message.</p>
     * @param int $code [optional] <p>Error code. Default: {@see Ecp_Gateway_Error::UNKNOWN_ERROR}.</p>
     * @param ?Exception $previous [optional] <p>Previous exception. Default: null.</p>
     * @since 2.0.0
     */
    public function __construct($message, $code = Ecp_Gateway_Error::UNKNOWN_ERROR, Exception $previous = null)
    {
        $this->set_base_message($message);

        parent::__construct($this->get_formatted_message(), $code, $previous);
    }

    /**
     * <h2>Writes exception to log.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    final public function write_to_logs()
    {
        foreach ($this->prepare_message() as $value) {
            if (is_array($value)) {
                list($value, $level) = $value;
            } else {
                $level = WC_Log_Levels::ERROR;
            }

            ecp_get_log()->add($value, $level);
        }
    }

    /**
     * <h2>Returns prepared error message as string array.</h2>
     *
     * @since 2.0.0
     * @return string[]
     */
    abstract protected function prepare_message();

    /**
     * <h2>Sets the base error message.</h2>
     *
     * @param string $message <p>Base error message</p>
     * @since 2.0.0
     * @return void
     */
    final protected function set_base_message($message)
    {
        $this->base_message = $message;
    }

    /**
     * <h2>Returns the base error message.</h2>
     *
     * @since 2.0.0
     * @return string <p>Base error message.</p>
     */
    final protected function get_base_message()
    {
        return $this->base_message;
    }

    /**
     * <h2>Returns formatted exception message.</h2>
     *
     * @since 2.0.0
     * @return string <p>Formatted exception message.</p>
     */
    private function get_formatted_message()
    {
        return $this->get_base_message();
    }
}