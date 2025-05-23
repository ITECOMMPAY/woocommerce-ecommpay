<?php

namespace common\helpers;

use common\exceptions\EcpGatewayDuplicateException;
use common\exceptions\EcpGatewayException;
use common\exceptions\EcpGatewayInvalidArgumentException;
use common\exceptions\EcpGatewayKeyNotFoundException;
use common\exceptions\EcpGatewayNotAvailableException;
use common\exceptions\EcpGatewayNotImplementedException;
use common\interfaces\EcpGatewaySerializerInterface;
use JsonSerializable;

defined( 'ABSPATH' ) || exit;

/**
 * <h2>JSON object.</h2>
 *
 * @class    EcpGatewayJson
 * @since    2.0.0
 * @package  Ecp_Gateway/Helpers
 * @category Class
 * @internal
 */
class EcpGatewayJson extends EcpGatewayArray implements JsonSerializable {
	private const PATH_SEPARATOR = '.';

	private const STRING_TYPE = 'string';
	private const INTEGER_TYPE = 'integer';
	private const FLOAT_TYPE = 'double';
	private const BOOL_TYPE = 'boolean';
	private const ARRAY_TYPE = 'array';
	private const OBJECT_TYPE = 'object';
	private const JSON_TYPE = 'json';

	const EMPTY_STRING = '';
	const EMPTY_ARRAY = [];
	const EMPTY_OBJECT = null;

	private array $register = [];

	public function __construct( array $array = [] ) {
		parent::__construct();

		$this->deserialize( $array );
	}


