<?php

namespace common\includes;

use common\api\EcpGatewayAPIPayment;
use common\helpers\EcpGatewayPaymentStatus;
use common\helpers\EcpGatewayRegistry;
use common\settings\EcpSettingsGeneral;
use Exception;

class EcpGatewayPaymentProvider extends EcpGatewayRegistry {
	const TRANSIENT_PREFIX = 'wc_ecp_transition_';

	/**
	 * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a
	 * transient before it asks the ECOMMPAY API. Cached data will always be used if available.
	 *
	 * If no data is cached, we will fetch the transaction from the API and cache it.
	 *
	 * @param EcpGatewayOrder $order
	 * @param bool $reload
	 *
	 * @return EcpGatewayPayment
	 */
	public function load( EcpGatewayOrder $order, bool $reload = false ): EcpGatewayPayment {
		ecp_get_log()->debug( __( 'Loading payment information...', 'woo-ecommpay' ) );
		ecp_get_log()->debug( __( 'Order ID:', 'woo-ecommpay' ), $order->get_id() );
		ecp_get_log()->debug( __( 'Reload?', 'woo-ecommpay' ), $reload ? __( 'Yes', 'woo-ecommpay' ) : __( 'No', 'woo-ecommpay' ) );

		if ( ! $reload && $this->is_transaction_caching_enabled() ) {
			ecp_get_log()->info( __( 'Try loading payment data from cache...', 'woo-ecommpay' ) );
			$transient = get_transient( $this->get_transient_id( $order->get_payment_id() ) );

			if ( $transient ) {
				// new EcpGatewayInfoStatus(json_decode($transient, true))
				$payment = @unserialize( $transient );

				if ( $payment instanceof EcpGatewayPayment ) {
					ecp_get_log()->info( __( 'Payment loaded from cache. Cache data exists.', 'woo-ecommpay' ) );

					return $payment;
				}

				ecp_get_log()->warning( __( 'Cache data corrupted:', 'woo-ecommpay' ), $transient );
			} else {
				ecp_get_log()->info( __( 'Invalid cache data.', 'woo-ecommpay' ) );
			}
		}

		if ( $order->get_ecp_status() === EcpGatewayPaymentStatus::INITIAL ) {
			ecp_get_log()->info( __( 'Payment is initial. Initialize blank payment data.', 'woo-ecommpay' ) );
			$payment = EcpGatewayPayment::stub( $order );
		} else {
			ecp_get_log()->info( __( 'Get payment data from ECOMMPAY.', 'woo-ecommpay' ) );
			$payment = $this->reload( $order );
		}

		ecp_get_log()->info( __( 'Payment information loaded:', 'woo-ecommpay' ), $order->get_payment_id() );

		if ( $this->is_transaction_caching_enabled() ) {
			$payment->save();
		}

		return $payment;
	}

	/**
	 * @return boolean
	 */
	private function is_transaction_caching_enabled(): bool {
		return apply_filters(
			'ecp_transaction_cache_enabled',
			ecp_is_enabled( EcpSettingsGeneral::OPTION_CACHING_ENABLED )
		);
	}

	private function get_transient_id( $id ): string {
		return self::TRANSIENT_PREFIX . $id;
	}

	/**
	 * @param EcpGatewayOrder $order
	 *
	 * @return EcpGatewayPayment
	 */
	private function reload( EcpGatewayOrder $order ): EcpGatewayPayment {
		$api     = new EcpGatewayAPIPayment();
		$status  = $api->status( $order );
		$payment = new EcpGatewayPayment( $order );

		if ( count( $status->get_errors() ) > 0 ) {
			if ( $status->try_get_payment( $info ) ) {
				$payment->set_info( $info );
			}

			return $payment;
		}

		return $payment->set_info( $status->get_payment() )
		               ->set_customer( $status->get_customer() )
		               ->set_acs( $status->get_acs() )
		               ->set_account( $status->get_account() )
		               ->set_operations( $status->get_operations() );
	}

	/**
	 * <h2>Stores payment details to the cache.</h2>
	 *
	 * @param EcpGatewayPayment $payment
	 *
	 * @return void
	 */
	public function save( EcpGatewayPayment $payment ): void {
		ecp_get_log()->debug( __( 'Save payment:', 'woo-ecommpay' ), $payment->get_id() );
		$payment->status_transition();

		if ( ! $this->is_transaction_caching_enabled() ) {
			ecp_get_log()->info( __( 'Cache disabled. Cancelled store payment details.', 'woo-ecommpay' ) );

			return;
		}

		try {
			$expiration = (int) ecommpay()->get_general_option(
				EcpSettingsGeneral::OPTION_CACHING_EXPIRATION,
				7 * DAY_IN_SECONDS
			);

			// Cache expiration in seconds
			$expiration = apply_filters( 'woocommerce_ecommpay_transaction_cache_expiration', $expiration );

			ecp_get_log()->debug( __( 'Expiration length:.', 'woo-ecommpay' ), $expiration );
			set_transient(
				$this->get_transient_id( $payment->get_id() ),
				serialize( $payment ),
				$expiration
			);
		} catch ( Exception $e ) {
			ecp_get_log()->error( __( 'Error saving payment ', 'woo-ecommpay' ), $payment->get_id() );
			$payment->get_order()->add_order_note( __( 'Error saving payment.', 'woocommerce' ) . ' ' . $e->getMessage() );
		}

		ecp_get_log()->info( __( 'Payment details successfully saved.', 'woo-ecommpay' ), $payment->get_id() );
	}
}
