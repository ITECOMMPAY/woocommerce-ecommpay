<?php

/**
 * Get all ECOMMPAY payment statuses.
 *
 * @return array
 * @since  2.0.0
 * @used-by Ecp_Gateway_Payment::set_status
 */
function ecp_get_payment_statuses(): array {
    $payment_statuses = Ecp_Gateway_Payment_Status::get_status_names();
    return apply_filters('ecp_payment_statuses', $payment_statuses);
}

/**
 * Get the nice name for a payment status.
 *
 * @param  string $status Status.
 *
 * @return string
 * @since  2.0.0
 */
function ecp_get_payment_status_name( string $status ): string {
    return Ecp_Gateway_Payment_Status::get_status_name($status);
}

/**
 * See if a string is an ECOMMPAY payment status.
 *
 * @param string $maybe_status
 *
 * @return bool
 * @since  2.0.0
 */
function ecp_is_payment_status( string $maybe_status ): bool {
    return array_key_exists($maybe_status, ecp_get_payment_statuses());
}

/**
 * Get the nice name for an operation type.
 *
 * @param  string $status Status.
 *
 * @return string
 * @since  2.0.0
 */
function ecp_get_operation_type_name( string $status ): string {
    return Ecp_Gateway_Operation_Type::get_status_name($status);
}
