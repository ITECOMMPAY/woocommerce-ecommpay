<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Not_Implemented_Exception class
 *
 * @class   Ecp_Gateway_Not_Implemented_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @abstract
 */
class Ecp_Gateway_Not_Implemented_Exception extends Ecp_Gateway_Exception
{
    /**
     * @var string Default exception message.
     */
    const DEFAULT_MESSAGE = 'Object is not instance of required interface.';

    /**
     * @var string
     */
    private $interface;

    /**
     * @var string
     */
    private $object;

    /**
     * Object is not implement required interface exception constructor.
     *
     * @param object $object
     * @param string $interface
     * @param int $code [optional] Error code. Default: {@see Ecp_Gateway_Error::NOT_IMPLEMENTED}.
     * @param string $message [optional] Base error message.
     *                                   Default: {@see Ecp_Gateway_Not_Implemented_Exception::DEFAULT_MESSAGE}.
     * @param ?Exception $previous [optional] Previous exception. Default: none.
     */
    public function __construct(
        $object,
        $interface,
        $code = Ecp_Gateway_Error::NOT_IMPLEMENTED,
        $message = self::DEFAULT_MESSAGE,
        Exception $previous = null
    ) {
        $this->object = get_class($object);
        $this->interface = $interface;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns not implement required interface object name.
     *
     * @return string
     */
    final public function get_object()
    {
        return $this->object;
    }

    /**
     * Returns required interface name.
     *
     * @return string
     */
    final public function get_interface()
    {
        return $this->interface;
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
                sprintf(__('Object name: %s', 'woo-ecommpay'), $this->get_object()),
                WC_Log_Levels::ERROR,
            ],
            [
                sprintf(__('Interface name: %s', 'woo-ecommpay'), $this->get_interface()),
                WC_Log_Levels::ERROR,
            ],
        ];
    }
}
