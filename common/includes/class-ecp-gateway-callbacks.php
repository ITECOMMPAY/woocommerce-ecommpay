<?php

use common\helpers\WCOrderStatus;
use common\modules\EcpModuleCapture;

defined('ABSPATH') || exit;

/**
 * <h2>Callback handler.</h2>
 *
 * @class    Ecp_Gateway_Callbacks
 * @version  2.0.0
 * @package  Ecp_Gateway/Includes
 * @category Class
 * @internal
 */
class Ecp_Gateway_Callbacks
{
	private const CALLBACKS_PRIORITY = 10;

	/**
     * <h2> List of supported operations.</h2>
     *
     * @var string[]
     * @since 2.0.0
     */
	private array $operations = [
		Ecp_Gateway_Operation_Type::SALE                  => 'woocommerce_ecommpay_callback_sale',
		Ecp_Gateway_Operation_Type::REFUND                => 'woocommerce_ecommpay_callback_refund',
		Ecp_Gateway_Operation_Type::REVERSAL              => 'woocommerce_ecommpay_callback_reversal',
		Ecp_Gateway_Operation_Type::RECURRING             => 'woocommerce_ecommpay_callback_recurring',
		Ecp_Gateway_Operation_Type::ACCOUNT_VERIFICATION  => 'woocommerce_ecommpay_callback_verify',
		Ecp_Gateway_Operation_Type::PAYMENT_CONFIRMATION  => 'woocommerce_ecommpay_callback_payment_confirmation',
		Ecp_Gateway_Operation_Type::CONTRACT_REGISTRATION => 'woocommerce_ecommpay_callback_contract_registration',
		Ecp_Gateway_Operation_Type::AUTH                  => 'woocommerce_ecommpay_callback_auth',
		Ecp_Gateway_Operation_Type::CAPTURE               => 'woocommerce_ecommpay_callback_capture',
		Ecp_Gateway_Operation_Type::CANCEL                => 'woocommerce_ecommpay_callback_cancel',
	];
	/**
	 * @var EcpModuleCapture|Ecp_Gateway_Registry
	 */
	private $capture_module;

