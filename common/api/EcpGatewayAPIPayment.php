<?php

namespace common\api;

use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;
use common\includes\EcpGatewayOrder;
use common\includes\EcpGatewayRefund;
use common\includes\filters\EcpAppendsFilterList;
use common\models\EcpGatewayInfoResponse;
use common\models\EcpGatewayInfoStatus;
use common\settings\EcpSettingsApplepay;
use common\settings\EcpSettingsCard;
use common\settings\EcpSettingsGeneral;
use common\settings\EcpSettingsGooglepay;

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

		// Run request
		$response = new EcpGatewayInfoStatus(
			$this->post(
				self::STATUS_API_ENDPOINT,
				apply_filters( EcpAppendsFilterList::ECP_APPEND_SIGNATURE, $this->create_status_request_form_data( $order ) )
			)
		);

		ecp_get_log()->info( ecpTr( 'Check payment status process completed.' ) );

		return $response;
	}

	/**
	 * <h2>Returns the underlying form data for the status request.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Order with payment.</p>
	 *
	 * @return array[] <p>Basic form-data.</p>
	 * @since 3.0.0
	 */
	private function create_status_request_form_data( EcpGatewayOrder $order ): array {
		ecp_get_log()->info( __( 'Create form data for status request.', 'woo-ecommpay' ) );
		$data                = $this->create_general_section(
			apply_filters(
				'ecp_append_merchant_callback_url',
				apply_filters( 'ecp_create_general_data', $order )
			)
		);
		$data['destination'] = self::MERCHANT_DESTINATION;

		return $data;
	}

	/**
	 * <h2>Sends data and return created refund transaction data.</h2>
	 *
	 * @param EcpGatewayRefund $refund <p>Refund object.</p>
	 * @param EcpGatewayOrder $order <p>Refunding order.</p>
	 *
	 * @return EcpGatewayInfoResponse
	 */
	public function refund( EcpGatewayRefund $refund, EcpGatewayOrder $order ): ?EcpGatewayInfoResponse {
		ecp_info( ecpTr( 'Run refund payment API process.' ) );
		ecp_debug( ecpTr( 'Refund ID:' ), $refund->get_id() );
		ecp_debug( ecpTr( 'Order ID:' ), $order->get_id() );

		// Create form data
		$data = $this->create_refund_request_form_data( $refund, $order );

		/** @var array $variables */
		$variables = ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_CUSTOM_VARIABLES, [] );

		if ( array_search( EcpSettingsGeneral::CUSTOM_RECEIPT_DATA, $variables, true ) ) {
			// Append receipt data
			$data = apply_filters( 'ecp_append_receipt_data', $data, $refund );
		}

		// Run request
		$response = $this->post(
			sprintf(
				'%s/%s',
				apply_filters( 'ecp_api_refund_endpoint_' . $order->get_payment_method(), $order->get_payment_system() ),
				'refund'
			),
			apply_filters( EcpAppendsFilterList::ECP_APPEND_SIGNATURE, $data )
		);

		$response = new EcpGatewayInfoResponse( $response );

		ecp_info( ecpTr( 'Refund payment process completed.' ) );

		return $response;
	}

	/**
	 * <h2>Returns the underlying form data for the refund request.</h2>
	 *
	 * @param EcpGatewayRefund $refund <p>Refund object.</p>
	 * @param EcpGatewayOrder $order <p>Refunding order.</p>
	 *
	 * @return array[] <p>Basic form-data.</p>
	 * @since 3.0.0
	 */
	final public function create_refund_request_form_data( EcpGatewayRefund $refund, EcpGatewayOrder $order ): array {
		ecp_get_log()->info( __( 'Create form data for refund request.', 'woo-ecommpay' ) );
		$data = $this->create_general_section(
			apply_filters(
				'ecp_append_merchant_callback_url',
				apply_filters( 'ecp_create_payment_data', $order )
			)
		);
		$data = apply_filters( 'ecp_append_payment_section', $data, $refund );

		return apply_filters( 'ecp_append_interface_type', $data );
	}

	public function cancel( EcpGatewayOrder $order ): EcpGatewayInfoResponse {
		ecp_info( 'Run cancel payment API process.' );
		ecp_debug( 'Order ID: ', $order->get_id() );
		$data     = $this->create_general_request_form_data( $order );
		$url = $this->getMethodEndpoint( $order->get_payment_method(), EcpGatewayAPI::CANCEL_ENDPOINT );
		$response = $this->post( $url, apply_filters( EcpAppendsFilterList::ECP_APPEND_SIGNATURE, $data ) );
		$response = new EcpGatewayInfoResponse( $response );
		$order->set_transaction_order_id( $response->get_request_id(), EcpGatewayOperationType::CANCEL );
		ecp_info( 'Cancel payment process completed.' );

		return $response;
	}

	final public function create_general_request_form_data( EcpGatewayOrder $order ): array {
		ecp_get_log()->info( __( 'Create general form data for request.', 'woo-ecommpay' ) );
		$data = $this->create_general_section(
			apply_filters(
				'ecp_append_merchant_callback_url',
				apply_filters( 'ecp_create_payment_data', $order )
			)
		);
		$data = apply_filters( 'ecp_append_payment_section', $data, $order );

		return apply_filters( 'ecp_append_interface_type', $data );
	}

	/**
	 * @param string $paymentMethod
	 * @param string $operation
	 *
	 * @return string
	 */
	function getMethodEndpoint( string $paymentMethod, string $operation ): string {
		$pm_endpoints_map = [
			EcpSettingsCard::ID      => self::CARD_ENDPOINT_PART,
			EcpSettingsApplepay::ID  => self::APPLE_PAY_ENDPOINT_PART,
			EcpSettingsGooglepay::ID => self::GOOGLE_PAY_ENDPOINT_PART,
		];

		$mappedMethod = $pm_endpoints_map[ $paymentMethod ] ?? $paymentMethod;

		return sprintf( '%s/%s', $mappedMethod, $operation );
	}

	public function capture( EcpGatewayOrder $order ): EcpGatewayInfoResponse {
		ecp_info( 'Run capture payment API process.' );
		ecp_debug( 'Order ID: ' . $order->get_id() );
		$data     = $this->create_general_request_form_data( $order );
		$url = $this->getMethodEndpoint( $order->get_payment_method(), EcpGatewayAPI::CAPTURE_ENDPOINT );
		$response = $this->post( $url, apply_filters( EcpAppendsFilterList::ECP_APPEND_SIGNATURE, $data ) );
		$response = new EcpGatewayInfoResponse( $response );
		$order->set_transaction_order_id( $response->get_request_id(), self::CAPTURE_OPERATION );
		ecp_info( 'Capture payment process completed.' );

		return $response;
	}
}
