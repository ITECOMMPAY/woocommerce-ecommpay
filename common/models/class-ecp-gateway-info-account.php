<?php

defined('ABSPATH') || exit;

/**
 * Ecp_Gateway_Info_Account
 *
 * Contains the details of the customer bank card or other payment account
 *
 * @class    Ecp_Gateway_Info_Account
 * @version  2.0.0
 * @package  Ecp_Gateway/Info
 * @category Class
 */
class Ecp_Gateway_Info_Account extends Ecp_Gateway_Json
{
    // region Constants

    /**
     * Label for masked bank card or other account number.
     */
    const FIELD_NUMBER = 'number';

    /**
     * Label for type of the customer bank card or the mobile operator which is used to perform a payment.
     */
    const FIELD_TYPE = 'type';

    /**
     * Label for cardholder name.
     */
    const FIELD_CARDHOLDER = 'card_holder';

    /**
     * Label for card expiration month.
     */
    const FIELD_EXPIRY_MONTH = 'expiry_month';

    /**
     * Label for card expiration year.
     */
    const FIELD_EXPIRY_YEAR = 'expiry_year';

    // endregion

    /**
     * <h2>Returns the masked bank card or other account number.</h2>
     *
     * @return string
     */
    public function get_number()
    {
        $this->try_get_string($number, self::FIELD_NUMBER);
        return $number;
    }

    /**
     * <h2>Returns the type of the customer bank card or the mobile operator which is used to perform a payment.</h2>
     *
     * @return ?string
     */
    public function get_type()
    {
        if ($this->try_get_string($type, self::FIELD_TYPE)) {
            return $type;
        }

        return null;
    }

    /**
     * <h2>Returns the cardholder name.</h2>
     *
     * @return ?string
     */
    public function get_cardholder()
    {
        if ($this->try_get_string($cardholder, self::FIELD_CARDHOLDER)) {
            return $cardholder;
        }

        return null;
    }

    /**
     * <h2>Return the card expiration month.</h2>
     *
     * @return ?int
     */
    public function get_expiry_month()
    {
        if ($this->try_get_int($month, self::FIELD_EXPIRY_MONTH)) {
            return $month;
        }

        return null;
    }

    /**
     * <h2>Return the card expiration year.</h2>
     *
     * @return ?int
     */
    public function get_expiry_year()
    {
        if ($this->try_get_int($year, self::FIELD_EXPIRY_YEAR)) {
            return $year;
        }

        return null;
    }

    /**
     * <h2>Return the card expiration date.</h2>
     *
     * @return ?DateTime
     */
    public function get_expiry()
    {
        $month = $this->get_expiry_month();
        $year = $this->get_expiry_year();

        if (!$month || !$year) {
            return null;
        }

        return (new DateTime())
            ->setTime(0, 0)
            ->setDate($year, $month, 1);
    }

    protected function unpackRules()
    {
        return [
            self::FIELD_EXPIRY_MONTH => function ($value) {
                return (int) $value;
            },
            self::FIELD_EXPIRY_YEAR => function ($value) {
                return (int) $value;
            }
        ];
    }
}