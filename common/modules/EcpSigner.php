<?php

namespace common\modules;
defined( 'ABSPATH' ) || exit;

use common\exceptions\EcpGatewayError;
use common\exceptions\EcpGatewaySignatureException;
use common\helpers\EcpGatewayRegistry;
use common\settings\EcpSettingsGeneral;

/**
 * @class    EcpSigner
 * @version  2.0.0
 * @package  Ecp_Gateway/Modules
 * @category Class
 * @internal
 */
final class EcpSigner extends EcpGatewayRegistry {


	/**
	 * <h2>Key separator when recursively executed.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const KEY_SEPARATOR = ':';

	/**
	 * <h2>Value separator.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const VALUE_SEPARATOR = ';';

	/**
	 * <h2>Boolean TRUE when signed.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const TRUE = '1';

	/**
	 * <h2>Boolean FALSE when signed.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const FALSE = '0';

	/**
	 * <h2>Signer Algorithm</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const ALGORITHM = 'sha512';

	/**
	 * <h2>Blank string.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const BLANK = '';

	/**
	 * <h2>Label for the signature field.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const NAME = 'signature';

	/**
	 * <h2>Label for the general field.</h2>
	 * @var string
	 * @since 2.0.0
	 */
	const GENERAL = 'general';


	/**
	 * <h2>Internal secret key.</h2>
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private string $secret_key;

	/**
	 * <h2>List of ignored keys for data normalization.</h2>
	 *
	 * @var string[]
	 * @since 2.0.0
	 */
	private array $ignore_params = [
		'frame_mode',
		'_plugin_version',
		'_wordpress_version',
		'_woocommerce_version',
	];


	/**
	 * <h2>Returns the result of data signature verification.</h2>
	 *
	 * @param array $data <p>Data for verification.</p>
	 *
	 * @return bool <b>TRUE</b> if the signature is valid or <b>FALSE</b> otherwise.
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	public function check( array $data ): bool {
		ecp_get_log()->info( __( 'Check signature', 'woo-ecommpay' ) );

		if ( array_key_exists( self::NAME, $data ) ) {
			ecp_get_log()->debug( __( 'Find signature in body data', 'woo-ecommpay' ) );
			$signature = $data[ self::NAME ];
			unset( $data[ self::NAME ] );

			return $this->get_signature( $data ) === $signature;
		}

		if ( array_key_exists( self::NAME, $data[ self::GENERAL ] ) ) {
			ecp_get_log()->debug( __( 'Find signature in general data', 'woo-ecommpay' ) );
			$signature = $data[ self::GENERAL ][ self::NAME ];
			unset( $data[ self::GENERAL ][ self::NAME ] );

			if ( count( $data[ self::GENERAL ] ) <= 0 ) {
				unset( $data[ self::GENERAL ] );
			}

			return $this->get_signature( $data ) === $signature;
		}

		ecp_get_log()->error( __( 'Not found signature', 'woo-ecommpay' ) );

		return false;
	}

	/**
	 * <h2>Returns the data signature.</h2>
	 *
	 * @param array $data <p>Data for signature.</p>
	 *
	 * @return string
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	public function get_signature( array $data ): string {
		$params_to_sign = $this->get_params_to_sign( $data, $this->ignore_params );
		$string_to_sign = $this->get_string_to_sign( $params_to_sign );

		return base64_encode( hash_hmac( self::ALGORITHM, $string_to_sign, $this->secret_key, true ) );
	}

	/**
	 * <h2>Returns the converted (normalised) data for the signature.</h2>
	 *
	 * @param array $params <p>Source data.</p>
	 * @param array $ignore_param_keys [optional] <p>List of ignored keys. Default: blank array.</p>
	 * @param int $current_level [optional] <p>Current nesting level. Default: 1</p>
	 * @param string $prefix [optional] <p>Prefix for current nesting level. Default: blank string.</p>
	 *
	 * @return array Normalised data
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	private function get_params_to_sign(
		array $params,
		array $ignore_param_keys = [],
		int $current_level = 1,
		string $prefix = self::BLANK
	): array {
		$params_to_sign = [];

		foreach ( $params as $key => $value ) {
			if ( ( in_array( $key, $ignore_param_keys ) && $current_level === 1 ) ) {
				continue;
			}

			if ( strpos( $key, self::VALUE_SEPARATOR ) !== false ) {
				throw new EcpGatewaySignatureException(
					__( 'Key contains an invalid character', 'woo-ecommpay' ),
					$key,
					EcpGatewayError::INVALID_KEY
				);
			}

			if ( is_string( $value ) && strpos( $value, self::VALUE_SEPARATOR ) !== false ) {
				throw new EcpGatewaySignatureException(
					__( 'Value contains an invalid character', 'woo-ecommpay' ),
					$value,
					EcpGatewayError::INVALID_VALUE
				);
			}

			$paramKey = ( $prefix ? $prefix . self::KEY_SEPARATOR : self::BLANK ) . str_replace( ':', '::', $key );

			switch ( true ) {
				case is_array( $value ):
					$params_to_sign = array_merge(
						$params_to_sign,
						$this->get_params_to_sign( $value, $ignore_param_keys, $current_level + 1, $paramKey )
					);
					break;
				case is_bool( $value ):
					$params_to_sign[ $paramKey ] = $paramKey . self::KEY_SEPARATOR . $value ? self::TRUE : self::FALSE;
					break;
				default:
					$params_to_sign[ $paramKey ] = $paramKey . self::KEY_SEPARATOR . $value;
			}
		}

		if ( $current_level === 1 ) {
			ksort( $params_to_sign, SORT_NATURAL );
		}

		return $params_to_sign;
	}

	/**
	 * <h2>Returns data converted from array to string for signature.</h2>
	 *
	 * @param array $params_to_sign <p>Data for signature as array.</p>
	 *
	 * @return string Data for signature as string
	 * @since 2.0.0
	 */
	private function get_string_to_sign( array $params_to_sign ): string {
		return implode( self::VALUE_SEPARATOR, $params_to_sign );
	}

	/**
	 * <h2>Appends the signature into data.</h2>
	 *
	 * @param array &$data <p>Data for signature.</p>
	 *
	 * @return array
	 * @throws EcpGatewaySignatureException <p>
	 * When the key or value of one of the parameters contains the character
	 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
	 * </p>
	 * @since 2.0.0
	 */
	public function sign( array $data ): array {
		$signature = $this->get_signature( $data );

		if ( array_key_exists( self::GENERAL, $data ) ) {
			ecp_debug( 'Adding signature to general data...' );
			$data[ self::GENERAL ][ self::NAME ] = $signature;
		} else {
			ecp_debug( 'Adding signature to body data...' );
			$data[ self::NAME ] = $signature;
		}

		ecp_debug( 'Signature added ✅' );

		return $data;
	}

	/**
	 * @inheritDoc
	 * @return void
	 * @since 2.0.0
	 */
	protected function init(): void {
		$this->secret_key = ecommpay()->get_general_option( EcpSettingsGeneral::OPTION_SECRET_KEY );
	}
}
