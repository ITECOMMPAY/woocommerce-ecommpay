<?php

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Base ECOMMPAY Gate2025 API</h2>
 *
 * @class    Ecp_Gateway_API
 * @version  2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class Ecp_Gateway_API {


	/**
	 * <h2>Default API protocol name.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private const PROTOCOL = 'https';

	/**
	 * <h2>Default API host name.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private const HOST = 'api.ecommpay.com';

	/**
	 * <h2>Default API protocol version.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private const VERSION = 'v2';

	protected const MERCHANT_DESTINATION = 'merchant';

	protected const STATUS_API_ENDPOINT = 'status';

	public const CAPTURE_ENDPOINT = 'capture';
	public const CANCEL_ENDPOINT = 'cancel';

	/**
	 * <h2>The API url.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private string $api_url;

	/**
	 * <h2>Request headers.</h2>
	 *
	 * @var string[]
	 * @since 2.0.0
	 */
	private array $headers;


	/**
	 * <h2>API Constructor.</h2>
	 *
	 * @param string $append <p>Additional parameters to base API URL.</p>
	 *
	 * @since 2.0.0
	 */
	public function __construct( string $append = '' ) {
		$this->api_url = sprintf(
			'%s://%s/%s%s',
			$this->getProtocol(),
			$this->getHost(),
			$this->getVersion(),
			$append !== '' ? '/' . $append : ''
		);

		$this->headers = [
			'X-ECOMMPAY_PLUGIN' => Ecp_Core::WC_ECP_VERSION,
			'X-WORDPRESS'       => wp_version(),
			'X-WOOCOMMERCE'     => wc_version(),
			'Accept'            => 'application/json',
			'Content-Type'      => 'application/json',
		];

		$this->hooks();
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API protocol name.</h2>
	 *
	 * @return string <p>Protocol name.</p>
	 * @since 2.0.0
	 */
	private function getProtocol(): string {
		$proto = getenv( 'ECP_PROTO' );

		return is_string( $proto ) ? $proto : self::PROTOCOL;
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API host name.</h2>
	 *
	 * @return string <p>Host name.</p>
	 * @since 2.0.0
	 */
	private function getHost(): string {
		$host = getenv( 'ECP_GATE_HOST' );

		return is_string( $host ) ? $host : self::HOST;
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API version.</h2>
	 *
	 * @return string <p>API version.</b>
	 * @since 2.0.0
	 */
	private function getVersion(): string {
		$version = getenv( 'ECP_GATE_VERSION' );

		return is_string( $version ) ? $version : self::VERSION;
	}

	/**
	 * <h2>Adds hooks and filters.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	protected function hooks() {
	}

	/**
	 * <h2>Performs an API GET request.</h2>
	 *
	 * @param string $path <p>API request string.</p>
	 *
	 * @return array <p>Response data as array.</p>
	 * @since 2.0.0
	 */
	final public function get( string $path ): array {
		// Start the request and return the response
		return $this->execute( 'GET', $path );
	}


	/**
	 * <h2>Executes the API request.</h2>
	 *
	 * @param string $request_type <p>The type of request being made.</p>
	 * @param string $path <p>API request string.</p>
	 * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
	 *
	 * @return array <p>Response data as array.</p>
	 * @since 2.0.0
	 */
	private function execute( string $request_type, string $path, array $form = [] ): array {
		$full_path = $this->get_url( $path );

		ecp_debug( 'Started API request.', [
			'type' => $request_type,
			'path' => $full_path,
			'form' => $form
		] );

		switch ( $request_type ) {
			case 'GET':
				$response = wp_remote_get( $full_path, $this->get_args( $form ) );
				break;
			case 'HEAD':
				$response = wp_remote_head( $full_path, $this->get_args( $form ) );
				break;
			default:
				$response = wp_remote_post( $full_path, $this->get_args( $form ) );
				break;
		}

		$response_data = wp_remote_retrieve_body( $response );
		$status_code   = intval( wp_remote_retrieve_response_code( $response ) );

		$response_data = json_decode( $response_data, true );

		if ( $response_data === null ) {
			$response_data = [
				'json_parse_error' => json_last_error_msg()
			];
		}

		ecp_debug( 'API request executed. Status code: ' . $status_code . '. Response:', $response_data );

		$result = $status_code === 200
			? $response_data
			: [
				Ecp_Gateway_Info_Status::FIELD_ERRORS => [
					[
						Ecp_Gateway_Info_Error::FIELD_MESSAGE => 'Communication error',
					]
				]
			];

		if ( is_array( $result ) ) {
			return $result;
		}

		ecp_warn(
			_x( 'JSON parse data with error: ', 'Log information', 'woo-ecommpay' ),
			json_last_error_msg()
		);

		ecp_info(
			_x( 'JSON source string data: ', 'Log information', 'woo-ecommpay' ),
			$response_data
		);

		return [];
	}

	/**
	 * <h2>Returns the API request string and appends it to the API url.</h2>
	 *
	 * @param string $params <p>API request string.</p>
	 *
	 * @return string <p>Current object.</p>
	 * @since 2.0.0
	 */
	private function get_url( string $params ): string {
		return $this->api_url . '/' . trim( $params, '/' );
	}

	/**
	 * <h2>Returns the request properties.</h2>
	 *
	 * @return array <p>Request properties.</b>
	 * @since 2.2.1
	 */
	private function get_args( array $body = [] ) {
		$args = [
			'timeout'     => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $this->headers,
		];

		if ( count( $body ) > 0 ) {
			$body = json_encode( $body );

			if ( $body !== false ) {
				$args['body'] = $body;
			} else {
				ecp_get_log()->alert( json_last_error_msg() );
			}
		}

		return $args;
	}


	/**
	 * <h2>Performs an API POST request.</h2>
	 *
	 * @param string $path <p>API request string.</p>
	 * @param array $form [optional] <p>Form data for send. Default: blank array.</p>
	 *
	 * @return array <p>Response data as array.</p>
	 * @since 2.0.0
	 */
	final public function post( string $path, array $form = [] ): array {
		// Start the request and return the response
		return $this->execute( 'POST', $path, $form );
	}

	/**
	 * <h2>Returns form data with general section.</h2>
	 *
	 * @param array $data <p>Order object or request identifier.</p>
	 *
	 * @return array
	 * @since 3.0.0
	 */
	protected function create_general_section( array $data ): array {
		return [
			Ecp_Gateway_Signer::GENERAL => $data
		];
	}

	protected function get_general_data( $order ): array {
		return [
			Ecp_Gateway_Signer::GENERAL => apply_filters(
				'ecp_append_merchant_callback_url',
				apply_filters( 'ecp_create_general_data', $order )
			)
		];
	}


}
