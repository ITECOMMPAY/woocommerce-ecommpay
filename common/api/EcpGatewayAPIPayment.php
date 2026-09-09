<?php
/**
 * ECOMMPAY Gateway Payment API class.
 *
 * @package Ecp_Gateway/Api
 * @since   2.0.0
 */

namespace common\api;

use common\enums\EcpWcPaymentMethods;
use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;
use common\includes\EcpGatewayOrder;
use common\includes\EcpGatewayRefund;
use common\includes\filters\EcpApiFilters;
use common\includes\filters\EcpAppendsFilters;
use common\models\EcpGatewayInfoResponse;
use common\models\EcpGatewayInfoStatus;
use function ecp_debug;
use function ecp_get_log;
use function ecp_info;
use function ecpTr;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Payment ECOMMPAY Gate2025 API.</h2>
 *
 * @class    EcpGatewayAPIPayment
 * @since    2.0.0
 * @package  Ecp_Gateway/Api
 * @category Class
 */
class EcpGatewayAPIPayment extends EcpGatewayAPI {

	private const CARD_ENDPOINT_PART = 'card';

	private const APPLE_PAY_ENDPOINT_PART = 'applepay';

	private const GOOGLE_PAY_ENDPOINT_PART = 'googlepay';

	private const CAPTURE_OPERATION = 'capture';

	/**
	 * <h2>Payment Gate2025 API constructor.</h2>
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct( 'payment' );
	}

	/**
	 * <h2>Sends a request and returns information about the payment.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order for request.</p>
	 *
	 * @return EcpGatewayInfoStatus <p>Payment status information.</p>
	 * @since 2.0.0
	 */
	public function status( EcpGatewayOrder $order ): EcpGatewayInfoStatus {
		ecp_get_log()->info( ecpTr( 'Run check payment status API process.' ) );
		ecp_debug( ecpTr( 'Order ID:' ), $order->get_id() );
		ecp_debug( ecpTr( 'Current payment status:' ), $order->get_ecp_status() );
		ecp_debug( ecpTr( 'Payment method:' ), $order->get_payment_system() );

		if ( $order->get_ecp_status() === EcpGatewayPaymentStatus::INITIAL ) {
			return new EcpGatewayInfoStatus();
		}

		$response = new EcpGatewayInfoStatus(
			$this->post(
				self::STATUS_API_ENDPOINT,
				apply_filters(
					EcpAppendsFilters::ECP_APPEND_SIGNATURE,
					$this->build_general_api_block( $order->get_payment_id() )
				)
			)
		);

		ecp_get_log()->info( ecpTr( 'Check payment status process completed.' ) );

		return $response;
	}

	/**
	 * <h2>Sends data and return created refund transaction data.</h2>
	 *
	 * @param EcpGatewayRefund $refund <p>Refund object.</p>
	 * @param EcpGatewayOrder  $order <p>Refunding order.</p>
	 *
	 * @return EcpGatewayInfoResponse
	 */
	public function refund( EcpGatewayRefund $refund, EcpGatewayOrder $order ): ?EcpGatewayInfoResponse {
		ecp_info( ecpTr( 'Run refund payment API process.' ) );
		ecp_debug( ecpTr( 'Refund ID:' ), $refund->get_id() );
		ecp_debug( ecpTr( 'Order ID:' ), $order->get_id() );

		$response = $this->post(
			sprintf(
				'%s/%s',
				apply_filters( EcpApiFilters::ECP_API_REFUND_ENDPOINT_PREFIX . $order->get_payment_method(), $order->get_payment_system() ),
				'refund'
			),
			apply_filters(
				EcpAppendsFilters::ECP_APPEND_SIGNATURE,
				$this->create_refund_request_form_data( $refund )
			)
		);

		$response = new EcpGatewayInfoResponse( $response );

		ecp_info( ecpTr( 'Refund payment process completed.' ) );

		return $response;
	}

	/**
	 * <h2>Returns the underlying form data for the refund request.</h2>
	 *
	 * @param EcpGatewayRefund $refund <p>Refund object.</p>
	 *
	 * @return array[] <p>Basic form-data.</p>
	 * @since 3.0.0
	 */
	final public function create_refund_request_form_data( EcpGatewayRefund $refund ): array {
		$api_data                                  = $this->build_general_api_block_with_payment( $refund->get_order()->get_payment_id(), $refund );
		$refund_reason                             = $refund->get_reason();
		$api_data['payment']['description']        = $refund_reason ? $refund_reason : sprintf( 'User %s create refund', wp_get_current_user()->ID );
		$api_data['payment']['merchant_refund_id'] = $refund->get_payment_id();

		return $api_data;
	}

	/**
	 * <h2>Cancels the payment.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 *
	 * @return EcpGatewayInfoResponse <p>API response.</p>
	 * @since 2.0.0
	 */
	public function cancel( EcpGatewayOrder $order ): EcpGatewayInfoResponse {
		ecp_info( 'Run cancel payment API process.' );
		ecp_debug( 'Order ID: ', $order->get_id() );
		$data     = $this->build_general_api_block_with_payment( $order->get_payment_id(), $order );
		$url      = $this->get_method_endpoint( $order->get_payment_method(), EcpGatewayAPI::CANCEL_ENDPOINT );
		$response = $this->post( $url, apply_filters( EcpAppendsFilters::ECP_APPEND_SIGNATURE, $data ) );
		$response = new EcpGatewayInfoResponse( $response );
		$order->set_transaction_order_id( $response->get_request_id(), EcpGatewayOperationType::CANCEL );
		ecp_info( 'Cancel payment process completed.' );

		return $response;
	}

	/**
	 * <h2>Returns the API endpoint for a specific payment method and operation.</h2>
	 *
	 * @param string $payment_method <p>Payment method ID.</p>
	 * @param string $operation <p>Operation type.</p>
	 *
	 * @return string <p>API endpoint path.</p>
	 * @since 2.0.0
	 */
	private function get_method_endpoint( string $payment_method, string $operation ): string {
		$pm_endpoints_map = array(
			EcpWcPaymentMethods::CARD       => self::CARD_ENDPOINT_PART,
			EcpWcPaymentMethods::APPLE_PAY  => self::APPLE_PAY_ENDPOINT_PART,
			EcpWcPaymentMethods::GOOGLE_PAY => self::GOOGLE_PAY_ENDPOINT_PART,
		);

		$mapped_method = $pm_endpoints_map[ $payment_method ] ?? $payment_method;

		return sprintf( '%s/%s', $mapped_method, $operation );
	}

	/**
	 * <h2>Captures the authorized payment.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order object.</p>
	 *
	 * @return EcpGatewayInfoResponse <p>API response.</p>
	 * @since 2.0.0
	 */
	public function capture( EcpGatewayOrder $order ): EcpGatewayInfoResponse {
		ecp_info( 'Run capture payment API process.' );
		ecp_debug( 'Order ID: ' . $order->get_id() );
		$data     = $this->build_general_api_block_with_payment( $order->get_payment_id(), $order );
		$url      = $this->get_method_endpoint( $order->get_payment_method(), EcpGatewayAPI::CAPTURE_ENDPOINT );
		$response = $this->post( $url, apply_filters( EcpAppendsFilters::ECP_APPEND_SIGNATURE, $data ) );
		$response = new EcpGatewayInfoResponse( $response );
		$order->set_transaction_order_id( $response->get_request_id(), self::CAPTURE_OPERATION );
		ecp_info( 'Capture payment process completed.' );

		return $response;
	}
}
