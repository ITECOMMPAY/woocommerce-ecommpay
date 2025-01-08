<?php

namespace common\helpers;

abstract class EcpAbstractApiObject {

	public static function get_status_codes(): array {
		if ( ! static::$codes ) {
			static::$codes = static::compile_codes();
		}

		return static::$codes;
	}

	private static function compile_codes(): array {
		$data = [];

		foreach ( static::get_status_names() as $key => $value ) {
			$data[ $key ] = str_replace( ' ', '-', $key );
		}

		return $data;
	}

	public static function get_status_names(): array {
		if ( ! static::$names ) {
			static::$names = static::compile_names();
		}

		return static::$names;
	}

}
