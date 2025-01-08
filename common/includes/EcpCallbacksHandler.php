<?php

namespace common\includes;

use common\exceptions\EcpGatewaySignatureException;
use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayOperationType;
use common\includes\callbackOperations\EcpAuthOperationHandler;
use common\includes\callbackOperations\EcpCancelOperationHandler;
use common\includes\callbackOperations\EcpCaptureOperationHandler;
use common\includes\callbackOperations\EcpConfirmOperationHandler;
use common\includes\callbackOperations\EcpContractRegistrationOperationHandler;
use common\includes\callbackOperations\EcpRecurringOperationHandler;
use common\includes\callbackOperations\EcpSaleOperationHandler;
use common\includes\callbackOperations\EcpVerifyOperationHandler;
use common\models\EcpGatewayInfoCallback;
use common\modules\EcpModuleRefund;
use Exception;
use WC_Data_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Callback handler.</h2>
 *
 * @class    Ecp_Gateway_Callbacks
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 * @internal
 */
class EcpCallbacksHandler {
	private const CALLBACKS_PRIORITY = 10;
	public EcpOrderManager $order_manager;

	/**
	 * <h2> List of supported operations.</h2>
	 *
	 * @var string[]
	 * @since 2.0.0
	 */
	private array $operations = [
		EcpGatewayOperationType::SALE                  => 'woocommerce_ecommpay_callback_sale',
		EcpGatewayOperationType::REFUND                => 'woocommerce_ecommpay_callback_refund',
		EcpGatewayOperationType::REVERSAL              => 'woocommerce_ecommpay_callback_reversal',
		EcpGatewayOperationType::RECURRING             => 'woocommerce_ecommpay_callback_recurring',
		EcpGatewayOperationType::ACCOUNT_VERIFICATION  => 'woocommerce_ecommpay_callback_verify',
		EcpGatewayOperationType::PAYMENT_CONFIRMATION  => 'woocommerce_ecommpay_callback_payment_confirmation',
		EcpGatewayOperationType::CONTRACT_REGISTRATION => 'woocommerce_ecommpay_callback_contract_registration',
		EcpGatewayOperationType::AUTH                  => 'woocommerce_ecommpay_callback_auth',
		EcpGatewayOperationType::CAPTURE               => 'woocommerce_ecommpay_callback_capture',
		EcpGatewayOperationType::CANCEL                => 'woocommerce_ecommpay_callback_cancel',
	];
	private EcpVerifyOperationHandler $ecp_verify_operation_handler;
	private EcpAuthOperationHandler $auth_operation_handler;
	private EcpSaleOperationHandler $ecp_sale_operation_handler;
	private EcpContractRegistrationOperationHandler $contract_registration_handler;
	private EcpRecurringOperationHandler $recurring_operation_handler;
	private EcpConfirmOperationHandler $ecp_confirm_operation_handler;
	private EcpCaptureOperationHandler $ecp_capture_operation_handler;
	private EcpCancelOperationHandler $ecp_cancel_operation_handler;

	private EcpOrderNotesFormer $ecp_order_notes_former;

