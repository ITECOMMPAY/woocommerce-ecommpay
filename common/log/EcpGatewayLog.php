<?php

namespace common\log;

use common\helpers\EcpGatewayRegistry;
use common\settings\EcpSettingsGeneral;
use Exception;
use WC_Log_Levels;
use WC_Logger;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Internal logger.</h2>
 *
 * @class    EcpGatewayLog
 * @since    2.0.0
 * @package  Ecp_Gateway/Log
 * @category Class
 * @internal
 */
class EcpGatewayLog extends EcpGatewayRegistry {
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
	private string $domain = self::ECOMMPAY_DOMAIN;

	/**
	 * <h2>Threshold level.</h2>
	 *
	 * @var ?int
	 * @since 2.0.0
	 */
	private ?int $threshold = null;

	/**
	 * <h2>The WC_Logger instance.</h2>
	 *
	 * @var WC_Logger
	 * @since 2.0.0
	 */
	private WC_Logger $logger;

	/**
	 * <h2>List of keys for the masked value.</h2>
	 *
	 * @var string[]
	 * @since 2.0.0
	 */
	private array $masked = [
		'customer_first_name'                           => '***',
		'customer_last_name'                            => '***',
		'customer_address'                              => '***',
		'customer_phone'                                => '***',
		'customer_email'                                => '***@***',
		'customer_city'                                 => '***',
		'customer_state'                                => '***',
		'customer_zip'                                  => '***',
		'customer_country'                              => '***',
		'billing_address'                               => '***',
		'billing_city'                                  => '***',
		'billing_country'                     => '***',
		'billing_postal'                      => '***',
		'billing_region'                      => '***',
		'billing_region_code'                 => '***',
		'signature'                           => '*SECRET*',
		"card_holder"                         => '** CARD_HOLDER **',
		'expiry_month'                        => '**',
		'expiry_year'                         => '****',
		'phone'                               => '***',
		'token'                               => '** TOKEN **',
		EcpSettingsGeneral::OPTION_SECRET_KEY => '*SECRET*',
	];

