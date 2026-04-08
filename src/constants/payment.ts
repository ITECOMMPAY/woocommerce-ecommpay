/**
 * Payment page frame modes.
 */
export enum FrameMode {
	IFRAME = 'iframe',
	EMBEDDED = 'embedded',
	POPUP = 'popup',
}

/**
 * Payment page versions.
 */
export enum PaymentPageVersion {
	MODERN = 'v5',
}

export const PM_EMBEDDED_SUBMIT = 'epframe.embedded_mode.submit'
export const PM_EMBEDDED_CHECK_VALIDATION = 'epframe.embedded_mode.check_validation'

export const RESOLVE_PARAM_FIELDS = [
	'redirect_success_url',
	'customer_first_name',
	'customer_last_name',
	'customer_phone',
	'customer_zip',
	'customer_address',
	'customer_city',
	'customer_country',
	'customer_email',
	'billing_address',
	'billing_city',
	'billing_country',
	'billing_postal',
	'billing_region',
] as const
