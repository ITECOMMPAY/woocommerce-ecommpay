<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Key_Not_Found_Exception class
 *
 * @class   Ecp_Gateway_Key_Not_Found_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class Ecp_Gateway_Key_Not_Found_Exception extends Ecp_Gateway_Exception
{
    /**
     * Contain corrupted key name.
     *
     * @var int|string
     */
    private $key;

    /**
     * Contain list of available key names.
     *
     * @var int[]|string[]
     */
    private $available;

    /**
     * Exception constructor.
     *
     * @param int|string $key Corrupted key name.
     * @param int[]|string[] $available List of available key names.
     * @param int $code [optional] Error code. Default: {@see Ecp_Gateway_Error::KEY_NOT_FOUND}.
     * @param string $message [optional] Base error message. Default: none.
     * @param ?Exception $previous Previous exception. Default: none.
     */
    public function __construct(
        $key,
        $available,
        $code = Ecp_Gateway_Error::KEY_NOT_FOUND,
        $message = null,
        Exception $previous = null
    ) {
        $this->key = $key;
        $this->available = $available;

        if ($message === null) {
            $message = _x('Key not found in the current array.', 'Exception message', 'woo-ecommpay');
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns corrupted key name.
     *
     * @return int|string
     */
    final public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns list of available key names.
     *
     * @return int[]|string[]
     */
    final public function getAvailable()
    {
        return $this->available;
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
                sprintf(_x('Searched key: %s', 'Exception message', 'woo-ecommpay'), $this->getKey()),
                WC_Log_Levels::ERROR,
            ],
            [
                sprintf(
                    _x('Available keys: %s', 'Exception message', 'woo-ecommpay'),
                    implode(', ', $this->getAvailable())
                ),
                WC_Log_Levels::ERROR,
            ]
        ];
    }
}