	/**
	 * <h2>Destructor logger.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function __destruct() {
		// Write end separator
		$this->end();
	}

	/**
	 * <h2>Inserts a separation line for better overview in the logs.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function end(): void {
		$this->debug(
			sprintf(
				_x( '>>--------------------------------------> Ended %s', 'Log information', 'woo-ecommpay' ),
				esc_url_raw( wc_get_var( $_SERVER['REQUEST_URI'], '' ) )
			)
		);
	}

	/**
	 * <h2>Adds a debug level message.</h2>
	 * <p>Detailed debug information.</p>
	 *
	 * @param string|array|object $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function debug( $message, $data = null ): void {
		if ( is_array( $message ) || is_object( $message ) ) {
			$data    = $message;
			$message = gettype( $message ) . ' => ';
		}
		$this->add( $message, WC_Log_Levels::DEBUG, $data );
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
	 * @return void
	 * @since 2.0.0
	 */
	public function add( string $message, string $level = WC_Log_Levels::INFO, $data = null ): void {
		if ( ! $this->should_handle( $level ) ) {
			return;
		}

		switch ( true ) {
			case is_null( $data ):
				break;
			case is_array( $data ):
				$message .= ' ' . $this->print_secured_array( $data );
				break;
			case is_object( $data ):
				$message .= ' ' . $this->print_secured_array( (array) $data );
				break;
			default:
				$message .= ' ' . $this->print_s( $data );
		}

		$this->logger->log(
			$level,
			$message,
			[
				'source' => $this->domain,
			]
		);
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
	 *
	 * @return bool <b>TRUE</b> if the log should be handled or <b>FALSE</b> otherwise.
	 * @since 2.0.0
	 */
	private function should_handle( string $level ): bool {
		if ( null === $this->threshold ) {
			return true;
		}

		return $this->threshold <= WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * <h2>Returns formatted array for logging.</h2>
	 *
	 * @param array $data <p>Array data.</p>
	 *
	 * @return string <p>Data as string with masked values if needed.</p>
	 * @since 2.0.0
	 */
	private function print_secured_array( array $data ): string {
		return json_encode( $this->mask( $data ), JSON_PRETTY_PRINT );
	}

	/**
	 * <h2>Returns array with masked values if needed.</h2>
	 *
	 * @param array $data <p>Source array.</p>
	 *
	 * @return array <p>Array with masked values.</p>
	 * @since 2.0.0
	 */
	private function mask( array $data ): array {
		foreach ( $data as $key => &$value ) {
			if ( array_key_exists( $key, $this->masked ) ) {
				$value = $this->masked[ $key ];
			}

			if ( is_array( $value ) ) {
				$value = $this->mask( $value );
			}
		}

		return $data;
	}

	/**
	 * <h2>Returns formatted Json-string for logging.</h2>
	 *
	 * @param string $data <p>Source Json-string.</p>
	 *
	 * @return string <p>String with masked values if needed.</p>
	 * @since 2.0.0
	 */
	private function print_s( string $data ): string {
		if ( strpos( $data, '{' ) === 0 ) {
			try {
				return json_encode( $this->mask( json_decode( $data, true ) ) );
			} catch ( Exception $e ) {
				return $e->getMessage();
			}
		}

		return $data;
	}

	/**
	 * <h2>Adds an emergency level message.</h2>
	 * <p>System is unusable.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function emergency( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::EMERGENCY, $data );
	}

	/**
	 * <h2>Adds an alert level message.</h2>
	 * <p>Action must be taken immediately.</p>
	 * <p>Example: Entire website down, database unavailable, etc.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function alert( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::ALERT, $data );
	}

	/**
	 * <h2>Adds a critical level message.</h2>
	 * <p>Critical conditions.</p>
	 * <p>Example: Application component unavailable, unexpected exception.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function critical( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::CRITICAL, $data );
	}

	/**
	 * <h2>Adds an error level message.</h2>
	 * <p>Runtime errors that do not require immediate action but should typically be logged and monitored.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function error( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::ERROR, $data );
	}

	/**
	 * <h2>Adds a warning level message.</h2>
	 * <p>Exceptional occurrences that are not errors.</p>
	 * <p>Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function warning( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::WARNING, $data );
	}

	/**
	 * <h2>Adds a notice level message.</h2>
	 * <p>Normal but significant events.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function notice( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::NOTICE, $data );
	}

	/**
	 * <h2>Adds an info level message.</h2>
	 * <p>Interesting events.</p>
	 * <p>Example: User logs in, SQL logs.</p>
	 *
	 * @param string $message <p>Message to log.</p>
	 * @param int|float|string|array $data [optional] <p>Additional log data. By default: none.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 * @see EcpGatewayLog::add
	 */
	public function info( string $message, $data = null ): void {
		$this->add( $message, WC_Log_Levels::INFO, $data );
	}

	/**
	 * <h2>Insert stack trace in the logs.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function trace(): void {
		$this->debug( 'Stack trace:', debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 5 ) );
	}


	/**
	 * <h2>Clears the entire log file.</h2>
	 *
	 * @return bool <b>TRUE</b> if clears successfully or <b>FALSE</b> otherwise.
	 * @since 2.0.0
	 */
	public function clear(): bool {
		return $this->logger->clear( $this->domain );
	}

	/**
	 * <h2>Returns the log text domain.</h2>
	 *
	 * @return string <p>Log text domain.</p>
	 * @since 2.0.0
	 */
	public function get_domain(): string {
		return $this->domain;
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	protected function init(): void {
		$this->logger = new WC_Logger();
		$this->init_threshold();
		$this->start();
	}

	/**
	 * <h2>Initialize threshold level for logger.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function init_threshold(): void {
		$level = ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_LOG_LEVEL, WC_Log_Levels::DEBUG );

		$this->threshold = WC_Log_Levels::is_valid_level( $level )
			? WC_Log_Levels::get_level_severity( $level )
			: WC_Log_Levels::get_level_severity( WC_Log_Levels::ERROR );
	}

	/**
	 * <h2>Inserts a separation line for better overview in the logs.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function start(): void {
		$this->debug(
			sprintf(
				_x( '<--------------------------------------<< Running %s', 'Log information', 'woo-ecommpay' ),
				esc_url_raw( wc_get_var( $_SERVER['REQUEST_URI'], '' ) )
			)
		);
	}


}
