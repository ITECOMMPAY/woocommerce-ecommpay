<?php

use common\helpers\WCOrderStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Ecp_Gateway_Subscription
 *
 * Extends Woocommerce subscription for easy access to internal data.
 *
 * @class    Ecp_Gateway_Subscription
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class Ecp_Gateway_Subscription extends WC_Subscription {
	use ECP_Gateway_Order_Extension;

	public function set_recurring_id( $recurring_id ) {
		$this->set_ecp_meta( '_ecommpay_recurring_id', $recurring_id );
	}

	/**
	 * <h2>Returns recurring identifier.</h2>
	 *
	 * @return int <p>Recurring identifier.</p>
	 */
	public function get_recurring_id(): int {
		return (int) $this->get_ecp_meta( '_ecommpay_recurring_id' );
	}

	/**
	 * <h2>Returns the parent order from the subscription.</h2>
	 *
	 * @param int $id [optional] <i>** Unusable **</i>
	 *
	 * @return ?Ecp_Gateway_Order Parent Worder if exists or <b>NULL</b> otherwise.
	 * @since 2.0.0
	 * @uses ecp_get_order()
	 */
	public function get_order( $id = 0 ) {
		return ecp_get_order( $this->get_parent_id() );
	}
}
