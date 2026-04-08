export interface PaymentOptions {
	redirect_success_url?: string
	redirect_success_enabled?: boolean
	redirect_fail_url?: string
	redirect_fail_enabled?: boolean
	customer_first_name?: string
	customer_last_name?: string
	customer_phone?: string
	customer_zip?: string
	customer_address?: string
	customer_city?: string
	customer_country?: string
	customer_email?: string
	billing_address?: string
	billing_city?: string
	billing_country?: string
	billing_postal?: string
	billing_region?: string
	avs_post_code?: string
	avs_street_address?: string
	payment_amount?: number
	payment_currency?: string
	[key: string]: unknown
}

export interface ResolveParams {
	redirect_success_url: string
	customer_first_name: string
	customer_last_name: string
	customer_phone: string
	customer_zip: string
	customer_address: string
	customer_city: string
	customer_country: string
	customer_email: string
	billing_address: string
	billing_city: string
	billing_country: string
	billing_postal: string
	billing_region: string
	avs_post_code?: string
	avs_street_address?: string
}
