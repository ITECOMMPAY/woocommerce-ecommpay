<?php

namespace common\includes;

use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewaySubscription
 *
 * Extends Woocommerce subscription for easy access to internal data.
 *
 * @class    EcpGatewaySubscription
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 */
class EcpGatewaySubscription extends WC_Subscription {
	use EcpGatewayOrderExtension;

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
	 * @return ?EcpGatewayOrder Parent Worder if exists or <b>NULL</b> otherwise.
	 * @since 2.0.0
	 * @uses ecp_get_order()
	 */
	public function get_order( $id = 0 ): ?EcpGatewayOrder {
		return ecp_get_order( $this->get_parent_id() );
	}
}
