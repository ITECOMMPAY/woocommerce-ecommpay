<?php

defined('ABSPATH') || exit;

/**
 * <h2>Internal logger.</h2>
 *
 * @class    Ecp_Gateway_Log
 * @since    2.0.0
 * @package  Ecp_Gateway/Log
 * @category Class
 * @internal
 */
class Ecp_Gateway_Log extends Ecp_Gateway_Registry
{
    /**
     * <h2>Logger domain.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    const ECOMMPAY_DOMAIN = 'ecp-gateway';

    /**
     * <h2>The domain handler used to name the log.</h2>
     *
     * @var string
     * @since 2.0.0
     */
    private $domain = self::ECOMMPAY_DOMAIN;

    /**
     * <h2>Threshold level.</h2>
     *
     * @var ?int
     * @since 2.0.0
     */
    private $threshold = null;

    /**
     * <h2>The WC_Logger instance.</h2>
     *
     * @var WC_Logger
     * @since 2.0.0
     */
    private $logger;

    /**
     * <h2>List of keys for the masked value.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
    private $masked = [
        'customer_first_name' => '***',
        'customer_last_name' => '***',
        'customer_address' => '***',
        'customer_phone' => '***',
        'customer_email' => '***@***',
        'customer_city' => '***',
        'customer_state' => '***',
        'customer_zip' => '***',
        'customer_country' => '***',
        'billing_address' => '***',
        'billing_city' => '***',
        'billing_country' => '***',
        'billing_postal' => '***',
        'billing_region' => '***',
        'billing_region_code' => '***',
        'signature' => '*SECRET*',
        "card_holder" => '** CARD_HOLDER **',
        'expiry_month' => '**',
        'expiry_year' => '****',
        'phone' => '***',
        'token' => '** TOKEN **',
        Ecp_Gateway_Settings_Page::OPTION_SECRET_KEY => '*SECRET*',
    ];

    /**
     * @inheritDoc
     * @since 2.0.0
     * @return void
     */
    protected function init()
    {
        $this->init_threshold();
        $this->logger = new WC_Logger();
        $this->start();
    }

    /**
     * <h2>Destructor logger.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function __destruct()
    {
        // Write end separator
        $this->end();
    }

    /**
     * <h2>Adds an emergency level message.</h2>
     * <p>System is unusable.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function emergency($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::EMERGENCY, $data);
    }

    /**
     * <h2>Adds an alert level message.</h2>
     * <p>Action must be taken immediately.</p>
     * <p>Example: Entire website down, database unavailable, etc.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function alert($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::ALERT, $data);
    }

    /**
     * <h2>Adds a critical level message.</h2>
     * <p>Critical conditions.</p>
     * <p>Example: Application component unavailable, unexpected exception.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function critical($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::CRITICAL, $data);
    }

    /**
     * <h2>Adds an error level message.</h2>
     * <p>Runtime errors that do not require immediate action but should typically be logged and monitored.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function error($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::ERROR, $data);
    }

    /**
     * <h2>Adds a warning level message.</h2>
     * <p>Exceptional occurrences that are not errors.</p>
     * <p>Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function warning($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::WARNING, $data);
    }

    /**
     * <h2>Adds a notice level message.</h2>
     * <p>Normal but significant events.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function notice($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::NOTICE, $data);
    }

    /**
     * <h2>Adds an info level message.</h2>
     * <p>Interesting events.</p>
     * <p>Example: User logs in, SQL logs.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function info($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::INFO, $data);
    }

    /**
     * <h2>Adds a debug level message.</h2>
     * <p>Detailed debug information.</p>
     *
     * @param string $message <p>Message to log.</p>
     * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
     * @since 2.0.0
     * @return void
     * @see Ecp_Gateway_Log::add
     */
    public function debug($message, $data = null)
    {
        $this->add($message, WC_Log_Levels::DEBUG, $data);
    }

    /**
     * <h2>Adds message to log.</h2>
     * <p>Uses the build in logging method in WooCommerce.</p>
     * <p>Logs are available inside the System status tab.</p>
     *
     * @param string $message <p>Log message.</p>
     * @param mixed $data <p>Additional data.</p>
     * @param string $level [optional] <p>
     * Level of event message. By default: {@see WC_Log_Levels::INFO}.<br/>
     * Possible values:<br/>
     * {@see WC_Log_Levels::EMERGENCY} - Emergency event.<br/>
     * {@see WC_Log_Levels::CRITICAL} - Critical error event.<br/>
     * {@see WC_Log_Levels::ERROR} - Error event.<br/>
     * {@see WC_Log_Levels::WARNING} - Warning event.<br/>
     * {@see WC_Log_Levels::ALERT} - Alert event.<br/>
     * {@see WC_Log_Levels::NOTICE} - Notice event.<br/>
     * {@see WC_Log_Levels::INFO} - Info event.<br/>
     * {@see WC_Log_Levels::DEBUG} - Debug event.<br/>
     * </p>
     *
     * @since 2.0.0
     * @return void
     */
    public function add($message, $level = WC_Log_Levels::INFO, $data = null)
    {
        if (!$this->should_handle($level)) {
            return;
        }

        switch (true) {
            case is_null($data):
                break;
            case is_array($data):
                $message .= ' ' . $this->print_r($data);
                break;
            default:
                $message .= ' ' . $this->print_s($data);
        }

        $this->logger->log(
            $level,
            $message,
            [
                'source'  => $this->domain,
                '_legacy' => false,
            ]
        );
    }

