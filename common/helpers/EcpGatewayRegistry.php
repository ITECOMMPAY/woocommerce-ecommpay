<?php

namespace common\helpers;

use common\exceptions\EcpGatewayError;
use common\exceptions\EcpGatewayLogicException;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>Registry of internal library modules.</h2>
 *
 * @class    EcpGatewayRegistry
 * @version  2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @abstract
 * @internal
 */
abstract class EcpGatewayRegistry {

	protected const MODE_PURCHASE    = 'purchase';
	protected const MODE_CARD_VERIFY = 'card_verify';

	/**
	 * <h2>An array of instances of various modules.</h2>
	 *
	 * @var array
	 * @since 2.0.0
	 */
	private static array $instances;

	/**
	 * <h2>Closed constructor of base registry.</h2>
	 * <p>To instantiate modules, you need to use the following of the static methods:<br/>
	 *      - {@see EcpGatewayRegistry::get_by_class()} Get instance by class name.<br/>
	 *      - {@see EcpGatewayRegistry::get_instance()} Get an instance from the called class.<br/>
	 * </p>
	 * @since 2.0.0
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * <h2>Sets up a new object.</h2>
	 * <p>Can be used for adding hooks and filters.</p>
	 * <p>Executed when an object instance is created.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	protected function init(): void {
		// Default: do nothing
	}

	/**
	 * <h2>Return an instance of an object by the class being called.</h2>
	 *
	 * @return static An instance of the current object.
	 * @throws EcpGatewayLogicException
	 * @since 2.0.0
	 */
	final public static function get_instance(): EcpGatewayRegistry {
		$class_name = get_called_class();

		return self::get_by_class( $class_name );
	}

	/**
	 * <h2>Returns an object instance by class name.</h2>
	 *
	 * @param string $class_name <p>Object class name.</p>
	 *
	 * @return static An instance of an object created by the class name.
	 * @throws EcpGatewayLogicException
	 * @since 2.0.0
	 */
	final public static function get_by_class( string $class_name ): EcpGatewayRegistry {
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			if ( ! class_exists( $class_name ) ) {
				throw new EcpGatewayLogicException(
					sprintf(
						/* translators: %s: class name */
						esc_html__( 'Class \'%s\' not found', 'woo-ecommpay' ),
						esc_html( $class_name )
					),
					EcpGatewayError::NOT_AVAILABLE // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				);
			}
			self::$instances[ $class_name ] = new $class_name();
		}

		return self::$instances[ $class_name ];
	}

	/**
	 * <h2>Cloning disabled.</h2>
	 * @since 2.0.0
	 */
	private function __clone() {
	}
}
