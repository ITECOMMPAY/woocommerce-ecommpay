<?php


namespace common\helpers;

/**
 * Enum class for all the order statuses.
 *
 * For a full documentation on the public order statuses, please refer to the following link:
 * https://woocommerce.com/document/managing-orders/order-statuses/
 *
 * @since WooCommerce 9.5.0
 * https://github.com/woocommerce/woocommerce/blob/7f32086ae0e9e4370fc5a111e5d6c9d70785e96f/plugins/woocommerce/src/Enums/OrderStatus.php#L33
 */
final class WCOrderStatus {
	/**
	 * The order has been received, but no payment has been made.
	 *
	 * @var string
	 */
	const PENDING = 'pending';

	/**
	 * The customer’s payment failed or was declined, and no payment has been successfully made.
	 *
	 * @var string
	 */
	const FAILED = 'failed';

	/**
	 * The order is awaiting payment confirmation.
	 *
	 * @var string
	 */
	const ON_HOLD = 'on-hold';

	/**
	 * Order fulfilled and complete.
	 *
	 * @var string
	 */
	const COMPLETED = 'completed';

	/**
	 * Payment has been received (paid), and the stock has been reduced.
	 *
	 * @var string
	 */
	const PROCESSING = 'processing';

	/**
	 * Orders are automatically put in the Refunded status when an admin or shop manager has fully refunded the order’s value after payment.
	 *
	 * @var string
	 */
	const REFUNDED = 'refunded';

	/**
	 * The order was canceled by an admin or the customer.
	 *
	 * @var string
	 */
	const CANCELLED = 'cancelled';

	/**
	 * The order is in the trash.
	 *
	 * @var string
	 */
	const TRASH = 'trash';

	/**
	 * The order is a draft (legacy status).
	 *
	 * @var string
	 */
	const NEW = 'new';

	/**
	 * The order is an automatically generated draft.
	 *
	 * @var string
	 */
	const AUTO_DRAFT = 'auto-draft';

	/**
	 * Draft orders are created when customers start the checkout process while the block version of the checkout is in place.
	 *
	 * @var string
	 */
	const DRAFT = 'draft';
}