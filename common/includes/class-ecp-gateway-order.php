<?php

use Automattic\WooCommerce\Admin\Overrides\Order;
use common\helpers\WCOrderStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Order
 *
 * Extends Woocommerce order for easy access to internal data.
 *
 * @class    Ecp_Gateway_Order
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Order extends Order {
	use ECP_Gateway_Order_Extension;

	private const STATUS_FAILED = 'failed';

	/**
	 * Mark in order metadata for counting changed payment method.
	 */
	private const META_PAYMENT_METHOD_CHANGE_COUNT = '_ecommpay_payment_method_change_count';
	/**
	 * Mark in order metadata for counting failed payments.
	 */
	const META_FAILED_PAYMENT_COUNT = '_ecommpay_failed_payment_count';
	const META_REFUND_ATTEMPTS_COUNT = '_ecommpay_refund_attempts_count';
	/**
	 * Transaction identifier in order metadata.
	 */
	const META_TRANSACTION_ID = '_transaction_id';
	/**
	 * @var ?Ecp_Gateway_Payment
	 */
	private $payment;

	/**
	 * Returns the order ID based on the ID retrieved from the ECOMMPAY callback.
	 *
	 * @param Ecp_Gateway_Info_Callback $info The callback data as associative array.
	 *
	 * @return int Order identifier
	 */
	public static function get_order_id_from_callback( Ecp_Gateway_Info_Callback $info ) {
		global $wpdb;
		$payment_id = $info->get_payment()->get_id() ?? $_GET['payment_id'];

		if ( ecp_HPOS_enabled() ) {
			$orders = wc_get_orders( [
				'limit'      => 1,
				'meta_query' => [
					[
						'key'   => '_payment_id',
						'value' => $payment_id,
					],
				],
			] );

			return current( $orders ) ? current( $orders )->get_id() : false;
		} else {
			$query = "SELECT DISTINCT ID FROM $wpdb->posts as posts "
			         . "LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id "
			         . "WHERE meta.meta_value = %s AND meta.meta_key = %s";

			return $wpdb->get_var( $wpdb->prepare( $query, $payment_id, '_payment_id' ) );
		}
	}

	/**
	 * <h2>Fetches transaction data based on a transaction ID.</h2>
	 * <p>This method checks if the transaction is cached in a transient before it asks the ECOMMPAY API.
	 * Cached data will always be used if available.</p>
	 * <p>If no data is cached, we will fetch the transaction from the API and cache it.</p>
	 *
	 * @return Ecp_Gateway_Payment Order payment
	 */
	public function get_payment( $reload = false, $force = false ): ?Ecp_Gateway_Payment {
		if ( $reload || ! $this->payment ) {
			$this->payment = Ecp_Gateway_Payment_Provider::get_instance()->load( $this, $force );
		}

		return $this->payment;
	}

	/**
	 * @return string
	 */
	public function create_payment_id(): string {
		$_payment_id = $this->get_ecp_meta( '_payment_id' );
		if ( $_payment_id != '' & ( $_REQUEST['action'] != 'ecommpay_process' ) ) {
			$id = $_payment_id;
		} else if ( ! empty ( $_REQUEST['payment_id'] ) ) {
			$id = $_REQUEST['payment_id'];
		} else {
			$id = $this->get_id() . '_' . ( $this->get_failed_ecommpay_payment_count() + 1 );
		}

		$this->set_payment_id( $id );
		$this->set_ecp_payment_status( Ecp_Gateway_Payment_Status::INITIAL );
		$this->save_meta_data();

		ecp_get_log()->debug( __( 'New payment identifier created:', 'woo-ecommpay' ), $id );

		return $id;
	}

	/**
	 * <h2>Returns the count of failed payment attempts.</h2>
	 *
	 * @return int
	 */
	public function get_failed_ecommpay_payment_count(): int {
		$count = $this->get_ecp_meta( self::META_FAILED_PAYMENT_COUNT );

		if ( ! empty ( $count ) ) {
			ecp_get_log()->debug( __( 'Count of failed payment attempts:', 'woo-ecommpay' ), $count );

			return $count;
		}

		ecp_get_log()->debug( __( 'No failed payment attempts', 'woo-ecommpay' ) );

		return 0;
	}

	/**
	 * <h2>Returns subscriptions by order.</h2>
	 *
	 * @return Ecp_Gateway_Subscription[]
	 * @since 2.0.0
	 */
	public function get_subscriptions(): ?array {
		ecp_get_log()->debug( __( 'Find subscription', 'woo-ecommpay' ) );
		ecp_get_log()->debug( $this->get_id() );
		$subscriptions = wcs_get_subscriptions_for_order( $this->get_id() );

		if ( count( $subscriptions ) <= 0 ) {
			ecp_get_log()->warning( __( 'Subscription is not found.', 'woo-ecommpay' ) );
			ecp_get_log()->debug( __( 'Parent order ID:', 'woo-ecommpay' ), $this->get_id() );

			return null;
		}

		$ecp_subscriptions = [];
		foreach ( $subscriptions as $subscription ) {
			$ecp_subscriptions[] = ecp_get_order( $subscription->get_id() );
		}

		return $ecp_subscriptions;
	}


	/**
	 * <h2>Returns not processed refund object.</h2>
	 *
	 * @return Ecp_Gateway_Refund <p>Refund object.</b>
	 * @throws Ecp_Gateway_Logic_Exception When the refund object is not found.
	 * @throws Exception
	 */
	public function find_unprocessed_refund(): Ecp_Gateway_Refund {
		ecp_get_log()->debug( __( 'Find order unprocessed refund.', 'woo-ecommpay' ) );

		foreach ( $this->get_refunds() as $refund ) {
			if ( ! $refund->get_ecp_transaction_id() ) {
				ecp_get_log()->debug( __( 'Unprocessed refund found:', 'woo-ecommpay' ), $refund->get_id() );

				return $refund;
			}
		}

		throw new Ecp_Gateway_Logic_Exception( 'Not found refund object.' );
	}

	/**
	 * Get order refunds.
	 *
	 * @return Ecp_Gateway_Refund[] array of WC_Order_Refund objects
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function get_refunds(): array {
		$cache_key   = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $this->get_id();
		$cached_data = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		/** @var Ecp_Gateway_Refund[] $refunds */
		$refunds = ecp_get_orders(
			[
				'type'   => 'shop_order_refund',
				'parent' => $this->get_id(),
				'limit'  => - 1,
			]
		);

		wp_cache_set( $cache_key, $refunds, $this->cache_group );

		return $refunds;
	}

	/**
	 * <h2>Returns refund object by ECOMMPAY Request ID.</h2>
	 *
	 * @param string $request_id <p>ECOMMPAY Request ID</p>
	 *
	 * @return Ecp_Gateway_Refund|null <p>Refund object</p>
	 * @throws Ecp_Gateway_Logic_Exception When the refund object is not found.
	 * @throws Exception
	 */
	public function find_refund_by_request_id( string $request_id ): ?Ecp_Gateway_Refund {
		ecp_get_log()->debug( __( 'Find order refund by ECOMMPAY Request ID.', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Request ID:', 'woo-ecommpay' ), $request_id );

		foreach ( $this->get_refunds() as $refund ) {
			if ( $request_id === $refund->get_ecp_transaction_id() ) {
				ecp_get_log()->info( __( 'Refund by request id found. Refund ID:', 'woo-ecommpay' ), $refund->get_id() );

				return $refund;
			}
		}
		ecp_get_log()->info( __( 'Refund by request id is NOT found. Request ID:', 'woo-ecommpay' ), $request_id );
		return null;
	}
	
	/**
	 * Checks if the order is currently in a failed renewal
	 *
	 * @return bool
	 */
	public function subscription_is_renewal_failure(): bool {
		if ( ! ecp_subscription_is_active() ) {
			return false;
		}

		return ecp_subscription_is_renewal( $this ) && $this->get_status() === self::STATUS_FAILED;
	}

	/**
	 * <h2>Increase the count of failed payment attempts with ECOMMPAY.</h2>
	 *
	 * @return int
	 * @uses Ecp_Gateway_Order::get_failed_ecommpay_payment_count()
	 */
	public function increase_failed_ecommpay_payment_count(): int {
		$count = $this->get_failed_ecommpay_payment_count() + 1;
		$this->set_ecp_meta( self::META_FAILED_PAYMENT_COUNT, $count );

		ecp_get_log()->debug( __( 'Count of failed payment attempts increased:', 'woo-ecommpay' ), $count );

		return $count;
	}

	/**
	 * <h2>Increase the count of refund attempts with ECOMMPAY.</h2>
	 *
	 * @return int
	 * @uses Ecp_Gateway_Order::get_refund_attempts_count()
	 */
	public function increase_refund_attempts_count(): int {
		$count = $this->get_refund_attempts_count() + 1;
		$this->set_ecp_meta( self::META_REFUND_ATTEMPTS_COUNT, $count );

		ecp_get_log()->debug( __( 'Count of refund attempts increased:', 'woo-ecommpay' ), $count );

		return $count;
	}

	/**
	 * <h2>Returns the count of refund attempts.</h2>
	 *
	 * @return int
	 */
	public function get_refund_attempts_count(): int {
		$count = $this->get_ecp_meta( self::META_REFUND_ATTEMPTS_COUNT );

		if ( ! empty ( $count ) ) {
			ecp_get_log()->debug( __( 'Count of refund attempts:', 'woo-ecommpay' ), $count );

			return $count;
		}

		ecp_get_log()->debug( __( 'No refund attempts', 'woo-ecommpay' ) );

		return 0;
	}

	/**
	 * Increases the amount of times the customer has updated his card.
	 *
	 * @return int
	 * @uses Ecp_Gateway_Order::get_payment_method_change_count()
	 */
	public function increase_payment_method_change_count(): int {
		$count = $this->get_payment_method_change_count() + 1;
		$this->set_ecp_meta( self::META_PAYMENT_METHOD_CHANGE_COUNT, $count );

		return $count;
	}

	/**
	 * Gets the amount of times the customer has updated his card.
	 *
	 * @return int
	 */
	public function get_payment_method_change_count(): int {
		$count = $this->get_ecp_meta( self::META_PAYMENT_METHOD_CHANGE_COUNT );

		if ( ! empty ( $count ) ) {
			return $count;
		}

		return 0;
	}

	/**
	 * <h2>Returns the result of checking if am order contains a subscription product.</h2>
	 *
	 * @return bool <b>TRUE</b> if order contains a subscription product or <b>FALSE</b> otherwise.
	 */
	public function contains_subscription(): bool {
		if ( ! ecp_subscription_is_active() ) {
			return false;
		}

		if ( function_exists( 'wcs_order_contains_subscription' ) ) {
			return wcs_order_contains_subscription( $this );
		}

		ecp_get_log()->debug( __( 'The order does not contain subscription products', 'woo-ecommpay' ) );

		return false;
	}


	public function get_billing_address(): string {
		return implode( ' ', [ $this->get_billing_address_1(), $this->get_billing_address_2() ] );
	}

	public function get_shipping_type(): string {
		return '07';
	}

	public function get_shipping_name_indicator(): string {
		return $this->get_billing_first_name() === $this->get_shipping_first_name()
		       && $this->get_billing_last_name() === $this->get_shipping_last_name()
			? '01' : '02';
	}

	public function get_shipping_address(): string {
		return implode( ' ', [ $this->get_shipping_address_1(), $this->get_shipping_address_2() ] );
	}

	/**
	 * @param string $comment
	 * @param int $parent_comment
	 *
	 * @return int|null
	 */
	public function append_order_comment( string $comment, int $parent_comment = 0 ) {
		$commentData = [
			'comment_post_ID'      => $this->get_id(),
			'comment_author'       => 'ECOMMPAY',
			'comment_agent'        => 'Gate2025',
			'comment_author_email' => 'support@ecommpay.com',
			'comment_author_url'   => 'https://ecommpay.com',
			'comment_content'      => $comment,
			'comment_type'         => 'order_note',
			'comment_approved'     => 1,
			'comment_parent'       => $parent_comment,
			'user_id'              => 0,
		];

		$result = wp_insert_comment( $commentData );

		if ( ! is_numeric( $result ) ) {
			return null;
		}

		return $result;
	}

	/**
	 * Check if the action we are about to perform is allowed according to the current transaction state.
	 *
	 * @param $action
	 *
	 * @return boolean
	 */
	public function is_action_allowed( $action ): bool {
		$state             = $this->get_ecp_status();
		$remaining_balance = $this->get_payment()->get_remaining_balance();

		$allowed_states = [
			Ecp_Gateway_Operation_Type::REFUND => [
				Ecp_Gateway_Payment_Status::PARTIALLY_REVERSED,
				Ecp_Gateway_Payment_Status::PARTIALLY_REFUNDED,
				Ecp_Gateway_Payment_Status::SUCCESS
			],
			'renew'                            => [ 'awaiting capture' ],
			'recurring'                        => [ 'subscribe' ],
			'subscription'                     => [ 'success' ]
		];

		// We want to still allow captures if there is a remaining balance.
		if ( 'awaiting capture' === $state && $remaining_balance > 0 && $action !== 'cancel' ) {
			return true;
		}

		return in_array( $state, $allowed_states[ $action ] );
	}

	public function needs_processing(): bool {
        if (ecp_is_enabled(Ecp_Gateway_Settings_General::OPTION_AUTO_COMPETE_ORDER)) {
            return false;
        }
        return parent::needs_processing();
    }
}