	/**
	 * @param array $data
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function deserialize( array $data ): void {
		foreach ( $data as $key => $value ) {
			switch ( true ) {
				case array_key_exists( $key, $this->register ):
					if ( $value instanceof $this->register[ $key ] ) {
						$this->set( $key, $value );
					} else {
						$this->set( $key, new $this->register[ $key ]( $value ) );
					}
					break;
				case array_key_exists( $key, $this->unpackRules() ):
					$options = $this->unpackRules()[ $key ];

					if ( is_callable( $options ) ) {
						$this->set( $key, $options( $value ) );
						continue ( 2 );
					}

					$className   = array_shift( $options );
					$transformer = EcpGatewayRegistry::get_by_class( $className );

					if ( $transformer instanceof EcpGatewaySerializerInterface ) {
						$this->set( $key, $transformer->deserialize( $value, ...$options ) );
					}
					break;
				default:
					$method = 'set_' . $key;

					if ( method_exists( $this, $method ) ) {
						$this->$method( $value );
					} else {
						$this->set( $key, $value );
					}
			}
		}
	}

	/**
	 * <h2>Sets value for tree path.</h2>
	 *
	 * @param string $path <p>Tree path.</p>
	 * @param mixed $value <p>Value for tree path.</p>
	 *
	 * @return static <p>Curren JSON object.</p>
	 * @since 2.0.0
	 */
	protected function set( string $path, $value ): EcpGatewayJson {
		$this->parseValue( $value );
		$keys = $this->parse_path( $path );
		$key  = array_shift( $keys );

		if ( preg_match( '|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match ) ) {
			$key        = $match['array'];
			$indexesRaw = $match['indexes'];
			preg_match_all( '|\[(?P<index>\d+)]|', $indexesRaw, $match );
			$indexes = $match['index'];

			$this->try_get_array( $array, $key, [] );
			$child = &$array;

			foreach ( $indexes as $index ) {
				$child = &$child[ $index ];
			}

			if ( count( $keys ) > 0 ) {
				if ( ! $child instanceof EcpGatewayJson ) {
					$child = new EcpGatewayJson();
				}

				$child->set( implode( self::PATH_SEPARATOR, $keys ), $value );
			} else {
				$child = $value;
			}

			$this->offsetSet( $key, $array );
		} elseif ( count( $keys ) > 0 ) {
			$result = $this->try_get_object( $child, $key, new EcpGatewayJson() );

			if ( ! $result ) {
				$this->offsetSet( $key, $child );
			}

			$child->set( implode( self::PATH_SEPARATOR, $keys ), $value );
		} else {
			$this->offsetSet( $key, $value );
		}

		return $this;
	}

	/**
	 * @param mixed &$value
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function parseValue( &$value ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => &$v ) {
				if ( ! is_numeric( $k ) ) {
					$value = new EcpGatewayJson( $value );

					return;
				}

				if ( is_array( $v ) ) {
					$this->parseValue( $v );
				}
			}
		}
	}

	/**
	 * <h2>Returns json-path as array.</h2>
	 *
	 * @param string $path <p>Tree path as string.</p>
	 *
	 * @return string[] <p>Tree path as array.</p>
	 * @since 2.0.0
	 */
	private function parse_path( string $path ): array {
		return explode( self::PATH_SEPARATOR, $path );
	}

	/**
	 * <h2>Returns the result of trying to put an array value into variable by reference.</h2>
	 *
	 * @param ?array &$value <p>Container for array value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param array|null $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try to get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_array( ?array &$value, string $path, ?array $default = self::EMPTY_ARRAY ): bool {
		try {
			$value = $this->get_array( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}

	/**
	 * <h2>Returns the array value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return array <p>The array value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_array( string $path ) {
		return $this->get_value( self::ARRAY_TYPE, $path );
	}

	/**
	 * Returns value from json-tree by type.
	 *
	 * @param string $type Variable type.
	 * @param string $path Json-tree path.
	 *
	 * @return array|bool|float|int|string
	 * @throws EcpGatewayInvalidArgumentException
	 * @throws EcpGatewayKeyNotFoundException
	 * @throws EcpGatewayNotImplementedException
	 * @since 2.0.0
	 */
	private function get_value( string $type, string $path ) {
		$keys = $this->parse_path( $path );
		$key  = array_shift( $keys );

		if ( preg_match( '|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match ) ) {
			$key        = $match['array'];
			$indexesRaw = $match['indexes'];
			preg_match_all( '|\[(?P<index>\d+)]|', $indexesRaw, $match );
			$indexes = $match['index'];

			$array = $this->get_current_value( self::ARRAY_TYPE, $key );

			foreach ( $indexes as $index ) {
				$array = $array[ $index ];
			}

			if ( count( $keys ) > 0 ) {
				return $array->get_value( $type, implode( self::PATH_SEPARATOR, $keys ) );
			}

			return $array;
		}

		if ( count( $keys ) > 0 ) {
			return $this->get_current_value( self::OBJECT_TYPE, $key )
			            ->get_value( $type, implode( self::PATH_SEPARATOR, $keys ) );
		}

		return $this->get_current_value( $type, $key );
	}

	/**
	 * @param string $type
	 * @param string $path
	 *
	 * @return array|bool|float|int|string|static
	 * @throws EcpGatewayInvalidArgumentException
	 * @throws EcpGatewayKeyNotFoundException
	 * @throws EcpGatewayNotImplementedException
	 * @since 2.0.0
	 */
	private function get_current_value( string $type, string $path ) {
		if ( ! $this->offsetExists( $path ) ) {
			throw new EcpGatewayKeyNotFoundException( $path, $this->keys() );
		}

		$result = $this->offsetGet( $path );

		switch ( $type ) {
			case self::STRING_TYPE:
				if ( ! is_string( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}
				break;
			case self::BOOL_TYPE:
				if ( ! is_bool( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}
				break;
			case self::INTEGER_TYPE:
				if ( ! is_int( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}
				break;
			case self::FLOAT_TYPE:
				if ( ! is_float( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}
				break;
			case self::ARRAY_TYPE:
				if ( $result instanceof EcpGatewayJson ) {
					$result = $result->to_array();
				}
				if ( ! is_array( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}
				break;
			case self::JSON_TYPE:
				if ( ! is_object( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}

				if ( ! $result instanceof EcpGatewayJson ) {
					throw new EcpGatewayNotImplementedException( $result, EcpGatewayJson::class );
				}
				break;
			case self::OBJECT_TYPE:
				if ( ! is_object( $result ) ) {
					throw new EcpGatewayInvalidArgumentException( $path, $type, gettype( $result ) );
				}

				break;
		}

		return $result;
	}

	/**
	 * <h2>Returns current object as array value.</h2>
	 *
	 * @return array <p>Native array.</p>
	 * @since 2.0.0
	 */
	public function to_array(): array {
		return $this->jsonSerialize();
	}

	/**
	 * @inheritDoc
	 * @return array <p>Prepared array for {@see json_encode}.</p>
	 * @since 2.0.0
	 */
	public function jsonSerialize(): array {
		$result = [];

		foreach ( parent::to_array() as $key => $value ) {
			$value = $this->serialize( $value, $key );

			if ( $value === null ) {
				continue;
			}

			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * @param $value
	 * @param $key
	 *
	 * @return array|mixed|string|null
	 * @since 2.0.0
	 */
	private function serialize( $value, $key ) {
		switch ( true ) {
			case array_key_exists( $key, $this->packRules() ):
				$options = $this->packRules()[ $key ];

				if ( is_callable( $options ) ) {
					return $options( $value );
				}

				$className   = array_shift( $options );
				$transformer = EcpGatewayRegistry::get_by_class( $className );

				if ( $transformer instanceof EcpGatewaySerializerInterface ) {
					return $transformer->serialize( $value, ...$options );
				}

				return $value;
			case $value instanceof EcpGatewayArray && ! $value instanceof EcpGatewayJson:
				return $value->to_array();
			case $value instanceof EcpGatewayJson:
				if ( $value->count() <= 0 ) {
					return null;
				}

				return $value->jsonSerialize();
			case is_array( $value ):
				$this->serialize_array( $value );

				return $value;
			default:
				return $value;
		}
	}

	/**
	 * <h2>Return json pack rules.</h2>
	 *
	 * @return array
	 * @since 2.0.0
	 */
	protected function packRules(): array {
		return [];
	}

	/**
	 * @param $array
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function serialize_array( &$array ): void {
		foreach ( $array as &$item ) {
			if ( $item instanceof EcpGatewayJson ) {
				$item = $item->jsonSerialize();
			}

			if ( is_array( $item ) ) {
				$this->serialize_array( $item );
			}
		}

		unset( $item );
	}

	/**
	 * <h2>Returns the result of trying to put an object value into variable by reference.</h2>
	 *
	 * @param ?object &$value <p>Container for object value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param ?object $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try to get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_object( ?object &$value, string $path, ?object $default = self::EMPTY_OBJECT ): bool {
		try {
			$value = $this->get_object( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}


	/**
	 * <h2>Returns the object value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return object <p>The object value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_object( string $path ) {
		return $this->get_value( self::OBJECT_TYPE, $path );
	}


	/**
	 * <h2>Return json unpack rules.</h2>
	 *
	 * @return array
	 * @since 2.0.0
	 */
	protected function unpackRules(): array {
		return [];
	}

	/**
	 * <h2>Returns object as key-value pairs.</h2>
	 *
	 * @param string $separator [optional] <p>Tree path separator. Default: {@see EcpGatewayJson::PATH_SEPARATOR}.</p>
	 * @param string $prefix [optional] <p>Prefix for root. Default: {@see EcpGatewayJson::EMPTY_STRING}.</p>
	 *
	 * @return array <p>List of key-value pairs.</p>
	 * @since 2.0.0
	 */
	public function to_pairs( string $separator = self::PATH_SEPARATOR, string $prefix = self::EMPTY_STRING ): array {
		return $this->to_key_value_pairs( $this->jsonSerialize(), $prefix, $separator );
	}


	/**
	 * @param array $data
	 * @param string $prefix
	 * @param string $separator
	 *
	 * @return array
	 * @since 2.0.0
	 */
	private function to_key_value_pairs( array $data, string $prefix = self::EMPTY_STRING, string $separator = self::PATH_SEPARATOR ): array {
		$result = [];

		foreach ( $data as $key => $value ) {
			if ( is_int( $key ) && $prefix !== self::EMPTY_STRING ) {
				$key = $prefix . '[' . $key . ']';
			} else {
				$key = ( $prefix !== self::EMPTY_STRING ? $prefix . $separator : self::EMPTY_STRING ) . $key;
			}

			if ( is_array( $value ) ) {
				$result = array_merge(
					$result,
					$this->to_key_value_pairs(
						$value,
						$key,
						$separator
					)
				);
			} else {
				$result[ $key ] = $value;
			}
		}

		if ( count( $result ) === 0 && $prefix !== self::EMPTY_STRING ) {
			$result[ $prefix ] = [];
		}

		return $result;
	}

	/**
	 * @param array $values
	 * @param bool $onDuplicateReplace
	 *
	 * @return static
	 * @throws EcpGatewayDuplicateException
	 * @since 2.0.0
	 */
	public function append( array $values, bool $onDuplicateReplace = false ): EcpGatewayJson {
		$list = $this->to_key_value_pairs( $values );

		foreach ( $list as $key => $value ) {
			$this->parseValue( $value );
			$this->add( $key, $value, $onDuplicateReplace );
		}

		return $this;
	}

	/**
	 * <h2>Add value by tree path.</h2>
	 *
	 * @param string $path <p>Tree path.</p>
	 * @param mixed $value <p>Value for tree path.</p>
	 * @param bool $onDuplicateReplace [optional] <p>Replace value on duplicate? Default: no.</p>
	 *
	 * @return static <p>Current JSON object.</p>
	 * @throws EcpGatewayDuplicateException <p>If tree path already exists.</p>
	 * @since 2.0.0
	 */
	protected function add( string $path, $value, bool $onDuplicateReplace = false ): EcpGatewayJson {
		if ( $this->has( $path ) && ! $onDuplicateReplace ) {
			throw new EcpGatewayDuplicateException( $path );
		}

		$this->set( $path, $value );

		return $this;
	}

	/**
	 * <h2>Returns the result of checking if the tree path exists in the object.</h2>
	 *
	 * @param string $path <p>Tree path.</p>
	 *
	 * @return bool <p><b>TRUE</b> if the tree path exists, or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function has( string $path ): bool {
		$keys  = $this->parse_path( $path );
		$key   = array_shift( $keys );
		$child = null;

		if ( preg_match( '|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match ) ) {
			$key        = $match['array'];
			$indexesRaw = $match['indexes'];
			preg_match_all( '|\[(?P<index>\d+)]|', $indexesRaw, $match );
			$indexes = $match['index'];

			$result = $this->try_get_array( $child, $key, null );

			if ( ! $result ) {
				return $result;
			}

			foreach ( $indexes as $index ) {
				if ( ! is_array( $child ) ) {
					return false;
				}

				if ( ! isset ( $child[ $index ] ) ) {
					return false;
				}

				$child = $child[ $index ];
			}
		}

		if ( count( $keys ) > 0 ) {
			if ( $child !== null ) {
				if ( ! $child instanceof EcpGatewayJson ) {
					return false;
				}
			} else {
				return $this->try_get_object( $child, $key, null );
			}

			return $child->has( implode( self::PATH_SEPARATOR, $keys ) );
		}

		return $this->offsetExists( $key );
	}

	/**
	 * <h2>Returns the result of trying to put an integer value into variable by reference.</h2>
	 *
	 * @param ?int &$value <p>Container for integer value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param int $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try to get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_int( ?int &$value, string $path, int $default = 0 ): bool {
		try {
			$value = $this->get_int( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}

	/**
	 * <h2>Returns the integer value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return int <p>The int value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_int( string $path ) {
		return $this->get_value( self::INTEGER_TYPE, $path );
	}


	/**
	 * <h2>Returns the result of trying to put a string value into variable by reference.</h2>
	 *
	 * @param ?string &$value <p>Container for string value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param string $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_string( ?string &$value, string $path, string $default = self::EMPTY_STRING ): bool {
		try {
			$value = $this->get_string( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}

	/**
	 * <h2>Returns the string value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return string <p>The string value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_string( string $path ) {
		return $this->get_value( self::STRING_TYPE, $path );
	}

	/**
	 * <h2>Returns the result of trying to put a float value into variable by reference.</h2>
	 *
	 * @param ?float &$value <p>Container for float value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param float $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_float( ?float &$value, string $path, float $default = 0.0 ): bool {
		try {
			$value = $this->get_float( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}


	/**
	 * <h2>Returns the float value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return float <p>The float value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_float( string $path ) {
		return $this->get_value( self::FLOAT_TYPE, $path );
	}

	/**
	 * <h2>Returns the result of trying to put a bool value into variable by reference.</h2>
	 *
	 * @param ?bool &$value <p>Container for bool value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param bool $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_bool( ?bool &$value, string $path, bool $default = false ): bool {
		try {
			$value = $this->get_bool( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}

	/**
	 * <h2>Returns the bool value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return bool <p>The bool value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_bool( string $path ) {
		return $this->get_value( self::BOOL_TYPE, $path );
	}

	/**
	 * <h2>Returns the result of trying to put an JSON-object value into variable by reference.</h2>
	 *
	 * @param ?EcpGatewayJson &$value <p>Container for JSON-object value - variable by reference.</p>
	 * @param string $path <p>Json-tree path.</p>
	 * @param ?EcpGatewayJson $default [optional] <p>Default value if path in json-tree is not found.</p>
	 *
	 * @return bool <p><b>TRUE</b> if success try get string value or <b>FALSE</b> otherwise.</p>
	 * @since 2.0.0
	 */
	protected function try_get_json( ?EcpGatewayJson &$value, string $path, ?EcpGatewayJson $default = self::EMPTY_OBJECT ): bool {
		try {
			$value = $this->get_json( $path );

			return true;
		} catch ( EcpGatewayException $e ) {
			$value = $default;
		}

		return false;
	}

	/**
	 * <h2>Returns the JSON-object value by path in json-tree.</h2>
	 *
	 * @param string $path <p>Json-tree path.</p>
	 *
	 * @return EcpGatewayJson <p>The JSON-object value by JSON-path.</p>
	 * @throws EcpGatewayException <p>If Json-tree path is not available in current object.</p>
	 * @since 2.0.0
	 */
	protected function get_json( string $path ) {
		return $this->get_value( self::JSON_TYPE, $path );
	}

	/**
	 * <h2>Returns a value by path.</h2>
	 * <p>Always throw exception.</p>
	 *
	 * @param string $path <p>Tree path.</p>
	 *
	 * @return void
	 * @throws EcpGatewayNotAvailableException <p>Always.</p>
	 * @noinspection PhpUnusedParameterInspection
	 * @since 2.0.0
	 */
	protected function get( string $path ): void {
		throw new EcpGatewayNotAvailableException();
	}

	/**
	 * <h2>Removes a tree from an object along a path.</h2>
	 *
	 * @param string $path <p>Removing path.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	protected function remove( string $path ): void {
		$keys  = $this->parse_path( $path );
		$key   = array_shift( $keys );
		$child = null;

		if ( preg_match( '|^(?P<array>[^]\[]+)(?P<indexes>(?:\[\d+])+)$|', $key, $match ) ) {
			$key        = $match['array'];
			$indexesRaw = $match['indexes'];
			preg_match_all( '|\[(?P<index>\d+)]|', $indexesRaw, $match );
			$indexes = $match['index'];
			$result  = $this->try_get_array( $array, $key, null );

			if ( ! $result ) {
				return;
			}

			$child = &$array;
			$count = count( $indexes ) - 1;

			for ( $i = 0; $i < $count; ++ $i ) {
				if ( ! isset ( $child[ $indexes[ $i ] ] ) ) {
					return;
				}

				if ( ! is_array( $child[ $indexes[ $i ] ] ) ) {
					return;
				}

				$child = &$child[ $indexes[ $i ] ];
			}

			if ( count( $keys ) <= 0 ) {
				unset( $child[ $indexes[ $count ] ] );
				$this->set( $key, $array );

				return;
			}

			$child = $child[ $indexes[ $count ] ];
		}

		if ( count( $keys ) > 0 ) {
			if ( $child !== null ) {
				if ( ! $child instanceof EcpGatewayJson ) {
					return;
				}
			} else {
				$result = $this->try_get_object( $child, $key, null );

				if ( ! $result ) {
					return;
				}
			}

			$child->remove( implode( self::PATH_SEPARATOR, $keys ) );
		}

		$this->offsetUnset( $key );
	}

	/**
	 * <h2>Register JSON-class for key for deserialize procedure.</h2>
	 *
	 * @param string $key <p>Key for JSON-value.</h2>
	 * @param string|callable $class <p>Class name for deserialize procedure.</p>
	 *
	 * @return void
	 * @since 2.0.0
	 */
	protected function register( string $key, $class ): void {
		$this->register[ $key ] = $class;
	}


}
