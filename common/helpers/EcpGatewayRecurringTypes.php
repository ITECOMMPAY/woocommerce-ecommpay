<?php

namespace common\helpers;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayRecurringTypes class
 *
 * @class    EcpGatewayRecurringTypes
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayRecurringTypes extends EcpAbstractApiObject {
	/**
	 * One-click payment
	 */
	public const PAYMENT = 'C';

	/**
	 * COF-purchase (Regular)
	 */
	public const REGULAR = 'R';

	/**
	 * Auto-payment
	 */
	public const AUTO = 'U';

	protected static array $names = [];

	protected static function compile_names(): array {
		return [
			self::PAYMENT => _x( 'One-click', 'Recurring type', 'woo-ecommpay' ),
			self::REGULAR => _x( 'Regular', 'Recurring type', 'woo-ecommpay' ),
			self::AUTO    => _x( 'Auto-payment', 'Recurring type', 'woo-ecommpay' ),
		];
	}
}
