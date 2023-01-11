<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Error class
 *
 * @class   Ecp_Gateway_Error
 * @since   2.0.0
 * @package Ecp_Gateway/Exceptions
 */
class Ecp_Gateway_Error
{
    const UNKNOWN_ERROR = 1;
    const NOT_AVAILABLE = 10;
    const NOT_IMPLEMENTED = 11;
    const INVALID_ARGUMENT = 1000;
    const KEY_NOT_FOUND = 1001;
    const OVERFLOW = 1002;
    const DUPLICATE = 1003;
    const INVALID_VALUE = 1004;
    const INVALID_KEY = 1005;
    const UNDEFINED_API_ERROR = 2000;
}