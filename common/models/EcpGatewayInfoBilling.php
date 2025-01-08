<?php

namespace common\models;

use common\helpers\EcpGatewayJson;

defined( 'ABSPATH' ) || exit;

/**
 * EcpGatewayInfoBilling
 *
 * Contains information about the billing address of the customer.
 *
 * @class    EcpGatewayInfoBilling
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class EcpGatewayInfoBilling extends EcpGatewayJson {


	/**
	 * Label for street address of the customer billing address.
	 */
	const FIELD_ADDRESS = 'address';

	/**
	 * Label for city of the customer billing address.
	 */
	const FIELD_CITY = 'city';

	/**
	 * Label for country of the customer billing address
	 */
	const FIELD_COUNTRY = 'country';

	/**
	 * Label for postcode of the customer billing address.
	 */
	const FIELD_POSTAL = 'postal';

	/**
	 * Label for region of the customer billing address.
	 */
	const FIELD_REGION = 'region';


	/**
	 * <h2>Returns the street address of the customer billing address.</h2>
	 *
	 * @return ?string
	 */
	public function get_address(): ?string {
		if ( $this->try_get_string( $address, self::FIELD_ADDRESS ) ) {
			return $address;
		}

		return null;
	}

	/**
	 * <h2>Returns the city of the customer billing address.</h2>
	 *
	 * @return ?string
	 */
	public function get_city(): ?string {
		if ( $this->try_get_string( $city, self::FIELD_CITY ) ) {
			return $city;
		}

		return null;
	}

	/**
	 * <h2>Returns the country of the customer billing address.</h2>
	 *
	 * @return ?string Country in ISO 3166-1 alpha-2 format, if available, or <b>NULL</b> otherwise.
	 */
	public function get_country(): ?string {
		if ( $this->try_get_string( $country, self::FIELD_COUNTRY ) ) {
			return $country;
		}

		return null;
	}

	/**
	 * <h2>Returns the postcode of the customer billing address.</h2>
	 *
	 * @return ?string
	 */
	public function get_postal(): ?string {
		if ( $this->try_get_string( $postal, self::FIELD_POSTAL ) ) {
			return $postal;
		}

		return null;
	}

	/**
	 * <h2>Returns the region of the customer billing address.</h2>
	 *
	 * @return ?string
	 */
	public function get_region(): ?string {
		if ( $this->try_get_string( $region, self::FIELD_REGION ) ) {
			return $region;
		}

		return null;
	}
}
