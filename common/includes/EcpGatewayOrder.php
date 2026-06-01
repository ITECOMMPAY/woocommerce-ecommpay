<?php

namespace common\includes;

use Automattic\WooCommerce\Admin\Overrides\Order;
use common\exceptions\EcpGatewayLogicException;
use common\helpers\EcpGatewayOperationType;
use common\helpers\EcpGatewayPaymentStatus;
use common\models\EcpGatewayInfoCallback;
use common\modules\EcpModuleSubscription;
use common\settings\EcpSettingsGeneral;
use Exception;
use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayOrder
 *
 * Extends Woocommerce order for easy access to internal data.
 *
 * @class    EcpGatewayOrder
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class EcpGatewayOrder extends Order {
	use EcpGatewayOrderExtension;

	/**
	 * Mark in order metadata for counting failed payments.
	 */
	public const META_FAILED_PAYMENT_COUNT  = '_ecommpay_failed_payment_count';
	public const META_REFUND_ATTEMPTS_COUNT = '_ecommpay_refund_attempts_count';

	/**
	 * Transaction identifier in order metadata.
	 */
	public const META_TRANSACTION_ID               = '_transaction_id';
	private const CANCEL_ACTION                    = 'cancel';
	private const STATUS_FAILED                    = 'failed';
	private const META_PAYMENT_METHOD_CHANGE_COUNT = '_ecommpay_payment_method_change_count';
	private const ORDER_PAY_ECOMMPAY_ACTION_NAME   = 'ecommpay_process';
	private const SQL_GET_ORDER_BY_PAYMENT_ID      = 'SELECT DISTINCT ID FROM %i as posts '
		. 'LEFT JOIN %i as meta ON posts.ID = meta.post_id '
		. 'WHERE meta.meta_value = %s AND meta.meta_key = %s';
	private const ACTION_RENEW                     = 'renew';
	private const ACTION_RECURRING                 = 'recurring';
	private const ACTION_SUBSCRIPTION              = 'subscription';
	private const STATUS_SUBSCRIBE                 = 'subscribe';
	private const STATUS_SUCCESS                   = 'success';

	/**
	 * Payment statuses that require creating a new payment_id.
	 */
	private const STATUSES_REQUIRING_NEW_PAYMENT_ID = array(
		EcpGatewayPaymentStatus::DECLINE,
		EcpGatewayPaymentStatus::EXPIRED,
		EcpGatewayPaymentStatus::INTERNAL_ERROR,
		EcpGatewayPaymentStatus::EXTERNAL_ERROR,
	);

	/**
	 * @var ?EcpGatewayPayment
	 */
	private ?EcpGatewayPayment $payment = null;


	/**
	 * Returns the order ID based on the ID retrieved from the ECOMMPAY callback.
	 *
	 * @param EcpGatewayInfoCallback $info The callback data as associative array.
	 *
	 * @return int Order identifier
	 */
	public static function get_order_id_from_callback( EcpGatewayInfoCallback $info ) {
		global $wpdb;

		$payment_id = $info->get_payment()->get_id();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Payment ID from callback parameter
		if ( ! $payment_id && isset( $_GET['payment_id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Payment ID from callback parameter
			$payment_id = sanitize_text_field( wp_unslash( $_GET['payment_id'] ) );
		}

		if ( ecp_HPOS_enabled() ) {
			$orders = wc_get_orders(
				array(
					'limit'      => 1,
					'meta_query' => array(
						array(
							'key'   => '_payment_id',
							'value' => $payment_id,
						),
					),
				)
			);

			return current( $orders ) ? current( $orders )->get_id() : false;
		} else {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare(
				self::SQL_GET_ORDER_BY_PAYMENT_ID,
				$wpdb->posts,
				$wpdb->postmeta,
				$payment_id,
				'_payment_id'
			);

			return $wpdb->get_var( $query );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	/**
	 * <h2>Fetches transaction data based on a transaction ID.</h2>
	 * <p>This method checks if the transaction is cached in a transient before it asks the ECOMMPAY API.
	 * Cached data will always be used if available.</p>
	 * <p>If no data is cached, we will fetch the transaction from the API and cache it.</p>
	 *
	 * @return EcpGatewayPayment Order payment
	 * @throws EcpGatewayLogicException
	 */
	public function get_payment( $reload = false, $force = false ): ?EcpGatewayPayment {
		if ( $reload || ! $this->payment ) {
			$this->payment = EcpGatewayPaymentProvider::get_instance()->load( $this, $force );
		}

		return $this->payment;
	}

	/**
	 * @return string
	 */
	public function create_payment_id(): string {
		$embeddedModePaymentId = $this->getEmbeddedModePaymentId();
		if ( $embeddedModePaymentId ) {
			$paymentId = $embeddedModePaymentId;
		} else {
			// Check if we can reuse existing payment_id first.
			$reusablePaymentId = $this->get_reusable_payment_id();
			if ( $reusablePaymentId ) {
				ecp_info(
					sprintf(
						ecp_tr( 'Reusing existing payment ID %1$s (status: %2$s)' ),
						$reusablePaymentId,
						$this->get_ecp_status()
					)
				);
				return $reusablePaymentId;
			}
			$paymentId = generateNewPaymentId( $this );
		}

		$this->set_payment_id( $paymentId );
		$this->set_ecp_payment_status( EcpGatewayPaymentStatus::INITIAL );
		$this->save_meta_data();

		ecp_debug( ecp_tr( 'New payment identifier created:' ), $paymentId );

		return $paymentId;
	}

	private function get_reusable_payment_id(): ?string {
		$existing_payment_id = $this->get_payment_id();

		if ( ! $existing_payment_id ) {
			return null;
		}

		$current_status = $this->get_ecp_status();

		if ( in_array( $current_status, self::STATUSES_REQUIRING_NEW_PAYMENT_ID, true ) ) {
			return null;
		}

		return $existing_payment_id;
	}

	private function getEmbeddedModePaymentId(): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Payment ID from payment gateway callback
		return $_POST['payment_id'] ?? null;
	}

	/**
	 * <h2>Returns the count of failed payment attempts.</h2>
	 *
	 * @return int
	 */
	public function get_failed_ecommpay_payment_count(): int {
		$count = $this->get_ecp_meta( self::META_FAILED_PAYMENT_COUNT );

		if ( ! empty( $count ) ) {
			ecp_debug( ecp_tr( 'Count of failed payment attempts:' ), $count );

			return $count;
		}

		ecp_debug( ecp_tr( 'No failed payment attempts' ) );

		return 0;
	}

	/**
	 * <h2>Returns subscriptions by order.</h2>
	 *
	 * @return EcpGatewaySubscription[]
	 * @since 2.0.0
	 */
	public function get_subscriptions(): ?array {
		ecp_debug( ecp_tr( 'Find subscription' ) );
		ecp_debug( $this->get_id() );
		$subscriptions = wcs_get_subscriptions_for_order( $this->get_id() );

		if ( count( $subscriptions ) <= 0 ) {
			ecp_warn( ecp_tr( 'Subscription is not found.' ) );
			ecp_debug( ecp_tr( 'Parent order ID:' ), $this->get_id() );

			return null;
		}

		$ecp_subscriptions = array();
		foreach ( $subscriptions as $subscription ) {
			$ecp_subscriptions[] = ecp_get_order( $subscription->get_id() );
		}

		return $ecp_subscriptions;
	}

	/**
		* <h2>Returns not processed refund object.</h2>
	 *
	 * @return EcpGatewayRefund <p>Refund object.</b>
	 * @throws EcpGatewayLogicException When the refund object is not found.
	 * @throws Exception
	 * @throws Exception
	 */
	public function find_unprocessed_refund(): EcpGatewayRefund {
		ecp_debug( ecp_tr( 'Find order unprocessed refund.' ) );

		foreach ( $this->get_refunds() as $refund ) {
			if ( ! $refund->get_ecp_transaction_id() ) {
				ecp_debug( ecp_tr( 'Unprocessed refund found:' ), $refund->get_id() );

				return $refund;
			}
		}

		throw new EcpGatewayLogicException( 'Not found refund object.' );
	}

	/**
	 * Get order refunds.
	 *
	 * Caches only refund IDs in wp_cache (safe — WooCommerce only re-wraps objects,
	 * not plain integers). Objects are always fetched fresh by ID via ecp_get_orders()
	 * so they are always returned as EcpGatewayRefund instances.
	 *
	 * @return EcpGatewayRefund[] array of EcpGatewayRefund objects
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function get_refunds(): array {
		$cache_key  = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refund_ids' . $this->get_id();
		$refund_ids = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $refund_ids ) {
			$refunds    = $this->fetch_refunds_from_db();
			$refund_ids = $this->extract_refund_ids( $refunds );
			wp_cache_set( $cache_key, $refund_ids, $this->cache_group );
		} else {
			$refunds = $this->fetch_refunds_by_ids( $refund_ids );
		}

		return $this->filter_ecp_refunds( $refunds );
	}

	/**
	 * Fetch all refunds for this order from the database.
	 *
	 * @return EcpGatewayRefund[]
	 * @throws Exception
	 */
	private function fetch_refunds_from_db(): array {
		return ecp_get_orders(
			array(
				'type'   => EcpModuleSubscription::SHOP_ORDER_REFUND,
				'parent' => $this->get_id(),
				'limit'  => -1,
			)
		);
	}

	/**
	 * Fetch refunds by their IDs (cache hit path).
	 *
	 * @param int[] $refund_ids
	 *
	 * @return EcpGatewayRefund[]
	 * @throws Exception
	 */
	private function fetch_refunds_by_ids( array $refund_ids ): array {
		if ( empty( $refund_ids ) ) {
			return array();
		}

		return ecp_get_orders(
			array(
				'type'          => EcpModuleSubscription::SHOP_ORDER_REFUND,
				'post__in'      => $refund_ids,
				'orderby'       => 'post__in',
				'limit'         => -1,
				'no_found_rows' => true,
			)
		);
	}

	/**
	 * Extract IDs from a list of refund objects.
	 *
	 * @param EcpGatewayRefund[] $refunds
	 * @return int[]
	 */
	private function extract_refund_ids( array $refunds ): array {
		$ids = array();
		foreach ( $refunds as $refund ) {
			if ( $refund instanceof EcpGatewayRefund ) {
				$ids[] = $refund->get_id();
			}
		}
		return $ids;
	}

	/**
	 * Filter a list of orders keeping only EcpGatewayRefund instances.
	 *
	 * @param array $refunds
	 * @return EcpGatewayRefund[]
	 */
	private function filter_ecp_refunds( array $refunds ): array {
		$result = array();
		foreach ( $refunds as $refund ) {
			if ( $refund instanceof EcpGatewayRefund ) {
				$result[] = $refund;
			}
		}
		return $result;
	}

	/**
	 * <h2>Returns refund object by ECOMMPAY Request ID.</h2>
	 *
	 * @param string $request_id <p>ECOMMPAY Request ID</p>
	 *
	 * @return EcpGatewayRefund|null <p>Refund object</p>
	 * @throws Exception
	 */
	public function find_refund_by_request_id( string $request_id ): ?EcpGatewayRefund {
		ecp_debug( ecp_tr( 'Find order refund by ECOMMPAY Request ID.' ) );
		ecp_debug( ecp_tr( 'Request ID:' ), $request_id );

		foreach ( $this->get_refunds() as $refund ) {
			if ( $request_id === $refund->get_ecp_transaction_id() ) {
				ecp_info( ecp_tr( 'Refund by request id found. Refund ID:' ), $refund->get_id() );

				return $refund;
			}
		}
		ecp_info( ecp_tr( 'Refund by request id is NOT found. Request ID:' ), $request_id );

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
	 * @uses EcpGatewayOrder::get_failed_ecommpay_payment_count()
	 */
	public function increase_failed_ecommpay_payment_count(): int {
		$count = $this->get_failed_ecommpay_payment_count() + 1;
		$this->set_ecp_meta( self::META_FAILED_PAYMENT_COUNT, $count );

		ecp_debug( ecp_tr( 'Count of failed payment attempts increased:' ), $count );

		return $count;
	}

	/**
	 * <h2>Increase the count of refund attempts with ECOMMPAY.</h2>
	 *
	 * @return int
	 * @uses EcpGatewayOrder::get_refund_attempts_count()
	 */
	public function increase_refund_attempts_count(): int {
		$count = $this->get_refund_attempts_count() + 1;
		$this->set_ecp_meta( self::META_REFUND_ATTEMPTS_COUNT, $count );

		ecp_debug( ecp_tr( 'Count of refund attempts increased:' ), $count );

		return $count;
	}

	/**
	 * <h2>Returns the count of refund attempts.</h2>
	 *
	 * @return int
	 */
	public function get_refund_attempts_count(): int {
		$count = $this->get_ecp_meta( self::META_REFUND_ATTEMPTS_COUNT );

		if ( ! empty( $count ) ) {
			ecp_debug( ecp_tr( 'Count of refund attempts:' ), $count );

			return $count;
		}

		ecp_debug( ecp_tr( 'No refund attempts' ) );

		return 0;
	}

	/**
	 * Increases the amount of times the customer has updated his card.
	 *
	 * @return int
	 * @uses EcpGatewayOrder::get_payment_method_change_count()
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

		if ( ! empty( $count ) ) {
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

		ecp_debug( ecp_tr( 'The order does not contain subscription products' ) );

		return false;
	}

	public function get_billing_address(): string {
		return trim( implode( ' ', array( $this->get_billing_address_1(), $this->get_billing_address_2() ) ) );
	}

	public function get_billing_postcode( $context = 'view' ): string {
		return trim( parent::get_billing_postcode( $context ) );
	}

	/**
	 * @param string $comment
	 * @param int $parent_comment
	 *
	 * @return int|null
	 */
	public function append_order_comment( string $comment, int $parent_comment = 0 ) {
		$commentData = array(
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
		);

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
	 * @throws EcpGatewayLogicException
	 */
	public function is_action_allowed( $action ): bool {
		$state             = $this->get_ecp_status();
		$remaining_balance = $this->get_payment()->get_remaining_balance();

		$allowed_states = array(
			EcpGatewayOperationType::REFUND => array(
				EcpGatewayPaymentStatus::PARTIALLY_REVERSED,
				EcpGatewayPaymentStatus::PARTIALLY_REFUNDED,
				EcpGatewayPaymentStatus::SUCCESS,
			),
			self::ACTION_RENEW              => array( EcpGatewayPaymentStatus::AWAITING_CAPTURE ),
			self::ACTION_RECURRING          => array( self::STATUS_SUBSCRIBE ),
			self::ACTION_SUBSCRIPTION       => array( self::STATUS_SUCCESS ),
		);

		// We want to still allow captures if there is a remaining balance.
		if ( EcpGatewayPaymentStatus::AWAITING_CAPTURE === $state && $remaining_balance > 0 && $action !== self::CANCEL_ACTION ) {
			return true;
		}

		return in_array( $state, $allowed_states[ $action ], true );
	}

	public function needs_processing(): bool {
		if ( ecp_is_enabled( EcpSettingsGeneral::OPTION_AUTO_COMPETE_ORDER ) ) {
			return false;
		}

		return parent::needs_processing();
	}
}