	/**
     * <h2>Callback handler constructor.</h2>
     *
     * @param array $data <p>Callback data.</p>
     * @since 2.0.0
     */
    private function __construct(array $data)
    {
	    $this->capture_module = EcpModuleCapture::get_instance();

	    add_action( 'woocommerce_ecommpay_callback_refund', [
		    Ecp_Gateway_Module_Refund::get_instance(),
		    'handle'
	    ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_reversal', [
		    Ecp_Gateway_Module_Refund::get_instance(),
		    'handle'
	    ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_sale', [ $this, 'sale' ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_auth', [ $this, 'auth' ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_cancel', [
		    $this,
		    'capture_or_cancel_received'
	    ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_capture', [
		    $this,
		    'capture_or_cancel_received'
	    ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_recurring', [ $this, 'recurring' ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_verify', [ $this, 'verify' ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_payment_confirmation', [
		    $this,
		    'confirm'
	    ], self::CALLBACKS_PRIORITY, 2 );
	    add_action( 'woocommerce_ecommpay_callback_contract_registration', [
		    $this,
		    'contractRegistration'
	    ], self::CALLBACKS_PRIORITY, 2 );

	    // Decode the body into JSON
        $info = new Ecp_Gateway_Info_Callback($data);

        // Instantiate order object
        $order = $this->get_order($info);

        // Execute callback process.
        $this->processor($info, $order);
    }

	/**
	 * @throws Exception
	 */
	public static function handle(): Ecp_Gateway_Callbacks {
		ecp_info( ecpL( 'Run callback handler.', 'Log information' ) );

        // Get callback body
        $body = file_get_contents('php://input');

        $data = json_decode($body, true);

		if ( $data === null ) {
			$data = [
				'json_parse_error' => json_last_error_msg()
			];
		}

		ecp_debug( 'Incoming callback data:', $data );

        // Check signature
        self::check_signature($data);

        return new static($data);
    }

    /**
     * @param Ecp_Gateway_Order $order
     * @param Ecp_Gateway_Info_Callback $callback
     *
     * @return void
     * @since 2.0.0
     */
	public function processor( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order )
    {
        ecp_get_log()->info(__('Run callback processor', 'woo-ecommpay'));

        do_action('ecp_accepted_callback_before_processing', $order, $callback);
        do_action('ecp_accepted_callback_before_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        // Clear card - payment is not initial.
        WC()->cart->empty_cart();

        if (array_key_exists($callback->get_operation()->get_type(), $this->operations)) {
            do_action($this->operations[$callback->get_operation()->get_type()], $callback, $order);
            $message = 'OK';
        } else {
            $message = sprintf(
                __('Not supported operation type: %s', 'woo-ecommpay'),
                $callback->get_operation()->get_type()
            );
            ecp_get_log()->warning($message);
        }

        do_action('ecp_accepted_callback_after_processing', $order, $callback);
        do_action('ecp_accepted_callback_after_processing_' . $callback->get_operation()->get_type(), $order, $callback);

        http_response_code(200);
        die ($message);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     *
     * @return void
     * @throws WC_Data_Exception|Ecp_Gateway_API_Exception
     */
	public function verify( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order )
    {
        ecp_get_log()->info(__('Apply verify callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);

        $order->set_transaction_order_id($callback->get_operation()->get_request_id());
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     *
     * @return void
     * @throws WC_Data_Exception|Ecp_Gateway_API_Exception
     */
	public function confirm( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order )
    {
        ecp_get_log()->info(__('Apply payment confirmation callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->update_subscription($order, $callback);
        $this->process($callback, $order);
    }

	/**
	 * @param Ecp_Gateway_Info_Callback $callback
	 * @param Ecp_Gateway_Order $order
	 *
	 * @throws WC_Data_Exception
	 * @throws Ecp_Gateway_API_Exception
	 */
	public function recurring( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order )
    {
        ecp_get_log()->info(__('Apply recurring callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);

        // Set the transaction order ID
        $this->update_payment($order, $callback);
        $order->set_payment_system($callback->get_payment()->get_method());
        $this->process($callback, $order);
    }

	/**
	 * @param Ecp_Gateway_Info_Callback $callback
	 * @param Ecp_Gateway_Order $order
	 *
	 * @throws WC_Data_Exception
	 * @throws Ecp_Gateway_API_Exception
	 */
	public function sale( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order ) {
		ecp_get_log()->info( __( 'Apply sale callback data.', 'woo-ecommpay' ) );
		$this->log_order_data( $order );

		// Set the transaction order ID
		$this->update_payment( $order, $callback );
		$this->update_subscription( $order, $callback );
		$order->set_payment_system( $callback->get_payment()->get_method() );
		$this->process( $callback, $order );
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function auth( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order ) {
		ecp_info( ecpTr( 'Apply auth callback data.' ) );
		$this->log_order_data( $order );

		// Set the transaction order ID
		$this->update_payment( $order, $callback );
		$this->update_subscription( $order, $callback );
		$order->set_payment_system( $callback->get_payment()->get_method() );

		$operation        = $callback->get_operation();
		$operation_status = $operation->get_status();
		$operation_type   = $operation->get_type();

		if ( $operation_type === Ecp_Gateway_Operation_Type::AUTH ) {
			switch ( $operation_status ) {
				case Ecp_Gateway_Operation_Status::SUCCESS:
					$order->add_order_note( ecpTr( sprintf( 'The payment of %s was authorized.', $order->get_formatted_order_total() ) ) );
					$this->hold_order( $callback, $order );
					break;
				case Ecp_Gateway_Operation_Status::DECLINE:
				case Ecp_Gateway_Operation_Status::EXPIRED:
				case Ecp_Gateway_Operation_Status::INTERNAL_ERROR:
				case Ecp_Gateway_Operation_Status::EXTERNAL_ERROR:
				$order->add_order_note( ecpTr( sprintf( 'An authorization of %s was declined.', $order->get_formatted_order_total() ) ) );
				$this->decline_order( $callback, $order );
					break;
			}
		}
	}

	/**
	 * @param Ecp_Gateway_Info_Callback $callback
	 * @param Ecp_Gateway_Order $order
	 *
	 * @return void
	 */
	public function capture_or_cancel_received( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order ) {
		ecp_info( ecpTr( 'Handling ' . $callback->get_operation()->get_type() . ' callback for order ID: ' ), $order->get_id() );
		$this->log_order_data( $order );
		$this->update_payment( $order, $callback );

		$transaction_order_id = $order->get_transaction_order_id( 'view', $callback->get_operation()->get_type() );

		$is_callback_from_dashboard = false;

		if ( empty( $transaction_order_id ) ) {
			$is_callback_from_dashboard = true;
			if ( ! empty( $order->get_payment() ) ) {
				$order->set_operation_status( $callback->get_operation()->get_status(), $callback->get_operation()->get_type() );
			}
		} else {
			$order->set_operation_status( $callback->get_operation()->get_status(), $callback->get_operation()->get_type() );
		}

		$order->set_payment_system( $callback->get_payment()->get_method() );

		$operation        = $callback->get_operation();
		$operation_status = $operation->get_status();
		$operation_type   = $operation->get_type();

		$dashboard_append_text                = ( $is_callback_from_dashboard
			? ' via Dashboard of ECOMMPAY' : '' );
		$dashboard_append_text_recommendation = ( $is_callback_from_dashboard
			? "\nIt is recommended that you apply the corresponding changes in the order, including the order amount and status." : '' );

		$append_order_note_text = '';

		$callback_sum      = $callback->get_payment()->get_sum();
		$callback_amount   = $callback_sum->get_amount();
		$callback_currency = $callback_sum->get_currency();
		$total_amount      = $order->get_total_minor();
		$sum_less          = $callback_amount < $total_amount;
		$sum_equal         = $callback_amount === $total_amount;

		switch ( $operation_type ) {
			case 'capture':
				if ( $operation_status === Ecp_Gateway_Operation_Status::SUCCESS ) {
					$append_order_note_text = $sum_equal
						? sprintf(
							'The payment of %s was captured%s.',
							$order->get_formatted_order_total(),
							$dashboard_append_text
						)
						: sprintf(
							'The payment of %s %s was captured%s. The rest is returned to the payer.%s',
							$callback_amount,
							$callback_currency,
							$dashboard_append_text,
							$dashboard_append_text_recommendation
						);

					$this->complete_order( $callback, $order, $sum_less );
				} else {
					$append_order_note_text = sprintf(
						'Capture operation%s was declined: %s',
						$dashboard_append_text,
						$operation->get_message()
					);
				}
				break;

			case 'cancel':
				if ( $operation_status === Ecp_Gateway_Operation_Status::SUCCESS ) {
					if ( $sum_equal ) {
						$append_order_note_text = sprintf(
							'Payment authorization of %s was canceled%s.',
							$order->get_formatted_order_total(),
							$dashboard_append_text
						);
						$this->cancel_order( $order );
					} else if ( $sum_less ) {
						$remaining_amount       = ecp_price_multiplied_to_float( $total_amount - $callback_amount, $callback_currency );
						$append_order_note_text = sprintf(
							'Payment authorization of %s was canceled%s. The rest (%s %s) can be either captured or canceled. %s',
							$order->get_formatted_order_total(),
							$dashboard_append_text,
							$remaining_amount,
							$callback_currency,
							$dashboard_append_text_recommendation
						);
					}
				} else {
					$append_order_note_text = sprintf(
						'Cancel operation%s was declined: %s',
						$dashboard_append_text,
						$operation->get_message()
					);
				}
				break;

			default:
				ecp_error( 'Unknown operation type: ' . $operation_type );
				break;
		}

		if ( ! empty( $append_order_note_text ) ) {
			$order->add_order_note( $append_order_note_text );
		}
	}

    /**
    * @param Ecp_Gateway_Info_Callback $callback
    * @param Ecp_Gateway_Order $order
     *
     * @throws WC_Data_Exception|Ecp_Gateway_API_Exception
     */
	public function contractRegistration( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order ): void
    {
        ecp_get_log()->info(__('Apply contract confirmation callback data.', 'woo-ecommpay'));
        $this->log_order_data($order);
        $this->update_subscription($order, $callback);

	    if ( $callback->get_payment()->get_sum()->get_amount() === 0 ) {
		    $this->update_payment( $order, $callback );
		    $order->set_payment_system( $callback->get_payment()->get_method() );
		    $this->process( $callback, $order );
	    }
    }

    private function log_order_data(Ecp_Gateway_Order $order)
    {
	    ecp_debug( ecpTr( 'Order info: ' ), [
		    'ID'             => $order->get_id(),
		    'Payment ID'     => $order->get_payment_id(),
		    'Transaction ID' => $order->get_ecp_transaction_id()
	    ] );
    }

	/**
	 * @param Ecp_Gateway_Info_Callback $callback
	 * @param Ecp_Gateway_Order $order
	 *
	 * @return void
	 * @throws WC_Data_Exception|Ecp_Gateway_API_Exception
	 */
    private function process(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
	    $status = $callback->get_payment()->get_status();
	    switch ( $status ) {
            case Ecp_Gateway_Operation_Status::AWAITING_CONFIRMATION:
                $this->hold_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::AWAITING_CUSTOMER:
                $this->decline_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::EXTERNAL_PROCESSING:
                break;
	        case Ecp_Gateway_Operation_Status::AWAITING_FINALIZATION:
		        $order->add_order_note( __( 'Direct debit request has been submitted successfully. Activation may take some time to complete.', 'woo-ecommpay' ) );
		        $this->processOperation( $callback, $order );
		        break;
	        default:
                $this->processOperation($callback, $order);
                break;
        }
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function processOperation(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        switch ($callback->get_operation()->get_status()) {
            case Ecp_Gateway_Operation_Status::SUCCESS:
                $this->complete_order($callback, $order);
                break;
            case Ecp_Gateway_Operation_Status::DECLINE:
            case Ecp_Gateway_Operation_Status::EXPIRED:
            case Ecp_Gateway_Operation_Status::INTERNAL_ERROR:
            case Ecp_Gateway_Operation_Status::EXTERNAL_ERROR:
                $this->decline_order($callback, $order);
                break;
        }
    }

    /**
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
     * @return void
     * @throws WC_Data_Exception
     */
    private function hold_order(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
        ecp_get_log()->debug(__('Run awaiting confirmation process.', 'woo-ecommpay'), $order->get_id());
        $order->set_transaction_id($callback->get_operation()->get_request_id());
        $order->update_status('on-hold');
        ecp_get_log()->debug(__('Awaiting confirmation process completed.', 'woo-ecommpay'), $order->get_id());
    }

	/**
	 * Complete order
	 *
	 * @param Ecp_Gateway_Info_Callback $callback
	 * @param Ecp_Gateway_Order $order
	 * @param bool $skip_amount_check
	 *
	 * @return void
	 */
	private function complete_order( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order, bool $skip_amount_check = false )
    {
        $order_currency = $order->get_currency_uppercase();
        $payment_currency = $callback->get_payment_currency();

        $is_amount_equal = $order->get_total_minor() === $callback->get_payment_amount_minor();
        $is_currency_equal = $order_currency === $payment_currency;

        ecp_get_log()->debug(__('Run success process.', 'woo-ecommpay'), $order->get_id());
        $order->payment_complete($callback->get_operation()->get_request_id());
        ecp_get_log()->debug(__('Success process completed.', 'woo-ecommpay'), $order->get_id());

	    if ( ! $skip_amount_check && ( ! $is_amount_equal || ! $is_currency_equal ) ) {
            $message = sprintf(
	            'The payment amount does not match the order amount. The order has %s %s. The payment has %s %s',
                $order->get_total(), $order_currency, $callback->get_payment_amount(), $payment_currency
            );
            $order->add_order_note(__($message, 'woo-ecommpay'));
        }
    }

	/**
	 * Decline order
	 *
     * @param Ecp_Gateway_Info_Callback $callback
     * @param Ecp_Gateway_Order $order
	 *
     * @return void
     * @throws WC_Data_Exception
     */
    private function decline_order(Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order)
    {
	    ecp_get_log()->debug( __( 'Run failed process.', 'woo-ecommpay' ), $order->get_id() );
	    $order->set_transaction_id( $callback->get_operation()->get_request_id() );
	    $order->update_status( WCOrderStatus::FAILED );
        $order->increase_failed_ecommpay_payment_count();
	    $this->append_order_errors( $callback, $order );
	    ecp_debug( ecpTr( 'Failed process completed.' ), $order->get_id() );
    }

	/**
	 * Cancel order
	 *
	 * @param Ecp_Gateway_Order $order
	 *
	 * @return void
	 */
	private function cancel_order( Ecp_Gateway_Order $order ) {
		ecp_get_log()->debug( __( 'Run cancel process.', 'woo-ecommpay' ), $order->get_id() );
		$order->update_status( WCOrderStatus::CANCELLED );
		$order->set_ecp_payment_status( Ecp_Gateway_Payment_Status::CANCELLED );
		ecp_get_log()->debug( __( 'Cancel process completed.', 'woo-ecommpay' ), $order->get_id() );
	}

    /**
     * @param $data
     * @return void
     */
    private static function check_signature($data)
    {
        ecp_get_log()->debug(__('Verify signature', 'woo-ecommpay'));
        try {
            if (!ecp_check_signature($data)) {
                $message = _x('Invalid callback signature.', 'Error message', 'woo-ecommpay');
                ecp_get_log()->error($message);

                http_response_code(400);
                die ($message);
            }

            ecp_get_log()->debug(__('Signature verified.', 'woo-ecommpay'));
        } catch (Ecp_Gateway_Signature_Exception $e) {
            $e->write_to_logs();
            http_response_code(500);
            die ($e->getMessage());
        }
    }

    /**
     * <h2>Returns order by callback information.</h2>
     *
     * @param Ecp_Gateway_Info_Callback $info <p>Callback information.</p>
     *
     * @return Ecp_Gateway_Order <p>Payment order.</p>
     * @since 2.0.0
     */
	private function get_order( Ecp_Gateway_Info_Callback $info ): Ecp_Gateway_Order {
        // Fetch order number;
	    $order_number = Ecp_Gateway_Order::get_order_id_from_callback( $info );
        $order = ecp_get_order($order_number);

        if (!$order) {
            // Print debug information to logs
            $message = __('Order not found', 'woo-ecommpay');
            ecp_get_log()->error($message);
            ecp_get_log()->info(__('Transaction failed for', 'woo-ecommpay'), $order_number);

            foreach ($info->get_errors() as $error) {
                ecp_get_log()->add(__('Error code:', 'woo-ecommpay'), $error->get_code());
                ecp_get_log()->add(__('Error field:', 'woo-ecommpay'), $error->get_field());
                ecp_get_log()->add(__('Error message:', 'woo-ecommpay'), $error->get_message());
                ecp_get_log()->add(__('Error description:', 'woo-ecommpay'), $error->get_description());
            }

            ecp_get_log()->add(__('Response data: %s', 'woo-ecommpay'), json_encode($info));

            http_response_code(404);
            die ($message);
        }

        return $order;
    }

    /**
     * <h2>Update payment data.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Payment order.</p>
     * @param Ecp_Gateway_Info_Callback $callback <p>Callback information.</p>
     *
     * @return void
     * @since 2.0.0
     */
	private function update_payment( Ecp_Gateway_Order $order, Ecp_Gateway_Info_Callback $callback )
    {
        $payment = $order->get_payment();
        $payment->add_operation($callback->get_operation());
        $payment->set_info($callback->get_payment());
        $payment->save();
    }

    /**
     * <h2>Sets to subscriptions recurring information.</h2>
     *
     * @param Ecp_Gateway_Order $order <p>Parent payment order.</p>
     * @param Ecp_Gateway_Info_Callback $callback <p>Callback information.</p>
     * @since 2.0.0
     * @return void
     */
    private function update_subscription($order, $callback)
    {
        if (!$order->contains_subscription()) {
            return;
        }

        if (!$callback->try_get_recurring($recurring)) {
            ecp_get_log()->warning(
                __('No recurring information found in callback data. The Subscription cannot be renewed.', 'woo-ecommpay')
            );
            return;
        }

        ecp_get_log()->debug(__('Order has subscriptions', 'woo-ecommpay'));
        $subscriptions = $order->get_subscriptions();

        if ($subscriptions === null) {
            return;
        }

        ecp_get_log()->debug(__('Recurring ID:', 'woo-ecommpay'), $recurring->get_id());

        foreach ($subscriptions as $subscription) {
            ecp_get_log()->debug(__('Subscription ID:', 'woo-ecommpay'), $subscription->get_id());
            $subscription->set_recurring_id($callback->get_recurring()->get_id());
            $subscription->save();
        }
    }

	private function append_order_errors( Ecp_Gateway_Info_Callback $callback, Ecp_Gateway_Order $order ) {
		if ( ! empty( $callback->get_errors() ) ) {
			$errors_text = '';
			foreach ( $callback->get_errors() as $error ) {
				$errors_text .= sprintf(
					'An error with code %s (%s) occurred. ',
					$error['code'], $error['message']
				);
			}
			$order->add_order_note( $errors_text . 'You can refer <a href="https://developers.ecommpay.com/en/en_platform_payment_info_codes.html" target="_blank">to the ECOMMPAY article</a> for more information.' );
		}
	}

}
