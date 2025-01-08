<?php

namespace common\interfaces;

/**
 * <h2>An interface for serializing and deserializing objects.</h2>
 *
 * @class    EcpGatewaySerializerInterface
 * @since    2.0.0
 * @package  Ecp_Gateway/Interfaces
 * @category Interface
 * @internal
 */
interface EcpGatewaySerializerInterface {
	/**
	 * <h2>Returns result of serialize object as string.</h2>
	 *
	 * @param object $value <p>Serializable object.</p>
	 * @param mixed ...$args <p>Additional arguments.</p>
	 *
	 * @return string <p>Serialized object.</p>
	 * @since 2.0.0
	 */
	public function serialize( object $value, ...$args ): string;

	/**
	 * <h2>Returns result of deserialized string as object.</h2>
	 *
	 * @param string $value <p>Serialized string.</p>
	 * @param mixed ...$args <p>Additional arguments.</p>
	 *
	 * @return object <p>Deserialized object.</p>
	 * @since 2.0.0
	 */
	public function deserialize( string $value, ...$args ): object;
}
