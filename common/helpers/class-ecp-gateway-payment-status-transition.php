<?php

/**
 * WC_Gateway_Ecommpay_Model_Payment_Status_Transition
 *
 * @class    WC_Gateway_Ecommpay_Model_Payment_Status_Transition
 * @version  2.0.0
 * @package  WC_Gateway_Ecommpay/Models
 * @category Class
 */
class Ecp_Gateway_Payment_Status_Transition extends Ecp_Gateway_Json {
	const FIELD_NEW = 'new';
	const FIELD_OLD = 'old';
	const FIELD_NOTE = 'note';

	public function get_note() {
		$this->try_get_string( $note, self::FIELD_NOTE );

		return $note;
	}

	public function is_changed() {
		return $this->get_new() !== $this->get_old();
	}

	public function get_new() {
		$this->try_get_string( $status, self::FIELD_NEW, Ecp_Gateway_Payment_Status::INITIAL );

		return $status;
	}

	public function get_old() {
		$this->try_get_string( $status, self::FIELD_OLD, Ecp_Gateway_Payment_Status::INITIAL );

		return $status;
	}

	protected function unpackRules() {
		return [
			self::FIELD_OLD => function ( $value ) {
				return ecp_is_payment_status( $value )
					? $value
					: Ecp_Gateway_Payment_Status::INITIAL;
			},
			self::FIELD_NEW => function ( $value ) {
				return ecp_is_payment_status( $value )
					? $value
					: Ecp_Gateway_Payment_Status::INITIAL;
			}
		];
	}
}