	/**
	 * <h2>Callback handler constructor.</h2>
	 *
	 * @param array $data <p>Callback data.</p>
	 *
	 * @since 2.0.0
	 */
	private function __construct( array $data ) {
		$this->ecp_order_notes_former        = new EcpOrderNotesFormer( $this );
		$this->order_manager                 = new EcpOrderManager( $this->ecp_order_notes_former );
		$this->ecp_verify_operation_handler  = new EcpVerifyOperationHandler( $this, $this->order_manager );
		$this->auth_operation_handler        = new EcpAuthOperationHandler( $this->order_manager );
		$this->ecp_sale_operation_handler    = new EcpSaleOperationHandler( $this );
		$this->contract_registration_handler = new EcpContractRegistrationOperationHandler( $this, $this->order_manager );
		$this->recurring_operation_handler   = new EcpRecurringOperationHandler( $this, $this->order_manager );
		$this->ecp_confirm_operation_handler = new EcpConfirmOperationHandler( $this );
		$this->ecp_capture_operation_handler = new EcpCaptureOperationHandler( $this );
		$this->ecp_cancel_operation_handler  = new EcpCancelOperationHandler( $this );

		add_action( 'woocommerce_ecommpay_callback_refund', [
			EcpModuleRefund::get_instance(),
			'handle'
		], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_reversal', [
			EcpModuleRefund::get_instance(),
			'handle'
		], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_sale', [ $this, 'sale' ], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_auth', [ $this, 'auth' ], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_cancel', [
			$this,
			'cancel'
		], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_capture', [
			$this,
			'capture'
		], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_recurring', [ $this, 'recurring' ], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_verify', [ $this, 'verify' ], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_payment_confirmation', [
			$this,
			'confirm'
		], self::CALLBACKS_PRIORITY, 2 );
		add_action( 'woocommerce_ecommpay_callback_contract_registration', [
			$this,
			'contract_registration'
		], self::CALLBACKS_PRIORITY, 2 );

		// Decode the body into JSON
		$info = new EcpGatewayInfoCallback( $data );

		// Instantiate order object
		$order = $this->get_order( $info );

		// Execute callback process.
		$this->processor( $info, $order );
	}

	/**
	 * <h2>Returns order by callback information.</h2>
	 *
	 * @param EcpGatewayInfoCallback $info <p>Callback information.</p>
	 *
	 * @return EcpGatewayOrder <p>Payment order.</p>
	 * @since 2.0.0
	 */
	private function get_order( EcpGatewayInfoCallback $info ): EcpGatewayOrder {
		// Fetch order number;
		$order_number = EcpGatewayOrder::get_order_id_from_callback( $info );
		$order        = ecp_get_order( $order_number );

		if ( ! $order ) {
			// Print debug information to logs
			$message = __( 'Order not found', 'woo-ecommpay' );
			ecp_get_log()->error( $message );
			ecp_get_log()->info( __( 'Transaction failed for', 'woo-ecommpay' ), $order_number );

			foreach ( $info->get_errors() as $error ) {
				ecp_get_log()->add( __( 'Error code:', 'woo-ecommpay' ), $error->get_code() );
				ecp_get_log()->add( __( 'Error field:', 'woo-ecommpay' ), $error->get_field() );
				ecp_get_log()->add( __( 'Error message:', 'woo-ecommpay' ), $error->get_message() );
				ecp_get_log()->add( __( 'Error description:', 'woo-ecommpay' ), $error->get_description() );
			}

			ecp_get_log()->add( __( 'Response data: %s', 'woo-ecommpay' ), json_encode( $info ) );

			http_response_code( 404 );
			die ( $message );
		}

		return $order;
	}

	/**
	 * @param EcpGatewayOrder $order
	 * @param EcpGatewayInfoCallback $callback
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function processor( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		ecp_get_log()->info( __( 'Run callback processor', 'woo-ecommpay' ) );

		do_action( 'ecp_accepted_callback_before_processing', $order, $callback );
		do_action( 'ecp_accepted_callback_before_processing_' . $callback->get_operation()->get_type(), $order, $callback );

		// Clear card - payment is not initial.
		WC()->cart->empty_cart();

		if ( array_key_exists( $callback->get_operation()->get_type(), $this->operations ) ) {
			do_action( $this->operations[ $callback->get_operation()->get_type() ], $callback, $order );
			$message = 'OK';
		} else {
			$message = sprintf(
				__( 'Not supported operation type: %s', 'woo-ecommpay' ),
				$callback->get_operation()->get_type()
			);
			ecp_get_log()->warning( $message );
		}

		do_action( 'ecp_accepted_callback_after_processing', $order, $callback );
		do_action( 'ecp_accepted_callback_after_processing_' . $callback->get_operation()->get_type(), $order, $callback );

		http_response_code( 200 );
		die ( $message );
	}

	/**
	 * @throws Exception
	 */
	public static function handle(): EcpCallbacksHandler {
		ecp_info( ecpL( 'Run callback handler.', 'Log information' ) );

		// Get callback body
		$body = file_get_contents( 'php://input' );

		$data = json_decode( $body, true );

		if ( $data === null ) {
			$data = [ 'json_parse_error' => json_last_error_msg() ];
		}

		ecp_debug( 'Incoming callback data:', $data );

		// Check signature
		self::check_signature( $data );

		return new static( $data );
	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	private static function check_signature( $data ): void {
		ecp_get_log()->debug( __( 'Verify signature', 'woo-ecommpay' ) );
		try {
			if ( ! ecp_check_signature( $data ) ) {
				$message = _x( 'Invalid callback signature.', 'Error message', 'woo-ecommpay' );
				ecp_get_log()->error( $message );

				http_response_code( 400 );
				die ( $message );
			}

			ecp_get_log()->debug( __( 'Signature verified.', 'woo-ecommpay' ) );
		} catch ( EcpGatewaySignatureException $e ) {
			$e->write_to_logs();
			http_response_code( 500 );
			die ( $e->getMessage() );
		}
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function recurring( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		$this->recurring_operation_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function process( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		$status = $callback->get_payment()->get_status();
		switch ( $status ) {
			case EcpGatewayOperationStatus::AWAITING_CONFIRMATION:
				$this->order_manager->hold_order( $callback, $order );
				break;
			case EcpGatewayOperationStatus::AWAITING_CUSTOMER:
				$this->order_manager->decline_order( $callback, $order );
				break;
			case EcpGatewayOperationStatus::EXTERNAL_PROCESSING:
				break;
			case EcpGatewayOperationStatus::AWAITING_FINALIZATION:
				$order->add_order_note( __( 'Direct debit request has been submitted successfully. Activation may take some time to complete.', 'woo-ecommpay' ) );
				$this->processOperation( $callback, $order );
				break;
			default:
				$this->processOperation( $callback, $order );
				break;
		}
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	private function processOperation( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		switch ( $callback->get_operation()->get_status() ) {
			case EcpGatewayOperationStatus::SUCCESS:
				$this->order_manager->complete_order( $callback, $order );
				break;
			case EcpGatewayOperationStatus::DECLINE:
			case EcpGatewayOperationStatus::EXPIRED:
			case EcpGatewayOperationStatus::INTERNAL_ERROR:
			case EcpGatewayOperationStatus::EXTERNAL_ERROR:
			$this->order_manager->decline_order( $callback, $order );
				break;
		}
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function auth( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		$this->auth_operation_handler->process( $callback, $order );
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function contract_registration( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		$this->contract_registration_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function verify( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		$this->ecp_verify_operation_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function confirm( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): void {
		$this->ecp_confirm_operation_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @throws WC_Data_Exception
	 */
	public function sale( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ) {
		$this->ecp_sale_operation_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return bool
	 */
	public function capture( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): bool {
		return $this->ecp_capture_operation_handler->process( $callback, $order );
	}

	/**
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return bool
	 */
	public function cancel( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): bool {
		return $this->ecp_cancel_operation_handler->process( $callback, $order );
	}

	/**
	 * Determine if the callback is from the dashboard
	 *
	 * @param EcpGatewayInfoCallback $callback
	 * @param EcpGatewayOrder $order
	 *
	 * @return bool
	 */
	public function is_callback_from_dashboard( EcpGatewayInfoCallback $callback, EcpGatewayOrder $order ): bool {
		$transaction_order_id = $order->get_transaction_order_id( 'view', $callback->get_operation()->get_type() );

		return empty( $transaction_order_id );
	}
}
