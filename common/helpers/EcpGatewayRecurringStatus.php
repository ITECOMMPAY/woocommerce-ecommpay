<?php

namespace common\helpers;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayRecurringStatus class
 *
 * @class    EcpGatewayRecurringStatus
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayRecurringStatus extends EcpAbstractApiObject {
	/**
	 * COF-purchase is active
	 */
	const ACTIVE = 'active';

	/**
	 * COF-purchase is cancelled
	 */
	const CANCELLED = 'cancelled';

	protected static array $names = [];


	protected static function compile_names(): array {
		return [
			self::ACTIVE    => _x( 'Active', 'Recurring status', 'woo-ecommpay' ),
			self::CANCELLED => _x( 'Cancelled', 'Recurring status', 'woo-ecommpay' ),
		];
	}
}
