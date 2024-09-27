<?php

/**
 * <h2>An interface for serializing and deserializing objects.</h2>
 *
 * @class    Ecp_Gateway_Serializer_Interface
 * @since    2.0.0
 * @package  Ecp_Gateway/Interfaces
 * @category Interface
 * @internal
 */
interface Ecp_Gateway_Serializer_Interface {
	/**
	 * <h2>Returns result of serialize object as string.</h2>
	 *
	 * @param object $value <p>Serializable object.</p>
	 * @param mixed ...$args <p>Additional arguments.</p>
	 *
	 * @return string <p>Serialized object.</p>
	 * @since 2.0.0
	 */
	public function serialize( $value, ...$args );

	/**
	 * <h2>Returns result of deserialized string as object.</h2>
	 *
	 * @param string $value <p>Serialized string.</p>
	 * @param mixed ...$args <p>Additional arguments.</p>
	 *
	 * @return object <p>Deserialized object.</p>
	 * @since 2.0.0
	 */
	public function deserialize( $value, ...$args );
}
