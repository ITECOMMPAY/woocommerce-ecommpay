<?php

namespace common\helpers;

/**
 * WC_Gateway_Ecommpay_Model_Payment_Status_Transition
 *
 * @class    WC_Gateway_Ecommpay_Model_Payment_Status_Transition
 * @version  2.0.0
 * @package  WC_Gateway_Ecommpay/Models
 * @category Class
 */
class EcpGatewayPaymentStatusTransition extends EcpGatewayJson {
	private const FIELD_NEW = 'new';
	private const FIELD_OLD = 'old';
	private const FIELD_NOTE = 'note';

	public function get_note(): ?string {
		$this->try_get_string( $note, self::FIELD_NOTE );

		return $note;
	}

	public function is_changed(): bool {
		return $this->get_new() !== $this->get_old();
	}

	public function get_new(): ?string {
		$this->try_get_string( $status, self::FIELD_NEW, EcpGatewayPaymentStatus::INITIAL );

		return $status;
	}

	public function get_old(): ?string {
		$this->try_get_string( $status, self::FIELD_OLD, EcpGatewayPaymentStatus::INITIAL );

		return $status;
	}

	protected function unpackRules(): array {
		return [
			self::FIELD_OLD => function ( $value ) {
				return ecp_is_payment_status( $value )
					? $value
					: EcpGatewayPaymentStatus::INITIAL;
			},
			self::FIELD_NEW => function ( $value ) {
				return ecp_is_payment_status( $value )
					? $value
					: EcpGatewayPaymentStatus::INITIAL;
			}
		];
	}
}
