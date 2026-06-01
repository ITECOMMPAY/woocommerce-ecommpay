<?php
/**
 * ECOMMPAY Gateway API base class.
 *
 * @package Ecp_Gateway/Api
 * @since   2.0.0
 */

namespace common\api;

use common\EcpCore;
use common\models\EcpGatewayInfoError;
use common\models\EcpGatewayInfoStatus;
use WC_Abstract_Order;
use function ecommpay;
use function ecp_callback_url;
use function ecp_debug;
use function ecp_get_log;
use function ecp_info;
use function ecp_price_multiply;
use function ecp_warn;
use function wc_version;
use function wp_version;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Base ECOMMPAY Gate2025 API</h2>
 *
 * @class    EcpGatewayAPI
 * @version  2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class EcpGatewayAPI {

	/**
	 * <h2>HTTP status code for successful response.</h2>
	 *
	 * @var int
	 * @since 2.0.0
	 */
	private const HTTP_OK = 200;

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


	protected const STATUS_API_ENDPOINT = 'status';

	public const CAPTURE_ENDPOINT = 'capture';
	public const CANCEL_ENDPOINT  = 'cancel';

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
			$this->get_protocol(),
			$this->get_host(),
			$this->get_version(),
			'' !== $append ? '/' . $append : ''
		);

		$this->headers = array(
			'X-ECOMMPAY_PLUGIN' => EcpCore::WC_ECP_VERSION,
			'X-WORDPRESS'       => wp_version(),
			'X-WOOCOMMERCE'     => wc_version(),
			'Accept'            => 'application/json',
			'Content-Type'      => 'application/json',
		);

		$this->hooks();
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API protocol name.</h2>
	 *
	 * @return string <p>Protocol name.</p>
	 * @since 2.0.0
	 */
	private function get_protocol(): string {
		$proto = getenv( 'ECP_PROTO' );

		return is_string( $proto ) ? $proto : self::PROTOCOL;
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API host name.</h2>
	 *
	 * @return string <p>Host name.</p>
	 * @since 2.0.0
	 */
	private function get_host(): string {
		$host = getenv( 'ECP_GATE_HOST' );

		return is_string( $host ) ? $host : self::HOST;
	}

	/**
	 * <h2>Returns the ECOMMPAY Gate2025 API version.</h2>
	 *
	 * @return string <p>API version.</b>
	 * @since 2.0.0
	 */
	private function get_version(): string {
		$version = getenv( 'ECP_GATE_VERSION' );

		return is_string( $version ) ? $version : self::VERSION;
	}

	/**
	 * <h2>Adds hooks and filters.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	protected function hooks(): void {
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
		// Start the request and return the response.
		return $this->execute( 'GET', $path );
	}


	/**
	 * <h2>Executes the API request.</h2>
	 *
	 * @param string $request_type <p>The type of request being made.</p>
	 * @param string $path <p>API request string.</p>
	 * @param array  $form [optional] <p>Form data for send. Default: blank array.</p>
	 *
	 * @return array <p>Response data as array.</p>
	 * @since 2.0.0
	 */
	private function execute( string $request_type, string $path, array $form = array() ): array {
		$full_path = $this->get_url( $path );

		ecp_debug(
			'Started API request.',
			array(
				'type' => $request_type,
				'path' => $full_path,
				'form' => $form,
			)
		);

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

		if ( null === $response_data ) {
			$response_data = array(
				'json_parse_error' => json_last_error_msg(),
			);
		}

		ecp_debug( 'API request executed. Status code: ' . $status_code . '. Response:', $response_data );

		$result = self::HTTP_OK === $status_code
			? $response_data
			: array(
				EcpGatewayInfoStatus::FIELD_ERRORS => array(
					array(
						EcpGatewayInfoError::FIELD_MESSAGE => 'Communication error',
					),
				),
			);

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

		return array();
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
	 * @param array $body [optional] <p>Request body data. Default: blank array.</p>
	 * @return array <p>Request properties.</b>
	 * @since 2.2.1
	 */
	private function get_args( array $body = array() ): array {
		$args = array(
			'timeout'     => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $this->headers,
		);

		if ( count( $body ) > 0 ) {
			$body = wp_json_encode( $body );

			if ( false !== $body ) {
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
	 * @param array  $form [optional] <p>Form data for send. Default: blank array.</p>
	 *
	 * @return array <p>Response data as array.</p>
	 * @since 2.0.0
	 */
	final public function post( string $path, array $form = array() ): array {
		// Start the request and return the response.
		return $this->execute( 'POST', $path, $form );
	}

	/**
	 * <h2>Builds general API block data.</h2>
	 *
	 * @param string|null $payment_id [optional] <p>Payment ID. Default: null.</p>
	 * @return array <p>General API block data.</p>
	 * @since 2.0.0
	 */
	protected function build_general_api_block( string $payment_id = null ): array {
		$block = array(
			'general'        => array(
				'project_id'            => ecommpay()->get_project_id(),
				'merchant_callback_url' => ecp_callback_url(),
			),
			'interface_type' => ecommpay()->get_interface_type(),
		);

		if ( null !== $payment_id ) {
			$block['general']['payment_id'] = $payment_id;
		}

		return $block;
	}

	/**
	 * <h2>Builds general API block data with payment information.</h2>
	 *
	 * @param string            $payment_id <p>Payment ID.</p>
	 * @param WC_Abstract_Order $order <p>WooCommerce order object.</p>
	 * @return array <p>General API block data with payment.</p>
	 * @since 2.0.0
	 */
	protected function build_general_api_block_with_payment( string $payment_id, WC_Abstract_Order $order ): array {
		$api_data            = $this->build_general_api_block( $payment_id );
		$api_data['payment'] = array(
			'amount'   => ecp_price_multiply( abs( $order->get_total() ), $order->get_currency() ),
			'currency' => $order->get_currency(),
		);

		return $api_data;
	}
}
