<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_API_Exception class
 *
 * @class   Ecp_Gateway_API_Exception
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 * @category Class
 * @internal
 */
class Ecp_Gateway_API_Exception extends Ecp_Gateway_Exception
{
    /**
     * Contains the curl object instance.
     *
     * @var ?string
     * @since 2.0.0
     */
    private $curl_request_data;

    /**
     * Contains the curl url
     *
     * @var ?string
     * @since 2.0.0
     */
    private $curl_request_url;

    /**
     * Contains the curl response data
     *
     * @var ?string
     * @since 2.0.0
     */
    private $curl_response_data;

    /**
     * Redefine the exception so message isn't optional
     *
     * @param string $message Base error message
     * @param int $code [optional] Error code. Default: {@see Ecp_Gateway_Error::UNDEFINED_API_ERROR}.
     * @param ?string $curl_request_url [optional] Request URL. Default: null.
     * @param ?string $curl_request_data [optional] Request data. Default: null.
     * @param ?string $curl_response_data [optional] Response data. Default: null.
     * @param ?Exception $previous [optional] Previous exception. Default: null.
     * @since 2.0.0
     */
    public function __construct(
        $message,
        $code = Ecp_Gateway_Error::UNDEFINED_API_ERROR,
        $curl_request_url = null,
        $curl_request_data = null,
        $curl_response_data = null,
        Exception $previous = null
    )
    {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);

        $this->curl_request_data = $curl_request_data;
        $this->curl_request_url = $curl_request_url;
        $this->curl_response_data = $curl_response_data;
    }

    final public function get_curl_request_url()
    {
        return $this->curl_request_url;
    }

    final public function get_curl_request_data()
    {
        return $this->curl_request_data;
    }

    final public function get_curl_response_data()
    {
        return $this->curl_response_data;
    }

    /**
     * Stores the exception dump in the WooCommerce system logs
     *
     * @since 2.0.0
     * @return string[][]
     */
    protected function prepare_message()
    {
        $data = [
            [$this->get_base_message(), WC_Log_Levels::CRITICAL],
            [sprintf(__('ECOMMPAY API Exception file: %s', 'woo-ecommpay'), $this->getFile()), WC_Log_Levels::ERROR],
            [sprintf(__('ECOMMPAY API Exception line: %s', 'woo-ecommpay'), $this->getLine()), WC_Log_Levels::ERROR],
            [sprintf(__('ECOMMPAY API Exception code: %s', 'woo-ecommpay'), $this->getCode()), WC_Log_Levels::ERROR],
        ];

        if ($this->get_curl_request_url()) {
            $data[] = [
                sprintf(
                    __('ECOMMPAY API Exception Request URL: %s', 'woo-ecommpay'),
                    $this->get_curl_request_url()
                ),
                WC_Log_Levels::ERROR
            ];
        }

        if ($this->get_curl_request_data()) {
            $data[] = [
                sprintf(
                    __('ECOMMPAY API Exception Request DATA: %s', 'woo-ecommpay'),
                    $this->get_curl_request_data()
                ),
                WC_Log_Levels::ERROR
            ];
        }

        if ($this->get_curl_response_data()) {
            $data[] = [
                sprintf(
                    __('ECOMMPAY API Exception Response DATA: %s', 'woo-ecommpay'),
                    $this->get_curl_response_data()
                ),
                WC_Log_Levels::ERROR
            ];
        }

        $data[] = [
            sprintf(
                __('Stack trace: %s', 'woo-ecommpay'),
                implode(PHP_EOL, $this->get_trace_as_array_string())
            ),
            WC_Log_Levels::DEBUG
        ];

        return $data;
    }

    private function get_trace_as_array_string()
    {
        $result = [''];

        foreach ($this->getTrace() as $i => $item) {
            $result[] = sprintf(
                '[%d] %s%s%s(%s) - %s::%d',
                $i,
                isset($item['class']) ? $item['class'] : '',
                isset($item['type']) ? $item['type'] : '',
                $item['function'],
                implode(', ', $this->prepare_trace_args(isset($item['args']) ? $item['args'] : [])),
                $item['file'],
                $item['line']
            );
        }

        return $result;
    }

    private function prepare_trace_args($args)
    {
        if (!is_array($args)) {
            return [];
        }

        foreach ($args as &$arg) {
            switch (true) {
                case is_object($arg):
                    $arg = get_class($arg);
                    break;
                case is_resource($arg):
                    $arg = '** Resource **';
                    break;
                case is_array($arg):
                    $arg = '[' . implode(', ', $this->prepare_trace_args($arg)) . ']';
                    break;
                case is_null($arg):
                    $arg = 'NULL';
                    break;
                case is_string($arg):
                    $arg = '"' . $arg . '"';
                    break;
                case is_callable($arg):
                    $arg = 'Closure("' . $arg . '")';
                    break;
            }
        }

        return $args;
    }
}
