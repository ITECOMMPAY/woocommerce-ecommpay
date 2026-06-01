<?php

namespace common\includes\filters;

class EcpApiFilters {

	public const WP_AJAX_NOPRIV_GET_PAYMENT_STATUS                 = 'wp_ajax_nopriv_get_payment_status';
	public const WP_AJAX_CHECK_CART_AMOUNT                         = 'wp_ajax_check_cart_amount';
	public const WP_AJAX_ECOMMPAY_PROCESS                          = 'wp_ajax_ecommpay_process';
	public const WP_AJAX_NOPRIV_ECOMMPAY_BREAK                     = 'wp_ajax_nopriv_ecommpay_break';
	public const WP_AJAX_NOPRIV_GET_DATA_FOR_PAYMENT_FORM          = 'wp_ajax_nopriv_get_data_for_payment_form';
	public const WP_AJAX_GET_PAYMENT_STATUS                        = 'wp_ajax_get_payment_status';
	public const WP_AJAX_ECOMMPAY_BREAK                            = 'wp_ajax_ecommpay_break';
	public const ECP_API_REFUND_ENDPOINT_PREFIX                    = 'ecp_api_refund_endpoint_';
	public const WP_AJAX_GET_DATA_FOR_PAYMENT_FORM                 = 'wp_ajax_get_data_for_payment_form';
	public const WP_AJAX_NOPRIV_ECOMMPAY_PROCESS                   = 'wp_ajax_nopriv_ecommpay_process';
	public const WP_AJAX_ADD_PAYMENT_ID_TO_ORDER                   = 'wp_ajax_add_payment_id_to_order';
	public const WP_AJAX_NOPRIV_CHECK_CART_AMOUNT                  = 'wp_ajax_nopriv_check_cart_amount';
	public const WP_AJAX_NOPRIV_ADD_PAYMENT_ID_TO_ORDER            = 'wp_ajax_nopriv_add_payment_id_to_order';
	public const WP_AJAX_ECP_PROCESS_CANCEL_ORDER                  = 'wp_ajax_ecp_process_cancel_order';
	public const WP_AJAX_ECOMMPAY_RUN_DATA_UPGRADER                = 'wp_ajax_ecommpay_run_data_upgrader';
	public const WP_AJAX_ECOMMPAY_MANUAL_TRANSACTION_ACTIONS       = 'wp_ajax_ecommpay_manual_transaction_actions';
	public const WP_AJAX_ECOMMPAY_EMPTY_LOGS                       = 'wp_ajax_ecommpay_empty_logs';
	public const WP_AJAX_ECOMMPAY_FLUSH_CACHE                      = 'wp_ajax_ecommpay_flush_cache';
	public const WP_AJAX_WOOCOMMERCE_ECOMMPAY_FLUSH_RUNTIME_ERRORS = 'wp_ajax_woocommerce_ecommpay_flush_runtime_errors';

	// Callback operation hooks
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_SALE                  = 'woocommerce_ecommpay_callback_sale';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_REFUND                = 'woocommerce_ecommpay_callback_refund';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_REVERSAL              = 'woocommerce_ecommpay_callback_reversal';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_RECURRING             = 'woocommerce_ecommpay_callback_recurring';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_VERIFY                = 'woocommerce_ecommpay_callback_verify';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_PAYMENT_CONFIRMATION  = 'woocommerce_ecommpay_callback_payment_confirmation';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_CONTRACT_REGISTRATION = 'woocommerce_ecommpay_callback_contract_registration';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_AUTH                  = 'woocommerce_ecommpay_callback_auth';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_CAPTURE               = 'woocommerce_ecommpay_callback_capture';
	public const WOOCOMMERCE_ECOMMPAY_CALLBACK_CANCEL                = 'woocommerce_ecommpay_callback_cancel';
}