    /**
     * <h2>Insert stack trace in the logs.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function trace()
    {
        $this->debug('Stack trace:', debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5));
    }

    /**
     * <h2>Clears the entire log file.</h2>
     *
     * @since 2.0.0
     * @return bool <b>TRUE</b> if clears successfully or <b>FALSE</b> otherwise.
     */
    public function clear()
    {
        return $this->logger->clear($this->domain);
    }

    /**
     * <h2>Inserts a separation line for better overview in the logs.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function start()
    {
        $this->debug(
            sprintf(
                _x('<--------------------------------------<< Running %s', 'Log information', 'woo-ecommpay'),
                $_SERVER['REQUEST_URI']
            )
        );
    }

    /**
     * <h2>Inserts a separation line for better overview in the logs.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    public function end()
    {
        $this->debug(
            sprintf(
                _x('>>--------------------------------------> Ended %s', 'Log information', 'woo-ecommpay'),
                $_SERVER['REQUEST_URI']
            )
        );
    }

    /**
     * <h2>Returns the log text domain.</h2>
     *
     * @since 2.0.0
     * @return string <p>Log text domain.</p>
     */
    public function get_domain()
    {
        return $this->domain;
    }

    // region Private methods

    /**
     * <h2>Initialize threshold level for logger.</h2>
     *
     * @since 2.0.0
     * @return void
     */
    private function init_threshold()
    {
        $level = ecommpay()->get_option(Ecp_Gateway_Settings_Page::OPTION_LOG_LEVEL, WC_Log_Levels::ERROR);

        $this->threshold = WC_Log_Levels::is_valid_level($level)
            ? WC_Log_Levels::get_level_severity($level)
            : WC_Log_Levels::get_level_severity(WC_Log_Levels::ERROR);
    }

    /**
     * <h2>Determine whether to handle or ignore log.</h2>
     *
     * @param string $level <p>
     * Possible values:<br/>
     * {@see WC_Log_Levels::EMERGENCY} - Emergency event.<br/>
     * {@see WC_Log_Levels::CRITICAL} - Critical error event.<br/>
     * {@see WC_Log_Levels::ERROR} - Error event.<br/>
     * {@see WC_Log_Levels::WARNING} - Warning event.<br/>
     * {@see WC_Log_Levels::ALERT} - Alert event.<br/>
     * {@see WC_Log_Levels::NOTICE} - Notice event.<br/>
     * {@see WC_Log_Levels::INFO} - Info event.<br/>
     * {@see WC_Log_Levels::DEBUG} - Debug event.<br/>
     * </p>
     * @since 2.0.0
     * @return bool <b>TRUE</b> if the log should be handled or <b>FALSE</b> otherwise.
     */
    private function should_handle($level)
    {
        if (null === $this->threshold) {
            return true;
        }
        return $this->threshold <= WC_Log_Levels::get_level_severity($level);
    }

    /**
     * <h2>Returns formatted array for logging.</h2>
     *
     * @param array $data <p>Array data.</p>
     * @since 2.0.0
     * @return string <p>Data as string with masked values if needed.</p>
     */
    private function print_r(array $data)
    {
        return wc_print_r($this->mask($data), true);
    }

    /**
     * <h2>Returns formatted Json-string for logging.</h2>
     *
     * @param string $data <p>Source Json-string.</p>
     * @since 2.0.0
     * @return string <p>String with masked values if needed.</p>
     */
    private function print_s($data)
    {
        if (strpos($data, '{') === 0) {
            try {
                return json_encode($this->mask(json_decode($data, true)));
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return $data;
    }

    /**
     * <h2>Returns array with masked values if needed.</h2>
     *
     * @param array $data <p>Source array.</p>
     * @since 2.0.0
     * @return array <p>Array with masked values.</p>
     */
    private function mask(array $data)
    {
        foreach ($data as $key => &$value) {
            if (array_key_exists($key, $this->masked)) {
                $value = $this->masked[$key];
            }

            if (is_array($value)) {
                $value = $this->mask($value);
            }
        }

        return $data;
    }

    // endregion
}