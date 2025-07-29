<?php

namespace common\includes;

use common\exceptions\EcpGatewayAPIException;
use common\helpers\EcpGatewayOperationStatus;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayPaymentStatusTransition;
use common\models\EcpGatewayInfoAccount;
use common\models\EcpGatewayInfoACS;
use common\models\EcpGatewayInfoCustomer;
use common\models\EcpGatewayInfoError;
use common\models\EcpGatewayInfoOperation;
use common\models\EcpGatewayInfoPayment;
use DateTimeInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayPayment
 *
 * Contains payment details.
 *
 * @class    EcpGatewayPayment
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class EcpGatewayPayment {


	/**
	 * <h2>Status transition.</h2>
	 *
	 * @var ?EcpGatewayPaymentStatusTransition
	 */
	private ?EcpGatewayPaymentStatusTransition $status_transition = null;

	/**
	 * <h2>Parent order.</h2>
	 *
	 * @var EcpGatewayOrder
	 */
	private EcpGatewayOrder $order;

	/**
	 * <h2>Transactions.</h2>
	 *
	 * @var EcpGatewayInfoOperation[]
	 */
	private array $operations = [];

	/**
	 * <h2>Customer information.</h2>
	 *
	 * @var ?EcpGatewayInfoCustomer
	 */
	private ?EcpGatewayInfoCustomer $customer;

	/**
	 * <h2>Account information.</h2>
	 *
	 * @var ?EcpGatewayInfoAccount
	 */
	private ?EcpGatewayInfoAccount $account;

	/**
	 * <h2>ACS information.</h2>
	 *
	 * @var ?EcpGatewayInfoACS
	 */
	private ?EcpGatewayInfoACS $acs;

	/**
	 * <h2>List of errors.</h2>
	 *
	 * @var EcpGatewayInfoError[]
	 */
	private array $errors = [];

	/**
	 * <h2>Payment information.</h2>
	 *
	 * @var EcpGatewayInfoPayment
	 */
	private ?EcpGatewayInfoPayment $info = null;


	/**
	 * <h2>ECOMMPAY payment details constructor.</h2>
	 *
	 * @param EcpGatewayOrder $order <p>Parent order for payment.</p>
	 *
	 * @since 2.0.0
	 */
	public function __construct( EcpGatewayOrder $order ) {
		$this->order = $order;
	}

	public static function stub( $order ): EcpGatewayPayment {
		$obj = new static( $order );
		$obj->set_info( new EcpGatewayInfoPayment( [
			'status' => EcpGatewayPaymentStatus::INITIAL,
			'method' => 'Not selected',
		] ) );

		return $obj;
	}

	/**
	 * <h2>Returns parent order.</h2>
	 *
	 * @return EcpGatewayOrder Parent order object
	 * @since 2.0.0
	 */
	public function get_order(): EcpGatewayOrder {
		return $this->order;
	}

	/**
	 * <h2>Stores payment details to the cache.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function save(): void {
		EcpGatewayPaymentProvider::get_instance()->save( $this );
	}

	/**
	 * <h2>Set payment status.</h2>
	 * <p>Note: This method does not save the new status. To save the new status, you must run the
	 * {@see EcpGatewayPayment::status_transition() status transition} process.</p>
	 * <p>When you saved payment details, the status transition will be performed automatically.</p>
	 *
	 * @param string $new_status <p>Status to change the payment to.</p>
	 * @param string $note [optional] <p>Optional note to add. Default: blank string.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function set_status( string $new_status, string $note = '' ): void {
		ecp_get_log()->info( __( 'Setting the payment status.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'New payment status:', 'woo-ecommpay' ), $new_status );
		ecp_get_log()->debug( __( 'Note:', 'woo-ecommpay' ), $note !== '' ? $note : __( '* Not defined *', 'woo-ecommpay' ) );
		$old_status = $this->order->get_ecp_status();

		if ( ! $this->order->get_object_read() ) {
			ecp_get_log()->warning( __( 'Order object could not be read. Process interrupted.' ), 'woo-ecommpay' );

			return;
		}

		$this->status_transition = new EcpGatewayPaymentStatusTransition(
			[
				'old'  => $old_status,
				'new'  => $new_status,
				'note' => $note
			]
		);

		if ( ! $this->status_transition->is_changed() ) {
			ecp_get_log()->debug( __( 'Old and new payment status are identically. Process interrupted.' ), 'woo-ecommpay' );

			return;
		}

		$this->order->set_ecp_payment_status( $this->status_transition->get_new() );
		$this->order->maybe_set_date_paid();
		ecp_get_log()->debug( __( 'The payment status has settled.', 'woo-ecommpay' ) );
	}

	/**
	 * <h2>Transition the payment status.</h2>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function status_transition(): void {
		ecp_get_log()->info( __( 'Transition the payment status.', 'woo-ecommpay' ) );

		if ( ! $this->status_transition ) {
			ecp_get_log()->warning( __( 'Transition is not set. Interrupt process.', 'woo-ecommpay' ) );

			return;
		}

		// Copy status transition to local variable.
		$transition = $this->status_transition;
		// Reset status transition variable.
		$this->status_transition = null;

		try {
			do_action( 'ecommpay_payment_status_' . $transition->get_new(), $this->get_id(), $this );

			switch ( true ) {
				case ! empty ( $transition->get_old() ):
					if ( ! $transition->is_changed() ) {
						return;
					}

					/* translators: 1: old payment status 2: new payment status */
					$note = sprintf(
						__( 'Payment status changed from %1$s to %2$s.', 'woo-ecommpay' ),
						ecp_get_payment_status_name( $transition->get_old() ),
						ecp_get_payment_status_name( $transition->get_new() )
					);

					do_action(
						'ecommpay_payment_status_' . $transition->get_old() . '_to_' . $transition->get_new(),
						$this->get_id(),
						$this
					);
					do_action(
						'ecommpay_payment_status_changed',
						$this->get_id(),
						$transition->get_old(),
						$transition->get_new(),
						$this
					);
					break;
				default:
					/* translators: %s: new payment status */
					$note = sprintf(
						__( 'Payment status set to %s.', 'woo-ecommpay' ),
						ecp_get_payment_status_name( $transition->get_new() )
					);
			}

			// Note the transition occurred.
			$this->order->add_order_note( trim( $transition->get_note() . ' ' . $note ) );
		} catch ( Exception $e ) {
			ecp_get_log()->error(
				sprintf( __( 'Status transition of payment #%d errored!', 'woo-ecommpay' ), $this->get_id() )
			);

			$this->order->add_order_note(
				__( 'Error during payment status transition.', 'woocommerce' ) . ' ' . $e->getMessage()
			);
		}

		ecp_get_log()->debug( __( 'The payment status has changed.', 'woo-ecommpay' ) );
	}

	/**
	 * <h2>Returns the payment identifier.</h2>
	 *
	 * @return string Payment identifier as string.
	 * @since 2.0.0
	 */
	public function get_id(): string {
		return $this->order->get_payment_id();
	}

	/**
	 * <h2>Returns customer information.</h2>
	 *
	 * @return ?EcpGatewayInfoCustomer
	 * @since 2.0.0
	 */
	public function get_customer(): ?EcpGatewayInfoCustomer {
		return $this->customer;
	}

	/**
	 * <h2>Sets customer information.</h2>
	 *
	 * @param EcpGatewayInfoCustomer|null $customer [optional]
	 *
	 * @return static Current payment object.
	 * @since 2.0.0
	 */
	public function set_customer( EcpGatewayInfoCustomer $customer = null ): EcpGatewayPayment {
		$this->customer = $customer;

		return $this;
	}

	/**
	 * <h2>Returns ACS information.</h2>
	 *
	 * @return ?EcpGatewayInfoACS
	 * @since 2.0.0
	 */
	public function get_acs(): ?EcpGatewayInfoACS {
		return $this->acs;
	}

	/**
	 * <h2>Sets ACS information.</h2>
	 *
	 * @param EcpGatewayInfoACS|null $acs [optional]
	 *
	 * @return static Current payment object.
	 * @since 2.0.0
	 */
	public function set_acs( EcpGatewayInfoACS $acs = null ): EcpGatewayPayment {
		$this->acs = $acs;

		return $this;
	}

	/**
	 * <h2>Returns account information.</h2>
	 * @return ?EcpGatewayInfoAccount
	 * @since 2.0.0
	 */
	public function get_account(): ?EcpGatewayInfoAccount {
		return $this->account;
	}

	/**
	 * <h2>Sets account information.</h2>
	 *
	 * @param EcpGatewayInfoAccount|null $account [optional]
	 *
	 * @return static Current payment object.
	 * @since 2.0.0
	 */
	public function set_account( EcpGatewayInfoAccount $account = null ): EcpGatewayPayment {
		$this->account = $account;

		return $this;
	}

	/**
	 * <h2>Returns transactions.</h2>
	 * @return EcpGatewayInfoOperation[]
	 * @since 2.0.0
	 */
	public function get_operations(): array {
		return $this->operations;
	}

	/**
	 * <h2>Sets transactions.</h2>
	 *
	 * @param EcpGatewayInfoOperation[] $operations [optional]
	 *
	 * @return static Current payment object.
	 * @since 2.0.0
	 */
	public function set_operations( array $operations = [] ): EcpGatewayPayment {
		foreach ( $operations as $operation ) {
			$this->add_operation( $operation );
		}

		return $this;
	}

	/**
	 * @param EcpGatewayInfoOperation $operation
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function add_operation( EcpGatewayInfoOperation $operation ): void {
		ecp_get_log()->info( __( 'Add operation to payment data', 'woo-ecommpay' ) );

		foreach ( $this->operations as &$origin ) {
			ecp_get_log()->debug( __( 'Check operation request id', 'woo-ecommpay' ), $origin->get_request_id() );

			// Find operation in current information
			if ( $origin->get_request_id() === $operation->get_request_id() ) {
				if ( ! $origin->try_get_date( $origin_date ) ) {
					ecp_get_log()->debug(
						__( 'Old operation date is not exists. Update operation.', 'woo-ecommpay' )
					);
					// Replace current by new value and save
					$origin = $operation;
					ecp_get_log()->debug( __( 'Complete - operation information changed', 'woo-ecommpay' ) );

					return;
				}

				if ( ! $operation->try_get_date( $operation_date ) ) {
					ecp_get_log()->debug(
						__( 'New operation date is not exists. Skip update operation.', 'woo-ecommpay' )
					);

					return;
				}

				ecp_get_log()->debug(
					__( 'Find. Check operation last date', 'woo-ecommpay' ),
					$origin_date->format( DateTimeInterface::RFC1123 )
				);

				if ( $origin_date > $operation_date ) {

					ecp_get_log()->debug(
						sprintf(
							__( 'New operation date [%s] is less then old operation date [%s]', 'woo-ecommpay' ),
							$operation_date->format( DateTimeInterface::RFC1123 ),
							$origin_date->format( DateTimeInterface::RFC1123 )
						)
					);

					return;
				}

				ecp_get_log()->debug(
					sprintf(
						__( 'New operation date [%s] is great then old operation date [%s]. Skip update', 'woo-ecommpay' ),
						$operation_date->format( DateTimeInterface::RFC1123 ),
						$origin_date->format( DateTimeInterface::RFC1123 )
					)
				);

				$origin = $operation;

				return;
			}
		}

		// New operation - add to list
		ecp_get_log()->info( __( 'Operation added to payment.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Operation request id:', 'woo-ecommpay' ), $operation->get_request_id() );
		$this->operations[] = $operation;
	}

	public function get_request_id(): string {
		return $this->get_last_operation()->get_request_id();
	}

	/**
	 * <h2>Returns the last successful transaction operation.</h2>
	 *
	 * @return ?EcpGatewayInfoOperation
	 * @since 2.0.0
	 */
	public function get_last_operation(): ?EcpGatewayInfoOperation {
		// Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
		foreach ( array_reverse( $this->get_operations() ) as $operation ) {
			if ( $operation->get_status() === EcpGatewayOperationStatus::SUCCESS ) {
				return $operation;
			}
		}

		return $this->get_first_operation();
	}

	/**
	 * <h2>Returns the first operation info.</h2>
	 *
	 * @return EcpGatewayInfoOperation
	 * @since 2.0.0
	 */
	public function get_first_operation(): EcpGatewayInfoOperation {
		$operations = $this->operations;
		usort( $operations, [ $this, 'sort_operation' ] );

		return $operations[0];
	}

	public function get_operation_status(): string {
		return $this->get_last_operation()->get_status();
	}

	/**
	 * Returns a transaction currency
	 *
	 * @return string
	 * @throws EcpGatewayAPIException
	 * @since 2.0.0
	 */
	public function get_currency(): string {
		if ( ! $this->info instanceof EcpGatewayInfoPayment ) {
			throw new EcpGatewayAPIException( 'No API payment resource data available.', 0 );
		}

		return $this->info->get_sum()->get_currency();
	}

	/**
	 * Returns a remaining balance
	 *
	 * @return mixed
	 * @since 2.0.0
	 */
	public function get_remaining_balance() {
		$balance = $this->get_balance();

		$authorized_operations = array_filter( $this->operations, function ( $operation ) {
			return in_array( $operation->get_type(), [ 'auth', 'recurring' ] );
		} );

		if ( empty ( $authorized_operations ) ) {
			return null;
		}

		$operation = reset( $authorized_operations );
		$amount    = $operation->get_sum_initial()->get_amount();
		$remaining = $amount;

		if ( $balance > 0 ) {
			$remaining = $amount - $balance;
		}

		return $remaining;
	}

	/**
	 * Returns the transaction balance
	 *
	 * @return float|int|null
	 * @since 2.0.0
	 */
	public function get_balance() {
		if ( is_null( $this->info ) ) {
			return null;
		}

		return ! empty ( $this->info->get_sum() ) ? $this->info->get_sum()->get_amount() : null;
	}

	/**
	 * Returns the current payment type
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function get_current_type(): string {
		if ( ! $this->has_operations() ) {
			return '';
		}

		return $this->get_last_operation()->get_type();
	}

	/**
	 * <h2>Returns result of check operations exists.</h2>
	 *
	 * @return bool <b>TRUE</b> if operations exists or <b>FALSE</b> otherwise.
	 * @since 2.0.0
	 */
	public function has_operations(): bool {
		return count( $this->operations ) > 0;
	}

	/**
	 * @param EcpGatewayInfoOperation $a
	 * @param EcpGatewayInfoOperation $b
	 *
	 * @return int
	 * @since 2.0.0
	 */
	public function sort_operation( EcpGatewayInfoOperation $a, EcpGatewayInfoOperation $b ): int {
		switch ( true ) {
			case $a->get_created_date() > $b->get_created_date():
				return 1;
			case $a->get_created_date() < $b->get_created_date():
				return - 1;
			default:
				return 0;
		}
	}

	/**
	 * <h2>Returns the operation by ECOMMPAY request identifier.</h2>
	 *
	 * @param string $request_id
	 *
	 * @return ?EcpGatewayInfoOperation Operation if exists or <b>NULL</b> otherwise.
	 * @since 2.0.0
	 */
	public function get_operation_by_request( string $request_id ): ?EcpGatewayInfoOperation {
		ecp_get_log()->info( __( 'Try get operation by request identifier.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Request ID:', 'woo-ecommpay' ), $request_id );
		ecp_get_log()->debug( __( 'Count of operations:', 'woo-ecommpay' ), count( $this->operations ) );

		foreach ( $this->operations as $operation ) {
			ecp_get_log()->debug( __( 'Operation checked request:', 'woo-ecommpay' ), $operation->get_request_id() );

			if ( $operation->get_request_id() === $request_id ) {
				ecp_get_log()->info( __( 'Found required operation information.', 'woo-ecommpay' ) );

				return $operation;
			}
		}

		ecp_get_log()->info( __( 'Not found required operation information.', 'woo-ecommpay' ) );

		return null;
	}

	/**
	 * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a
	 * transient before it asks the ECOMMPAY API. Cached data will always be used if available.
	 *
	 * If no data is cached, we will fetch the transaction from the API and cache it.
	 *
	 * @return EcpGatewayInfoPayment
	 * @since 2.0.0
	 */
	public function get_info(): EcpGatewayInfoPayment {
		if ( ! $this->info ) {
			$this->info = new EcpGatewayInfoPayment( [
				'status' => EcpGatewayPaymentStatus::INITIAL,
				'method' => 'Not selected',
			] );
		}

		return $this->info;
	}

	/**
	 * <h2>Sets payment information.</h2>
	 *
	 * @param EcpGatewayInfoPayment $info
	 *
	 * @return static Current payment object.
	 * @since 2.0.0
	 */
	public function set_info( EcpGatewayInfoPayment $info ): EcpGatewayPayment {
		$this->info = $info;
		$this->set_status( $info->get_status() );

		return $this;
	}

	/**
	 * @return ?int|int[]
	 * @since 2.0.0
	 */
	public function get_code() {
		if ( count( $this->errors ) > 0 ) {
			$codes = [];

			foreach ( $this->errors as $error ) {
				$codes[] = $error->get_code();
			}

			return $codes;
		}

		if ( $this->has_operations() ) {
			return $this->get_last_operation()->get_code();
		}

		return null;
	}

	/**
	 * @return ?string|string[]
	 * @since 2.0.0
	 */
	public function get_message() {
		/** @var EcpGatewayInfoError[] $errors */
		if ( count( $this->errors ) > 0 ) {
			$messages = [];
			foreach ( $errors as $error ) {
				$messages[] = $error->get_message();
			}

			return $messages;
		}

		if ( $this->has_operations() ) {
			return $this->get_last_operation()->get_message();
		}

		return null;
	}
}
