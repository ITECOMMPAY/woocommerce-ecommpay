<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Duplicate_Exception class
 *
 * @class   Ecp_Gateway_Duplicate_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @internal
 */
class Ecp_Gateway_Duplicate_Exception extends Ecp_Gateway_Exception
{
    /**
     * @var string
     * @since 2.0.0
     */
    private $key;

    /**
     * Exception constructor.
     *
     * @param mixed $key The duplicate key name.
     * @param int $errorCode [optional] Error code. Default: {@see Ecp_Gateway_Error::DUPLICATE}.
     * @param string $message [optional] Exception message. Default: none.
     * @param ?Exception $previous [optional] Previous exception. Default: none.
     * @since 2.0.0
     */
    public function __construct(
        $key,
        $errorCode = Ecp_Gateway_Error::DUPLICATE,
        $message = null,
        Exception $previous = null
    ) {
        $this->key = $key;

        if ($message === null) {
            $message = _x('Key is already exists in the current array', 'Exception message', 'woo-ecommpay');
        }

        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * Returns duplicate key name.
     *
     * @since 2.0.0
     * @return mixed
     */
    final public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return string[][]
     */
    protected function prepare_message()
    {
        return [
            [
                $this->get_base_message(),
                WC_Log_Levels::ALERT
            ],
            [
                sprintf(
                    /* translators: %s: Duplicate key name */
                    _x('Duplicated key: %s', 'Exception message', 'woo-ecommpay'),
                    $this->getKey()
                ),
                WC_Log_Levels::ERROR
            ]
        ];
    }
}
