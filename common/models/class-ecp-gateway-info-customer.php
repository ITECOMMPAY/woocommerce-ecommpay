<?php

defined('ABSPATH') || exit;

/**
 * <h2>Contains information about the customer who initiated the payment.</h2>
 *
 * @class    Ecp_Gateway_Info_Customer
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 * @internal
 */
class Ecp_Gateway_Info_Customer extends Ecp_Gateway_Json
{
    // region Constants

    /**
     * <h2>Label for unique identifier of the customer.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_ID = 'id';

    /**
     * <h2>Label for customer first name.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_FIRST_NAME = 'first_name';

    /**
     * <h2>Label for customer last name.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_LAST_NAME = 'last_name';

    /**
     * <h2>Label for customer middle name.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_MIDDLE_NAME = 'middle_name';

    /**
     * <h2>Label for billing information.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_BILLING = 'billing';

    /**
     * <h2>Label for customer city.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_CITY = 'city';

    /**
     * <h2>Label for country of the customer.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_COUNTRY = 'country';

    /**
     * <h2>Label for birthday of the customer.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_BIRTHDAY = 'day_of_birth';

    /**
     * <h2>Label for customer IP address as specified in the initial request.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_IP_ADDRESS = 'ip_address';

    /**
     * <h2>Label for customer phone number.</h2>
     *
     * @param string
     * @since 2.0.0
     */
    const FIELD_PHONE = 'phone';

    // endregion

    /**
     * <h2>Customer information constructor.</h2>
     *
     * @param array $array [optional] <p>Json-data as array.</p>
     */
    public function __construct(array $array = [])
    {
        $this->register(self::FIELD_BILLING, Ecp_Gateway_Info_Billing::class);

        parent::__construct($array);
    }

    /**
     * <h2>Returns customer unique identifier.</h2>
     *
     * @return int <p>Identifier in WordPress if available or <b>0</b> otherwise.</p>
     */
    public function get_id()
    {
        $this->try_get_id($id);
        return $id;
    }

    /**
     * <h2>Returns the result of trying to put a customer identifier value into variable by reference.</h2>
     *
     * @param int $id <p>Container fo customer identifier.</p>
     * @return bool <p><b>TRUE</b> if identifier exists or <b>FALSE</b> otherwise.</p>
     */
    public function try_get_id(&$id)
    {
        return $this->try_get_int($id, self::FIELD_ID);
    }

    /**
     * <h2>Returns the customer first name.</h2>
     *
     * @return string <p>Customer first name, if available, or <b>blank string</b> otherwise.</p>
     */
    public function get_first_name()
    {
        $this->try_get_first_name($name);
        return $name;
    }

    /**
     * <h2>Returns the result of trying to put a customer first name into variable by reference.</h2>
     *
     * @param int $name <p>Container fo customer first name.</p>
     * @return bool <p><b>TRUE</b> if name exists or <b>FALSE</b> otherwise.</p>
     */
    public function try_get_first_name(&$name)
    {
        return $this->try_get_string($name, self::FIELD_FIRST_NAME);
    }

    /**
     * <h2>Returns the customer last name.</h2>
     *
     * @return string <p>Customer last name, if available, or <b>blank string</b> otherwise.</p>
     */
    public function get_last_name()
    {
        $this->try_get_last_name($name);
        return $name;
    }

    /**
     * <h2>Returns the result of trying to put a customer last name into variable by reference.</h2>
     *
     * @param int $name <p>Container fo customer last name.</p>
     * @return bool <p><b>TRUE</b> if name exists or <b>FALSE</b> otherwise.</p>
     */
    public function try_get_last_name(&$name)
    {
        return $this->try_get_string($name, self::FIELD_LAST_NAME);
    }

    /**
     * <h2>Returns the customer middle name.</h2>
     *
     * @return ?string <p>Customer middle name, if available, or <b>blank string</b> otherwise.</p>
     */
    public function get_middle_name()
    {
        $this->try_get_middle_name($name);
        return $name;
    }

    /**
     * <h2>Returns the result of trying to put a customer middle name into variable by reference.</h2>
     *
     * @param int $name <p>Container fo customer middle name.</p>
     * @return bool <p><b>TRUE</b> if name exists or <b>FALSE</b> otherwise.</p>
     */
    public function try_get_middle_name(&$name)
    {
        return $this->try_get_string($name, self::FIELD_MIDDLE_NAME);
    }

    /**
     * <h2>Returns the billing information.</h2>
     *
     * @return ?Ecp_Gateway_Info_Billing Billing information, if available, or <b>NULL</b> otherwise.
     */
    public function get_billing()
    {
        if ($this->try_get_json($billing, self::FIELD_BILLING)) {
            return $billing;
        }

        return null;
    }

    /**
     * <h2>Returns the customer city.</h2>
     *
     * @return ?string Customer city, if available, or <b>NULL</b> otherwise.
     */
    public function get_city()
    {
        if ($this->try_get_string($city, self::FIELD_CITY)) {
            return $city;
        }

        return null;

    }

    /**
     * <h2>Returns the country of the customer.</h2>
     *
     * @return ?string Country of the customer, if available, or <b>NULL</b> otherwise.
     */
    public function get_country()
    {
        if ($this->try_get_string($country, self::FIELD_COUNTRY)) {
            return $country;
        }

        return null;

    }

    /**
     * <h2>Returns the birthday of the customer.</h2>
     *
     * @return ?DateTime Birthday of the customer, if available, or <b>NULL</b> otherwise.
     */
    public function get_birthday()
    {
        if ($this->try_get_object($birthday, self::FIELD_BIRTHDAY)) {
            return $birthday;
        }

        return null;
    }

    /**
     * <h2>Returns the customer IP-address.</h2>
     *
     * @return string Customer IP-address.
     */
    public function get_ip_address()
    {
        $this->try_get_string($ip, self::FIELD_IP_ADDRESS);
        return $ip;
    }

    /**
     * <h2>Returns the customer phone number.</h2>
     *
     * @return ?string Customer phone number, if available, or <b>NULL</b> otherwise.
     */
    public function get_phone()
    {
        if ($this->try_get_string($phone, self::FIELD_PHONE)) {
            return $phone;
        }

        return null;

    }

    /**
     * @inheritDoc
     */
    protected function unpackRules()
    {
        return [
            self::FIELD_BIRTHDAY => function ($value) {
                return DateTime::createFromFormat('d-m-Y', $value);
            }
        ];
    }
}